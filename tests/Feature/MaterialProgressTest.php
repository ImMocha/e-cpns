<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_mark_material_complete(): void
    {
        $material = Material::factory()->video()->create();

        $this->post(route('materials.progress.store', $material))->assertRedirect(route('login'));
        $this->assertDatabaseCount('material_progress', 0);
    }

    public function test_opening_material_tracks_view_and_completion_updates_dashboard_and_catalog(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['name' => 'TIU']);
        $video = Material::factory()->video()->create(['section_id' => $section->id, 'title' => 'Video Penalaran']);
        Material::factory()->video()->create(['section_id' => $section->id, 'title' => 'Video Kedua']);

        $this->actingAs($user)->get(route('materials.show', $video))
            ->assertOk()->assertSee('Tandai selesai');
        $this->assertDatabaseHas('material_progress', [
            'user_id' => $user->id, 'material_id' => $video->id, 'completed_at' => null,
        ]);

        $this->actingAs($user)->post(route('materials.progress.store', $video))
            ->assertRedirect(route('materials.show', $video));
        $completedAt = MaterialProgress::query()->sole()->completed_at;
        $this->actingAs($user)->post(route('materials.progress.store', $video))
            ->assertRedirect(route('materials.show', $video));

        $this->assertDatabaseCount('material_progress', 1);
        $this->assertSame($completedAt->toISOString(), MaterialProgress::query()->sole()->completed_at->toISOString());
        $this->actingAs($user)->get(route('dashboard'))
            ->assertViewHas('completedMaterialCount', 1)
            ->assertViewHas('materialCount', 2)
            ->assertViewHas('materialProgressPercent', 50)
            ->assertViewHas('completedVideoCount', 1)
            ->assertViewHas('recentMaterials', fn ($materials): bool => $materials->every(fn (Material $material): bool => $material->id !== $video->id))
            ->assertSee('1 / 2');
        $this->actingAs($user)->get(route('materials.index'))
            ->assertViewHas('completedMaterialIds', fn (array $ids): bool => isset($ids[$video->id]));
    }

    public function test_unmarking_completion_preserves_view_history_and_other_users_progress(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $material = Material::factory()->video()->create();

        $this->actingAs($firstUser)->post(route('materials.progress.store', $material))->assertRedirect();
        $this->actingAs($secondUser)->post(route('materials.progress.store', $material))->assertRedirect();
        $this->actingAs($firstUser)->delete(route('materials.progress.destroy', $material))
            ->assertRedirect(route('materials.show', $material));

        $this->assertDatabaseHas('material_progress', [
            'user_id' => $firstUser->id, 'material_id' => $material->id, 'completed_at' => null,
        ]);
        $this->assertNotNull(MaterialProgress::query()->whereBelongsTo($secondUser)->sole()->completed_at);
        $this->actingAs($firstUser)->get(route('dashboard'))->assertViewHas('completedMaterialCount', 0);
        $this->actingAs($secondUser)->get(route('dashboard'))->assertViewHas('completedMaterialCount', 1);
    }

    public function test_draft_premium_and_admin_preview_cannot_create_participant_progress(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $draft = Material::factory()->draft()->video()->create();
        $premium = Material::factory()->premium()->video()->create();
        $free = Material::factory()->video()->create();

        $this->actingAs($user)->post(route('materials.progress.store', $draft))->assertNotFound();
        $this->actingAs($user)->post(route('materials.progress.store', $premium))->assertForbidden();
        $this->actingAs($admin)->get(route('materials.show', $free))->assertOk();
        $this->actingAs($admin)->post(route('materials.progress.store', $free))->assertForbidden();

        $this->assertDatabaseCount('material_progress', 0);
    }

    public function test_pdf_without_a_file_cannot_be_marked_complete(): void
    {
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $material = Material::factory()->pdf()->create();

        $this->actingAs($user)->get(route('materials.show', $material))
            ->assertOk()->assertDontSee('Tandai selesai');
        $this->actingAs($user)->post(route('materials.progress.store', $material))
            ->assertUnprocessable();

        $this->assertDatabaseCount('material_progress', 0);
    }

    public function test_pdf_with_a_file_can_be_marked_complete(): void
    {
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $material = Material::factory()->pdf()->create();
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-test');

        $this->actingAs($user)->get(route('materials.show', $material))
            ->assertOk()->assertSee('Tandai selesai');
        $this->actingAs($user)->post(route('materials.progress.store', $material))
            ->assertRedirect(route('materials.show', $material));

        $this->assertNotNull(MaterialProgress::query()->sole()->completed_at);
    }

    public function test_hidden_material_is_not_counted_in_current_learning_progress(): void
    {
        $user = User::factory()->create();
        $material = Material::factory()->video()->create();
        $this->actingAs($user)->post(route('materials.progress.store', $material))->assertRedirect();
        $material->update(['status' => 'draft']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertViewHas('completedMaterialCount', 0)
            ->assertViewHas('materialCount', 0);
    }

    public function test_dashboard_prioritizes_the_last_opened_unfinished_material(): void
    {
        $user = User::factory()->create();
        $first = Material::factory()->video()->create(['title' => 'Video Pertama']);
        Material::factory()->video()->create(['title' => 'Video Terbaru']);

        $this->actingAs($user)->get(route('materials.show', $first))->assertOk();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertViewHas('recentMaterials', fn ($materials): bool => $materials->first()->id === $first->id)
            ->assertSee('Video Pertama');
    }
}
