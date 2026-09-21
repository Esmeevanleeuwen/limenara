#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v docker >/dev/null || { echo 'Docker ontbreekt. Open Docker Desktop en gebruik Ubuntu/WSL2.'; exit 1; }
docker info >/dev/null 2>&1 || { echo 'Docker is niet bereikbaar. Start Docker Desktop en zet WSL-integratie voor Ubuntu aan.'; exit 1; }
docker compose version >/dev/null
umask 077
if [[ ! -f .env ]]; then cp .env.example .env; fi
chmod 600 .env
random_hex() { od -An -N24 -tx1 /dev/urandom | tr -d ' \n'; }
if grep -q '^DB_PASSWORD=GENERATED_BY_SETUP$' .env; then sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$(random_hex)/" .env; fi
if grep -q '^DB_ROOT_PASSWORD=GENERATED_BY_SETUP$' .env; then sed -i "s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$(random_hex)/" .env; fi
sed -i "s/^WWWUSER=.*/WWWUSER=$(id -u)/;s/^WWWGROUP=.*/WWWGROUP=$(id -g)/" .env
echo '1/5 PHP-afhankelijkheden installeren…'
docker compose run --rm --build --no-deps bootstrap install --no-interaction --prefer-dist
echo '2/5 Lokale containers starten…'
docker compose up -d --build laravel.test mysql phpmyadmin mailpit
echo '3/5 Applicatiesleutel en databasetabellen…'
if grep -q '^APP_KEY=$' .env; then ./dev artisan key:generate --ansi; fi
./dev artisan migrate --seed
./dev composer check-platform-reqs
echo '4/5 Frontend installeren en bouwen…'
if [[ -f package-lock.json ]]; then ./dev npm ci; else ./dev npm install; fi
./dev npm run build
echo '5/5 Klaar. Standaardadressen (bij gewijzigde poorten: zie .env):'
echo 'Limenora:    http://localhost:8080'
echo 'phpMyAdmin:  http://localhost:8081'
echo 'Test-e-mail: http://localhost:8025'
echo 'Registreer eerst je account en bevestig de e-mail via Mailpit.'
echo 'Maak jezelf daarna beheerder: ./dev artisan limenora:admin jouw@email.nl'
echo 'Geen standaardaccounts of wachtwoorden aangemaakt. Gebruik uitsluitend testgegevens.'
