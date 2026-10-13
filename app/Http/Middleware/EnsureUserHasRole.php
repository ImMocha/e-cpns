<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi akses ke route Admin Panel.
 *
 * Dipakai sebagai `role:admin`. Alias didaftarkan di bootstrap/app.php.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(
            fn (string $role) => UserRole::tryFrom($role),
            $roles,
        );

        if ($user === null || ! in_array($user->role, array_filter($allowed), true)) {
            abort(403, 'Kamu tidak punya akses ke halaman Admin Panel.');
        }

        return $next($request);
    }
}
