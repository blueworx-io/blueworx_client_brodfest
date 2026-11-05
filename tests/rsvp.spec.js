import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp.js';

test('the RSVP page shows a close button in place of the menu, and an active RSVP pill', async ({ page }) => {
  await page.goto('/rsvp/');
  await expect(page.locator('[data-bf-close]')).toBeVisible();
  await expect(page.locator('[data-bf-menu-toggle]')).toHaveCount(0);
  await expect(page.locator('.bf-header .bf-pill')).toHaveClass(/is-active/);
});

test('guest fields appear only when bringing a guest', async ({ page }) => {
  await page.goto('/rsvp/');
  const guest = page.locator('[data-bf-guest]');
  await expect(guest).toBeHidden();
  await page.getByLabel('Yes').nth(1).check();
  await expect(guest).toBeVisible();
  await page.getByLabel('No').nth(1).check();
  await expect(guest).toBeHidden();
});

test('an incomplete RSVP is refused with a message', async ({ page }) => {
  await page.goto('/rsvp/');
  // Skip the browser's own checks to prove the server refuses it too.
  await page.locator('[data-bf-rsvp]').evaluate((form) => form.setAttribute('novalidate', ''));
  await page.locator('#bf-name').fill('No Email');
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('alert')).toContainText('Please add your name');
});

test('a reply is saved and listed under RSVPs in the admin', async ({ page }) => {
  const name = `Test Guest ${Date.now()}`;

  await page.goto('/rsvp/');
  await page.locator('#bf-name').fill(name);
  await page.locator('#bf-email').fill('guest@example.com');
  await page.locator('#bf-phone').fill('7700 900123');
  await page.locator('#bf-dietary').selectOption('vegetarian');
  await page.getByLabel('Yes').first().check();
  await page.getByLabel('Yes').nth(1).check();
  await page.locator('#bf-guest_name').fill('Plus One');
  await page.locator('#bf-guest_email').fill('plusone@example.com');
  await page.getByRole('button', { name: 'SEND' }).click();

  await expect(page.getByRole('status')).toContainText('Thank you for your RSVP');

  await loginAsAdmin(page);
  await page.goto('/wp-admin/edit.php?post_type=brodfest_rsvp&post_status=private');
  const row = page.locator('tr', { hasText: name });
  await expect(row).toContainText('Yes');
  await expect(row).toContainText('guest@example.com');
});
