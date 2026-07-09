# resources/js/app.js mengimpor ZiggyVue dari vendor/tightenco/ziggy, jadi
# vendor harus sudah ada SEBELUM `npm run build` — tahap ini menyediakannya
# tanpa menjalankan script artisan (belum ada source app lengkap di sini).
FROM composer:2 AS composer-deps

WORKDIR /app
COPY composer.json composer.lock ./
# Retry sekali: build server Render berbagi IP dan sering kena HTTP 429 dari
# codeload.github.com — jeda 30 detik biasanya cukup untuk lolos rate limit.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs \
    || (sleep 30 && composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs)


FROM node:20-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
COPY --from=composer-deps /app/vendor ./vendor
RUN npm run build


FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        xml \
        ctype \
        fileinfo \
        bcmath \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY --from=composer-deps /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build

# vendor/ dipakai ulang dari stage composer-deps — TIDAK ada unduhan GitHub di
# sini. Sebelumnya `composer install` penuh dijalankan lagi dan mati kena
# HTTP 429 (rate limit codeload.github.com di IP bersama Render).
# dump-autoload tetap memicu post-autoload-dump (package:discover) via
# script di composer.json.
RUN composer dump-autoload --optimize --no-dev

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# `php artisan serve` wraps PHP's built-in server, which is single-process
# unless this is set — without it one slow request blocks every other request.
ENV PHP_CLI_SERVER_WORKERS=4

# schedule:work runs Laravel's scheduler in a loop (no system cron available
# on this platform) — backgrounded so it runs alongside the web server;
# needed for the daily recurring-transactions job in routes/console.php.
CMD php artisan migrate --force && php artisan optimize && (php artisan schedule:work &) && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
