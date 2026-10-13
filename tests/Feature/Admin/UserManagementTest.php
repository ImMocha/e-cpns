<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ---- Index ---------------------------------------------------------------

    public function test_admin_can_list_users(): void
    {
        $users = User::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($users->pluck('name')->all());
    }

    public function test_index_search_filters_by_name_email_and_phone(): void
    {
        User::factory()->create([
            'name' => 'Rani Kusuma',
            'email' => 'rani@mail.com',
            'phone' => '081234567890',
        ]);
        User::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['q' => 'Rani']))
            ->assertOk()
            ->assertSee('Rani Kusuma');

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['q' => 'rani@mail.com']))
            ->assertOk()
            ->assertSee('rani@mail.com');

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['q' => '081234567890']))
            ->assertOk()
            ->assertSee('Rani Kusuma');
    }

    public function test_index_filters_by_role_and_status(): void
    {
        // Nama sengaja dibuat unik agar bisa dipastikan lewat isi tabel,
        // bukan lewat app shell (yang masih memakai config viewer).
        $admin = $this->admin();
        $admin->update(['name' => 'Admin Satu']);

        $peserta = User::factory()->create(['name' => 'Peserta Dua']);
        $nonaktif = User::factory()->inactive()->create(['name' => 'Peserta Tiga']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'admin']))
            ->assertOk()
            ->assertSee('Admin Satu')
            ->assertDontSee('Peserta Dua');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Peserta Tiga')
            ->assertDontSee('Peserta Dua');
    }

    public function test_index_shows_empty_state_when_nothing_matches(): void
    {
        User::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk()
            ->assertSee('Peserta tidak ditemukan');
    }

    public function test_index_paginates(): void
    {
        // 20 peserta + 1 admin yang sedang login = 21, jadi muncul 2 halaman.
        User::factory()->count(20)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            // false = jangan escape, karena "&ndash;" memang entity di markup.
            ->assertSee('1&ndash;12 dari 21 pengguna', false)
            ->assertSee('aria-label="Navigasi halaman"', false)
            ->assertSee(route('admin.users.index', ['page' => 2]), false);
    }

    // ---- Create --------------------------------------------------------------

    public function test_admin_sees_create_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Buat Akun Baru');
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Rani Kusuma',
                'email' => 'rani@mail.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => UserRole::User->value,
                'status' => UserStatus::Active->value,
                'phone' => '0812 3456 7890',
                'institution' => 'Universitas Indonesia',
                'address' => 'Bandung, Jawa Barat',
                'email_verified' => '1',
            ])
            ->assertRedirect();

        $user = User::where('email', 'rani@mail.com')->sole();

        $this->assertSame('Rani Kusuma', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertSame('0812 3456 7890', $user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(password_verify('rahasia123', $user->password));
    }

    public function test_create_hashes_password_and_never_stores_plain_text(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Hash Check',
                'email' => 'hash@mail.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'user',
                'status' => 'active',
            ]);

        $user = User::where('email', 'hash@mail.com')->sole();

        $this->assertNotSame('rahasia123', $user->password);
    }

    // ---- Validation ----------------------------------------------------------

    public static function invalidPayloads(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name'],
            'nama terlalu pendek' => [['name' => 'ab'], 'name'],
            'email tidak valid' => [['email' => 'bukan-email'], 'email'],
            'password terlalu pendek' => [['password' => 'pendek', 'password_confirmation' => 'pendek'], 'password'],
            'konfirmasi tidak sama' => [['password' => 'rahasia123', 'password_confirmation' => 'beda12345'], 'password'],
            'role tidak dikenal' => [['role' => 'superadmin'], 'role'],
            'status tidak dikenal' => [['status' => 'ditangguhkan'], 'status'],
            'telepon tidak valid' => [['phone' => 'bukan nomor!!'], 'phone'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidPayloads')]
    public function test_create_rejects_invalid_input(array $override, string $errorKey): void
    {
        $admin = $this->admin();
        $countBefore = User::count();

        $payload = array_merge([
            'name' => 'Rani Kusuma',
            'email' => 'rani@mail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'user',
            'status' => 'active',
        ], $override);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $payload)
            ->assertSessionHasErrors($errorKey)
            ->assertRedirect();

        // Tidak ada akun baru yang tersimpan.
        $this->assertSame($countBefore, User::count());
    }

    public function test_create_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@mail.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Duplikat',
                'email' => 'ada@mail.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('email');
    }

    // ---- Edit / Update -------------------------------------------------------

    public function test_admin_can_open_edit_form(): void
    {
        $user = User::factory()->create(['name' => 'Rani Kusuma']);

        $this->actingAs($this->admin())
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Rani Kusuma')
            ->assertSee('Ubah Akun');
    }

    public function test_admin_can_update_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Baru',
                'email' => 'baru@mail.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => UserRole::Admin->value,
                'status' => UserStatus::Inactive->value,
                'institution' => 'Kementerian Dalam Negeri',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@mail.com', $user->email);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame(UserStatus::Inactive, $user->status);
        $this->assertNull($user->email_verified_at);
    }

    public function test_update_keeps_old_password_when_field_left_blank(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'user',
                'status' => 'active',
            ]);

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_update_changes_password_when_provided(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
                'role' => 'user',
                'status' => 'active',
            ]);

        $this->assertTrue(password_verify('passwordbaru', $user->fresh()->password));
    }

    public function test_update_allows_keeping_own_email_but_rejects_another_users_email(): void
    {
        $actor = $this->admin();
        $other = User::factory()->create(['email' => 'other@mail.com']);

        $this->actingAs($actor)
            ->put(route('admin.users.update', $actor), [
                'name' => $actor->name,
                'email' => $actor->email,
                'role' => 'admin',
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($actor)
            ->put(route('admin.users.update', $other), [
                'name' => $other->name,
                'email' => $actor->email,
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('email');
    }

    // ---- Status toggle -------------------------------------------------------

    public function test_admin_can_toggle_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.users.status', $user));
        $this->assertSame(UserStatus::Inactive, $user->fresh()->status);

        $this->actingAs($this->admin())->patch(route('admin.users.status', $user));
        $this->assertSame(UserStatus::Active, $user->fresh()->status);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $actor = $this->admin();

        $this->actingAs($actor)->patch(route('admin.users.status', $actor));

        $this->assertSame(UserStatus::Active, $actor->fresh()->status);
    }

    // ---- Delete --------------------------------------------------------------

    public function test_admin_can_delete_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted($user);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $actor = $this->admin();

        $this->actingAs($actor)->delete(route('admin.users.destroy', $actor));

        $this->assertNotSoftDeleted($actor);
    }

    public function test_deleted_user_disappears_from_index(): void
    {
        $user = User::factory()->create(['name' => 'Dihapus']);
        $user->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('Dihapus');
    }

    // ---- Navigation ----------------------------------------------------------

    public function test_admin_sidebar_links_to_user_management(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.index'));
    }

    // ---- Flash message -------------------------------------------------------

    public function test_success_flash_is_rendered_as_a_toast(): void
    {
        $admin = $this->admin();
        $target = User::factory()->inactive()->create();

        $this->actingAs($admin)->patch(route('admin.users.status', $target));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('window.toast(', false)
            ->assertSee($target->name, false);
    }

    public function test_error_flash_is_rendered_when_action_is_blocked(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('"error"', false);
    }
}
