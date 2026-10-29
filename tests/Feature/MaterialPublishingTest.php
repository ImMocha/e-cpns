<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaterialPublishingTest extends TestCase
{
    use RefreshDatabase;

    public static function publishScopes(): array
    {
        return ['all' => ['all'], 'section' => ['section'], 'topic' => ['topic']];
    }

    #[DataProvider('publishScopes')]
    public function test_bulk_publish_respects_scope_and_preserves_existing_publications(string $scope): void
    {
        $admin = User::factory()->admin()->create();
        $topic = Topic::factory()->create();
        $otherTopic = Topic::factory()->create(['section_id' => $topic->section_id]);
        $target = Material::factory()->draft()->video()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $sameSection = Material::factory()->draft()->create(['topic_id' => $otherTopic->id, 'section_id' => $topic->section_id]);
        $otherSection = Material::factory()->draft()->create();
        $missing = Material::factory()->draft()->withoutSource()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $published = Material::factory()->create(['published_at' => '2026-01-01 12:00:00']);
        $archived = Material::factory()->draft()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $archived->delete();
        $payload = match ($scope) {
            'section' => ['scope' => $scope, 'section_id' => $topic->section_id],
            'topic' => ['scope' => $scope, 'topic_id' => $topic->id],
            default => ['scope' => $scope],
        };

        $this->actingAs($admin)->from(route('admin.materials.index'))
            ->post(route('admin.materials.publish'), $payload)
            ->assertRedirect(route('admin.materials.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('materials', ['id' => $target->id, 'status' => 'published', 'updated_by' => $admin->id]);
        $this->assertNotNull($target->refresh()->published_at);
        $this->assertDatabaseHas('materials', ['id' => $sameSection->id, 'status' => $scope === 'topic' ? 'draft' : 'published']);
        $this->assertDatabaseHas('materials', ['id' => $otherSection->id, 'status' => $scope === 'all' ? 'published' : 'draft']);
        $this->assertDatabaseHas('materials', ['id' => $missing->id, 'status' => 'draft', 'published_at' => null]);
        $this->assertDatabaseHas('materials', ['id' => $archived->id, 'status' => 'draft']);
        $this->assertDatabaseHas('materials', ['id' => $published->id, 'published_at' => '2026-01-01 12:00:00', 'updated_by' => null]);
    }

    public static function invalidScopes(): array
    {
        return [
            'missing scope' => [[], 'scope'],
            'unknown scope' => [['scope' => 'invalid'], 'scope'],
            'missing section' => [['scope' => 'section'], 'section_id'],
            'missing topic' => [['scope' => 'topic'], 'topic_id'],
            'unknown section' => [['scope' => 'section', 'section_id' => 999999], 'section_id'],
            'unknown topic' => [['scope' => 'topic', 'topic_id' => 999999], 'topic_id'],
        ];
    }

    #[DataProvider('invalidScopes')]
    public function test_invalid_scope_does_not_publish_anything(array $payload, string $error): void
    {
        $material = Material::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.materials.publish'), $payload)
            ->assertSessionHasErrors($error);

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'status' => 'draft']);
    }

    public function test_guest_cannot_publish(): void
    {
        $material = Material::factory()->draft()->create();

        $this->post(route('admin.materials.publish'), ['scope' => 'all'])->assertRedirect(route('login'));

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'status' => 'draft']);
    }

    public function test_member_cannot_publish(): void
    {
        $material = Material::factory()->draft()->create();

        $this->actingAs(User::factory()->create())->post(route('admin.materials.publish'), ['scope' => 'all'])->assertForbidden();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'status' => 'draft']);
    }

    public function test_admin_can_preview_drafts_with_matching_counts_and_field_navigation(): void
    {
        $draft = Material::factory()->draft()->video()->create(['title' => 'Draft untuk pemeriksaan']);

        $this->actingAs(User::factory()->admin()->create())->get(route('materials.index', ['show_drafts' => 1]))
            ->assertSee($draft->topic->name)->assertSee($draft->section->name)->assertSee('Pratinjau admin aktif')
            ->assertSee(route('materials.topic', ['topic' => $draft->topic, 'show_drafts' => 1]))
            ->assertViewHas('counts', fn (array $counts): bool => $counts['total'] === 1 && $counts['video'] === 1);
        $this->get(route('materials.topic', ['topic' => $draft->topic, 'show_drafts' => 1]))
            ->assertSee($draft->title)->assertSee(route('admin.materials.status', $draft));
    }

    public function test_admin_listing_hides_drafts_until_preview_is_enabled(): void
    {
        $draft = Material::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('materials.index'))
            ->assertDontSee($draft->title)->assertSee('Tampilkan draft (admin only)');
    }

    public function test_member_cannot_enable_draft_preview_or_see_admin_controls(): void
    {
        $draft = Material::factory()->draft()->create();

        $this->actingAs(User::factory()->create())->get(route('materials.index', ['show_drafts' => 1]))
            ->assertDontSee($draft->title)->assertDontSee('Tampilkan draft (admin only)')->assertDontSee('Terbitkan semua materi')
            ->assertViewHas('counts', fn (array $counts): bool => $counts['total'] === 0);
        $this->get(route('materials.show', $draft))->assertNotFound();
        $this->get(route('materials.file', $draft))->assertNotFound();
    }

    public function test_admin_can_publish_directly_from_member_listing(): void
    {
        $draft = Material::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.materials.status', $draft), ['member_listing' => 1])
            ->assertRedirect(route('materials.index', ['show_drafts' => 1, 'section_id' => $draft->section_id, 'topic_id' => $draft->topic_id]));

        $this->assertDatabaseHas('materials', ['id' => $draft->id, 'status' => 'published']);
    }
}
