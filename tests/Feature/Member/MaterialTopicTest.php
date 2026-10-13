<?php

declare(strict_types=1);

namespace Tests\Feature\Member;

use App\Models\Material;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialTopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_topics_and_their_lessons_are_ordered_in_accordions(): void
    {
        $first = Topic::factory()->create(['name' => 'Dasar Kebangsaan', 'position' => 1]);
        $second = Topic::factory()->create(['section_id' => $first->section_id, 'name' => 'Pendalaman', 'position' => 2]);
        Material::factory()->video()->create(['section_id' => $first->section_id, 'topic_id' => $first->id, 'title' => 'Video Pembahasan', 'position' => 2, 'duration_minutes' => 15]);
        Material::factory()->create(['section_id' => $first->section_id, 'topic_id' => $first->id, 'title' => 'PDF Pengantar', 'position' => 1, 'page_count' => 12]);
        Material::factory()->create(['section_id' => $second->section_id, 'topic_id' => $second->id, 'title' => 'PDF Lanjutan']);
        Material::factory()->draft()->create(['section_id' => $first->section_id, 'topic_id' => $first->id, 'title' => 'Draft Rahasia']);
        $this->actingAs(User::factory()->create())->get(route('materials.index'))
            ->assertOk()->assertSee('<details', false)
            ->assertSeeInOrder(['Dasar Kebangsaan', 'PDF Pengantar', 'Video Pembahasan', 'Pendalaman', 'PDF Lanjutan'])
            ->assertSee('15 menit')->assertSee('12 halaman')->assertDontSee('Draft Rahasia');
    }

    public function test_inactive_and_deleted_topics_hide_materials_and_deny_direct_pdf_access(): void
    {
        Storage::fake(Material::PDF_DISK);
        $topic = Topic::factory()->create(['is_active' => false]);
        $material = Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'Materi Tersembunyi']);
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-test');
        $this->actingAs(User::factory()->create());
        $this->get(route('materials.index'))->assertOk()->assertDontSee($material->title);
        $this->get(route('materials.show', $material))->assertNotFound();
        $this->get(route('materials.file', $material))->assertNotFound();
        $topic->update(['is_active' => true]);
        $topic->delete();
        $this->get(route('materials.index'))->assertDontSee($material->title);
        $this->get(route('materials.show', $material))->assertNotFound();
        $this->get(route('materials.file', $material))->assertNotFound();
    }

    public function test_search_by_topic_respects_format_filter_and_excludes_other_topics(): void
    {
        $topic = Topic::factory()->create(['name' => 'Nasionalisme']);
        Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'Dokumen Pengantar']);
        Material::factory()->video()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'Video Pengantar']);
        Material::factory()->create(['title' => 'Materi Bidang Lain']);
        $this->actingAs(User::factory()->create())->get(route('materials.index', ['q' => 'Nasionalisme', 'type' => 'pdf']))
            ->assertOk()->assertSee('Dokumen Pengantar')->assertDontSee('Video Pengantar')->assertDontSee('Materi Bidang Lain');
    }

    public function test_pagination_keeps_all_lessons_of_one_topic_together(): void
    {
        $topic = Topic::factory()->create(['position' => 0]);
        foreach (range(1, 13) as $number) {
            Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'title' => 'Pelajaran nomor '.$number, 'position' => $number]);
        }
        $this->actingAs(User::factory()->create())->get(route('materials.index'))
            ->assertOk()->assertSee('Pelajaran nomor 1')->assertSee('Pelajaran nomor 13')
            ->assertViewHas('topics', fn ($topics) => $topics->total() === 1 && $topics->first()->materials->count() === 13);
    }

    public function test_topic_text_is_escaped(): void
    {
        $topic = Topic::factory()->create(['name' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(2)>']);
        Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id]);
        $this->actingAs(User::factory()->create())->get(route('materials.index'))
            ->assertOk()->assertSee($topic->name)->assertSee($topic->description)
            ->assertDontSee($topic->name, false)->assertDontSee($topic->description, false);
    }

    public function test_migration_places_existing_and_archived_materials_in_their_fields_default_topic(): void
    {
        $first = Material::factory()->create();
        $second = Material::factory()->create();
        $archived = Material::factory()->create(['section_id' => $first->section_id]);
        $archived->delete();
        $migration = require database_path('migrations/2026_10_07_143839_create_topics_and_add_topic_id_to_materials.php');

        $migration->down();
        $migration->up();

        $this->assertDatabaseCount('materials', 3);
        $this->assertDatabaseCount('topics', 2);
        foreach ([$first, $second, $archived] as $material) {
            $restored = Material::withTrashed()->findOrFail($material->id);
            $this->assertSame($material->file_path, $restored->file_path);
            $this->assertSame($material->section_id, $restored->topic->section_id);
            $this->assertSame('Umum', $restored->topic->name);
        }
        $this->assertSoftDeleted($archived);
    }
}
