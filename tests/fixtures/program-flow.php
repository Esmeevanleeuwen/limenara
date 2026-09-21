<?php

// CLI-only, isolated test-database fixtures. Never expose this file as a web route.
if (PHP_SAPI !== 'cli' || (getenv('CI') !== 'true' && getenv('ALLOW_BROWSER_FIXTURES') !== '1')) { exit(1); }
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config('database.default');
$database = config('database.connections.'.$connection.'.database');
if (! $app->environment(['local', 'testing']) || ! is_string($database) || ! str_ends_with($database, '_test')) {
    fwrite(STDERR, 'Browser fixtures require a dedicated database ending in _test.'); exit(1);
}
$password = bin2hex(random_bytes(24));
$provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
$users = []; $secrets = [];
foreach (['staff', 'admin', 'member'] as $role) {
    $secret = $role === 'member' ? null : $provider->generateSecretKey();
    $u = \App\Models\User::factory()->create([
        'email' => $role.'-'.bin2hex(random_bytes(6)).'@example.test',
        'password' => $password,
        'two_factor_secret' => $secret ? encrypt($secret) : null,
        'two_factor_confirmed_at' => $secret ? now() : null,
        'two_factor_recovery_codes' => $secret ? encrypt(json_encode([bin2hex(random_bytes(16))])) : null,
    ]);
    $u->assignRole($role);
    $users[$role] = $u->email; $secrets[$role] = $secret;
}
// Consumed in-memory by the browser-test subprocess; never print this in CI logs.
echo json_encode(['users' => $users, 'password' => $password, 'secrets' => $secrets]);
