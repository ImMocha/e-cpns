<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamClaim;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\Voucher;
use App\TrashManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrashManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_types_groups_archived_records_and_filters_across_categories(): void
    {
        $topic = Topic::factory()->create(['name' => 'Arsip bersama']);
        $topic->delete();
        $material = Material::factory()->create(['title' => 'Materi arsip bersama']);
        $material->delete();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.trash.index', ['type' => 'all', 'q' => 'arsip']))
            ->assertOk()
            ->assertSee('trash-category-topics')
            ->assertSee('trash-category-materials')
            ->assertSee('Materi arsip bersama')
            ->assertSee(route('admin.trash.show', ['materials', $material->id]))
            ->assertSee(route('admin.trash.show', ['topics', $topic->id]));
    }

    public function test_topic_archive_and_recovery_preserve_previously_archived_children(): void
    {
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $old = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $old->delete();
        $exam = Exam::factory()->create(['topic_id' => $topic->id]);
        $attempt = ExamAttempt::factory()->create(['kind' => $exam->kind, 'bank_slug' => $exam->slug]);
        $this->actingAs(User::factory()->admin()->create());

        $this->delete(route('admin.topics.destroy', $topic))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($topic);
        $this->assertSoftDeleted($material);
        $this->assertSoftDeleted($exam);
        $this->assertSoftDeleted($attempt);
        $this->post(route('admin.trash.restore', ['topics', $topic->id]))->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($topic);
        $this->assertNotSoftDeleted($material);
        $this->assertNotSoftDeleted($exam);
        $this->assertNotSoftDeleted($attempt);
        $this->assertSoftDeleted($old);
    }

    public function test_permanent_topic_deletion_removes_dependents_but_keeps_shared_questions_and_packages(): void
    {
        $topic = Topic::factory()->create(['name' => 'Topik A']);
        $other = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $exam = Exam::factory()->create(['topic_id' => $topic->id]);
        $question = Question::factory()->create();
        $exam->questions()->attach($question);
        $otherExam = Exam::factory()->create(['topic_id' => $other->id]);
        $otherExam->questions()->attach($question);
        $package = Package::factory()->create();
        $package->materials()->attach($material);
        $package->exams()->attach($exam);
        $attempt = ExamAttempt::factory()->create(['kind' => $exam->kind, 'bank_slug' => $exam->slug]);
        $claim = ExamClaim::factory()->create(['exam_id' => $exam->id]);
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.topics.destroy', $topic));
        $page = $this->get(route('admin.trash.show', ['topics', $topic->id]));
        $page->assertOk()->assertSee('Topik A')->assertSee($material->title)->assertSee('Riwayat pengerjaan')
            ->assertSee('Materi pembelajaran')->assertSee('Latihan Topik')->assertSee('Relasi soal dalam sesi')
            ->assertSee($question->title)->assertSee($exam->title)->assertDontSee('Soal #'.$question->id);

        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();

        foreach ([$topic, $material, $exam, $attempt, $claim] as $model) {
            $this->assertModelMissing($model);
        }
        foreach ([$other, $otherExam, $question, $package] as $model) {
            $this->assertModelExists($model);
        }
        $this->assertDatabaseHas('exam_question', ['exam_id' => $otherExam->id, 'question_id' => $question->id]);
        $this->assertDatabaseMissing('material_package', ['material_id' => $material->id]);
    }

    public function test_package_deletion_handles_restrict_foreign_keys(): void
    {
        $package = Package::factory()->create();
        $grant = UserPackage::factory()->create(['package_id' => $package->id]);
        $voucher = Voucher::factory()->create(['package_id' => $package->id]);
        $transaction = Transaction::factory()->create(['package_id' => $package->id, 'user_package_id' => $grant->id, 'voucher_id' => $voucher->id]);
        $package->delete();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['packages', $package->id]))->assertOk();

        $this->delete(route('admin.trash.destroy', ['packages', $package->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();

        foreach ([$package, $grant, $voucher, $transaction] as $model) {
            $this->assertModelMissing($model);
        }
    }

    public function test_confirmation_and_fresh_impact_are_required(): void
    {
        $topic = Topic::factory()->create();
        $topic->delete();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['topics', $topic->id]));
        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]))->assertSessionHasErrors(['confirmation', 'fingerprint']);
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);

        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasErrors('impact');

        $this->assertModelExists($topic);
        $this->assertModelExists($material);
    }

    public function test_child_cannot_be_restored_while_parent_is_archived(): void
    {
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.topics.destroy', $topic));

        $this->post(route('admin.trash.restore', ['materials', $material->id]))->assertSessionHasErrors('restore');
        $this->assertSoftDeleted($material);
    }

    public function test_active_records_cannot_be_permanently_deleted_or_restored(): void
    {
        $topic = Topic::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['topics', $topic->id]));
        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertConflict();
        $this->post(route('admin.trash.restore', ['topics', $topic->id]))->assertConflict();
        $this->assertNotSoftDeleted($topic);
    }

    public function test_trash_is_admin_only_and_self_archival_is_forbidden(): void
    {
        $topic = Topic::factory()->create();
        $this->get(route('admin.trash.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.trash.show', ['topics', $topic->id]))->assertForbidden();
        $this->post(route('admin.trash.archive', ['topics', $topic->id]))->assertForbidden();
        $this->post(route('admin.trash.restore', ['topics', $topic->id]))->assertForbidden();
        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]))->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.trash.archive', ['users', $admin->id]))->assertForbidden();
        $this->assertNotSoftDeleted($admin);
        $this->get(route('admin.trash.show', ['invalid', 1]))->assertNotFound();
    }

    public function test_exclusive_questions_and_private_files_are_removed_only_on_permanent_deletion(): void
    {
        Storage::fake('local');
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id, 'file_path' => 'materials/pdf/trash-test.pdf']);
        $exam = Exam::factory()->create(['topic_id' => $topic->id]);
        $question = Question::factory()->create(['image_path' => 'questions/images/trash-test.png']);
        $exam->questions()->attach($question);
        Storage::disk('local')->put($material->file_path, 'pdf');
        Storage::disk('local')->put($question->image_path, 'image');
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.topics.destroy', $topic));
        Storage::disk('local')->assertExists($material->file_path);
        $this->assertSoftDeleted($question);
        $page = $this->get(route('admin.trash.show', ['topics', $topic->id]));

        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();

        $this->assertModelMissing($question);
        Storage::disk('local')->assertMissing($material->file_path);
        Storage::disk('local')->assertMissing($question->image_path);
    }

    public function test_shared_files_remain_when_another_record_uses_them(): void
    {
        Storage::fake('local');
        $material = Material::factory()->create(['file_path' => 'materials/pdf/shared.pdf']);
        $other = Material::factory()->create(['file_path' => $material->file_path]);
        Storage::disk('local')->put($material->file_path, 'pdf');
        $material->delete();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['materials', $material->id]));

        $this->delete(route('admin.trash.destroy', ['materials', $material->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();

        $this->assertModelExists($other);
        Storage::disk('local')->assertExists($material->file_path);
    }

    public function test_section_recovery_preserves_a_previously_archived_topic(): void
    {
        $topic = Topic::factory()->create();
        $section = $topic->section;
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $section->id]);
        $topic->delete();
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.sections.destroy', $section))->assertSessionHasNoErrors();

        $this->post(route('admin.trash.restore', ['sections', $section->id]))->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($section);
        $this->assertSoftDeleted($topic);
        $this->assertNotSoftDeleted($material);
    }

    public function test_user_permanent_deletion_removes_payments_and_nulls_authorship_without_deleting_content(): void
    {
        $user = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $user->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $user->delete();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['users', $user->id]));

        $this->delete(route('admin.trash.destroy', ['users', $user->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();

        $this->assertModelMissing($user);
        $this->assertModelMissing($transaction);
        $this->assertModelExists($material);
        $this->assertNull($material->fresh()->created_by);
    }

    public function test_an_active_child_of_a_legacy_archived_topic_can_still_be_archived(): void
    {
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $topic->delete();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.trash.archive', ['materials', $material->id]))->assertSessionHasNoErrors();

        $this->assertSoftDeleted($material);
    }

    public function test_a_database_failure_rolls_back_the_entire_deletion(): void
    {
        $topic = Topic::factory()->create();
        $material = Material::factory()->create(['topic_id' => $topic->id, 'section_id' => $topic->section_id]);
        $topic->delete();
        $this->actingAs(User::factory()->admin()->create());
        $page = $this->get(route('admin.trash.show', ['topics', $topic->id]));
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with(strtolower($query->sql), 'delete from') && str_contains($query->sql, 'topics')) {
                throw new \RuntimeException('Simulated failure after deleting dependent records.');
            }
        });

        $this->delete(route('admin.trash.destroy', ['topics', $topic->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertServerError();

        $this->assertModelExists($topic);
        $this->assertModelExists($material);
    }

    public static function resources(): array
    {
        return array_map(fn (string $type): array => [$type], array_keys(TrashManager::RESOURCES));
    }

    #[DataProvider('resources')]
    public function test_each_resource_supports_archive_list_recovery_and_permanent_deletion(string $type): void
    {
        $class = TrashManager::RESOURCES[$type][0];
        $record = $class::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.trash.index', ['type' => $type, 'state' => 'active']))->assertOk();
        $this->post(route('admin.trash.archive', [$type, $record->id]))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($record);
        $this->get(route('admin.trash.index', ['type' => $type]))->assertOk();
        $this->post(route('admin.trash.restore', [$type, $record->id]))->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($record);
        $this->post(route('admin.trash.archive', [$type, $record->id]))->assertSessionHasNoErrors();
        $page = $this->get(route('admin.trash.show', [$type, $record->id]))->assertOk();
        $this->delete(route('admin.trash.destroy', [$type, $record->id]), ['confirmation' => 'HAPUS PERMANEN', 'fingerprint' => $page->viewData('fingerprint')])->assertSessionHasNoErrors();
        $this->assertModelMissing($record);
    }
}
