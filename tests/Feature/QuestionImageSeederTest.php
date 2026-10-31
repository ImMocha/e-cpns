<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\QuestionImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionImageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_questions_have_real_images_and_can_be_previewed_and_saved_by_admin(): void
    {
        Storage::fake('local');
        Section::factory()->create(['code' => 'TIU']);
        $admin = User::factory()->admin()->create();

        $this->seed(QuestionImageSeeder::class);

        $this->assertDatabaseCount('questions', 2);
        $square = Question::query()->where('source_key', 'example-image-square')->sole();
        $pentagon = Question::query()->where('source_key', 'example-image-pentagon')->sole();
        $this->assertSame(['', '', '', '', ''], $square->options);
        $this->assertSame(2, $square->correct_option);
        $this->assertSame('Segi lima', $pentagon->options[$pentagon->correct_option]);
        $this->assertSame(2, Question::query()->visibleToMembers()->count());

        foreach ([$square, $pentagon] as $question) {
            $this->assertCount(count($question->options), $question->option_image_paths);
            $this->assertSame($admin->id, $question->created_by);
            foreach ([...$question->option_image_paths, $question->explanation_image_path] as $path) {
                Storage::disk('local')->assertExists($path);
                $image = getimagesizefromstring(Storage::disk('local')->get($path));
                $this->assertSame('image/png', $image['mime']);
                $this->assertSame([640, 420], [$image[0], $image[1]]);
            }
            $this->actingAs($admin)->get(route('admin.questions.show', $question))
                ->assertSee(route('admin.questions.media', [$question, 'option-0']))
                ->assertSee(route('admin.questions.media', [$question, 'explanation-image']));
            $this->get(route('admin.questions.media', [$question, 'explanation-image']))->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->put(route('admin.questions.update', $question), ['title' => $question->title, 'section_id' => $question->section_id,
                'body' => $question->body, 'options' => $question->options, 'correct_option' => $question->correct_option,
                'explanation' => $question->explanation, 'difficulty' => 'easy', 'status' => 'published'])->assertSessionHasNoErrors();
        }

        $this->put(route('admin.questions.update', $square), ['title' => $square->title, 'section_id' => $square->section_id,
            'body' => $square->body, 'options' => $square->options, 'correct_option' => $square->correct_option,
            'explanation' => $square->explanation, 'difficulty' => 'easy', 'status' => 'published',
            'option_images' => [0 => UploadedFile::fake()->image('replacement.png')]])->assertSessionHasNoErrors();

        Storage::disk('local')->assertMissing($square->option_image_paths[0]);
        Storage::disk('local')->assertExists($pentagon->option_image_paths);
    }

    public function test_reseeding_preserves_admin_edits_images_and_archived_questions_without_duplicates(): void
    {
        Storage::fake('local');
        Section::factory()->create(['code' => 'TIU']);
        $this->seed(QuestionImageSeeder::class);
        $square = Question::query()->where('source_key', 'example-image-square')->sole();
        $pentagon = Question::query()->where('source_key', 'example-image-pentagon')->sole();
        $ids = [$square->id, $pentagon->id];
        $square->update(['title' => 'Soal gambar revisi admin', 'body' => '<p>Pertanyaan dari admin.</p>',
            'option_image_paths' => ['questions/admin-option.png', null], 'options' => ['A', 'B'],
            'correct_option' => 0, 'explanation_image_path' => null]);
        Storage::disk('local')->put('questions/admin-option.png', 'admin image');
        $pentagon->delete();

        $this->seed(QuestionImageSeeder::class);

        $this->assertSame($ids, Question::withTrashed()->orderBy('id')->pluck('id')->all());
        $this->assertSame('Soal gambar revisi admin', $square->fresh()->title);
        $this->assertSame('<p>Pertanyaan dari admin.</p>', $square->fresh()->body);
        $this->assertSame(['questions/admin-option.png', null], $square->fresh()->option_image_paths);
        $this->assertNull($square->fresh()->explanation_image_path);
        $this->assertSoftDeleted($pentagon);
        $this->assertSame('admin image', Storage::disk('local')->get('questions/admin-option.png'));
    }

    public function test_missing_tiu_section_leaves_questions_and_storage_untouched(): void
    {
        Storage::fake('local');

        $this->seed(QuestionImageSeeder::class);

        $this->assertDatabaseCount('questions', 0);
        Storage::disk('local')->assertEmpty();
    }
}
