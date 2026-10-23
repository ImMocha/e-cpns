<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace(['name' => 'Paket Persiapan', 'description' => 'Belajar terarah', 'price' => 75000,
            'duration_days' => 30, 'tryout_quota' => 2, 'position' => 0, 'is_active' => true,
            'material_ids' => [Material::factory()->premium()->video()->create()->id], 'exam_ids' => []], $overrides);
    }

    public function test_admin_creates_updates_and_archives_a_package(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();
        $this->actingAs($admin)->get(route('admin.packages.create'))->assertOk();

        $this->post(route('admin.packages.store'), $payload)->assertSessionHasNoErrors();
        $package = Package::sole();
        $this->assertSame($payload['material_ids'], $package->materials()->pluck('materials.id')->all());
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'price' => 75000, 'created_by' => $admin->id]);
        $this->get(route('admin.packages.index'))->assertSee('Paket Persiapan');
        $this->get(route('admin.packages.edit', $package))->assertSee('Ketentuan paket');
        $this->put(route('admin.packages.update', $package), array_replace($payload, ['name' => 'Paket Revisi']))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.packages.edit', $package));
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'name' => 'Paket Revisi']);

        $this->delete(route('admin.packages.destroy', $package))->assertRedirect(route('admin.packages.index'));
        $this->assertSoftDeleted($package);
        $this->get(route('admin.packages.index', ['status' => 'archived']))->assertSee('Paket Revisi');
    }

    public function test_guest_cannot_access_package_management(): void
    {
        $this->get(route('admin.packages.index'))->assertRedirect(route('login'));
        $this->post(route('admin.packages.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_admin_deactivates_package_without_changing_existing_access(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = User::factory()->create();
        $material = Material::factory()->premium()->video()->create();
        $package = Package::factory()->create();
        $package->materials()->attach($material);
        $grant = UserPackage::grant($participant, $package, $admin);

        $this->actingAs($admin)->from(route('admin.packages.index'))
            ->patch(route('admin.packages.deactivate', $package))
            ->assertRedirect(route('admin.packages.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'is_active' => false, 'updated_by' => $admin->id]);
        $this->assertNotSoftDeleted($package);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'revoked_at' => null]);
        $this->assertTrue($material->fresh()->isAccessibleBy($participant));
        $this->post(route('admin.packages.members.store', $package), ['email' => $participant->email, 'note' => 'Uji'])
            ->assertSessionHasErrors('email');
        $this->get(route('admin.packages.index', ['status' => 'inactive']))->assertSee($package->name);
        $this->actingAs($participant)->get(route('packages.index'))->assertViewHas('packages', fn ($packages): bool => $packages->isEmpty());
    }

    public function test_guest_cannot_deactivate_package(): void
    {
        $package = Package::factory()->create();

        $this->patch(route('admin.packages.deactivate', $package))->assertRedirect(route('login'));

        $this->assertTrue($package->fresh()->is_active);
    }

    public function test_archived_package_cannot_be_deactivated(): void
    {
        $package = Package::factory()->create();
        $package->delete();

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.packages.deactivate', $package))->assertNotFound();

        $this->assertSoftDeleted($package);
    }

    public function test_package_actions_are_available_in_admin_list_and_edit_pages(): void
    {
        $package = Package::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.packages.index'))->assertSee('Nonaktifkan')->assertSee('Hapus paket');
        $this->get(route('admin.packages.edit', $package))->assertSee('Nonaktifkan')->assertSee('Hapus paket');
    }

    public function test_content_picker_supplies_topics_and_marks_only_available_content_for_bulk_selection(): void
    {
        $ready = Material::factory()->premium()->video()->create();
        $draft = Material::factory()->draft()->video()->create();
        $exam = Exam::factory()->create();
        $draftExam = Exam::factory()->create(['status' => 'draft']);
        $package = Package::factory()->create();
        $package->materials()->attach($ready);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.packages.edit', $package))
            ->assertSee('data-package-picker', false)->assertSee('Semua materi')->assertSee('Semua premium')
            ->assertSee('Pilih hasil filter')->assertSee('Semua latihan')->assertSee('Semua tryout')
            ->assertSee($ready->topic->name)->assertSee('Berikan akses manual')
            ->assertViewHas('availableMaterialIds', fn (array $ids): bool => isset($ids[$ready->id]) && ! isset($ids[$draft->id]))
            ->assertViewHas('availableExamIds', fn (array $ids): bool => isset($ids[$exam->id]) && ! isset($ids[$draftExam->id]))
            ->assertViewHas('selectedMaterials', [$ready->id]);
    }

    public function test_admin_can_save_multiple_selected_materials_and_sessions_in_one_package(): void
    {
        $materials = Material::factory()->count(3)->premium()->video()->create();
        $exams = Exam::factory()->count(2)->create();
        $payload = $this->payload(['material_ids' => $materials->modelKeys(), 'exam_ids' => $exams->modelKeys()]);

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), $payload)
            ->assertSessionHasNoErrors();

        $package = Package::sole();
        $this->assertSame($materials->modelKeys(), $package->materials()->orderBy('materials.id')->pluck('materials.id')->all());
        $this->assertSame($exams->modelKeys(), $package->exams()->orderBy('exams.id')->pluck('exams.id')->all());
    }

    /** @return array<string, array{string, string}> */
    public static function restrictedActions(): array
    {
        return ['list' => ['get', 'admin.packages.index'], 'create form' => ['get', 'admin.packages.create'],
            'edit form' => ['get', 'admin.packages.edit'], 'store' => ['post', 'admin.packages.store'],
            'update' => ['put', 'admin.packages.update'], 'archive' => ['delete', 'admin.packages.destroy'],
            'deactivate' => ['patch', 'admin.packages.deactivate'],
            'grant' => ['post', 'admin.packages.members.store'], 'revoke' => ['delete', 'admin.packages.members.destroy']];
    }

    #[DataProvider('restrictedActions')]
    public function test_participants_cannot_manage_packages_or_assign_access(string $method, string $routeName): void
    {
        $package = Package::factory()->create();
        $grant = UserPackage::factory()->for($package)->create();

        $this->actingAs(User::factory()->create())->{$method}(route($routeName, ['package' => $package, 'userPackage' => $grant]), [])
            ->assertForbidden();

        $this->assertDatabaseCount('packages', 1);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'revoked_at' => null]);
        $this->assertNotSoftDeleted($package);
        $this->assertTrue($package->fresh()->is_active);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidPackages(): array
    {
        return ['negative price' => [['price' => -1], 'price'], 'fractional price' => [['price' => '12.5'], 'price'],
            'zero duration' => [['duration_days' => 0], 'duration_days'], 'negative quota' => [['tryout_quota' => -1], 'tryout_quota'],
            'invalid content' => [['material_ids' => [9999]], 'material_ids.0'],
            'empty active package' => [['material_ids' => [], 'exam_ids' => []], 'material_ids']];
    }

    #[DataProvider('invalidPackages')]
    public function test_invalid_package_is_not_saved(array $overrides, string $field): void
    {
        $payload = $this->payload($overrides);

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), $payload)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_active_package_rejects_hidden_material_and_draft_session(): void
    {
        $material = Material::factory()->draft()->create();
        $exam = Exam::factory()->create(['status' => 'draft']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.packages.store'), $this->payload(['material_ids' => [$material->id], 'exam_ids' => [$exam->id]]))
            ->assertSessionHasErrors(['material_ids', 'exam_ids']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_package_with_tryout_rejects_zero_quota(): void
    {
        $exam = Exam::factory()->tryout()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'),
            $this->payload(['exam_ids' => [$exam->id], 'tryout_quota' => 0]))->assertSessionHasErrors('tryout_quota');

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_admin_grants_snapshot_and_catalog_edits_do_not_change_existing_access(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 12:00:00'));
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $material = Material::factory()->premium()->video()->create();
        $exam = Exam::factory()->tryout()->create();
        $package = Package::factory()->create(['name' => 'Paket Awal']);
        $package->materials()->attach($material);
        $package->exams()->attach($exam);

        $this->actingAs($admin)->post(route('admin.packages.members.store', $package),
            ['email' => $user->email, 'note' => 'Akses kelas'])->assertSessionHasNoErrors();
        $grant = UserPackage::sole();
        $this->assertSame([$material->id], $grant->materials()->pluck('materials.id')->all());
        $this->assertSame([$exam->id], $grant->exams()->pluck('exams.id')->all());
        $this->assertSame('2026-11-12 12:00:00', $grant->expires_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'granted_by' => $admin->id, 'note' => 'Akses kelas']);

        $this->put(route('admin.packages.update', $package), $this->payload(['name' => 'Paket Baru', 'duration_days' => 1,
            'tryout_quota' => 1, 'exam_ids' => []]))->assertSessionHasNoErrors();
        $this->assertSame('Paket Awal', $grant->fresh()->name);
        $this->assertSame(2, $grant->fresh()->tryout_quota);
        $this->assertTrue($material->fresh()->isAccessibleBy($user));
        $this->assertSame([$exam->id], $grant->fresh()->exams()->pluck('exams.id')->all());

        $this->post(route('admin.packages.members.store', $package), ['email' => $user->email, 'note' => 'Duplikat'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('user_packages', 1);
    }

    public function test_inactive_package_and_inactive_participant_cannot_receive_access(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $package = Package::factory()->inactive()->create();

        $this->actingAs($admin)->post(route('admin.packages.members.store', $package),
            ['email' => $user->email, 'note' => 'Uji'])->assertSessionHasErrors('email');
        $package->update(['is_active' => true]);
        $user->update(['status' => 'inactive']);
        $this->post(route('admin.packages.members.store', $package), ['email' => $user->email, 'note' => 'Uji'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('user_packages', 0);
    }

    public function test_revoke_checks_parent_package_and_works_after_archiving(): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $material = Material::factory()->premium()->video()->create();
        $package = Package::factory()->create();
        $package->materials()->attach($material);
        $grant = UserPackage::grant($user, $package, $admin);
        $other = Package::factory()->create();

        $this->actingAs($admin)->delete(route('admin.packages.members.destroy', [$other, $grant]))->assertNotFound();
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'revoked_at' => null]);
        $this->delete(route('admin.packages.destroy', $package))->assertRedirect();
        $this->assertTrue($material->fresh()->isAccessibleBy($user));
        $this->get(route('admin.packages.edit', $package))->assertOk()->assertSee($user->email);
        $this->delete(route('admin.packages.members.destroy', [$package, $grant]))->assertRedirect();
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'revoked_by' => $admin->id]);
        $this->assertFalse($material->fresh()->isAccessibleBy($user));
    }

    public function test_catalog_contains_only_active_packages_and_own_entitlements(): void
    {
        $user = User::factory()->create();
        $active = Package::factory()->create(['name' => 'Paket Tersedia']);
        $inactive = Package::factory()->inactive()->create(['name' => 'Paket Rahasia']);
        $archived = Package::factory()->create(['name' => 'Paket Arsip']);
        $archived->delete();
        UserPackage::factory()->for($user)->for($active)->create(['name' => 'Hak Milik Saya']);
        UserPackage::factory()->create(['name' => 'Hak Peserta Lain']);

        $this->actingAs($user)->get(route('packages.index'))->assertSee('Paket Tersedia')->assertSee('Hak Milik Saya')
            ->assertDontSee('Paket Rahasia')->assertDontSee('Paket Arsip')->assertDontSee('Hak Peserta Lain');
        $this->get(route('dashboard'))->assertSee('Hak Milik Saya');
    }

    public function test_empty_catalog_and_escaped_package_text_render_safely(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('packages.index'))->assertSee('Paket belum tersedia');
        Package::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->get(route('packages.index'))->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_grant_rejects_malformed_email_and_content_removed_after_publication(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $material = Material::factory()->premium()->video()->create();
        $package->materials()->attach($material);
        $material->delete();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.members.store', $package),
            ['email' => [$user->email], 'note' => 'Uji'])->assertSessionHasErrors('email');
        $this->post(route('admin.packages.members.store', $package), ['email' => $user->email, 'note' => 'Uji'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('user_packages', 0);
    }

    public function test_admin_can_revoke_package_of_soft_deleted_participant(): void
    {
        $admin = User::factory()->admin()->create();
        $grant = UserPackage::factory()->create();
        $grant->user->delete();

        $this->actingAs($admin)->delete(route('admin.packages.members.destroy', [$grant->package, $grant]))
            ->assertRedirect();

        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'revoked_by' => $admin->id]);
    }
}
