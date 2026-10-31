# Docker

| Stack | Purpose | Image | Command |
| --- | --- | --- | --- |
| `docker-compose.dev.yml` | Mounted-source dev sandbox | `laravoltdev/image:php8.5-frankenphp-debian` + Bun | `docker compose -f docker-compose.dev.yml up --build` |
| `docker-compose.prod.yml` | Baked demo/production: Laravel Octane on FrankenPHP, worker mode | `dunglas/frankenphp:1.13.1-php8.5-trixie` | `docker compose -f docker-compose.prod.yml up --build -d` |

## Development

The dev image mounts your working tree onto `/app` and runs `php artisan serve` on `:8000`. On first boot it creates the SQLite database and installs Composer packages into the mounted vendor directory. Vite is reachable on `:5173`: run `bun run dev` inside the container, or enable the optional `vite` sidecar in the compose file.

```bash
docker compose -f docker-compose.dev.yml up --build
# Open http://localhost:8000
```

## Production (Octane + FrankenPHP)

```bash
cp .env.docker.example .env.docker
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show   # paste into APP_KEY
docker compose -f docker-compose.prod.yml up --build -d
# Open http://localhost:8090
```

`docker/frankenphp/prod.Dockerfile` has three stages:
1. Composer installs the dependencies without dev packages and builds an authoritative class map.
2. Bun builds the Vite assets.
3. The FrankenPHP runtime gets both, plus the extensions the app needs (`bcmath exif gd intl opcache pcntl zip`). At build time it also unpacks and links Laravolt's UI assets and generates Octane's worker script.

The container runs as `www-data` on `:8080`. The compose file publishes it on `127.0.0.1:8090`, so a reverse proxy can sit in front.

On start, `docker/frankenphp/entrypoint.sh`:
1. Creates and seeds the SQLite database if the volume is empty, otherwise runs pending migrations.
2. Runs `php artisan optimize`.
3. Starts `octane:frankenphp`.

The `scheduler` service uses the same image to run `schedule:work`. With `APP_DEMO=true` it calls `demo:reset` every night at 00:00 WIB.

### Why worker mode

- **Boot once per worker.** Classic FrankenPHP or PHP-FPM boots Laravel and every service provider on every request. An Octane worker boots once, then reuses the application for up to `OCTANE_MAX_REQUESTS` requests.
- **OPcache with JIT, never re-checking files** (`docker/frankenphp/php.ini`). Nothing changes inside a running container.
- **SQLite tuned for concurrent use:** WAL journal, `synchronous=normal`, a 5 s busy timeout and `IMMEDIATE` transactions. Sessions use encrypted cookies and the cache uses files, so SQLite only receives real data changes.

Measured on tc2 (2 vCPU shared with other services), FrankenPHP 1.13.1 with PHP 8.5.12, `APP_ENV=production` after `optimize`, 2 workers, 4 concurrent clients, logged in as procurement:

| Endpoint | Octane | `php artisan serve` (local, one process) |
| --- | --- | --- |
| `/up` | ~950 req/s, p50 3 ms | ~30 req/s, p50 98 ms |
| `/home` (dashboard, ~15 queries) | ~50 req/s, p50 56–61 ms | ~15 req/s, p50 206 ms |
| `/purchase-requests` | ~61 req/s, p50 48 ms | ~17 req/s, p50 179 ms |
| `/items` | ~79 req/s, p50 39 ms | not measured |

These numbers come from a short run on a busy machine, so read them as orders of magnitude, not a benchmark. Throughput scales with `OCTANE_WORKERS` and CPU count.

### Octane caveats

- **Do not keep request state in singletons or static properties.** Workers live across requests. The app follows this rule.
- **Needs laravolt/laravolt v8.0.1 or later.**
  - Before the fix, Laravolt's sidebar appended the whole menu again on every request under Octane, so latency grew without bound (7 s, then 26 s, within ~100 requests).
  - In production it also cached one user's menu for everyone. On Laravel 13 that cached object cannot be restored, so every page after the first returns 500 with any persistent cache.
  - The fix ([#476](https://github.com/laravolt/laravolt/pull/476), released in v8.0.1) was verified with this stack: latency stayed flat over ~1,000 requests and each role saw its own menu.

## Notes

- PHP 8.5 is required (matches `composer.json`).
- To move off SQLite, set `DB_CONNECTION` and the `DB_*` variables in `.env.docker` and add a database service. The code does not depend on SQLite.
- The prod stack bakes everything into the image. Only `database/data` and `storage` are volumes.
