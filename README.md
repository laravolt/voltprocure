# VoltProcure

A procurement and inventory demo built on [Laravolt](https://github.com/laravolt/laravolt) 8. It shows what a small but real business system looks like on Laravel 13, Livewire 4, Preline 5 and Tailwind CSS 4.3.

A department asks for goods, its manager approves them line by line, procurement turns the approval into a purchase order, and the warehouse receives the delivery, in parts if needed. Stock updates itself and every movement is traced back to its document.

```mermaid
flowchart LR
    PR["Purchase request<br/>PR-2026-0001"] -->|manager approves<br/>per line| A[Approved]
    PR -->|manager rejects<br/>with a reason| R[Rejected]
    A -->|procurement sets<br/>agreed prices| PO["Purchase order<br/>PO-2026-0001"]
    PO -->|warehouse receives,<br/>partial allowed| GRN["Goods receipt<br/>GRN-2026-0001"]
    GRN --> S[(Stock + movement log)]
```

![Dashboard: requests waiting for approval, open orders, low stock, monthly spend and weekly order value](docs/screenshots/dashboard.png)

| Approving a request | Stock | On a phone |
|---|---|---|
| ![A manager approves a purchase request line by line](docs/screenshots/approval.png) | ![Stock list with reorder-point bars](docs/screenshots/stock.png) | ![Purchase orders at 390px wide, each row a card with its status badge](docs/screenshots/orders-phone.png) |

Screenshots come from the production Docker image in CI (`.github/workflows/docker.yml`).

## What is in it

| Page | What it does |
|---|---|
| **Ringkasan** (dashboard) | Requests waiting for approval, open orders with overdue ones flagged, low stock, month-to-date spend against the same days last month, weekly order value for 8 weeks (with a table view), and a "your work" list that depends on your role |
| **Permintaan barang** | Status tabs, create as draft or submit, per-line approval, rejection with a required reason, document history |
| **Pesanan pembelian** | Create from an approved request at the agreed price, receive goods in parts, receipt history |
| **Stok barang** | Low-stock filter, search, reorder-point bar, movement history per item |
| **Pemasok** | Suppliers with open orders and total ordered |

Rules live in `app/Actions/Procurement` and are covered by tests:
- Only the department's manager decides.
- Nothing is ordered before approval.
- A receipt never exceeds what is still outstanding.
- Document numbers run per year (`PR-`, `PO-`, `GRN-`).

The UI is in Indonesian, because the demo's users are.

## Demo accounts

All passwords are `password`.

| Email | Role | Try this |
|---|---|---|
| `dewi@voltprocure.test` | Requester (IT) | Create a request and submit it |
| `bagus@voltprocure.test` | IT manager | Approve fewer items than requested, or reject |
| `andi@voltprocure.test` | Procurement | Turn an approved request into a PO |
| `siti@voltprocure.test` | Warehouse | Receive part of an order and watch stock move |

Other accounts: requesters `yoga`, `maya`, `fajar` and managers `wulan`, `hendra`, `ratna`, one per department.

The seeder replays two months of activity through the same actions the UI uses, so totals, statuses and stock always agree. Dates are relative to the day you seed.

## Run it locally

Requires PHP 8.5, Composer and Bun.

```bash
composer setup   # install, .env, key, migrate, build assets
php artisan db:seed
composer dev     # server, queue, logs and Vite together
```

## Run it with Docker (Octane + FrankenPHP)

The production image serves the app with [Laravel Octane](https://laravel.com/docs/octane) on [FrankenPHP](https://frankenphp.dev) in worker mode: each worker boots Laravel once and keeps it in memory. It is built on [`laravoltdev/image`](https://github.com/laravolt/image), the Laravolt base image (ServerSideUp PHP v5, scanned and rebuilt weekly).

```bash
cp .env.docker.example .env.docker
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show   # paste into APP_KEY
docker compose -f docker-compose.prod.yml up -d --build
```

Open http://localhost:8090.
- **First start:** the container creates the SQLite database and seeds the demo.
- **Nightly reset:** with `APP_DEMO=true`, the `scheduler` service wipes and reseeds it every night at 00:00 WIB, so the dates stay current.
- **Reset by hand:** `docker compose -f docker-compose.prod.yml exec app php artisan demo:reset`.

With 2 workers on a 4-vCPU CI runner, the dashboard serves ~130 req/s (about 7,800 per minute) and the lists 160–220 req/s; the [`perf` workflow](.github/workflows/perf.yml) measures it and compares a branch with main on the same runner.

[DOCKER.md](DOCKER.md) covers:
- how the image is built;
- the performance settings (OPcache JIT, SQLite WAL, cookie sessions);
- the tuning knobs `OCTANE_WORKERS` and `OCTANE_MAX_REQUESTS`;
- the Laravolt version this setup needs (8.0.1 or later).

## Design

The interface follows one direction, **"meja kerja rapi"** (a tidy desk): tidy, certain, a little warm.

- **Graphite neutrals, one accent.** Laravolt's views are written in `gray-*` and `blue-*`. `resources/css/app.css` maps those scales to zinc and to the brand indigo `#4F46E5`, so every page follows, including Laravolt's built-in user and role screens, without overriding a single vendor view.
- **One rich moment.** The dashboard's weekly order chart is the only decorated element. Everything else stays quiet.
- **Signature.** Every document number is set in tabular monospace (`PR-2026-0187`), so it reads as a reference you can quote over the phone.
- **Details.**
  - Numbers use tabular figures.
  - Hover styles apply only on devices that can hover.
  - Inputs are 16px on phones, so iOS does not zoom in.
  - On phones, list tables become labelled cards through Laravolt's Basictable (the `basictable` class).
  - Status badges always carry a label, never color alone.
  - The chart has a table view.

The direction was worked out with [unslop](https://github.com/heydamir/unslop) by [@heydamir](https://github.com/heydamir) (CC BY 4.0): audit, direction, then passes over spacing, color, type, copy, layout and details.

## Quality gates

```bash
composer test   # type coverage 100%, Pest, Pint, Rector, Prettier, PHPStan max
```

## Dependency status

VoltProcure runs on released versions: laravolt/laravolt ^8.1 (Preline 5, Tailwind 4.3) and laravolt/avatar ^8.0.1. Serving it with Octane needs laravolt/laravolt 8.0.1 or later; older versions grow the sidebar on every request and, in production, return 500 after the first page. See [DOCKER.md](DOCKER.md#octane-caveats).

## License

MIT, see [LICENSE](LICENSE). Design direction credit: unslop by @heydamir, CC BY 4.0.
