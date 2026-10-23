<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(Request $request): View|RedirectResponse
    {
        // Sudah login? Jangan tampilkan form lagi.
        if ($request->user() !== null) {
            return redirect()->to($this->home($request));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:160'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        // Pesan yang sama untuk "email tidak terdaftar" maupun "password salah",
        // supaya halaman ini tidak bisa dipakai menebak email yang terdaftar.
        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak cocok dengan akun kami.',
            ]);
        }

        $request->session()->regenerate();

        // PRD Aturan 12: akun yang dinonaktifkan tidak boleh punya sesi aktif.
        if (! $request->user()->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

        // Admin selalu mendarat di Admin Panel, apa pun halaman yang tadi
        // dibuka. Peserta ikut ke "intended URL" supaya deep link tetap jalan.
        return $request->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Kamu sudah keluar dari akun.');
    }

    /** Halaman tujuan setelah login, sesuai role. */
    private function home(Request $request): string
    {
        return $request->user()?->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /** Kunci throttle per email + IP, supaya satu IP tidak mengunci akun orang lain. */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }
}
