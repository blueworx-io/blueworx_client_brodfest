import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp.js';
import { fillInvitee, attendingRadio, partnerRadio, comingAlone } from './helpers/rsvp.js';

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
  await attendingRadio(page, 'yes').check();
  await partnerRadio(page, 'yes').check();
  await expect(guest).toBeVisible();
  await partnerRadio(page, 'no').check();
  await expect(guest).toBeHidden();
});

// The partner question, the partner and the accessibility tick are only for
// someone who is coming (Mark, 12 Nov 2026).
test('the partner question and accessibility tick show only once attending is Yes', async ({ page }) => {
  await page.goto('/rsvp/');
  const extra = page.locator('fieldset[data-bf-attending]');
  await expect(extra).toBeHidden();
  await expect(page.locator('.bf-check')).toBeHidden();
  await attendingRadio(page, 'yes').check();
  await expect(extra).toBeVisible();
  await expect(page.locator('.bf-check')).toBeVisible();
  await partnerRadio(page, 'yes').check();
  await expect(page.locator('#bf-guest_name')).toBeVisible();
  // Saying no hides the lot, partner included.
  await attendingRadio(page, 'no').check();
  await expect(extra).toBeHidden();
  await expect(page.locator('#bf-guest_name')).toBeHidden();
});

test('every field must be filled in, the partner’s too when they come', async ({ page }) => {
  await page.goto('/rsvp/');
  for (const id of ['#bf-name', '#bf-phone', '#bf-email', '#bf-dietary', '#bf-allergies']) {
    await expect(page.locator(id), id).toHaveAttribute('required', '');
  }
  await attendingRadio(page, 'yes').check();
  await expect(partnerRadio(page, 'yes')).toHaveJSProperty('required', true);
  await partnerRadio(page, 'yes').check();
  for (const id of ['#bf-guest_name', '#bf-guest_phone', '#bf-guest_email', '#bf-guest_dietary', '#bf-guest_allergies']) {
    await expect(page.locator(id), id).toHaveJSProperty('required', true);
  }
});

test('an incomplete RSVP is refused with a message', async ({ page }) => {
  await page.goto('/rsvp/');
  // Skip the browser's own checks to prove the server refuses it too.
  await page.locator('[data-bf-rsvp]').evaluate((form) => form.setAttribute('novalidate', ''));
  await page.locator('#bf-name').fill('No Email');
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('alert')).toContainText('Please fill in every field');
});

test('a reply missing only the phone number is refused by the server', async ({ page }) => {
  await page.goto('/rsvp/');
  await page.locator('[data-bf-rsvp]').evaluate((form) => form.setAttribute('novalidate', ''));
  await fillInvitee(page, { name: `No Phone ${Date.now()}`, email: 'nophone@example.com', phone: '' });
  await comingAlone(page);
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('alert')).toContainText('Please fill in every field');
});

test('a partner missing their details is refused by the server', async ({ page }) => {
  await page.goto('/rsvp/');
  await page.locator('[data-bf-rsvp]').evaluate((form) => form.setAttribute('novalidate', ''));
  await fillInvitee(page, { name: `Half Partner ${Date.now()}`, email: 'halfpartner@example.com' });
  await attendingRadio(page, 'yes').check();
  await partnerRadio(page, 'yes').check();
  await page.locator('#bf-guest_name').fill('Partner Without Email');
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('alert')).toContainText('Please fill in every field');
});

// Mark sent eight test replies and three arrived (12 Nov 2026): past five an
// hour from one connection the form threw replies away and still said thank
// you. Eight in a row from one browser must all be kept.
test('eight replies in a row from one visitor are all kept', async ({ page, browser }) => {
  test.setTimeout(240000);
  const stamp = Date.now();
  const visitor = await (await browser.newContext({ baseURL: test.info().project.use.baseURL })).newPage();
  for (let i = 1; i <= 8; i++) {
    await visitor.goto('/rsvp/');
    await fillInvitee(visitor, { name: `Burst ${stamp} ${i}`, email: `burst${stamp}-${i}@example.com` });
    await comingAlone(visitor);
    await visitor.getByRole('button', { name: 'SEND' }).click();
    await expect(visitor.getByRole('status')).toContainText('Thank you for your RSVP');
  }
  await visitor.context().close();

  await loginAsAdmin(page);
  await page.goto(`/wp-admin/users.php?role=brodfest_guest&s=${encodeURIComponent(`burst${stamp}`)}`);
  await expect(page.locator('#the-list tr', { hasText: `Burst ${stamp}` })).toHaveCount(8);
});

