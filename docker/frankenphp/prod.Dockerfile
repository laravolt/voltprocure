# syntax=docker/dockerfile:1

# VoltProcure: Laravel Octane on FrankenPHP (worker mode).
# The app boots once per worker and stays in memory, so each request skips
# the framework bootstrap entirely.
#
# Built on laravoltdev/image: ServerSideUp PHP v5 with FrankenPHP, the PHP
# extensions Laravolt needs, a hardened Caddyfile that Octane uses as is, and
# images that are scanned and rebuilt weekly. Pin a versioned tag.

ARG BASE_IMAGE=laravoltdev/image:php8.5-prod-v2.1.0

# --- PHP dependencies -------------------------------------------------------
FROM ${BASE_IMAGE} AS vendor
USER root
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
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
FROM ${BASE_IMAGE} AS app
USER root

# Octane uses pcntl for signals; install-php-extensions skips it if present.
RUN install-php-extensions pcntl

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --chmod=755 docker/frankenphp/60-voltprocure.sh /etc/entrypoint.d/60-voltprocure.sh
COPY --chmod=755 docker/frankenphp/voltprocure-octane /usr/local/bin/voltprocure-octane

# Laravolt ships its UI assets as a zip inside the package; unpack and link
# them into public/ at build time so no request ever touches the archive.
# Octane's worker entry point is generated, not committed (see .gitignore).
# public/storage is linked here because www-data cannot write to public/.
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN php artisan package:discover --ansi \
 && php artisan laravolt:extract-assets --no-interaction \
 && php artisan laravolt:link --no-interaction \
 && php artisan storage:link --no-interaction \
 && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php \
 && mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs database/data \
 && chown -R www-data:www-data storage bootstrap/cache database/data

# OPcache with JIT, never re-checking files: nothing changes inside a running
# container. The base image turns these variables into php.ini settings.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/data/voltprocure.sqlite \
    OCTANE_SERVER=frankenphp \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_JIT=tracing \
    PHP_OPCACHE_JIT_BUFFER_SIZE=64M

USER www-data
EXPOSE 8080
VOLUME ["/var/www/html/database/data", "/var/www/html/storage"]

# /up boots the app, so a stuck worker fails the check (the base image's
# /healthcheck is answered by Caddy alone).
HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
  CMD curl -fsS -o /dev/null http://127.0.0.1:8080/up || exit 1

# The base image's entrypoint runs /etc/entrypoint.d (60-voltprocure.sh
# prepares the database and caches), then this command.
CMD ["voltprocure-octane"]
