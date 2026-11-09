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

// Replies are people in the WordPress Users list, not a list of their own
// (Luke, 9 Nov 2026): one "Brodfest guest" per person, the plus-one linked to
// whoever brought them, with every answer from the form on show.
//
// One test, two replies: the form takes five an hour from one visitor, and
// the other specs send three.
const GUESTS = '/wp-admin/users.php?role=brodfest_guest&orderby=registered&order=desc';

async function reply(page, { name, email, attending = 'Yes', guest = null }) {
  await page.goto('/rsvp/');
  await page.locator('#bf-name').fill(name);
  await page.locator('#bf-email').fill(email);
  await page.locator('#bf-phone').fill('7700 900123');
  await page.locator('#bf-dietary').selectOption('vegetarian');
  await page.locator('#bf-allergies').fill('Peanuts');
  await page.getByLabel(attending).first().check();
  if (guest) {
    await page.getByLabel('Yes').nth(1).check();
    await page.locator('#bf-guest_name').fill(guest.name);
    await page.locator('#bf-guest_email').fill(guest.email);
    await page.locator('#bf-guest_dietary').selectOption('vegan');
  }
  await page.locator('input[name="accessibility"]').check();
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('status')).toContainText('Thank you for your RSVP');
}

test('a reply becomes people in Users, a second reply changes them, and no real account is touched', async ({ page, browser }) => {
  test.setTimeout(180000);
  await loginAsAdmin(page);
  await page.goto('/wp-admin/profile.php');
  const adminEmail = await page.locator('#email').inputValue();
  const adminLogin = await page.locator('#user_login').inputValue();

  const stamp = Date.now();
  const name = `Test Guest ${stamp}`;
  const email = `guest${stamp}@example.com`;
  const plusOne = `Plus One ${stamp}`;

  // The form is open to anyone, so a reply must never reach a real account:
  // the plus-one here is given the administrator's own email.
  await reply(page, { name, email, guest: { name: plusOne, email: adminEmail } });

  await page.goto(GUESTS);
  const row = page.locator('#the-list tr').filter({ has: page.locator('.column-brodfest_type', { hasText: 'Primary' }) }).filter({ hasText: name });
  await expect(row).toHaveCount(1);
  const guestId = (await row.getAttribute('id')).replace('user-', '');
  await expect(row.locator('.column-role')).toHaveText('Brodfest guest');
  await expect(row.locator('.column-brodfest_attending')).toHaveText('Yes');
  await expect(row.locator('.column-brodfest_email')).toHaveText(email);
  await expect(row.locator('.column-brodfest_phone')).toHaveText('+44 7700 900123');
  await expect(row.locator('.column-brodfest_dietary')).toHaveText('Vegetarian');
  await expect(row.locator('.column-brodfest_allergies')).toHaveText('Peanuts');
  await expect(row.locator('.column-brodfest_accessibility')).toHaveText('Yes');
  await expect(row.locator('.column-brodfest_linked')).toHaveText(plusOne);

  const guestRow = page.locator('#the-list tr').filter({ has: page.locator('.column-brodfest_type', { hasText: 'Guest' }) }).filter({ hasText: plusOne });
  await expect(guestRow).toHaveCount(1);
  await expect(guestRow.locator('.column-brodfest_attending')).toHaveText('Yes');
  await expect(guestRow.locator('.column-brodfest_dietary')).toHaveText('Vegan');
  await expect(guestRow.locator('.column-brodfest_linked')).toHaveText(name);
  await expect(guestRow.locator('.column-brodfest_email')).toHaveText(adminEmail);

  // The administrator is exactly as they were.
  await page.goto('/wp-admin/users.php?role=administrator');
  const admin = page.locator('#the-list tr').filter({ has: page.locator('.column-username', { hasText: adminLogin }) });
  await expect(admin.locator('.column-email')).toContainText(adminEmail);
  await expect(admin.locator('.column-role')).toHaveText('Administrator');
  await expect(admin.locator('.column-brodfest_type')).toHaveText('—');

  // A guest has no public page: WordPress would otherwise show their name at
  // an address anyone could count through.
  const authorPage = await browser.newContext({ baseURL: test.info().project.use.baseURL });
  const author = await authorPage.request.get(`/?author=${guestId}`);
  expect(author.status()).toBe(404);
  expect(await author.text()).not.toContain(name);
  await authorPage.close();

  // The old list of its own is gone.
  await expect(page.locator('#adminmenu a[href*="post_type=brodfest_rsvp"]')).toHaveCount(0);

  // Guests are records, not sign-ins: asking for a password is refused.
  const visitor = await (await browser.newContext({ baseURL: test.info().project.use.baseURL })).newPage();
  await visitor.goto((process.env.WP_LOGIN_PATH || '/wp-login.php') + '?action=lostpassword');
  await visitor.locator('#user_login').fill(email);
  await visitor.locator('#wp-submit').click();
  await expect(visitor.locator('#login_error, .notice-error')).toBeVisible();
  await visitor.context().close();

  // Replying again changes the same person, and the plus-one no longer
  // brought is removed.
  await reply(page, { name, email, attending: 'No' });
  await page.goto(GUESTS);
  const again = page.locator('#the-list tr', { hasText: name });
  await expect(again).toHaveCount(1);
  await expect(again.locator('.column-brodfest_attending')).toHaveText('No');
  await expect(again.locator('.column-brodfest_linked')).toHaveText('—');
  await expect(page.locator('#the-list tr', { hasText: plusOne })).toHaveCount(0);
});
