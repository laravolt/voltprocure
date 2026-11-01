#!/bin/sh
# Runs from the base image's entrypoint (ServerSideUp), before the command.
# For the web server (voltprocure-octane) it checks APP_KEY, creates and seeds
# the SQLite database on first start or runs pending migrations, and caches
# config, routes and views. Other commands (schedule:work, artisan one-offs)
# skip it.
set -eu

case "${DOCKER_CMD:-}" in
    *voltprocure-octane*) ;;
    *) return 0 ;;
esac

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is empty. Generate one with: docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show" >&2
    return 1
fi

cd "${APP_BASE_DIR:-/var/www/html}"

if [ ! -s "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    php artisan migrate --force --seed --no-interaction
else
    php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction
