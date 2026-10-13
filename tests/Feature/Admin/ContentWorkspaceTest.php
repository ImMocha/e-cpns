<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_filters_and_counts_are_scoped_to_the_selected_topic(): void
    {
        $topic = Topic::factory()->create();
        $wanted = Material::factory()->video()->draft()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'Video Pilihan']);
        Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'PDF Dalam Topik']);
        Material::factory()->video()->draft()->create(['title' => 'Video Bidang Lain']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.materials.index', ['topic_id' => $topic->id, 'type' => 'video', 'status' => 'draft', 'q' => 'Pilihan']))
            ->assertOk()->assertSee($wanted->title)->assertDontSee('PDF Dalam Topik')->assertDontSee('Video Bidang Lain')
            ->assertViewHas('selectedSection', fn ($section) => $section->id === $topic->section_id)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 2 && $stats['published'] === 1 && $stats['draft'] === 1);
    }

    public function test_topic_and_material_pagination_do_not_interfere(): void
    {
        $topic = Topic::factory()->create(['position' => 0]);
        Topic::factory()->count(9)->create(['section_id' => $topic->section_id, 'position' => 1]);
        Material::factory()->count(12)->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.materials.index', ['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'page' => 2, 'topic_page' => 2]))
            ->assertOk()
            ->assertViewHas('materials', fn ($materials) => $materials->currentPage() === 2 && $materials->count() === 2)
            ->assertViewHas('topics', fn ($topics) => $topics->currentPage() === 2 && $topics->count() === 2)
            ->assertSee('Sedang dibuka');
    }

    public function test_topic_from_a_different_field_returns_404(): void
    {
        $topic = Topic::factory()->create();
        $other = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.materials.index', ['section_id' => $other->id, 'topic_id' => $topic->id]))
            ->assertNotFound();
    }

    public function test_topic_directory_search_and_state_filters_respect_the_field(): void
    {
        $section = Section::factory()->create();
        $wanted = Topic::factory()->create(['section_id' => $section->id, 'name' => 'Logika Dasar', 'is_active' => false]);
        Topic::factory()->create(['section_id' => $section->id, 'name' => 'Logika Lanjutan', 'is_active' => true]);
        Topic::factory()->create(['name' => 'Logika Bidang Lain', 'is_active' => false]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.topics.index', ['section_id' => $section->id, 'q' => 'Logika', 'state' => 'inactive']))
            ->assertOk()->assertSee($wanted->name)->assertDontSee('Logika Lanjutan')->assertDontSee('Logika Bidang Lain')
            ->assertViewHas('topics', fn ($topics) => $topics->total() === 1);
    }

    public function test_empty_topic_filter_excludes_topics_with_materials(): void
    {
        $empty = Topic::factory()->create(['name' => 'Topik Kosong']);
        $filled = Topic::factory()->create(['name' => 'Topik Berisi']);
        Material::factory()->create(['section_id' => $filled->section_id, 'topic_id' => $filled->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.topics.index', ['state' => 'empty']))
            ->assertSee($empty->name)->assertDontSee($filled->name)
            ->assertViewHas('counts', fn ($counts) => $counts['total'] === 2 && $counts['empty'] === 1);
    }

    public function test_material_save_returns_to_its_topic_and_keeps_assignment(): void
    {
        $topic = Topic::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.materials.store'), [
                'title' => 'Materi Baru Dalam Topik', 'section_id' => $topic->section_id,
                'topic_id' => $topic->id, 'type' => 'video', 'status' => 'draft',
                'access_level' => 'free', 'workspace' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.materials.index', ['section_id' => $topic->section_id, 'topic_id' => $topic->id]));

        $this->assertDatabaseHas('materials', ['title' => 'Materi Baru Dalam Topik', 'section_id' => $topic->section_id, 'topic_id' => $topic->id]);
    }

    public function test_status_change_returns_to_the_topic_without_changing_assignment(): void
    {
        $material = Material::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.materials.status', $material), ['workspace' => 1])
            ->assertRedirect(route('admin.materials.index', ['section_id' => $material->section_id, 'topic_id' => $material->topic_id]));

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'status' => 'draft', 'topic_id' => $material->topic_id]);
    }

    public function test_archive_returns_to_the_topic_and_soft_deletes_only_the_material(): void
    {
        $material = Material::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.materials.destroy', $material), ['workspace' => 1])
            ->assertRedirect(route('admin.materials.index', ['section_id' => $material->section_id, 'topic_id' => $material->topic_id]));

        $this->assertSoftDeleted($material);
        $this->assertModelExists($material->topic);
    }

    public function test_workspace_escapes_titles_and_confirmation_text(): void
    {
        $title = 'Materi "ASN" <script>alert(1)</script>';
        Material::factory()->create(['title' => $title]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.materials.index'))
            ->assertOk()->assertSee($title)->assertDontSee($title, false)
            ->assertSee('data-content-confirm', false)->assertDontSee('onsubmit=', false);
    }

    public function test_topic_search_rejects_array_input_without_a_server_error(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.topics.index', ['q' => ['unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_topic_create_prefills_the_field_and_save_returns_to_it(): void
    {
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.topics.create', ['section_id' => $section->id]))
            ->assertViewHas('topic', fn ($topic) => (int) $topic->section_id === $section->id);

        $this->post(route('admin.topics.store'), ['section_id' => $section->id, 'name' => 'Topik Baru', 'is_active' => 1, 'workspace' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.topics.index', ['section_id' => $section->id]));

        $this->assertDatabaseHas('topics', ['name' => 'Topik Baru', 'section_id' => $section->id]);
    }
}
