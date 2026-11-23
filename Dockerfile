# ==================================================================
# Moodle Deadline Companion — image produksi/demo (Laravel 13, PHP 8.3)
# Multi-stage: composer install lalu runtime PHP ringan (Alpine).
# ==================================================================

# ---------- Stage 1: dependensi composer ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --no-progress

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader

# ---------- Stage 2: runtime ----------
FROM php:8.3-cli-alpine

# Ekstensi yang dibutuhkan Laravel + SQLite
RUN docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html

COPY --from=vendor /app .
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p database storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache \
    && chown -R www-data:www-data database storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=8080

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
