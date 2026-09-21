# Bootstraps vendor/ without requiring PHP or Composer on Windows.
FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev libsqlite3-dev libxml2-dev \
    && docker-php-ext-install -j$(nproc) bcmath intl mbstring pdo_mysql pdo_sqlite zip dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
