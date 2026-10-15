<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:160'],
        ]);
        $credentials = [...$validated, 'status' => UserStatus::Active->value];

        try {
            Password::sendResetLink($credentials);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            if ($user = Password::getUser($credentials)) {
                Password::deleteToken($user);
            }

            return back()->withInput($validated)->withErrors([
                'email' => 'Email belum dapat dikirim. Silakan coba lagi beberapa saat atau hubungi admin.',
            ]);
        }

        return back()->with('status', 'Jika email terdaftar pada akun aktif, tautan reset password akan dikirim. Periksa juga folder spam.');
    }
}
