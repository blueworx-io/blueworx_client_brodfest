import { test, expect } from '@playwright/test';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

// Mark's review of the Save the Date site (Pastel, 5 Nov 2026) and the copy
// he sent with it.

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

test('the header is 170 tall from tablets up, and phones keep the mobile 207', async ({ page }) => {
  for (const [width, height] of [[1920, 170], [1280, 170], [1024, 170], [900, 170], [768, 170], [767, 207]]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    const box = await page.locator('.bf-header').boundingBox();
    expect(box.height, `header at ${width}`).toBe(height);
  }
});

test('the header keeps at least the mobile 48px from either edge', async ({ page }) => {
  for (const width of [1024, 1280, 1599]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    const toggle = await page.locator('.bf-header__toggle').boundingBox();
    const pill = await page.locator('.bf-header .bf-pill').boundingBox();
    expect(Math.round(toggle.x), `menu button at ${width}`).toBeGreaterThanOrEqual(48);
    expect(Math.round(width - pill.x - pill.width), `RSVP at ${width}`).toBeGreaterThanOrEqual(48);
  }
});

test('the open menu blurs the page behind it and has no mountain', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/');
  await page.click('.bf-header__toggle');
  await expect(page.locator('body')).toHaveClass(/is-menu-open/);
  expect(await page.locator('.bf-menu').evaluate((el) => getComputedStyle(el).backdropFilter)).toContain('blur');
  await expect(page.locator('.bf-menu__mountain')).toHaveCount(0);
});

test('a menu item turns white on rollover, without going bold', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/');
  await page.click('.bf-header__toggle');
  const item = page.locator('.bf-menu__items a').nth(1);
  const before = await item.evaluate((el) => getComputedStyle(el).fontWeight);
  await item.hover();
  await expect(item).toHaveCSS('color', 'rgb(255, 255, 255)');
  expect(await item.evaluate((el) => getComputedStyle(el).fontWeight)).toBe(before);
});

test.describe('the opening screen', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => window.localStorage.removeItem('brodfest-intro-seen'));
  });

  test('has no line under the logo and no ring around Enter', async ({ page }) => {
    await page.goto('/');
    const enter = page.locator('[data-bf-enter]');
    await expect(enter).toBeVisible();
    await expect(page.locator('.bf-intro__line')).toHaveCount(0);
    await expect(enter).toBeFocused();
    expect(await enter.evaluate((el) => getComputedStyle(el).outlineStyle)).toBe('none');
  });

  test('still shows the focus ring to someone using the keyboard', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('[data-bf-enter]')).toBeVisible();
    await page.keyboard.press('Shift');
    expect(await page.locator('[data-bf-enter]').evaluate((el) => getComputedStyle(el).outlineStyle)).toBe('solid');
  });
});

test('the home page plays the Save the Date video, wide on desktop and square on phones', async ({ page }) => {
  await page.goto('/');
  const sources = page.locator('.bf-hero__video source');
  await expect(sources).toHaveCount(2);
  await expect(sources.first()).toHaveAttribute('src', /BF_SAVE_THE_DATE_1920X840/);
  await expect(sources.first()).toHaveAttribute('media', '(min-width: 1024px)');
  await expect(sources.last()).toHaveAttribute('src', /BF_SAVE_THE_DATE_1080X1080/);
  const ok = await page.request.get(await sources.first().getAttribute('src'));
  expect(ok.status()).toBe(200);
});

test('Celebration has the new copy, the dress code and transport', async ({ page }) => {
  await page.goto('/celebrate/');
  await expect(page.locator('h1')).toHaveText('CELEBRATION');
  await expect(page.locator('.bf-body').first()).toContainText('Ibiza is calling.');
  await expect(page.locator('h2')).toHaveText(['DRESS CODE', 'TRANSPORT FOR BRODFEST IBIZA']);
  await expect(page.locator('main')).toContainText('Black, Gold & Ibiza Bold');
  await expect(page.locator('main')).toContainText('will land before Christmas.');
  // The subtitles take the area-header style (as TALAMANCA).
  await expect(page.locator('h2').first()).toHaveClass(/bf-area__name/);
});

