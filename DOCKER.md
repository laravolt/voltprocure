# Docker

Two compose stacks ship with the Laravolt v7 starter kit:

| Stack | Purpose | Image | Command |
| --- | --- | --- | --- |
| `docker-compose.dev.yml` | Mounted-source dev sandbox | `php:8.4-cli-alpine` + Composer + Node | `docker compose -f docker-compose.dev.yml up --build` |
| `docker-compose.prod.yml` | Baked production demo | `dunglas/frankenphp:1-php8.4-alpine` | `docker compose -f docker-compose.prod.yml up --build -d` |

## Development

The dev image mounts your working tree onto `/app` and runs `php artisan serve` on `:8000`. SQLite is created on first boot, Composer installs into the mounted vendor directory, and Vite is reachable on `:5173` (run `npm run dev` inside the container — or enable the optional `vite` sidecar in the compose file).

```bash
docker compose -f docker-compose.dev.yml up --build
# Open http://localhost:8000
```

Inside the container:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan tinker
docker compose -f docker-compose.dev.yml exec app php artisan migrate:fresh
docker compose -f docker-compose.dev.yml exec app npm run dev -- --host
```

## Production

The prod image builds the Composer vendor and Vite assets in dedicated stages and then copies them into a FrankenPHP runtime. SQLite is used by default for portability; uncomment the `postgres` service block in `docker-compose.prod.yml` (and switch your `.env` to `DB_CONNECTION=pgsql`) to add a real database.

```bash
docker compose -f docker-compose.prod.yml up --build -d
# Open http://localhost:8090
```

The image runs the Caddy/FrankenPHP combo on port `:8080`; the compose file binds it to `127.0.0.1:8090` on the host so you can put your own reverse proxy in front.

## Notes

- PHP 8.4 is required (matches `composer.json`).
- `CACHE_STORE=array` is the safe default while Laravolt v7 is still moving fast — it sidesteps the now-fixed permission-cache serialization issue cleanly. Switch to `database` or `redis` once you have those services wired in.
- The starter kit uses [Bun](https://bun.sh/) locally (`bun.lock`), but the prod Dockerfile uses npm to avoid bundling an additional toolchain. Run `npm install` once to generate a `package-lock.json` if you want reproducible CI builds.
- The dev stack mounts the host's working tree, so changes are picked up live; the prod stack bakes everything into the image and exposes only `storage/`, `bootstrap/cache/`, and `database/` as named volumes.
