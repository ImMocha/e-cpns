<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicLearningPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_link_opens_first_accessible_lesson_and_ordered_sidebar(): void
    {
        $topic = Topic::factory()->create();
        $locked = $this->lesson($topic, ['position' => 0, 'access_level' => 'premium']);
        $first = $this->lesson($topic, ['position' => 1]);
        $second = $this->lesson($topic, ['position' => 2]);
        $hidden = $this->lesson($topic, ['status' => 'draft']);
        $other = Material::factory()->video()->create();
        $user = User::factory()->create();
        MaterialProgress::factory()->create(['user_id' => $user->id, 'material_id' => $second->id, 'completed_at' => now()]);

        $this->actingAs($user)->get(route('materials.index'))->assertSee(route('materials.topic', $topic), false)
            ->assertDontSee('<details', false);
        $this->get(route('materials.topic', $topic))->assertOk()->assertViewHas('material', fn (Material $material): bool => $material->is($first))
            ->assertSeeInOrder([$locked->title, $first->title, $second->title])->assertSee('Selesai')
            ->assertSee($first->video_url)->assertDontSee($locked->video_url)->assertDontSee($hidden->title)->assertDontSee($other->title);
        $this->assertDatabaseHas('material_progress', ['user_id' => $user->id, 'material_id' => $first->id]);
        $this->get(route('materials.topic', ['topic' => $topic, 'material' => $second->id]))
            ->assertViewHas('material', fn (Material $material): bool => $material->is($second))->assertSee($second->video_url)->assertDontSee($first->video_url);
    }

    public function test_locked_topic_never_exposes_content_or_records_progress(): void
    {
        $topic = Topic::factory()->create();
        $locked = $this->lesson($topic, ['access_level' => 'premium']);

        $this->actingAs(User::factory()->create())->get(route('materials.topic', $topic))
            ->assertOk()->assertSee('Materi Premium')->assertSee('Lihat paket belajar')->assertDontSee($locked->video_url);
        $this->assertDatabaseCount('material_progress', 0);
        $this->get(route('materials.file', $locked))->assertForbidden();
    }

    public function test_topic_rejects_other_topics_materials_hidden_lessons_and_invalid_input(): void
    {
        $topic = Topic::factory()->create();
        $this->lesson($topic);
        $other = Material::factory()->video()->create();
        $draft = $this->lesson($topic, ['status' => 'draft']);
        $this->actingAs(User::factory()->create());

        foreach ([$other->id, $draft->id, 999999] as $id) {
            $this->get(route('materials.topic', ['topic' => $topic, 'material' => $id]))->assertNotFound();
        }
        $this->get(route('materials.topic', ['topic' => $topic, 'material' => ['invalid']]))->assertSessionHasErrors('material');
    }

    public function test_guests_and_hidden_or_empty_topics_cannot_be_opened(): void
    {
        $topic = Topic::factory()->create();
        $this->lesson($topic);
        $this->get(route('materials.topic', $topic))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $topic->update(['is_active' => false]);
        $this->get(route('materials.topic', $topic))->assertNotFound();
        $topic->update(['is_active' => true]);
        $topic->section->update(['is_active' => false]);
        $this->get(route('materials.topic', $topic))->assertNotFound();
        $topic->section->update(['is_active' => true]);
        $topic->delete();
        $this->get(route('materials.topic', $topic))->assertNotFound();
        $this->get(route('materials.topic', Topic::factory()->create()))->assertNotFound();
    }

    public function test_only_admin_can_include_drafts_in_topic_navigation(): void
    {
        $topic = Topic::factory()->create();
        $published = $this->lesson($topic, ['position' => 1]);
        $draft = $this->lesson($topic, ['position' => 0, 'status' => 'draft']);
        $url = route('materials.topic', ['topic' => $topic, 'show_drafts' => 1]);

        $this->actingAs(User::factory()->create())->get($url)->assertDontSee($draft->title)->assertSee($published->video_url);
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertSee($draft->title)->assertSee($draft->video_url)->assertSee('Draft');
        $this->assertDatabaseCount('material_progress', 1);
    }

    /** @param array<string, mixed> $attributes */
    private function lesson(Topic $topic, array $attributes = []): Material
    {
        return Material::factory()->video()->create($attributes + ['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
    }
}
