import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

// At every width from the mobile artboard up nothing may push the page
// sideways (phones are checked zoomed, in design-geometry.spec.js). Run with
// real scrollbars, as on Windows, since the page's scale counts the
// scrollbar and its centring does not. It starts at 782: a 767 window less a
// 15px scrollbar leaves less room than the mobile artboard itself.
test.use({ launchOptions: { ignoreDefaultArgs: ['--hide-scrollbars'] } });

const PATHS = [...new Set(JSON.parse(readFileSync(new URL('./fixtures/design-geometry.json', import.meta.url), 'utf8')).map((r) => r.path))];

test('no page scrolls sideways at any width', async ({ page }) => {
  test.setTimeout(240000);
  await skipIntro(page);
  await loginAsAdmin(page);
  const wide = [];
  for (const width of [782, 1023, 1024, 1440, 1599, 1600, 1920, 2560]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const path of PATHS) {
      await page.goto(path);
      // WordPress's admin bar, shown only to a signed-in admin, is not the site's.
      await page.addStyleTag({ content: '#wpadminbar{display:none!important}' });
      const [scroll, client] = await page.evaluate(() => [document.documentElement.scrollWidth, document.documentElement.clientWidth]);
      if (scroll > client) wide.push(`${path} at ${width}: ${scroll}px wide in ${client}`);
    }
  }
  expect(wide, wide.join('\n')).toEqual([]);
});
