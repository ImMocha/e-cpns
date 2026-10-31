<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Question;
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

    public function test_previous_and_next_links_follow_lesson_order_and_skip_hidden_materials(): void
    {
        $topic = Topic::factory()->create();
        $first = $this->lesson($topic, ['position' => 10]);
        $current = $this->lesson($topic, ['position' => 10]);
        $this->lesson($topic, ['position' => 11, 'status' => 'draft']);
        $deleted = $this->lesson($topic, ['position' => 12]);
        $deleted->delete();
        $next = $this->lesson($topic, ['position' => 20]);
        $this->actingAs(User::factory()->create());

        $this->get(route('materials.topic', ['topic' => $topic, 'material' => $current->id]))
            ->assertViewHas('previousMaterial', fn (Material $material): bool => $material->is($first))
            ->assertViewHas('nextMaterial', fn (Material $material): bool => $material->is($next))
            ->assertViewHas('isLastMaterial', false)->assertSee('Materi 2 dari 3')
            ->assertSee('Materi sebelumnya')->assertSee('Materi berikutnya')
            ->assertSee(route('materials.topic', ['topic' => $topic, 'material' => $next->id]))
            ->assertDontSee('Materi terakhir dalam topik ini')->assertDontSee($deleted->title);
        $this->get(route('materials.show', $first))->assertViewHas('previousMaterial', null)
            ->assertViewHas('nextMaterial', fn (Material $material): bool => $material->is($current));
    }

    public function test_last_lesson_offers_topic_quiz_and_next_topic_without_bypassing_completion_requirements(): void
    {
        $topic = Topic::factory()->create(['position' => 1]);
        $first = $this->lesson($topic, ['position' => 0]);
        $last = $this->lesson($topic, ['position' => 1]);
        $nextTopic = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 2]);
        $this->lesson($nextTopic);
        $quiz = Exam::factory()->for($topic)->create(['requires_topic_completion' => true]);
        $quiz->questions()->attach(Question::factory()->published()->create());
        $user = User::factory()->create();
        MaterialProgress::factory()->for($user)->for($first)->create(['completed_at' => now()]);
        $this->actingAs($user);

        $this->get(route('materials.show', $last))->assertViewHas('isLastMaterial', true)->assertViewHas('nextMaterial', null)
            ->assertViewHas('nextTopic', fn (Topic $topic): bool => $topic->is($nextTopic))
            ->assertSeeInOrder(['aria-label="Daftar materi topik"', 'Latihan Soal Topik', $quiz->title, '</aside>', 'Materi terakhir dalam topik ini'], false)
            ->assertSee('Kerjakan soal topik')->assertSee('Lanjut ke topik berikutnya')
            ->assertSee(route('materials.topic', $nextTopic))->assertSee('Selesaikan materi topik dahulu')->assertDontSee('Mulai latihan');

        $this->assertNull(MaterialProgress::query()->whereBelongsTo($user)->whereBelongsTo($last)->sole()->completed_at);
        $this->post(route('materials.progress.store', $last))->assertRedirect();
        $this->get(route('materials.show', $last))->assertSee('Mulai latihan')->assertSee(route('quizzes.start', $quiz->slug));
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_next_topic_uses_position_then_id_within_the_same_section_and_skips_unavailable_topics(): void
    {
        $topic = Topic::factory()->create(['position' => 5]);
        $lesson = $this->lesson($topic);
        Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5]);
        $inactive = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5, 'is_active' => false]);
        $this->lesson($inactive);
        $draftOnly = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5]);
        $this->lesson($draftOnly, ['status' => 'draft']);
        $archived = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5]);
        $this->lesson($archived);
        $archived->delete();
        $mismatched = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5]);
        Material::factory()->video()->create(['topic_id' => $mismatched->id]);
        $otherSection = Topic::factory()->create(['position' => 5]);
        $this->lesson($otherSection);
        $expected = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 5]);
        $this->lesson($expected);
        $later = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 6]);
        $this->lesson($later);

        $this->actingAs(User::factory()->create())->get(route('materials.show', $lesson))
            ->assertViewHas('nextTopic', fn (Topic $topic): bool => $topic->is($expected))
            ->assertSee($expected->name)->assertDontSee($draftOnly->name)->assertDontSee($archived->name)
            ->assertDontSee($mismatched->name)->assertDontSee($inactive->name)->assertDontSee($otherSection->name);
    }

    public function test_last_topic_without_quiz_offers_catalog_instead_of_dead_end_links(): void
    {
        $topic = Topic::factory()->create();
        $lesson = $this->lesson($topic);

        $this->actingAs(User::factory()->create())->get(route('materials.show', $lesson))
            ->assertViewHas('nextTopic', null)->assertViewHas('isLastMaterial', true)
            ->assertSee('Soal untuk topik ini belum tersedia.')->assertSee('Pilih topik lain')
            ->assertDontSee('Lanjut ke topik berikutnya')->assertDontSee('Kerjakan soal topik')
            ->assertDontSee('Latihan Soal Topik');
    }

    public function test_admin_navigation_keeps_draft_preview_and_members_cannot_navigate_to_draft_only_topics(): void
    {
        $topic = Topic::factory()->create(['position' => 0]);
        $published = $this->lesson($topic, ['position' => 0]);
        $draft = $this->lesson($topic, ['position' => 1, 'status' => 'draft']);
        $draftTopic = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 1]);
        $this->lesson($draftTopic, ['status' => 'draft']);
        $url = route('materials.topic', ['topic' => $topic, 'material' => $published->id, 'show_drafts' => 1]);

        $this->actingAs(User::factory()->admin()->create())->get($url)
            ->assertViewHas('nextMaterial', fn (Material $material): bool => $material->is($draft))
            ->assertSee(route('materials.topic', ['topic' => $topic, 'material' => $draft->id, 'show_drafts' => 1]));
        $this->get(route('materials.show', $draft))->assertViewHas('showDrafts', true)
            ->assertViewHas('nextTopic', fn (Topic $topic): bool => $topic->is($draftTopic))
            ->assertSee(route('materials.topic', ['topic' => $draftTopic, 'show_drafts' => 1]));
        $this->actingAs(User::factory()->create())->get($url)->assertViewHas('nextMaterial', null)
            ->assertViewHas('nextTopic', null)->assertDontSee($draft->title)->assertDontSee($draftTopic->name);
    }

    public function test_navigating_to_premium_material_or_topic_preserves_content_access_checks(): void
    {
        $topic = Topic::factory()->create(['position' => 0]);
        $first = $this->lesson($topic, ['position' => 0]);
        $locked = $this->lesson($topic, ['position' => 1, 'access_level' => 'premium']);
        $nextTopic = Topic::factory()->create(['section_id' => $topic->section_id, 'position' => 1]);
        $lockedNext = $this->lesson($nextTopic, ['access_level' => 'premium']);
        $this->actingAs(User::factory()->create());

        $this->get(route('materials.show', $first))->assertViewHas('nextMaterial', fn (Material $material): bool => $material->is($locked))
            ->assertDontSee($locked->video_url);
        $this->get(route('materials.topic', ['topic' => $topic, 'material' => $locked->id]))
            ->assertSee('Materi Premium')->assertDontSee($locked->video_url)->assertSee(route('materials.topic', $nextTopic));
        $this->get(route('materials.topic', $nextTopic))->assertSee('Materi Premium')->assertDontSee($lockedNext->video_url);
        $this->assertDatabaseCount('material_progress', 1);
    }

    /** @param array<string, mixed> $attributes */
    private function lesson(Topic $topic, array $attributes = []): Material
    {
        return Material::factory()->video()->create($attributes + ['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
    }
}
