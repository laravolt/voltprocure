#!/bin/sh
# `octane` (the default command) prepares the SQLite database and caches,
# then serves HTTP. Anything else (schedule:work, artisan one-offs) runs as is.
set -eu

if [ "${1:-}" != "octane" ]; then
    exec "$@"
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is empty. Generate one with: docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show" >&2
    exit 1
fi

if [ ! -s "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    php artisan migrate --force --seed --no-interaction
else
    php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction

exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=8080 \
    --workers="${OCTANE_WORKERS:-auto}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-1000}" \
    --log-level="${OCTANE_LOG_LEVEL:-warn}"
