<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionImagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(Section $section, array $overrides = []): array
    {
        return array_replace(['title' => 'Pilih gambar yang tepat', 'section_id' => $section->id,
            'body' => '<p>Manakah gambar yang sesuai pola?</p>', 'options' => ['Segitiga', 'Persegi', 'Lingkaran'],
            'correct_option' => 1, 'explanation' => '<p>Pola membentuk persegi.</p>',
            'difficulty' => 'easy', 'status' => 'published'], $overrides);
    }

    public function test_admin_can_publish_image_only_choices_and_explanation_and_load_them_in_both_editors(): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, [
            'options' => ['', 'Persegi', ''], 'explanation' => '',
            'option_images' => [0 => UploadedFile::fake()->image('segitiga.png'), 2 => UploadedFile::fake()->image('lingkaran.jpg')],
            'explanation_image' => UploadedFile::fake()->image('pembahasan.png'),
            'option_image_paths' => ['forged.png'], 'explanation_image_path' => 'forged.png',
        ]), ['Accept' => 'application/json'])->assertCreated();

        $question = Question::sole();
        $this->assertSame(['', 'Persegi', ''], $question->options);
        $this->assertNull($question->option_image_paths[1]);
        Storage::disk('local')->assertExists([$question->option_image_paths[0], $question->option_image_paths[2], $question->explanation_image_path]);
        $response->assertJsonPath('question.option_image_urls.0', route('admin.questions.media', [$question, 'option-0']))
            ->assertJsonPath('question.option_image_urls.1', null)
            ->assertJsonPath('question.explanation_image_url', route('admin.questions.media', [$question, 'explanation-image']));
        $this->getJson(route('admin.questions.show', $question))->assertJsonPath('question.option_image_urls', $response->json('question.option_image_urls'));
        $this->get(route('admin.questions.edit', $question))->assertSee('Gambar pembahasan')->assertSee('data-option-image-source', false);
        $this->get(route('admin.questions.show', $question))->assertSee(route('admin.questions.media', [$question, 'option-2']))
            ->assertSee(route('admin.questions.media', [$question, 'explanation-image']));
    }

    public function test_deleting_an_option_keeps_remaining_images_aligned_and_removes_unused_files(): void
    {
        Storage::fake('local');
        foreach (['a.png', 'b.png', 'c.png', 'explanation.png'] as $name) {
            Storage::disk('local')->put('questions/'.$name, $name);
        }
        $question = Question::factory()->published()->create(['options' => ['A', 'B', 'C'],
            'option_image_paths' => ['questions/a.png', 'questions/b.png', 'questions/c.png'], 'explanation_image_path' => 'questions/explanation.png']);
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.questions.update', $question), $this->payload($question->section, [
            'options' => ['B', 'C'], 'option_image_sources' => [1, 2],
        ]))->assertSessionHasNoErrors();

        $question->refresh();
        $this->assertSame(['questions/b.png', 'questions/c.png'], $question->option_image_paths);
        $this->assertSame('questions/explanation.png', $question->explanation_image_path);
        Storage::disk('local')->assertMissing('questions/a.png');
        Storage::disk('local')->assertExists(['questions/b.png', 'questions/c.png', 'questions/explanation.png']);
    }

    public function test_admin_can_replace_and_remove_option_and_explanation_images(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('questions/old-option.png', 'old option');
        Storage::disk('local')->put('questions/old-explanation.png', 'old explanation');
        $question = Question::factory()->published()->create(['option_image_paths' => ['questions/old-option.png'],
            'explanation_image_path' => 'questions/old-explanation.png']);
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.questions.update', $question), $this->payload($question->section, [
            'option_images' => [0 => UploadedFile::fake()->image('new-option.png')], 'remove_option_images' => [0 => true],
            'explanation_image' => UploadedFile::fake()->image('new-explanation.png'), 'remove_explanation_image' => true,
        ]))->assertSessionHasNoErrors();

        $question->refresh();
        $paths = [$question->option_image_paths[0], $question->explanation_image_path];
        Storage::disk('local')->assertMissing(['questions/old-option.png', 'questions/old-explanation.png']);
        Storage::disk('local')->assertExists($paths);

        $this->put(route('admin.questions.update', $question), $this->payload($question->section, [
            'remove_option_images' => [0 => true], 'remove_explanation_image' => true,
        ]))->assertSessionHasNoErrors();

        $this->assertSame([null, null, null], $question->fresh()->option_image_paths);
        $this->assertNull($question->fresh()->explanation_image_path);
        Storage::disk('local')->assertMissing($paths);
        $this->get(route('admin.questions.media', [$question, 'option-0']))->assertNotFound();
        $this->get(route('admin.questions.media', [$question, 'explanation-image']))->assertNotFound();
    }

    public function test_removing_the_only_content_of_an_option_or_published_explanation_is_rejected(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('questions/option.png', 'option');
        Storage::disk('local')->put('questions/explanation.png', 'explanation');
        $question = Question::factory()->published()->create(['options' => ['', 'B'], 'correct_option' => 1, 'explanation' => '',
            'option_image_paths' => ['questions/option.png', null], 'explanation_image_path' => 'questions/explanation.png']);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question), $this->payload($question->section, [
            'options' => ['', 'B'], 'explanation' => '', 'remove_option_images' => [0 => true], 'remove_explanation_image' => true,
        ]))->assertSessionHasErrors(['options.0' => 'Setiap pilihan jawaban harus diisi.', 'explanation' => 'Lengkapi pembahasan sebelum menerbitkan soal.']);

        $this->assertSame(['questions/option.png', null], $question->fresh()->option_image_paths);
        Storage::disk('local')->assertExists(['questions/option.png', 'questions/explanation.png']);
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_image_uploads_are_rejected_without_saving_files(string $field, bool $oversized): void
    {
        Storage::fake('local');
        $file = $oversized ? UploadedFile::fake()->image('large.png')->size(2049) : UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml');
        $upload = $field === 'explanation_image' ? [$field => $file] : ['option_images' => [2 => $file]];

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'),
            $this->payload(Section::factory()->create(), $upload))->assertSessionHasErrors($field);

        $this->assertDatabaseCount('questions', 0);
        Storage::disk('local')->assertDirectoryEmpty('questions');
    }

    public static function invalidImages(): array
    {
        return ['option format' => ['option_images.2', false], 'option size' => ['option_images.2', true],
            'explanation format' => ['explanation_image', false], 'explanation size' => ['explanation_image', true]];
    }

    #[DataProvider('invalidImageMappings')]
    public function test_invalid_option_image_mappings_return_validation_errors(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.questions.store'),
            $this->payload(Section::factory()->create(), $overrides))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('questions', 0);
    }

    public static function invalidImageMappings(): array
    {
        return ['missing source' => [['option_image_sources' => [0, 1]], 'option_image_sources'],
            'invalid source' => [['option_image_sources' => [0, [], 2]], 'option_image_sources.1'],
            'arbitrary path' => [['option_image_sources' => ['questions/other.png', 1, 2]], 'option_image_sources.0'],
            'out of range removal' => [['remove_option_images' => [5 => true]], 'remove_option_images.5']];
    }

    public function test_admin_image_endpoints_require_admin_access(): void
    {
        $question = Question::factory()->create();
        $url = route('admin.questions.media', [$question, 'option-0']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_shuffled_images_match_their_options_and_scoring_and_are_private_to_the_attempt_owner(): void
    {
        Storage::fake('local');
        $paths = ['questions/a.png', 'questions/b.png', 'questions/c.png'];
        foreach ($paths as $path) {
            Storage::disk('local')->put($path, 'image');
        }
        $question = Question::factory()->published()->create(['options' => ['A', 'B', 'C'], 'option_image_paths' => $paths, 'correct_option' => 1]);
        $exam = Exam::factory()->tryout()->create();
        $exam->questions()->attach($question);
        $owner = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($owner, $exam);
        $snapshot = $attempt->question_snapshot['questions'][0];
        $config = $attempt->clientConfig()['questions'][0];

        foreach ($snapshot['options'] as $index => $option) {
            $this->assertSame(['A' => $paths[0], 'B' => $paths[1], 'C' => $paths[2]][$option], $snapshot['option_image_paths'][$index]);
            $this->actingAs($owner)->get($config['optionImageUrls'][$index])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->assertSame('B', $snapshot['options'][$snapshot['answer']]);
        $this->assertArrayNotHasKey('option_image_paths', $config);
        $this->assertArrayNotHasKey('explanation_image_path', $config);
        $this->actingAs(User::factory()->create())->get($config['optionImageUrls'][0])->assertNotFound();
        $this->actingAs($owner)->get(route('exam-attempts.media', [$attempt, $question->id, 'option-9']))->assertNotFound();

        $attempt->update(['answers' => [(string) $question->id => $snapshot['answer']]]);
        $attempt->complete();
        $this->assertSame(5, $attempt->score);
        $this->actingAs($owner)->get($attempt->resultUrl())->assertSee($config['optionImageUrls'][0]);
        Storage::disk('local')->assertExists($paths);
    }

    public function test_snapshot_images_survive_admin_replacement_and_explanation_access_requires_completion_and_active_package(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('questions/option.png', 'old option');
        Storage::disk('local')->put('questions/explanation.png', 'old explanation');
        $question = Question::factory()->published()->create(['option_image_paths' => ['questions/option.png'], 'explanation_image_path' => 'questions/explanation.png']);
        $exam = Exam::factory()->tryout()->create(['random_options' => false]);
        $exam->questions()->attach($question);
        $owner = User::factory()->create();
        $package = UserPackage::factory()->for($owner)->create();
        $attempt = ExamAttempt::resumeOrStart($owner, $exam);
        $snapshot = $attempt->question_snapshot;
        $url = route('exam-attempts.media', [$attempt, $question->id, 'explanation-image']);
        $this->assertArrayNotHasKey('explanation_image_path', $attempt->clientConfig()['questions'][0]);
        $this->actingAs($owner)->get($url)->assertNotFound();

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question), $this->payload($question->section, [
            'option_images' => [0 => UploadedFile::fake()->image('new.png')],
            'explanation_image' => UploadedFile::fake()->image('new-explanation.png'),
        ]))->assertSessionHasNoErrors();
        $question->delete();
        $this->assertSame($snapshot, $attempt->fresh()->question_snapshot);
        Storage::disk('local')->assertExists(['questions/option.png', 'questions/explanation.png']);

        $attempt->complete();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->get($attempt->resultUrl())->assertSee($url);
        $package->update(['revoked_at' => now()]);
        $this->get($url)->assertNotFound();
        $this->get($attempt->resultUrl())->assertDontSee($url)
            ->assertViewHas('reviewQuestions', fn (array $questions): bool => ! array_key_exists('explanation_image_path', $questions[0]));
    }
}
