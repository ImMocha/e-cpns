<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function peserta(): User
    {
        return User::factory()->create(['password' => Hash::make('rahasia123')]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['password' => Hash::make('rahasia123')]);
    }

    // ---- Guest guard ---------------------------------------------------------

    public function test_guest_sees_login_page(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke Akunmu');
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $user = $this->peserta();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_is_redirected_to_admin_panel_from_login_page(): void
    {
        $this->actingAs($this->admin())->get(route('login'))->assertRedirect(route('admin.dashboard'));
    }

    // ---- Login ---------------------------------------------------------------

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = $this->peserta();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_records_last_login_at(): void
    {
        $user = User::factory()->neverLoggedIn()->create(['password' => Hash::make('rahasia123')]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $user = $this->peserta();

        $this->get(route('login'));

        $idBefore = session()->getId();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123']);

        // session fixation: id sesi harus berubah setelah login berhasil.
        $this->assertNotSame($idBefore, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_the_session_id(): void
    {
        $this->actingAs($this->peserta());

        $idBefore = session()->getId();

        $this->post(route('logout'));

        $this->assertNotSame($idBefore, session()->getId());
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->peserta();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email_using_the_same_message(): void
    {
        $this->peserta();

        $this->post(route('login.store'), [
            'email' => 'tidak-ada@mail.com',
            'password' => 'apa-saja',
        ])->assertSessionHasErrors([
            'email' => 'Email atau password tidak cocok dengan akun kami.',
        ]);

        $this->assertGuest();
    }

    public function test_login_rejects_inactive_account(): void
    {
        $user = User::factory()->inactive()->create(['password' => Hash::make('rahasia123')]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_validates_input(): void
    {
        $this->post(route('login.store'), ['email' => 'bukan-email', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = $this->peserta();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
    }

    // ---- Redirect by role ----------------------------------------------------

    public function test_admin_is_redirected_to_admin_panel(): void
    {
        $admin = $this->admin();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_participant_is_redirected_to_own_dashboard(): void
    {
        $user = $this->peserta();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_honours_the_intended_url(): void
    {
        $user = $this->peserta();

        $this->get(route('materials.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('materials.index'));
    }

    public function test_admin_always_lands_on_admin_panel_even_with_an_intended_url(): void
    {
        $admin = $this->admin();

        // Admin membuka halaman peserta dulu, jadi ada "intended URL" yang menyesatkan.
        $this->get(route('materials.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_redirect_uses_a_full_url_on_the_request_host(): void
    {
        $admin = $this->admin();

        $response = $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'rahasia123']);

        $this->assertSame(
            url('/admin'),
            $response->headers->get('Location'),
        );
    }

    // ---- Logout --------------------------------------------------------------

    public function test_user_can_log_out(): void
    {
        $this->actingAs($this->peserta())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logout_clears_the_session(): void
    {
        $this->actingAs($this->peserta());
        Session::put('rahasia', 'ada');

        $this->post(route('logout'));

        $this->assertNull(session('rahasia'));
    }

    // ---- Access control ------------------------------------------------------

    public function test_guest_cannot_reach_member_pages(): void
    {
        foreach ([
            'dashboard', 'materials.index', 'quizzes.index',
            'tryouts.index', 'history.index', 'statistics.index', 'leaderboard.index',
            'packages.index', 'checkout', 'certificates.index', 'profile.edit',
        ] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_guest_cannot_reach_admin_pages(): void
    {
        foreach ([
            'admin.dashboard', 'admin.users.index', 'admin.users.create',
            'admin.materials.index', 'admin.questions.index', 'admin.transactions.index',
        ] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_participant_cannot_reach_admin_pages(): void
    {
        $user = $this->peserta();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_reach_admin_pages(): void
    {
        $this->actingAs($this->admin())->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_can_also_reach_member_pages(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_user_session_is_terminated_on_next_request(): void
    {
        $user = $this->peserta();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['status' => UserStatus::Inactive]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // ---- App shell reflects the logged-in user ------------------------------

    public function test_app_shell_shows_the_logged_in_user_not_a_hardcoded_demo(): void
    {
        $user = User::factory()->create([
            'name' => 'Zulfikar Akbar',
            'email' => 'zulfikar@mail.com',
        ]);

        // Hanya string miliknya app shell (dropdown akun + avatar) yang dicek.
        // Isi halaman dashboard masih data demo prototype, jadi nama "Rani Kusuma"
        // masih bisa muncul di leaderboard mini sampai halaman itu disambung ke data nyata.
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Zulfikar Akbar')
            ->assertSee('zulfikar@mail.com')
            ->assertSee($user->initials(), false)
            ->assertDontSee('rani.kusuma@mail.com');
    }

    public function test_admin_shell_shows_the_logged_in_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Satria',
            'email' => 'boss@mail.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Admin Satria')
            ->assertSee('boss@mail.com')
            ->assertDontSee('Bagas Pratama')
            ->assertDontSee('admin@lontar.asia');
    }

    public function test_member_sidebar_shows_admin_panel_link_only_for_admins(): void
    {
        $this->actingAs($this->peserta())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Admin Panel');

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin Panel');
    }

    public function test_profile_page_shows_the_logged_in_user(): void
    {
        $user = $this->peserta();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    // ---- Session persistence -------------------------------------------------

    public function test_remember_me_issues_a_remember_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('rahasia123'),
            'remember_token' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia123',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors()
            ->assertCookie(Auth::guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_with_remember_disabled_does_not_issue_a_remember_cookie(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('rahasia123'),
            'remember_token' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia123',
            'remember' => '0',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors()
            ->assertCookieMissing(Auth::guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_remember_me_does_not_authenticate_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('rahasia123'),
            'remember_token' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-salah',
            'remember' => '1',
        ])->assertInvalid(['email' => 'Email atau password tidak cocok dengan akun kami.'])
            ->assertCookieMissing(Auth::guard('web')->getRecallerName());

        $this->assertGuest();
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_auth_guard_is_configured_for_the_user_model(): void
    {
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertTrue(Auth::guard('web')->user() === null);
    }
}
