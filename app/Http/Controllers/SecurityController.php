<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class SecurityController
{
    public function show(Request $request)
    {
        $user = $request->user();
        $recentPassword = (int) $request->session()->get('auth.password_confirmed_at', 0) > time() - config('auth.password_timeout');
        return Inertia::render('security', [
            'enabled' => (bool) $user->two_factor_secret, 'confirmed' => (bool) $user->two_factor_confirmed_at,
            'recentPassword' => $recentPassword,
            'qr' => $recentPassword && $user->two_factor_secret && ! $user->two_factor_confirmed_at ? $user->twoFactorQrCodeSvg() : null,
            'recoveryCodes' => $recentPassword && $user->two_factor_secret ? $user->recoveryCodes() : [],
            'unlocked' => $request->session()->get('staff_mfa_user') === $user->id && (int) $request->session()->get('staff_mfa_at', 0) > now()->subHours(4)->timestamp,
        ]);
    }
    public function unlock(Request $request, TwoFactorAuthenticationProvider $provider)
    {
        $data = $request->validate(['password' => ['required', 'current_password'], 'code' => ['required', 'digits:6']]);
        $user = $request->user();
        abort_unless($user->two_factor_confirmed_at && $user->two_factor_secret, 403);
        if (! $provider->verify(decrypt($user->two_factor_secret), $data['code'])) {
            return back()->withErrors(['code' => 'De verificatiecode klopt niet of is verlopen.']);
        }
        $request->session()->regenerate();
        $request->session()->put(['staff_mfa_user' => $user->id, 'staff_mfa_at' => now()->timestamp]);
        return redirect('/dashboard')->with('success', 'Werkomgeving ontgrendeld voor deze sessie.');
    }
}
