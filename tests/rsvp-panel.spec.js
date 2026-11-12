import { test, expect } from '@playwright/test';
import { skipIntro } from './helpers/wp.js';
import { fillInvitee, comingAlone } from './helpers/rsvp.js';

// RSVP lowers over the page the guest is on and goes back up when they close
// it or have replied, rather than opening a page of its own and closing to
// the home page (client, 6 Nov 2026). The RSVP pill closes it too.

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/celebrate/');
});

const panel = (page) => page.locator('[data-bf-rsvp-panel]');
const pill = (page) => page.locator('.bf-header .bf-pill');

async function open(page) {
  await pill(page).click();
  // The form arrives from the server once the panel opens; on CI's single
  // PHP process that can queue behind the page's own assets.
  await expect(panel(page).locator('[data-bf-rsvp]')).toBeVisible({ timeout: 30000 });
  // Lowered fully, under the header.
  await expect.poll(() => panel(page).evaluate((el) => Math.round(el.getBoundingClientRect().top))).toBe(
    Math.round(await page.locator('.bf-header').evaluate((el) => el.getBoundingClientRect().bottom)),
  );
}

async function expectClosed(page) {
  await expect(panel(page)).toBeHidden();
  expect(new URL(page.url()).pathname).toBe('/celebrate/');
  await expect(page.locator('main h1')).toHaveText('CELEBRATION');
  await expect(pill(page)).not.toHaveClass(/is-active/);
}

test('RSVP lowers over the page you are on, which stays behind it', async ({ page }) => {
  await open(page);
  expect(new URL(page.url()).pathname).toBe('/celebrate/');
  await expect(page.locator('main h1')).toHaveText('CELEBRATION');
  await expect(pill(page)).toHaveClass(/is-active/);
  await expect(panel(page).locator('h1, h2').first()).toHaveText('RSVP');
  await expect(page.locator('#bf-name')).toBeFocused();
});

test('the RSVP pill closes it again', async ({ page }) => {
  await open(page);
  await pill(page).click();
  await expectClosed(page);
  await expect(pill(page)).toBeFocused();
});

test('the header close button and Esc close it', async ({ page }) => {
  await open(page);
  await page.locator('.bf-header__toggle').click();
  await expectClosed(page);
  await expect(page.locator('[data-bf-menu]')).toBeHidden();

  await open(page);
  await page.keyboard.press('Escape');
  await expectClosed(page);
});

test('a reply is sent from the panel, thanked there, and CLOSE goes back up to the page', async ({ page }) => {
  await open(page);
  await fillInvitee(panel(page), { name: `Panel Guest ${Date.now()}`, email: 'panel@example.com' });
  await comingAlone(panel(page));
  await panel(page).getByRole('button', { name: 'SEND' }).click();
  await expect(panel(page).getByRole('status')).toContainText('Thank you for your RSVP');
  expect(new URL(page.url()).pathname).toBe('/celebrate/');
  await panel(page).getByRole('link', { name: 'CLOSE' }).click();
  await expectClosed(page);
});

test('an incomplete reply is refused in the panel', async ({ page }) => {
  await open(page);
  await panel(page).locator('[data-bf-rsvp]').evaluate((form) => form.setAttribute('novalidate', ''));
  await page.locator('#bf-name').fill('No Email');
  await panel(page).getByRole('button', { name: 'SEND' }).click();
  await expect(panel(page).getByRole('alert')).toContainText('Please fill in every field');
  expect(new URL(page.url()).pathname).toBe('/celebrate/');
});

test('on the RSVP page itself, close and the pill go back to the page before', async ({ page }) => {
  await page.evaluate(() => { window.location.href = '/rsvp/'; });
  await page.waitForURL('**/rsvp/');
  await page.locator('[data-bf-close]').click();
  await page.waitForURL('**/celebrate/');

  await page.evaluate(() => { window.location.href = '/rsvp/'; });
  await page.waitForURL('**/rsvp/');
  await pill(page).click();
  await page.waitForURL('**/celebrate/');
});

test('on the RSVP page itself, CLOSE after sending goes back to the page before, not the form', async ({ page }) => {
  await page.evaluate(() => { window.location.href = '/rsvp/'; });
  await page.waitForURL('**/rsvp/');
  await fillInvitee(page, { name: `Return Guest ${Date.now()}`, email: 'return@example.com' });
  await comingAlone(page);
  await page.getByRole('button', { name: 'SEND' }).click();
  await expect(page.getByRole('status')).toContainText('Thank you for your RSVP');
  await page.getByRole('link', { name: 'CLOSE', exact: true }).click();
  await page.waitForURL('**/celebrate/');
});
