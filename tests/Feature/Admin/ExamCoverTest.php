<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class ExamCoverTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['practice'])]
    #[TestWith(['tryout'])]
    public function test_admin_can_create_a_session_with_a_cover(string $kind): void
    {
        Storage::fake('public');
        $prefix = ExamKind::from($kind)->adminRoutePrefix();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route($prefix.'.store'), [
            ...$this->payload($kind), 'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertSessionHasNoErrors();

        $exam = Exam::sole();
        Storage::disk('public')->assertExists($exam->cover_path);
        $this->get(route($prefix.'.edit', $exam))->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="cover"', false)->assertSee($exam->coverUrl());
        $this->get(route($prefix.'.index'))->assertSee($exam->coverUrl());
    }

    #[TestWith(['practice'])]
    #[TestWith(['tryout'])]
    public function test_replacing_a_cover_removes_the_old_file(string $kind): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exams/covers/old.jpg', 'old cover');
        $exam = Exam::factory()->{$kind}()->create(['cover_path' => 'exams/covers/old.jpg']);
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route(ExamKind::from($kind)->adminRoutePrefix().'.update', $exam), [
            ...$this->payload($kind, $exam), 'cover' => UploadedFile::fake()->image('new.png'), 'remove_cover' => true,
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('exams/covers/old.jpg');
        Storage::disk('public')->assertExists($exam->fresh()->cover_path);
    }

    public function test_saving_without_an_upload_preserves_the_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exams/covers/original.jpg', 'original');
        $exam = Exam::factory()->practice()->create(['cover_path' => 'exams/covers/original.jpg']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.practices.update', $exam), $this->payload('practice', $exam))
            ->assertSessionHasNoErrors();

        $this->assertSame('exams/covers/original.jpg', $exam->fresh()->cover_path);
        Storage::disk('public')->assertExists('exams/covers/original.jpg');
    }

    public function test_removing_a_cover_restores_the_generated_default(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exams/covers/old.jpg', 'old cover');
        $exam = Exam::factory()->practice()->create(['cover_path' => 'exams/covers/old.jpg']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.practices.update', $exam), [...$this->payload('practice', $exam), 'remove_cover' => true])
            ->assertSessionHasNoErrors();

        $this->assertNull($exam->fresh()->cover_path);
        $this->assertSame(asset('images/practice-card-cover.webp'), $exam->fresh()->coverUrl());
        Storage::disk('public')->assertMissing('exams/covers/old.jpg');
    }

    #[TestWith(['document'])]
    #[TestWith(['svg'])]
    #[TestWith(['oversized'])]
    public function test_invalid_cover_uploads_leave_the_existing_cover_unchanged(string $type): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exams/covers/old.jpg', 'old cover');
        $exam = Exam::factory()->tryout()->create(['cover_path' => 'exams/covers/old.jpg']);
        $file = match ($type) {
            'document' => UploadedFile::fake()->create('cover.pdf', 10, 'application/pdf'),
            'svg' => UploadedFile::fake()->createWithContent('cover.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            'oversized' => UploadedFile::fake()->image('cover.jpg')->size(5121),
        };

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.exams.update', $exam), [...$this->payload('tryout', $exam), 'cover' => $file])
            ->assertSessionHasErrors('cover');

        $this->assertSame('exams/covers/old.jpg', $exam->fresh()->cover_path);
        $this->assertSame(['exams/covers/old.jpg'], Storage::disk('public')->allFiles());
    }

    public function test_failed_save_cleans_up_the_new_upload_and_keeps_the_old_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exams/covers/old.jpg', 'old cover');
        $exam = Exam::factory()->practice()->create(['cover_path' => 'exams/covers/old.jpg']);
        Exam::saving(function (): void {
            throw new RuntimeException('Save failed');
        });

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.practices.update', $exam), [
                ...$this->payload('practice', $exam), 'cover' => UploadedFile::fake()->image('new.jpg'),
            ])->assertServerError();

        $this->assertSame('exams/covers/old.jpg', $exam->fresh()->cover_path);
        $this->assertSame(['exams/covers/old.jpg'], Storage::disk('public')->allFiles());
    }

    public function test_members_cannot_upload_session_covers(): void
    {
        Storage::fake('public');
        $exam = Exam::factory()->practice()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.practices.update', $exam), [
                ...$this->payload('practice', $exam), 'cover' => UploadedFile::fake()->image('cover.jpg'),
            ])->assertForbidden();

        $this->assertNull($exam->fresh()->cover_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[TestWith(['practice', 'quizzes.index', 'practice-card-cover.webp'])]
    #[TestWith(['tryout', 'tryouts.index', 'tryout-card-cover.webp'])]
    public function test_member_cards_show_generated_defaults_and_uploaded_covers(string $kind, string $route, string $default): void
    {
        Exam::factory()->{$kind}()->create();
        Exam::factory()->{$kind}()->create(['cover_path' => 'exams/covers/custom.jpg']);

        $this->actingAs(User::factory()->create())->get(route($route))
            ->assertSee(asset('images/'.$default))
            ->assertSee(Storage::disk('public')->url('exams/covers/custom.jpg'));
    }

    /** @return array<string, mixed> */
    private function payload(string $kind, ?Exam $exam = null): array
    {
        return ['kind' => $kind, 'title' => 'Sesi Bergambar', 'slug' => $exam?->slug ?? 'sesi-bergambar',
            'status' => 'draft', 'question_count' => 1, 'duration_minutes' => 30, 'points_per_correct' => 5,
            'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true, 'question_ids' => []];
    }
}
