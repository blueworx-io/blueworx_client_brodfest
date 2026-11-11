import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp.js';

// The plugin installs its own updates (Luke, 11 Nov 2026): WordPress shows
// them as switched on with nothing to press, and leaves every other plugin's
// choice alone.
test('WordPress installs Brodfest updates by itself, and only Brodfest', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/wp-admin/plugins.php');
  const rows = page.locator('#the-list tr[data-plugin]');
  const own = rows.filter({ has: page.locator('.plugin-title strong', { hasText: 'BlueWorx Clients | Brodfest' }) });
  await expect(own).toHaveCount(1);
  await expect(own.locator('.column-auto-updates')).toContainText('Auto-updates enabled');
  await expect(own.locator('.column-auto-updates a.toggle-auto-update')).toHaveCount(0);

  const others = await rows.evaluateAll((all) => all
    .filter((r) => !r.querySelector('.plugin-title strong').textContent.includes('Brodfest'))
    .map((r) => r.querySelector('.column-auto-updates')?.textContent.trim() ?? ''));
  for (const text of others) expect(text).not.toBe('Auto-updates enabled');
});
