<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn () => Inertia::render('auth/login'));
        Fortify::registerView(fn () => Inertia::render('auth/register'));
        Fortify::requestPasswordResetLinkView(fn (Request $r) => Inertia::render('auth/forgot-password', ['status' => $r->session()->get('status')]));
        Fortify::resetPasswordView(fn (Request $r) => Inertia::render('auth/reset-password', ['email' => $r->email, 'token' => $r->route('token')]));
        Fortify::verifyEmailView(fn (Request $r) => Inertia::render('auth/verify-email', ['status' => $r->session()->get('status'), 'email' => $r->user()?->email, 'localMailbox' => app()->isLocal() && config('mail.mailers.smtp.host') === 'mailpit' ? 'http://localhost:'.env('MAILPIT_PORT', '8025') : null]));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));
        RateLimiter::for('auth-forms', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(Str::lower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('two-factor', fn (Request $r) => Limit::perMinute(5)->by($r->session()->get('login.id').'|'.$r->ip()));
    }
}
