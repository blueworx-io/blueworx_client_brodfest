import { test, expect } from '@playwright/test';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

async function chooseSite(page, label) {
  await page.goto('/wp-admin/admin.php?page=brodfest');
  await page.getByRole('radio', { name: label }).check();
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText('Everything is saved')).toBeVisible();
}

test('switching to the Invite in the admin changes what visitors see', async ({ page, browser }) => {
  await loginAsAdmin(page);
  await chooseSite(page, 'Invite');

  const visitor = await browser.newContext();
  const visit = await visitor.newPage();
  await skipIntro(visit);
  try {
    await visit.goto('/');
    await expect(visit.locator('body')).toHaveClass(/brodfest-site-invite/);
    const response = await visit.goto('/recommendations/');
    expect(response.status()).toBe(200);
    await expect(visit.locator('body')).toHaveClass(/brodfest-page-recommendations/);
  } finally {
    await visitor.close();
    // Put the site back for every other spec.
    await chooseSite(page, 'Save the Date');
  }
});
