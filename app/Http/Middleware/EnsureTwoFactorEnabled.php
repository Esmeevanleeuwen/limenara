<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $recent = (int) $request->session()->get('staff_mfa_at', 0) > now()->subHours(4)->timestamp;
        if (! $user?->two_factor_confirmed_at || $request->session()->get('staff_mfa_user') !== $user->id || ! $recent) {
            return redirect('/settings/security')->with('error', 'Activeer tweestapsverificatie en ontgrendel je werkomgeving.');
        }
        return $next($request);
    }
}
