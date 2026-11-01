# Docker

| Stack | Purpose | Image | Command |
| --- | --- | --- | --- |
| `docker-compose.dev.yml` | Mounted-source dev sandbox | `laravoltdev/image:php8.5-dev` (pulled, not built) | `docker compose -f docker-compose.dev.yml up -d` |
| `docker-compose.prod.yml` | Baked demo/production: Laravel Octane on FrankenPHP, worker mode | built on `laravoltdev/image:php8.5-prod-v2.1.0` | `docker compose -f docker-compose.prod.yml up --build -d` |

## Development

The dev stack pulls `laravoltdev/image:php8.5-dev` (Bun and Xdebug included, amd64 and arm64) and mounts the working tree, so nothing is built locally. FrankenPHP serves the app on `:8080` and Vite on `:5173`.

```bash
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml exec app composer install
docker compose -f docker-compose.dev.yml exec app php artisan migrate --seed
docker compose -f docker-compose.dev.yml exec app bun install
docker compose -f docker-compose.dev.yml exec app bun run dev
# Open http://localhost:8080
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
3. The runtime is [`laravoltdev/image`](https://github.com/laravolt/image), with FrankenPHP and Laravolt's PHP extensions. It is ServerSideUp PHP v5, scanned with Trivy before every push and rebuilt weekly. At build time the image also gets `pcntl`, unpacks and links Laravolt's UI assets and `public/storage`, and generates Octane's worker script.

The container runs as `www-data` on `:8080`, from `/var/www/html`. The compose file publishes it on `127.0.0.1:8090`, so a reverse proxy can sit in front.

On start, the base image's entrypoint runs `docker/frankenphp/60-voltprocure.sh`, which:
1. Stops with a hint if `APP_KEY` is empty.
2. Creates and seeds the SQLite database if the volume is empty, otherwise runs pending migrations.
3. Runs `php artisan optimize`.

Then `voltprocure-octane` starts `octane:frankenphp` with the base image's Caddyfile (`--caddyfile=/etc/frankenphp/Caddyfile`), so worker mode gets the same trusted proxies, security headers, asset caching and `/healthcheck` as the image's classic mode. Other commands, such as `schedule:work` or a one-off `artisan` call, skip the preparation step.

The `scheduler` service uses the same image to run `schedule:work`. With `APP_DEMO=true` it calls `demo:reset` every night at 00:00 WIB.

### Why worker mode

- **Boot once per worker.** Classic FrankenPHP or PHP-FPM boots Laravel and every service provider on every request. An Octane worker boots once, then reuses the application for up to `OCTANE_MAX_REQUESTS` requests.
- **OPcache with JIT, never re-checking files** (`PHP_OPCACHE_ENABLE=1`, `PHP_OPCACHE_JIT=tracing` in the Dockerfile; the base image writes them to `php.ini`). Nothing changes inside a running container.
- **SQLite tuned for concurrent use:** WAL journal, `synchronous=normal`, a 5 s busy timeout and `IMMEDIATE` transactions. Sessions use encrypted cookies and the cache uses files, so SQLite only receives real data changes.

Measured on tc2 (2 vCPU shared with other services) before the move to laravoltdev/image, FrankenPHP 1.13.1 with PHP 8.5.12, `APP_ENV=production` after `optimize`, 2 workers, 4 concurrent clients, logged in as procurement:

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

- `APP_FORCE_HTTPS` (default `true`) makes production generate https URLs. `.env.docker.example` sets it to `false` for plain http on localhost; set it to `true` with an https `APP_URL` when a TLS proxy sits in front.

- PHP 8.5 is required (matches `composer.json`).
- To move off SQLite, set `DB_CONNECTION` and the `DB_*` variables in `.env.docker` and add a database service. The code does not depend on SQLite.
- The prod stack bakes everything into the image. Only `database/data` and `storage` are volumes (under `/var/www/html`).
- `TRUSTED_PROXY` defaults to `cloudflare`, ServerSideUp's default. Behind your own reverse proxy or load balancer, set `TRUSTED_PROXY=local` in `.env.docker` so the app sees the real client IP.
- FrankenPHP logs go to `stderr`, as JSON when there is no terminal (`CADDY_LOG_FORMAT=console` for plain lines).
