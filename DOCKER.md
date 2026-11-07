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
2. Creates the SQLite database if the volume is empty, otherwise runs pending migrations. The demo data and demo accounts (published passwords) are seeded only when `APP_DEMO=true`; otherwise the database starts empty and you create the first admin with `docker compose -f docker-compose.prod.yml exec app php artisan laravolt:admin`.
3. Runs `php artisan optimize`.

Then `voltprocure-octane` starts `octane:frankenphp` with the base image's Caddyfile (`--caddyfile=/etc/frankenphp/Caddyfile`), so worker mode gets the same trusted proxies, security headers, asset caching and `/healthcheck` as the image's classic mode. Other commands, such as `schedule:work` or a one-off `artisan` call, skip the preparation step.

The `scheduler` service uses the same image to run `schedule:work`. With `APP_DEMO=true` it calls `demo:reset` every night at 00:00 WIB.

### Why worker mode

- **Boot once per worker.** Classic FrankenPHP or PHP-FPM boots Laravel and every service provider on every request. An Octane worker boots once, then reuses the application for up to `OCTANE_MAX_REQUESTS` requests.
- **OPcache with JIT, never re-checking files** (`PHP_OPCACHE_ENABLE=1`, `PHP_OPCACHE_JIT=tracing` in the Dockerfile; the base image writes them to `php.ini`). Nothing changes inside a running container.
- **SQLite tuned for concurrent use:** WAL journal, `synchronous=normal`, a 5 s busy timeout and `IMMEDIATE` transactions. Sessions use encrypted cookies and the cache uses files, so SQLite only receives real data changes.

Measured by the [`perf-container` workflow](.github/workflows/perf-container.yml) on one GitHub-hosted runner (4 vCPU), PHP 8.5 with OPcache JIT, `APP_ENV=production` after `optimize`, signed in as procurement. Every column comes from the same run:

| Endpoint | In-process, one worker | Production image, one worker: CPU per request | Production image, 2 workers, wrk with 8 connections |
| --- | --- | --- | --- |
| `/up` | 1.2 ms | 1.2 ms | ~1,700 req/s, p50 5 ms |
| `/home` (dashboard, 16 queries) | 12.8 ms | 15.4 ms | ~135 req/s (~8,100/min), p50 58 ms |
| `/purchase-requests` | 10.2 ms | 12.2 ms | ~165 req/s, p50 48 ms |
| `/purchase-orders` | 10.5 ms | 11.9 ms | ~170 req/s, p50 47 ms |
| `/items` | 7.4 ms | 8.3 ms | ~235 req/s, p50 33 ms |
| `/suppliers` | 4.8 ms | 5.7 ms | ~345 req/s, p50 23 ms |

**In-process** is `scripts/perf/profile.php` run inside the image: Octane's `Worker` with no HTTP in front. The image columns use a published port (`-p 127.0.0.1:…:8080`), as `docker-compose.prod.yml` does.

Where the CPU goes beyond the in-process number, measured on the same runner:

- **HTTP serving.** Caddy, FrankenPHP and Octane's request and response handling add 1–2.5 ms per page (10–20%).
- **The second worker.** It adds 3–4% per request.
- **Compression.** Browsers ask for it and wrk does not. It adds about 6% with zstd or gzip and about 13% with br, and shrinks the dashboard from 71 KB to 12–14 KB.
- **docker-proxy.** The published port costs about 0.25 ms CPU per request and changes page throughput by less than 1% (three alternating rounds against host networking).
- **Not costs.**
  - The image's ZTS PHP is at most 0.3 ms per page slower than the NTS PHP on the runner, and faster on most pages.
  - Octane's per-request garbage collection (`octane.garbage`) is off by default, and this app does not publish `config/octane.php`.

Runners differ in CPU speed by up to 2x between runs, so read these as orders of magnitude, and only compare numbers from one run. The `perf` workflow's profile and load jobs run on different runners. To judge a change, push it to a `perf/**` branch: each job measures main and the branch one after the other on the same runner and prints the speed-up. Throughput scales with `OCTANE_WORKERS` and CPU count.

What keeps the pages fast:
- The dashboard reads each set of documents once and buckets the weekly chart in one pass.
- Lists eager-load what they show; `canDecide()` reuses the loaded department.
- Components that repeat per table row (`money`, `doc-no`, `status`, ...) are compiled by [Blaze](https://github.com/livewire/blaze) (`@blaze`). Blaze components cannot read `View::share` data such as `$errors`, so `field` stays on Blade.

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
