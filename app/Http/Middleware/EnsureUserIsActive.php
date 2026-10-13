<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD Aturan 12: admin yang menonaktifkan akun membatalkan sesi aktif peserta.
 * Akun nonaktif tidak boleh melanjutkan, tapi juga tidak boleh logout diam-diam —
 *Peserta diberi tahu supaya tahu kenapa suddenly tertendang.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Akun kamu sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.',
                ]);
        }

        return $next($request);
    }
}
