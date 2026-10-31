# Tests and CI

- Use Pest for all tests. Keep fast domain/component behavior in Feature tests and reserve Browser tests for JavaScript, accessibility state, pending requests, and full integration.
- A loading-state test must hold a real Livewire request, assert the intermediate disabled/spinner/`aria-busy` state, release the request, and assert the final outcome. Markup-only assertions are insufficient.
- Browser interception must read the exact URI from `[data-update-uri]`; never assume `/livewire/update`.
- Keep the existing HTTP fallback covered when progressively converting a server-rendered form to Livewire.
- Browser tests require Node.js, Playwright Chromium, built frontend assets, and `ext-sockets`. Bun may manage packages and builds but does not replace the Node.js runtime Playwright expects.
- Run tests only against an isolated test database. Never point `RefreshDatabase` at a shared, development, staging, or production database.
- Preserve committed screenshot baselines. Upload failure artifacts from CI, but do not commit transient failure screenshots.
- Before finalizing PHP changes, run the focused tests, `vendor/bin/pint --dirty --format agent`, and the repository's full `composer test` gate.
