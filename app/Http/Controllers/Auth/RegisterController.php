<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Province;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email:filter', 'max:160'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
            'province' => ['required', Rule::enum(Province::class)],
        ], ['province.required' => 'Pilih provinsi tempat tinggal untuk leaderboard lokal.',
            'province.enum' => 'Pilih provinsi yang tersedia dalam daftar.']);

        $user = User::withTrashed()->firstOrCreate(['email' => $validated['email']], [
            'name' => $validated['name'],
            'password' => $validated['password'],
            'province' => $validated['province'],
        ]);

        if (! $user->wasRecentlyCreated && ! $this->canResumeVerification($user, $validated['password'], $request->ip())) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar. Coba masuk dengan password akunmu atau gunakan fitur lupa password.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! $user->wasRecentlyCreated) {
            return redirect()->route('verification.notice');
        }

        try {
            event(new Registered($user));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('verification.notice')->withErrors([
                'email' => 'Akun berhasil dibuat, tetapi email verifikasi belum dapat dikirim. Silakan kirim ulang beberapa saat lagi.',
            ]);
        }

        return redirect()->route('verification.notice');
    }

    private function canResumeVerification(User $user, string $password, ?string $ip): bool
    {
        return ! $user->trashed()
            && $user->isActive()
            && ! $user->isAdmin()
            && ! $user->hasVerifiedEmail()
            && RateLimiter::attempt('registration-retry:'.$user->getKey().'|'.$ip, 5,
                fn (): bool => Hash::check($password, $user->getAuthPassword()));
    }
}
