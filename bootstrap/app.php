<?php

use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class, SecurityHeaders::class]);
        $middleware->alias(['mfa' => EnsureTwoFactorEnabled::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'code', 'recovery_code', 'token', 'answer', 'feedback']);
        // With no-referrer, a form must not rely on a different tab's previous URL.
        // Keep the normal Laravel error bag/response; only set its same-site return path.
        $exceptions->render(function (ValidationException $exception, Request $request) {
            $path = $request->path();
            $target = null;
            if (preg_match('#^mijn-programmas/(\d+)/(?:antwoorden/\d+|pauze|voortgang)$#', $path, $m)) $target = '/mijn-programmas/'.$m[1];
            elseif (preg_match('#^werk/inzendingen/(\d+)$#', $path, $m)) $target = '/werk/inzendingen/'.$m[1];
            elseif ($path === 'werk/programmas') $target = '/werk/programmas/nieuw';
            elseif (preg_match('#^werk/programmas/(\d+)(?:/indienen)?$#', $path, $m)) $target = '/werk/programmas/'.$m[1];
            elseif (preg_match('#^beheer/programmas/\d+/(?:publiceren|terugzetten)$#', $path)) $target = '/beheer/beoordelingen';
            if ($target) $exception->redirectTo($target);
            return null;
        });
    })->create();
