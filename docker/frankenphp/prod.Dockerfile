# syntax=docker/dockerfile:1

# VoltProcure: Laravel Octane on FrankenPHP (worker mode).
# The app boots once per worker and stays in memory, so each request skips
# the framework bootstrap entirely.

ARG PHP_IMAGE=dunglas/frankenphp:1.13.1-php8.5-trixie

# --- PHP dependencies -------------------------------------------------------
FROM ${PHP_IMAGE} AS vendor
COPY --from=composer/composer:2.10.3-bin /composer /usr/bin/composer
RUN install-php-extensions bcmath exif gd intl pcntl zip
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev

# --- Frontend build ---------------------------------------------------------
FROM oven/bun:1.3.14 AS assets
WORKDIR /app
COPY package.json bun.lock ./
RUN bun install --frozen-lockfile
COPY resources ./resources
COPY vite.config.js ./
# Tailwind scans app/ for class names used in PHP (status tones).
COPY app ./app
RUN bun run build

# --- Runtime ----------------------------------------------------------------
FROM ${PHP_IMAGE} AS app

RUN install-php-extensions bcmath exif gd intl opcache pcntl zip \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/frankenphp/php.ini "$PHP_INI_DIR/conf.d/zz-voltprocure.ini"

WORKDIR /app
COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --chmod=755 docker/frankenphp/entrypoint.sh /usr/local/bin/voltprocure-entrypoint

# Laravolt ships its UI assets as a zip inside the package; unpack and link
# them into public/ at build time so no request ever touches the archive.
# Octane's worker entry point is generated, not committed (see .gitignore).
# Caddy keeps its state under /config and /data, which must be writable once
# the container drops to www-data.
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN php artisan package:discover --ansi \
 && php artisan laravolt:extract-assets --no-interaction \
 && php artisan laravolt:link --no-interaction \
 && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs database/data /config/caddy /data/caddy \
 && chown -R www-data:www-data storage bootstrap/cache database/data /config/caddy /data/caddy

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/data/voltprocure.sqlite \
    OCTANE_SERVER=frankenphp

USER www-data
EXPOSE 8080
VOLUME ["/app/database/data", "/app/storage"]

HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/up") === false ? 1 : 0);'

ENTRYPOINT ["voltprocure-entrypoint"]
CMD ["octane"]
