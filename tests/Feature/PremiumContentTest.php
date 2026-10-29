<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialAccess;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PremiumContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_covers_archived_content_and_disables_free_claims(): void
    {
        $material = Material::factory()->create();
        $archivedMaterial = Material::factory()->create();
        $archivedMaterial->delete();
        $exam = Exam::factory()->tryout()->claimable()->create();
        $archivedExam = Exam::factory()->tryout()->create();
        $archivedExam->delete();
        $migration = require database_path('migrations/2026_10_29_075920_make_all_learning_content_premium.php');

        $migration->up();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'access_level' => 'premium']);
        $this->assertDatabaseHas('materials', ['id' => $archivedMaterial->id, 'access_level' => 'premium']);
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'access_level' => 'premium', 'free_claim_enabled' => false]);
        $this->assertDatabaseHas('exams', ['id' => $archivedExam->id, 'access_level' => 'premium']);
        $this->assertSoftDeleted($archivedMaterial);
        $this->assertSoftDeleted($archivedExam);
        $this->actingAs(User::factory()->create())->post(route('tryouts.start', $exam->slug))->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_new_database_records_default_to_premium_when_access_is_omitted(): void
    {
        $material = Material::factory()->raw();
        $exam = Exam::factory()->tryout()->raw();
        unset($material['access_level'], $exam['access_level']);

        $materialId = DB::table('materials')->insertGetId($material);
        $examId = DB::table('exams')->insertGetId($exam);

        $this->assertSame(MaterialAccess::Premium, Material::findOrFail($materialId)->access_level);
        $this->assertSame(MaterialAccess::Premium, Exam::findOrFail($examId)->access_level);
    }

    /** @return array<string, array{string}> */
    public static function forbiddenAccessLevels(): array
    {
        return ['free' => ['free']];
    }

    #[DataProvider('forbiddenAccessLevels')]
    public function test_admin_cannot_create_unconditionally_free_sessions(string $access): void
    {
        $payload = Exam::factory()->tryout()->raw(['status' => 'draft', 'access_level' => $access]);
        $payload['free_claim_terms'] = 'Syarat contoh';
        $payload['free_claim_instagram_url'] = 'https://www.instagram.com/ganeshanusa/';

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.exams.store'), $payload)
            ->assertSessionHasErrors('access_level');

        $this->assertDatabaseCount('exams', 0);
        $this->get(route('admin.exams.create'))->assertDontSee('value="free"', false)->assertSee('value="conditional"', false);
    }

    public function test_admin_creates_premium_material_and_cannot_choose_free_access(): void
    {
        $topic = Topic::factory()->create();
        $payload = ['section_id' => $topic->section_id, 'topic_id' => $topic->id,
            'title' => 'Materi Akses Premium', 'type' => 'pdf', 'status' => 'draft', 'access_level' => 'free'];
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.materials.store'), $payload)->assertSessionHasErrors('access_level');
        $this->post(route('admin.materials.store'), array_replace($payload, ['access_level' => 'premium']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('materials', ['title' => 'Materi Akses Premium', 'access_level' => 'premium']);
        $this->get(route('admin.materials.create'))->assertDontSee('value="free"', false);
    }
}
