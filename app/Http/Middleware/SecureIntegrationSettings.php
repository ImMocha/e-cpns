<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureIntegrationSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(app()->isProduction() && ! $request->isSecure(), 403, 'Pengaturan integrasi harus diakses melalui HTTPS.');
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

        return $response;
    }
}
