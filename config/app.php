<?php

return [
    'name' => env('APP_NAME', 'Limenora'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8080'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'nl'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => 'nl_NL',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [...array_filter(explode(',', env('APP_PREVIOUS_KEYS', '')))],
    'maintenance' => ['driver' => 'file', 'store' => 'database'],
];
