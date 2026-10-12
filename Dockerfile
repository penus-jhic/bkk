# syntax=docker/dockerfile:1

# --- 1. Build aset frontend (Vite) ---------------------------------------------
# Base image dipin per digest supaya build reproducible; perbarui digest saat upgrade versi
FROM node:22-alpine@sha256:0a7108bf6c7bf5de370ffb1a3ed6be93d405b43ff159f681a8d18c0e2bc2e402 AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- 2. Runtime PHP-FPM ----------------------------------------------------------
FROM php:8.4-fpm-alpine@sha256:78cd8de9970a9cd6ff4d98860a94eb5bf37dd2d5776b4785562dbfad01c29d5a AS app
WORKDIR /var/www/html

RUN apk add --no-cache libpq \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install pdo_pgsql pgsql opcache \
    && apk del .build-deps \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Dependensi PHP tanpa paket dev; composer hanya dipakai saat build
COPY --from=composer:2.10@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && rm /usr/bin/composer \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs public/uploads/mitra_logos \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads

# Jalan sebagai user biasa, bukan root
USER www-data

EXPOSE 9000

# Config/route/view di-cache saat start karena env baru tersedia saat runtime
CMD ["sh", "-c", "php artisan optimize && exec php-fpm"]
