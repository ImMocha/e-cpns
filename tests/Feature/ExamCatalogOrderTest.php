<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamCatalogOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function kinds(): array
    {
        return ['tryout' => ['tryout'], 'practice' => ['practice']];
    }

    #[DataProvider('kinds')]
    public function test_admin_order_is_saved_and_used_in_admin_member_and_home_catalogs(string $kind): void
    {
        $exams = Exam::factory()->count(4)->create(['kind' => $kind]);
        $other = Exam::factory()->create(['kind' => $kind === 'tryout' ? 'practice' : 'tryout']);
        $prefix = ExamKind::from($kind)->adminRoutePrefix();
        $this->actingAs(User::factory()->admin()->create())->get(route($prefix.'.index'))
            ->assertSee('data-exam-catalog-order', false)->assertSee('data-exam-order-handle', false)
            ->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === array_reverse($exams->modelKeys()));

        $order = [$exams[1]->id, $exams[3]->id, $exams[0]->id, $exams[2]->id];
        $this->patchJson(route($prefix.'.order'), ['exam_ids' => $order])->assertOk()->assertJsonPath('message', 'Urutan berhasil disimpan.');
        foreach ($order as $position => $id) {
            $this->assertDatabaseHas('exams', ['id' => $id, 'position' => $position + 1]);
        }
        $this->assertNull($other->fresh()->position);
        $this->get(route($prefix.'.index'))->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === $order);
        $this->actingAs(User::factory()->create())->get(route(ExamKind::from($kind)->catalogRoute()))
            ->assertViewHas('banks', fn ($items): bool => $items->pluck('exam.id')->all() === $order);
        $this->get(route('home'))->assertViewHas('featuredExams', fn (array $items): bool => $items[$kind]->pluck('exam.id')->all() === array_slice($order, 0, 3));
    }

    #[DataProvider('kinds')]
    public function test_reordering_filtered_items_keeps_hidden_sessions_in_their_slots(string $kind): void
    {
        $first = Exam::factory()->create(['kind' => $kind, 'position' => 1, 'title' => 'Pilihan A']);
        $hidden = Exam::factory()->create(['kind' => $kind, 'position' => 2, 'status' => 'draft']);
        $last = Exam::factory()->create(['kind' => $kind, 'position' => 3, 'title' => 'Pilihan B']);
        $prefix = ExamKind::from($kind)->adminRoutePrefix();
        $this->actingAs(User::factory()->admin()->create())->get(route($prefix.'.index', ['q' => 'Pilihan', 'status' => 'published']))
            ->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === [$first->id, $last->id]);

        $this->patchJson(route($prefix.'.order'), ['exam_ids' => [$last->id, $first->id]])->assertOk();
        $this->get(route($prefix.'.index'))->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === [$last->id, $hidden->id, $first->id]);
        $this->assertDatabaseHas('exams', ['id' => $hidden->id, 'position' => 2]);
    }

    public function test_reordering_second_page_preserves_first_page_and_new_sessions_follow_saved_order(): void
    {
        $ids = [];
        foreach (range(1, 14) as $position) {
            $ids[] = Exam::factory()->tryout()->create(['position' => $position])->id;
        }
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.exams.index', ['page' => 2]))->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === array_slice($ids, 12));

        $this->patchJson(route('admin.exams.order'), ['exam_ids' => [$ids[13], $ids[12]]])->assertOk();
        $this->get(route('admin.exams.index'))->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === array_slice($ids, 0, 12));
        $new = Exam::factory()->tryout()->create();
        $this->get(route('admin.exams.index', ['page' => 2]))
            ->assertViewHas('exams', fn ($items): bool => $items->modelKeys() === [$ids[13], $ids[12], $new->id]);
    }

    #[DataProvider('kinds')]
    public function test_only_admins_can_save_catalog_order(string $kind): void
    {
        $exams = Exam::factory()->count(2)->create(['kind' => $kind]);
        $url = route(ExamKind::from($kind)->adminRoutePrefix().'.order');
        $data = ['exam_ids' => $exams->modelKeys()];
        $this->patchJson($url, $data)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->patchJson($url, $data)->assertForbidden();
        foreach ($exams as $exam) {
            $this->assertNull($exam->fresh()->position);
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidOrders(): array
    {
        return ['missing' => ['missing'], 'empty' => ['empty'], 'single' => ['single'], 'duplicate' => ['duplicate'],
            'other kind' => ['kind'], 'archived' => ['archived'], 'nonexistent' => ['nonexistent'],
            'invalid id' => ['invalid'], 'not a list' => ['associative'], 'too many' => ['many']];
    }

    #[DataProvider('invalidOrders')]
    public function test_invalid_order_does_not_change_any_positions(string $case): void
    {
        $first = Exam::factory()->tryout()->create(['position' => 1]);
        $second = Exam::factory()->tryout()->create(['position' => 2]);
        $other = Exam::factory()->practice()->create();
        $archived = Exam::factory()->tryout()->create();
        $archived->delete();
        $order = match ($case) {
            'missing' => null,
            'empty' => [],
            'single' => [$first->id],
            'duplicate' => [$second->id, $second->id],
            'kind' => [$second->id, $other->id],
            'archived' => [$second->id, $archived->id],
            'nonexistent' => [$second->id, 999999],
            'invalid' => [$second->id, 'invalid'],
            'associative' => ['first' => $second->id, 'second' => $first->id],
            'many' => range(1, 13),
        };

        $this->actingAs(User::factory()->admin()->create())->patchJson(route('admin.exams.order'), ['exam_ids' => $order])
            ->assertUnprocessable()->assertJsonValidationErrors($case === 'invalid' ? 'exam_ids.1' : ($case === 'duplicate' ? 'exam_ids.0' : 'exam_ids'));
        $this->assertDatabaseHas('exams', ['id' => $first->id, 'position' => 1]);
        $this->assertDatabaseHas('exams', ['id' => $second->id, 'position' => 2]);
        $this->assertNull($other->fresh()->position);
    }

    public function test_topic_quiz_lists_do_not_offer_catalog_drag_handles(): void
    {
        Exam::factory()->count(2)->create();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.materials.quizzes.index'))
            ->assertDontSee('data-exam-catalog-order', false)->assertDontSee('data-exam-order-handle', false);
    }
}
