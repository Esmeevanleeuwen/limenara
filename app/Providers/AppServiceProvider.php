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
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(fn (object $user, string $url) =>
            (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Bevestig je e-mailadres — Limenora')
                ->greeting('Welkom bij Limenora,')
                ->line('Bevestig jouw e-mailadres om je account te gebruiken. Een gekozen programma blijft voor je klaarstaan; je beslist daarna zelf of je start.')
                ->action('E-mailadres bevestigen', $url)
                ->line('Deze link is tijdelijk geldig. Je kunt op de website een nieuwe link aanvragen. Heb je geen account gemaakt? Dan hoef je niets te doen.')
                ->salutation('Limenora · ruimte om te begrijpen')
        );
        Password::defaults(fn () => Password::min(12));
        Gate::policy(Program::class, ProgramPolicy::class);
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https');
        RateLimiter::for('platform-write', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('invitations', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('mfa-unlock', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?? $r->ip()));
    }
}
