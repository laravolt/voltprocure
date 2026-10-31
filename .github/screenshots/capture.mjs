// Logs in as each demo role and captures the main pages at desktop and phone sizes.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const base = process.env.APP_URL ?? 'http://127.0.0.1:8090';
const out = process.argv[2] ?? 'shots';
mkdirSync(out, { recursive: true });

const runs = [
  { user: 'andi', pages: { home: '/home', 'purchase-orders': '/purchase-orders', items: '/items', suppliers: '/suppliers' } },
  { user: 'bagus', pages: { 'purchase-requests': '/purchase-requests' } },
];
const viewports = { desktop: { width: 1440, height: 900 }, phone: { width: 390, height: 844 } };

const browser = await chromium.launch();
let failed = false;

for (const [size, viewport] of Object.entries(viewports)) {
  for (const run of runs) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 2, locale: 'id-ID' });
    const page = await context.newPage();
    page.on('pageerror', (e) => { console.error(`[${run.user}] page error: ${e.message}`); failed = true; });

    await page.goto(`${base}/auth/login`);
    await page.fill('input[name="email"]', `${run.user}@voltprocure.test`);
    await page.fill('input[name="password"]', 'password');
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

    for (const [name, path] of Object.entries(run.pages)) {
      const response = await page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
      if (response?.status() !== 200) { console.error(`${path}: HTTP ${response?.status()}`); failed = true; }
      await page.screenshot({ path: `${out}/${name}--${size}.png`, fullPage: size === 'phone' });
      console.log(`captured ${name}--${size}`);
    }

    // The first open purchase request, as its approver sees it.
    if (run.user === 'bagus') {
      const link = page.locator('a[href*="/purchase-requests/"]:not([href$="/create"])').first();
      if (await link.count()) {
        await link.click();
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: `${out}/purchase-request-detail--${size}.png`, fullPage: size === 'phone' });
        console.log(`captured purchase-request-detail--${size}`);
      }
    }
    await context.close();
  }
}

await browser.close();
process.exit(failed ? 1 : 0);
