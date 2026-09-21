<?php

namespace App\Providers;

use App\Models\Program;
use App\Policies\ProgramPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12));
        Gate::policy(Program::class, ProgramPolicy::class);
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https');
        RateLimiter::for('platform-write', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('invitations', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('mfa-unlock', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?? $r->ip()));
    }
}