// Replies are people in the WordPress Users list, not a list of their own
// (Luke, 9 Nov 2026): one "Brodfest guest" per person, the plus-one linked to
// whoever brought them, with every answer from the form on show.
//
// One test, two replies.
const GUESTS = '/wp-admin/users.php?role=brodfest_guest&orderby=registered&order=desc';

async function reply(page, { name, email, attending = 'Yes', guest = null }) {
  await page.goto('/rsvp/');
  await fillInvitee(page, { name, email, dietary: 'vegetarian', allergies: 'Peanuts' });
  await attendingRadio(page, attending.toLowerCase()).check();
  if ('Yes' === attending) {
    await partnerRadio(page, guest ? 'yes' : 'no').check();
    if (guest) {
      await page.locator('#bf-guest_name').fill(guest.name);
      await page.locator('#bf-guest_phone').fill('7700 900456');
      await page.locator('#bf-guest_email').fill(guest.email);
      await page.locator('#bf-guest_dietary').selectOption('vegan');
      await page.locator('#bf-guest_allergies').fill('None');
    }
    await page.locator('input[name="accessibility"]').check();
  }
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
  await expect(row.locator('.column-brodfest_contact')).toHaveText(email);
  await expect(row.locator('.column-brodfest_linked')).toHaveText(plusOne);
  // Their username is their email (Luke, 11 Nov 2026).
  await expect(row.locator('.column-username strong')).toHaveText(email);

  // The list keeps to who, whether and with whom (Luke, 9 Nov 2026): one
  // Email column, and the rest of the answers on the person's profile.
  const headings = (await page.locator('table.users thead th, table.users thead td').allInnerTexts()).map((t) => t.trim().split(/\r?\n/)[0]);
  expect(headings.filter((t) => /email/i.test(t))).toEqual(['Email']);
  for (const gone of ['Phone', 'Dietary', 'Allergies', 'Accessibility', 'Replied', 'Posts', 'Reply email']) expect(headings, gone).not.toContain(gone);

  const guestRow = page.locator('#the-list tr').filter({ has: page.locator('.column-brodfest_type', { hasText: 'Guest' }) }).filter({ hasText: plusOne });
  await expect(guestRow).toHaveCount(1);
  await expect(guestRow.locator('.column-brodfest_attending')).toHaveText('Yes');
  await expect(guestRow.locator('.column-brodfest_linked')).toHaveText(name);
  await expect(guestRow.locator('.column-brodfest_contact')).toHaveText(adminEmail);
  // An email that is somebody else's account is never taken as a username.
  await expect(guestRow.locator('.column-username strong')).toHaveText(/^brodfest-[a-z0-9]+$/);
  const plusOneId = (await guestRow.getAttribute('id')).replace('user-', '');

  // Every answer from the form is on the person's profile.
  const answers = page.locator('#brodfest-rsvp');
  const answer = (label) => answers.locator('tr').filter({ has: page.locator('th', { hasText: new RegExp('^\\s*' + label + '\\s*$') }) }).locator('td');
  await page.goto('/wp-admin/user-edit.php?user_id=' + guestId);
  await expect(answer('Type')).toHaveText('Primary');
  await expect(answer('Attending')).toHaveText('Yes');
  await expect(answer('Linked to')).toHaveText(plusOne);
  await expect(answer('Email')).toHaveText(email);
  await expect(answer('Phone')).toHaveText('+44 7700 900123');
  await expect(answer('Dietary')).toHaveText('Vegetarian');
  await expect(answer('Allergies')).toHaveText('Peanuts');
  await expect(answer('Accessibility')).toHaveText('Yes');
  await expect(answer('Replied')).not.toHaveText('—');
  await page.goto('/wp-admin/user-edit.php?user_id=' + plusOneId);
  await expect(answer('Type')).toHaveText('Guest');
  await expect(answer('Dietary')).toHaveText('Vegan');
  await expect(answer('Linked to')).toHaveText(name);
  // An administrator's profile has no such section.
  await page.goto('/wp-admin/profile.php');
  await expect(page.locator('#brodfest-rsvp')).toHaveCount(0);

  // The administrator is exactly as they were.
  await page.goto('/wp-admin/users.php?role=administrator');
  const admin = page.locator('#the-list tr').filter({ has: page.locator('.column-username', { hasText: adminLogin }) });
  await expect(admin.locator('.column-brodfest_contact')).toHaveText(adminEmail);
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
  await expect(again.locator('.column-username strong')).toHaveText(email);
  await expect(page.locator('#the-list tr', { hasText: plusOne })).toHaveCount(0);
});
