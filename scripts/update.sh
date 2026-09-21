#!/usr/bin/env bash
# Run after git pull --ff-only. Does NOT delete data, generate a key or reset user role assignments.
set -euo pipefail
cd "$(dirname "$0")/.."
docker info >/dev/null
docker compose stop queue
./dev up -d laravel.test mysql phpmyadmin mailpit
./dev composer install --no-interaction --prefer-dist
./dev artisan migrate --force
./dev artisan db:seed --force
./dev npm ci
./dev npm run build
./dev up -d queue
./dev artisan queue:restart
printf '\nUpdate klaar. Website: http://localhost:8080 · testmail: http://localhost:8025\n'
