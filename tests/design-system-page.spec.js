import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp.js';

// The theme's design system page is drawn at the home address, which is one
// of Brodfest's own pages. The stylesheet sweep that keeps those pages clean
// must leave the theme's page alone, or it arrives as bare text and swatches.
// Only the local harness runs with the theme installed.
const HAS_THEME = /127\.0\.0\.1|localhost/.test(process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8881');

test('the theme design system page keeps its own styles', async ({ page }) => {
  test.skip(!HAS_THEME, 'Needs the theme, which only the local harness installs.');
  await loginAsAdmin(page);

  const response = await page.goto('/?blueworx-design-system=1');
  test.skip(!(await response.text()).includes('bw-ref-main'), 'The theme is not active on this site.');

  await expect(page.locator('link[href*="design-reference.css"]')).toHaveCount(1);
  await expect(page.locator('link[href*="/site.css"]')).toHaveCount(1);
});