test('Information is Where to Stay, with the real hotels and only Cala Llonga discounted', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('h1')).toHaveText('WHERE TO STAY');
  await expect(page.locator('.bf-area__name')).toHaveText(["PLAYA D’EN BOSSA", 'TALAMANCA', 'CALA LLONGA']);
  await expect(page.locator('.bf-card__name')).toHaveText([
    'BLESS IBIZA THE SITE', 'HOTEL TORRE DEL MAR', 'AMA IBIZA SUITES',
    'NOBU HOTEL IBIZA BAY', 'HOTEL SIMBAD', 'HOTEL LUX ISLA',
    'HYDE IBIZA', 'MONDRIAN IBIZA',
  ]);
  await expect(page.locator('.bf-card .bf-button--card').first()).toHaveAttribute('href', 'https://www.thesiteibiza.com/en/hotels/bless-ibiza-the-site/');
  const discounts = page.locator('.bf-card__discount');
  await expect(discounts).toHaveCount(2);
  await expect(discounts.first()).toHaveText('For up to 25% discount on bookings made before 15/02/2027, quote BRODFEST');
  await expect(page.locator('.bf-area').last().locator('.bf-card__discount')).toHaveCount(2);
  await expect(page.getByText('GETTING HERE')).toHaveCount(0);
});

test('the map is the client\'s, 782 by 600 on desktop, in the same-height slot', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/accommodation/');
  const map = page.locator('.bf-map img');
  await expect(map).toHaveAttribute('alt', /Map of Ibiza/);
  expect(await map.evaluate((img) => img.complete && img.naturalWidth)).toBe(1630);
  const box = await map.boundingBox();
  expect([Math.round(box.width), Math.round(box.height)]).toEqual([782, 600]);
  expect(Math.round((await page.locator('.bf-map').boundingBox()).height)).toBe(782);
  await expect(page.locator('.bf-map .bf-placeholder-label')).toHaveCount(0);
});

test('Enquiries has the new copy and brodfestibiza.net addresses', async ({ page }) => {
  await page.goto('/enquiries/');
  await expect(page.locator('a[href^="mailto:"]')).toHaveText(['info@brodfestibiza.net', 'concierge@brodfestibiza.net']);
  await expect(page.locator('main')).toContainText('Concierge services are offered privately');
});

test('the Invite keeps its own copy until it has its own review', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/accommodation/?brodfest-site=invite');
  await expect(page.locator('h1')).toHaveText('ACCOMMODATION');
  await page.goto('/celebrate/?brodfest-site=invite');
  await expect(page.locator('h1')).toHaveText('CELEBRATE');
});

test.describe('RSVP', () => {
  test('the country code can be changed, and starts at +44', async ({ page }) => {
    await page.goto('/rsvp/');
    const code = page.locator('#bf-phone_prefix');
    await expect(code).toHaveValue('+44');
    expect(await code.evaluate((el) => el.tagName)).toBe('SELECT');
    await code.selectOption('+34');
    await expect(code).toHaveValue('+34');
  });

  test('the dietary list ends with Dairy Free and opens dark with gold brand lettering', async ({ page }) => {
    await page.goto('/rsvp/');
    const options = page.locator('#bf-dietary option');
    await expect(options.last()).toHaveText('Dairy Free');
    const style = await page.locator('#bf-dietary option').last().evaluate((el) => {
      const s = getComputedStyle(el);
      return { bg: s.backgroundColor, color: s.color, font: s.fontFamily };
    });
    expect(style.bg).toBe('rgb(11, 3, 1)');
    expect(style.color).toBe('rgb(209, 149, 78)');
    // The form's own brand font, not the browser's.
    expect(style.font).toMatch(/aller|Lato/);
  });

  test('a sent reply keeps the RSVP title, thanks the guest and offers Close', async ({ page }) => {
    await page.goto('/rsvp/');
    await page.locator('#bf-name').fill(`Thanks Test ${Date.now()}`);
    await page.locator('#bf-email').fill('thanks@example.com');
    await page.locator('#bf-dietary').selectOption('dairy-free');
    await page.getByLabel('Yes').first().check();
    await page.getByRole('button', { name: 'SEND' }).click();

    await expect(page.locator('h1')).toHaveText('RSVP');
    await expect(page.getByRole('status')).toHaveText('Thank you for your RSVP');
    const close = page.locator('.bf-rsvp a.bf-button--send');
    await expect(close).toHaveText('CLOSE');
    await close.click();
    await expect(page.locator('body')).toHaveClass(/brodfest-page-home/);
  });
});

test('Add to Calendar books 7pm Spanish time', async ({ page }) => {
  await page.goto('/');
  const href = await page.locator('.bf-home .bf-button--primary').getAttribute('href');
  const body = await (await page.request.get(href)).text();
  // 7pm in Ibiza in June is 17:00 UTC (summer time, UTC+2).
  expect(body).toContain('DTSTART:20270605T170000Z');
  expect(body).not.toContain('VALUE=DATE');
});
