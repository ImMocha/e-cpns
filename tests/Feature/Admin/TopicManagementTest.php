<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_archive_an_empty_topic(): void
    {
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.topics.create'))->assertOk();
        $this->post(route('admin.topics.store'), [
            'section_id' => $section->id, 'name' => '  Nasionalisme  ',
            'position' => 2, 'is_active' => 1,
        ])->assertRedirect(route('admin.topics.index'))->assertSessionHasNoErrors();
        $topic = Topic::query()->sole();
        $this->assertSame('Nasionalisme', $topic->name);
        $this->get(route('admin.topics.edit', $topic))->assertOk()->assertSee('Nasionalisme');
        $this->put(route('admin.topics.update', $topic), [
            'section_id' => $section->id, 'name' => 'Pancasila', 'position' => 4,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.topics.index'));
        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'name' => 'Pancasila', 'position' => 4, 'is_active' => false]);
        $this->delete(route('admin.topics.destroy', $topic))->assertRedirect(route('admin.topics.index'));
        $this->assertSoftDeleted($topic);
    }

    public function test_topic_names_are_unique_within_their_field(): void
    {
        $topic = Topic::factory()->create(['name' => 'Penalaran']);
        $this->actingAs(User::factory()->admin()->create());
        $payload = ['section_id' => $topic->section_id, 'name' => $topic->name, 'is_active' => 1];
        $this->post(route('admin.topics.store'), $payload)->assertSessionHasErrors('name');
        $payload['section_id'] = Section::factory()->create()->id;
        $this->post(route('admin.topics.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('topics', 2);
    }

    public function test_topic_with_archived_material_cannot_be_moved_or_deleted(): void
    {
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['section_id' => $topic->section_id, 'topic_id' => $topic->id]);
        $material->delete();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.topics.update', $topic), [
            'section_id' => Section::factory()->create()->id, 'name' => $topic->name, 'is_active' => 1,
        ])->assertSessionHasErrors('section_id');
        $this->delete(route('admin.topics.destroy', $topic))->assertSessionHas('error');
        $this->assertNotSoftDeleted($topic);
        $this->assertSame($topic->section_id, $topic->fresh()->section_id);
    }

    public function test_material_cannot_use_a_topic_from_another_field_or_a_deleted_topic(): void
    {
        $topic = Topic::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $payload = [
            'section_id' => Section::factory()->create()->id, 'topic_id' => $topic->id,
            'title' => 'Materi Penalaran Baru', 'type' => 'pdf', 'status' => 'draft', 'access_level' => 'free',
        ];
        $this->post(route('admin.materials.store'), $payload)->assertSessionHasErrors('topic_id');
        $payload['section_id'] = $topic->section_id;
        $topic->delete();
        $this->post(route('admin.materials.store'), $payload)->assertSessionHasErrors('topic_id');
        $this->assertDatabaseCount('materials', 0);
    }

    public function test_participant_cannot_manage_topics(): void
    {
        $topic = Topic::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.topics.index'))->assertForbidden();
        $this->post(route('admin.topics.store'), ['name' => 'Unauthorized'])->assertForbidden();
        $this->put(route('admin.topics.update', $topic), ['name' => 'Unauthorized'])->assertForbidden();
        $this->delete(route('admin.topics.destroy', $topic))->assertForbidden();
        $this->assertSame($topic->name, $topic->fresh()->name);
    }
}
