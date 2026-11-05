import { test, expect } from '@playwright/test';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

test('the front page is the plugin Home page, not the WordPress blog roll', async ({ page }) => {
  const response = await page.goto('/');
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).toHaveClass(/brodfest-page-home/);
  await expect(page.locator('body')).toHaveClass(/brodfest-site-save-the-date/);
});

test('the plugin renders the whole document with its header and footer', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('header.bf-header')).toHaveCount(1);
  await expect(page.locator('main#content')).toHaveCount(1);
  await expect(page.locator('footer.bf-footer')).toHaveCount(1);
  await expect(page.locator('#page, .wp-site-blocks')).toHaveCount(0);
});

test('the page has a title and loads only the plugin stylesheets', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/.+/);
  await expect(page.locator('link#brodfest-public-css')).toHaveCount(1);
  await expect(page.locator('link#brodfest-fonts-css')).toHaveAttribute('href', /use\.typekit\.net\/vjw6kra\.css/);
  await expect(page.locator('link#wp-block-library-css')).toHaveCount(0);
});

test('every Save the Date page loads, and the Invite-only pages send visitors home', async ({ page }) => {
  for (const path of ['/celebrate/', '/accommodation/', '/enquiries/', '/rsvp/']) {
    const response = await page.goto(path);
    expect(response.status(), path).toBe(200);
    await expect(page.locator('h1').first(), path).toBeAttached();
  }

  for (const path of ['/can-sakhara-story/', '/recommendations/']) {
    await page.goto(path);
    await expect(page.locator('body'), path).toHaveClass(/brodfest-page-home/);
  }
});

test('the menu opens, lists the Save the Date pages without Transport, and closes', async ({ page }) => {
  await page.goto('/celebrate/');
  const toggle = page.locator('[data-bf-menu-toggle]');
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');

  const items = page.locator('.bf-menu__items a');
  await expect(items).toHaveText(['CELEBRATE', 'ACCOMMODATION', 'ENQUIRIES']);
  await expect(page.locator('.bf-menu')).toBeVisible();

  await page.keyboard.press('Escape');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
});

test('a visitor cannot switch to the Invite from the address bar', async ({ page }) => {
  await page.goto('/?brodfest-site=invite');
  await expect(page.locator('body')).toHaveClass(/brodfest-site-save-the-date/);
});

test('an editor can preview the Invite, which has its own pages and menu', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/?brodfest-site=invite');
  await expect(page.locator('body')).toHaveClass(/brodfest-site-invite/);
  await expect(page.locator('.bf-home__lettering--invite')).toBeVisible();

  await page.goto('/can-sakhara-story/?brodfest-site=invite');
  await expect(page.locator('h1')).toHaveText('CAN SAKHARA STORY');

  await page.goto('/recommendations/?brodfest-site=invite');
  await expect(page.locator('.bf-category')).toHaveCount(2);

  await page.locator('[data-bf-menu-toggle]').click();
  await expect(page.locator('.bf-menu__items a')).toHaveText([
    'CELEBRATE',
    'CAN SAKHARA STORY',
    'ACCOMMODATION',
    'IBIZA RECOMMENDATIONS',
    'ENQUIRIES',
  ]);
  // Links keep the preview so the editor stays in the Invite.
  await expect(page.locator('.bf-menu__items a').first()).toHaveAttribute('href', /brodfest-site=invite/);
});

test('Accommodation shows each area with its hotels and the discount code', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('.bf-area')).toHaveCount(3);
  await expect(page.locator('.bf-area').first().locator('.bf-card')).toHaveCount(3);
  await expect(page.locator('.bf-card__code').first()).toHaveText('BRODFEST');
});

// A phone lays the page out 767 wide (the mobile design) and zooms it to fit.
test.describe('on a phone', () => {
  test.use({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });

  test('the hotels swipe, the dots follow, and nothing scrolls sideways', async ({ page }) => {
    await page.goto('/accommodation/');

    const carousel = page.locator('[data-bf-carousel]').first();
    const dots = carousel.locator('.bf-cards__dot');
    await expect(dots.first()).toHaveClass(/is-active/);

    await carousel.locator('[data-bf-track]').evaluate((track) => track.scrollTo({ left: track.scrollWidth }));
    await expect(dots.last()).toHaveClass(/is-active/);

    const width = await page.evaluate(() => document.documentElement.scrollWidth);
    expect(width).toBeLessThanOrEqual(767);
  });
});

test('Add to Calendar downloads the event as an all-day calendar entry', async ({ page }) => {
  await page.goto('/');
  const href = await page.locator('.bf-home .bf-button--primary').getAttribute('href');
  const response = await page.request.get(href);
  expect(response.headers()['content-type']).toContain('text/calendar');
  const body = await response.text();
  expect(body).toContain('DTSTART;VALUE=DATE:20270605');
  expect(body).toContain('SUMMARY:Brodfest');
});
