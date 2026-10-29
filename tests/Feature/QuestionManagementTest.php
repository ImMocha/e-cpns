<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(Section $section, array $overrides = []): array
    {
        return array_merge(['title' => 'Pola Bilangan', 'section_id' => $section->id,
            'body' => '<p>Berapa hasil <strong>12 + 8</strong>?</p>',
            'options' => ['18', '20', '22', '24', '26'], 'correct_option' => 1,
            'explanation' => '<p>12 + 8 = <strong>20</strong>.</p>', 'difficulty' => 'easy', 'status' => 'draft'], $overrides);
    }

    public function test_admin_can_create_formatted_question_without_accepting_server_owned_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.questions.store'), $this->payload($section, [
            'created_by' => 999, 'image_path' => 'private.php', 'video_path' => 'private.php', 'published_at' => now(),
            'body' => '<p>Berapa <strong>12 + 8</strong>?</p><script>alert(1)</script>',
            'explanation' => '<p onclick="alert(1)">12 + 8 = 20.</p>',
        ]));

        $question = Question::sole();
        $response->assertRedirect(route('admin.questions.edit', $question))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'created_by' => $admin->id, 'updated_by' => $admin->id,
            'image_path' => null, 'video_path' => null, 'published_at' => null, 'correct_option' => 1,
            'body' => '<p>Berapa <strong>12 + 8</strong>?</p>', 'explanation' => '<p>12 + 8 = 20.</p>']);
        $this->get(route('admin.questions.edit', $question))->assertSee('Preview langsung')->assertSee('Pilihan A');
        $this->get(route('admin.questions.show', $question))->assertSee('Kunci &amp; Pembahasan', false);
    }

    public function test_admin_can_publish_edit_return_to_draft_and_archive_question(): void
    {
        $this->travelTo(now()->startOfSecond());
        $question = Question::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.questions.update', $question), $this->payload($question->section, ['status' => 'published']))
            ->assertSessionHasNoErrors();
        $this->assertSame(QuestionStatus::Published, $question->fresh()->status);
        $this->assertTrue($question->fresh()->published_at->equalTo(now()));
        $publishedAt = $question->fresh()->published_at;

        $this->travel(1)->hours();
        $this->put(route('admin.questions.update', $question), $this->payload($question->section, ['status' => 'published', 'title' => 'Soal Revisi']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'title' => 'Soal Revisi', 'updated_by' => $admin->id]);
        $this->assertTrue($question->fresh()->published_at->equalTo($publishedAt));

        $this->put(route('admin.questions.update', $question), $this->payload($question->section))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'status' => 'draft', 'published_at' => null]);

        $this->delete(route('admin.questions.destroy', $question))->assertRedirect(route('admin.questions.index'));
        $this->assertSoftDeleted($question);
        $this->get(route('admin.questions.edit', $question))->assertNotFound();
    }

    #[DataProvider('invalidQuestions')]
    public function test_invalid_question_is_not_saved(array $overrides, string $field, ?string $message = null): void
    {
        $section = Section::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, $overrides));

        $response->assertSessionHasErrors($message ? [$field => $message] : [$field]);
        $this->assertDatabaseCount('questions', 0);
    }

    public static function invalidQuestions(): array
    {
        return [
            'empty title' => [['title' => ''], 'title'],
            'missing body' => [['body' => ''], 'body'],
            'unreadable body' => [['body' => '<script>alert(1)</script>'], 'body', 'Pertanyaan harus berisi teks yang dapat dibaca.'],
            'missing field' => [['section_id' => 999999], 'section_id'],
            'invalid difficulty' => [['difficulty' => 'unknown'], 'difficulty'],
            'invalid status' => [['status' => 'unknown'], 'status'],
            'one option' => [['options' => ['20']], 'options', 'Soal membutuhkan minimal 2 pilihan jawaban.'],
            'too many options' => [['options' => range('A', 'K')], 'options', 'Soal dapat memiliki maksimal 10 pilihan jawaban.'],
            'non sequential choices' => [['options' => [0 => '18', 2 => '20']], 'options', 'Urutan pilihan jawaban tidak valid.'],
            'empty choice' => [['options' => ['18', ' ']], 'options.1', 'Setiap pilihan jawaban harus diisi.'],
            'duplicate choices after trim' => [['options' => [' 20 ', '20']], 'options.0', 'Pilihan jawaban tidak boleh sama.'],
            'nested option' => [['options' => [['bad'], '20']], 'options.0'],
            'missing key' => [['correct_option' => null], 'correct_option', 'Pilih satu jawaban yang benar.'],
            'key outside choices' => [['correct_option' => 8], 'correct_option', 'Pilih kunci dari pilihan jawaban yang tersedia.'],
            'negative key' => [['correct_option' => -1], 'correct_option'],
            'unreadable published explanation' => [['status' => 'published', 'explanation' => '<p>&nbsp;</p>'], 'explanation', 'Lengkapi pembahasan sebelum menerbitkan soal.'],
        ];
    }

    #[DataProvider('validOptionCounts')]
    public function test_question_accepts_two_through_ten_choices(int $count): void
    {
        $section = Section::factory()->create();
        $options = array_map(fn (int $index): string => 'Pilihan '.$index, range(1, $count));

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'),
            $this->payload($section, ['options' => $options, 'correct_option' => $count - 1]))->assertSessionHasNoErrors();

        $this->assertSame($options, Question::sole()->options);
        $this->assertSame($count - 1, Question::sole()->correct_option);
    }

    public static function validOptionCounts(): array
    {
        return ['minimum' => [2], 'maximum' => [10]];
    }

    public function test_draft_may_omit_explanation_and_deleted_field_is_rejected(): void
    {
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, ['explanation' => '']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['status' => 'draft', 'explanation' => '']);
        $section->delete();

        $this->post(route('admin.questions.store'), $this->payload($section))->assertSessionHasErrors('section_id');
        $this->assertDatabaseCount('questions', 1);
    }

    public function test_guests_are_redirected_and_participants_cannot_manage_questions(): void
    {
        $question = Question::factory()->published()->create();

        $this->get(route('admin.questions.index'))->assertRedirect(route('login'));
        $this->get(route('admin.questions.show', $question))->assertRedirect(route('login'));
        $this->get(route('admin.questions.image', $question))->assertRedirect(route('login'));
        $this->get(route('admin.questions.video', $question))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.questions.create'))->assertForbidden();
        $this->post(route('admin.questions.store'), $this->payload($question->section))->assertForbidden();
        $this->put(route('admin.questions.update', $question), $this->payload($question->section))->assertForbidden();
        $this->delete(route('admin.questions.destroy', $question))->assertForbidden();
        $this->assertModelExists($question);
        $this->assertDatabaseCount('questions', 1);
    }

    public function test_question_image_is_private_replaceable_and_not_changed_when_validation_fails(): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, [
            'image' => UploadedFile::fake()->image('diagram.png'),
        ]))->assertSessionHasNoErrors();
        $question = Question::sole();
        $oldPath = $question->image_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->get(route('admin.questions.image', $question))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->put(route('admin.questions.update', $question), $this->payload($section, [
            'image' => UploadedFile::fake()->image('replacement.png'), 'correct_option' => 8,
        ]))->assertSessionHasErrors('correct_option');
        $this->assertSame($oldPath, $question->fresh()->image_path);

        $this->put(route('admin.questions.update', $question), $this->payload($section, [
            'image' => UploadedFile::fake()->image('replacement.png'),
        ]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($question->fresh()->image_path);

        $this->put(route('admin.questions.update', $question), $this->payload($section, ['remove_image' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'image_path' => null]);
        $this->get(route('admin.questions.image', $question))->assertNotFound();
    }

    public function test_non_image_upload_is_rejected_without_saving_question(): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, [
            'image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml'),
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('questions', 0);
        Storage::disk('local')->assertDirectoryEmpty('questions/images');
    }

    public function test_admin_can_upload_replace_keep_and_remove_video_alongside_image(): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, [
            'image' => UploadedFile::fake()->image('diagram.png'),
            'video' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4'),
        ]))->assertSessionHasNoErrors();
        $question = Question::sole();
        $oldPath = $question->video_path;
        $imagePath = $question->image_path;
        Storage::disk('local')->assertExists([$oldPath, $imagePath]);
        $this->get(route('admin.questions.edit', $question))->assertSee('Hapus video lama')->assertSee(route('admin.questions.video', $question));
        $this->get(route('admin.questions.video', $question))->assertOk()->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->put(route('admin.questions.update', $question), $this->payload($section))->assertSessionHasNoErrors();
        $this->assertSame($oldPath, $question->fresh()->video_path);
        $this->put(route('admin.questions.update', $question), $this->payload($section, [
            'video' => UploadedFile::fake()->create('new.webm', 100, 'video/webm'), 'correct_option' => 8,
        ]))->assertSessionHasErrors('correct_option');
        $this->assertSame($oldPath, $question->fresh()->video_path);
        Storage::disk('local')->assertExists($oldPath);

        $this->put(route('admin.questions.update', $question), $this->payload($section, [
            'video' => UploadedFile::fake()->create('new.webm', 100, 'video/webm'), 'remove_video' => 1,
        ]))->assertSessionHasNoErrors();
        $newPath = $question->fresh()->video_path;
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists([$newPath, $imagePath]);
        $this->get(route('admin.questions.video', $question))->assertHeader('Content-Type', 'video/webm');
        $this->put(route('admin.questions.update', $question), $this->payload($section, ['remove_video' => 1]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'video_path' => null, 'image_path' => $imagePath]);
        Storage::disk('local')->assertMissing($newPath);
        Storage::disk('local')->assertExists($imagePath);
        $this->get(route('admin.questions.video', $question))->assertNotFound();
    }

    #[DataProvider('invalidVideos')]
    public function test_invalid_video_does_not_save_question_or_media(string $filename, int $size, string $mime, string $message): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.questions.store'), $this->payload($section, [
            'image' => UploadedFile::fake()->image('diagram.png'),
            'video' => UploadedFile::fake()->create($filename, $size, $mime),
        ]))->assertSessionHasErrors(['video' => $message]);

        $this->assertDatabaseCount('questions', 0);
        Storage::disk('local')->assertDirectoryEmpty('questions');
    }

    public static function invalidVideos(): array
    {
        return [
            'unsupported format' => ['clip.mov', 1, 'video/quicktime', 'Video soal harus berupa file MP4 atau WebM.'],
            'disguised script' => ['clip.mp4', 1, 'text/html', 'Video soal harus berupa file MP4 atau WebM.'],
            'wrong extension' => ['clip.php', 1, 'video/mp4', 'Video soal harus menggunakan ekstensi .mp4 atau .webm.'],
            'over limit' => ['clip.mp4', 51201, 'video/mp4', 'Ukuran video soal maksimal 50 MB.'],
        ];
    }

    public function test_admin_filters_and_totals_use_saved_questions(): void
    {
        $section = Section::factory()->create();
        $match = Question::factory()->published()->for($section)->create(['title' => 'Pola Bilangan Nyata']);
        Question::factory()->for($section)->create(['title' => 'Pola Bilangan Draft']);
        Question::factory()->published()->create(['title' => 'Bidang Lain']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.questions.index', ['q' => 'Pola', 'section_id' => $section->id, 'status' => 'published', 'difficulty' => 'easy']))
            ->assertSee($match->title)->assertDontSee('Pola Bilangan Draft')->assertDontSee('Bidang Lain')
            ->assertViewHas('stats', ['total' => 3, 'published' => 2, 'draft' => 1, 'sections' => 2]);

    }

    public function test_example_seeder_does_not_overwrite_admin_edits_or_duplicate_questions(): void
    {
        Section::factory()->create(['code' => 'TIU']);
        $this->seed(QuestionSeeder::class);
        $question = Question::query()->where('title', 'Contoh: Pola Bilangan')->firstOrFail();
        $question->update(['body' => '<p>Disunting admin</p>']);

        $this->seed(QuestionSeeder::class);

        $this->assertDatabaseCount('questions', 3);
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'body' => '<p>Disunting admin</p>']);
    }
}
