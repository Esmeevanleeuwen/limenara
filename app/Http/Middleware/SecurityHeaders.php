<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Prevent plaintext page props from being stored in Inertia's browser history.
        Inertia::encryptHistory();
        // Logout/session expiry rotates the history key on the next anonymous page.
        if (! $request->user() && $request->isMethod('GET')) Inertia::clearHistory();
        $response = $next($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
