<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';
    public function share(Request $request): array
    {
        return [...parent::share($request),
            'auth' => fn () => ['user' => $request->user() ? [
                'id' => $request->user()->id, 'name' => $request->user()->name,
                'email' => $request->user()->email, 'verified' => $request->user()->hasVerifiedEmail(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->all(),
            ] : null],
            'flash' => fn () => ['success' => $request->session()->get('success'), 'error' => $request->session()->get('error')],
        ];
    }
}
