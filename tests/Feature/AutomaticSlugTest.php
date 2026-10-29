<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExamBank;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutomaticSlugTest extends TestCase
{
    use RefreshDatabase;

    public static function resources(): array
    {
        return ['exam' => ['exams', Exam::class, 80], 'article' => ['articles', Article::class, 180],
            'material' => ['materials', Material::class, 200], 'section' => ['sections', Section::class, 140]];
    }

    /** @param class-string<Model> $model */
    #[DataProvider('resources')]
    public function test_preview_skips_current_and_archived_slug_collisions_and_ignores_its_own_record(string $resource, string $model, int $limit): void
    {
        $first = $model::factory()->create(['slug' => 'judul-sama']);
        $archived = $model::factory()->create(['slug' => 'judul-sama-2']);
        $archived->delete();
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.slugs.preview', ['resource' => $resource, 'value' => 'Judul Sama!']))
            ->assertOk()->assertJsonPath('slug', 'judul-sama-3')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson(route('admin.slugs.preview', ['resource' => $resource, 'value' => 'Judul Sama', 'ignore_id' => $first->id]))
            ->assertJsonPath('slug', 'judul-sama');
        $this->assertSame(2, $model::withTrashed()->count());
    }

    /** @param class-string<Model> $model */
    #[DataProvider('resources')]
    public function test_long_duplicate_slugs_reserve_space_for_the_numeric_suffix(string $resource, string $model, int $limit): void
    {
        $model::factory()->create(['slug' => str_repeat('a', $limit)]);
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.slugs.preview', ['resource' => $resource, 'value' => str_repeat('A', 200)]))
            ->assertJsonPath('slug', str_repeat('a', $limit - 2).'-2');
    }

    public function test_preview_requires_admin_access_and_rejects_unknown_resources_or_invalid_input(): void
    {
        $url = route('admin.slugs.preview', ['resource' => 'exams', 'value' => 'Sesi Baru']);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.slugs.preview', ['resource' => 'users', 'value' => 'Sesi Baru']))->assertUnprocessable()->assertJsonValidationErrors('resource');
        $this->getJson(route('admin.slugs.preview', ['resource' => 'exams', 'value' => ['bad'], 'ignore_id' => -1]))->assertUnprocessable()->assertJsonValidationErrors(['value', 'ignore_id']);
    }

    public function test_preview_avoids_reserved_legacy_exam_addresses(): void
    {
        $reserved = ExamBank::cases()[0]->value;

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.slugs.preview', ['resource' => 'exams', 'value' => $reserved]))
            ->assertJsonPath('slug', $reserved.'-2');
    }

    public static function sessionRoutes(): array
    {
        return ['tryout' => ['tryout', 'admin.exams'], 'practice' => ['practice', 'admin.practices'],
            'topic quiz' => ['quiz', 'admin.materials.quizzes']];
    }

    #[DataProvider('sessionRoutes')]
    public function test_session_creation_generates_a_unique_slug_without_javascript_and_handles_supplied_duplicates(string $kind, string $prefix): void
    {
        Exam::factory()->create(['slug' => 'uji-sesi']);
        $archived = Exam::factory()->create(['slug' => 'uji-sesi-2']);
        $archived->delete();
        $payload = ['title' => 'Uji Sesi', 'kind' => $kind, 'status' => 'draft', 'question_count' => 1,
            'duration_minutes' => 30, 'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true];
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route($prefix.'.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exams', ['kind' => $kind, 'slug' => 'uji-sesi-3']);
        $this->post(route($prefix.'.store'), $payload + ['slug' => 'uji-sesi'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exams', ['kind' => $kind, 'slug' => 'uji-sesi-4']);
    }

    public static function contentResources(): array
    {
        return ['article' => ['articles', Article::class], 'material' => ['materials', Material::class], 'section' => ['sections', Section::class]];
    }

    /** @param class-string<Model> $model */
    #[DataProvider('contentResources')]
    public function test_content_creation_automatically_suffixes_a_supplied_duplicate_slug(string $resource, string $model): void
    {
        $model::factory()->create(['slug' => 'judul-baru']);
        $section = Section::factory()->create();
        $topic = Topic::factory()->for($section)->create();
        $payload = match ($resource) {
            'sections' => ['name' => 'Judul Baru', 'code' => 'BARU', 'is_active' => true],
            'materials' => ['title' => 'Judul Baru', 'section_id' => $section->id, 'topic_id' => $topic->id, 'type' => 'pdf', 'status' => 'draft', 'access_level' => 'premium'],
            'articles' => ['title' => 'Judul Baru', 'section_id' => $section->id, 'status' => 'draft'],
        };

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.'.$resource.'.store'), $payload + ['slug' => 'judul-baru'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas($resource, ['slug' => 'judul-baru-2']);
    }

    public function test_all_slug_forms_have_live_preview_and_saved_session_addresses_stay_locked(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['admin.exams' => 'exams', 'admin.practices' => 'exams', 'admin.materials.quizzes' => 'exams',
            'admin.articles' => 'articles', 'admin.materials' => 'materials', 'admin.sections' => 'sections'] as $prefix => $resource) {
            $this->get(route($prefix.'.create'))->assertSee('data-auto-slug="'.$resource.'"', false)
                ->assertSee(route('admin.slugs.preview'));
        }
        $exam = Exam::factory()->practice()->create(['slug' => 'alamat-tetap']);
        $payload = ['title' => 'Judul Sesi Diganti', 'kind' => 'practice', 'status' => 'draft', 'question_count' => 1,
            'duration_minutes' => 30, 'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true];
        $this->put(route('admin.practices.update', $exam), $payload)->assertSessionHasNoErrors();
        $this->assertSame('alamat-tetap', $exam->fresh()->slug);
        $this->put(route('admin.practices.update', $exam), $payload + ['slug' => 'alamat-berubah'])->assertSessionHasErrors('slug');
        $this->assertSame('alamat-tetap', $exam->fresh()->slug);
    }
}
