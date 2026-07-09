FROM node:20-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
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
COPY --from=node-builder /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

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
