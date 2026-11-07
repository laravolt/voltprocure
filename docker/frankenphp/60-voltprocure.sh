#!/bin/sh
# Runs from the base image's entrypoint (ServerSideUp), before the command.
# For the web server (voltprocure-octane) it checks APP_KEY, creates the SQLite
# database on first start (seeded with demo data only when APP_DEMO=true) or
# runs pending migrations, and caches
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
    # The demo seeders create accounts with published passwords, so only a
    # demo deployment gets them. Anything else starts with an empty database.
    case "${APP_DEMO:-false}" in
        true | TRUE | 1 | on | yes | "(true)")
            php artisan migrate --force --seed --no-interaction
            ;;
        *)
            php artisan migrate --force --no-interaction
            echo "APP_DEMO is not true: created an empty database without demo accounts. Create the first admin with: php artisan laravolt:admin" >&2
            ;;
    esac
else
    php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction
