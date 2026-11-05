import { test, expect } from '@playwright/test';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

// Brodfest's colours and fonts come from the theme's named styles
// (BlueWorx › Design), with the Brodfest values as fallbacks for a site
// without the theme.

test.use({ viewport: { width: 1920, height: 1080 } });

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

// Transitions off, so a colour is read after the change, not halfway through it.
const STILL = '*,*::before,*::after{transition:none!important;animation:none!important}';
const NO_THEME = STILL + ':root{--color-bg:initial;--color-text:initial;--color-primary:initial;--color-accent:initial;--font-heading:initial;--font-body:initial}';
const TEST_THEME = STILL + ':root{--color-bg:#000010;--color-text:#00ff00;--color-primary:#ff0000;--color-accent:#0000ff;--font-heading:serif;--font-body:monospace}';

// A computed colour as [r, g, b], whatever syntax the browser reports it in
// (color-mix() comes back as color(srgb …), not rgb()).
async function rgb(page, selector, property) {
  return page.locator(selector).first().evaluate((el, prop) => {
    const value = getComputedStyle(el)[prop];
    const ctx = document.createElement('canvas').getContext('2d');
    ctx.fillStyle = value;
    ctx.fillRect(0, 0, 1, 1);
    return Array.from(ctx.getImageData(0, 0, 1, 1).data.slice(0, 3));
  }, property);
}

async function font(page, selector) {
  return page.locator(selector).first().evaluate((el) => getComputedStyle(el).fontFamily);
}

function near(actual, expected, allowance) {
  return actual.every((v, i) => Math.abs(v - expected[i]) <= allowance);
}

test('with no theme names set, the page shows the Brodfest colours', async ({ page }) => {
  await page.goto('/celebrate/');
  await page.addStyleTag({ content: NO_THEME });
  expect(await rgb(page, 'body', 'backgroundColor')).toEqual([11, 3, 1]);
  expect(await rgb(page, 'body', 'color')).toEqual([209, 149, 78]);
  expect(await rgb(page, '.bf-title', 'color')).toEqual([162, 67, 0]);
  expect(await font(page, '.bf-title')).toMatch(/^"?futura-pt/);
  expect(await font(page, 'body')).toMatch(/^"?aller/);

  await page.goto('/');
  await page.addStyleTag({ content: NO_THEME });
  expect(await rgb(page, '.bf-pill', 'backgroundColor')).toEqual([209, 149, 78]);
  expect(await rgb(page, '.bf-button--primary', 'backgroundColor')).toEqual([99, 40, 0]);

  await page.goto('/rsvp/');
  await page.addStyleTag({ content: NO_THEME });
  const field = await rgb(page, '.bf-field', 'backgroundColor');
  expect(near(field, [33, 12, 7], 6), `field colour ${field}`).toBe(true);
});

test("the colours follow the theme's names", async ({ page }) => {
  await page.goto('/celebrate/');
  await page.addStyleTag({ content: TEST_THEME });
  expect(await rgb(page, 'body', 'backgroundColor')).toEqual([0, 0, 16]);
  expect(await rgb(page, 'body', 'color')).toEqual([0, 255, 0]);
  expect(await rgb(page, '.bf-title', 'color')).toEqual([0, 0, 255]);
  expect(await font(page, '.bf-title')).toBe('serif');
  expect(await font(page, 'body')).toBe('monospace');

  await page.goto('/');
  await page.addStyleTag({ content: TEST_THEME });
  expect(await rgb(page, '.bf-pill', 'backgroundColor')).toEqual([255, 0, 0]);
  expect(await rgb(page, '.bf-button--primary', 'backgroundColor')).not.toEqual([99, 40, 0]);

  await page.goto('/rsvp/');
  await page.addStyleTag({ content: TEST_THEME });
  const field = await rgb(page, '.bf-field', 'backgroundColor');
  expect(near(field, [33, 12, 7], 6), `field colour ${field}`).toBe(false);
});

// The theme's names exist only where the Enhance theme is active; CI's test
// site has no theme, so these skip there while the rest of the file runs.
async function themeActive(page) {
  await page.goto('/');
  return (await page.locator('body').getAttribute('class')).includes('wp-theme-blueworx-labs-wordpress-theme');
}

test("the theme's design styles stay on Brodfest pages", async ({ page }) => {
  test.skip(!(await themeActive(page)), 'Enhance theme not active');
  await expect(page.locator('style#blueworx-design-inline-css')).toHaveCount(1);
  const bg = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--color-bg').trim());
  expect(bg).not.toBe('');
  await expect(page.locator('link#blueworx-site-css')).toHaveCount(0);
});

test("Brodfest's fonts and preset are on the Design screen", async ({ page }) => {
  test.skip(!(await themeActive(page)), 'Enhance theme not active');
  await loginAsAdmin(page);
  const response = await page.goto('/wp-admin/admin.php?page=blueworx-design');
  expect(response.status()).toBe(200);
  const text = await page.locator('#wpbody').innerText();
  expect(text).toContain('Futura PT');
  expect(text).toContain('Aller');
  expect(text).toContain('Brodfest');
});

test('the desktop text gutter is not changed by the theme', async ({ page }) => {
  await page.goto('/celebrate/');
  const padding = await page.locator('.bf-text').first().evaluate((el) => parseFloat(getComputedStyle(el).paddingLeft));
  expect(Math.round(padding)).toBe(178);
});

async function fill(page, selector) {
  return page.locator(selector).first().evaluate((el) => {
    const ctx = document.createElement('canvas').getContext('2d');
    ctx.fillStyle = getComputedStyle(el).fill;
    ctx.fillRect(0, 0, 1, 1);
    return Array.from(ctx.getImageData(0, 0, 1, 1).data.slice(0, 3));
  });
}

test('the artwork follows the theme', async ({ page }) => {
  await page.goto('/');
  await page.addStyleTag({ content: TEST_THEME });
  for (const selector of ['.bf-header__logo svg path', '.bf-footer__mountain svg path', '.bf-home__date svg path', '.bf-header__icon--menu svg rect, .bf-header__icon--menu svg path']) {
    expect(await fill(page, selector), selector).toEqual([0, 255, 0]);
  }
  expect(await fill(page, '.bf-home__lettering svg path')).toEqual([0, 0, 255]);
  const close = await page.locator('.bf-header__icon--close svg path').first().evaluate((el) => getComputedStyle(el).stroke);
  expect(close).toBe('rgb(0, 255, 0)');

  await loginAsAdmin(page);
  await page.goto('/can-sakhara-story/?brodfest-site=invite');
  await page.addStyleTag({ content: TEST_THEME });
  expect(await fill(page, '.bf-story-banner svg path')).toEqual([0, 255, 0]);
  const field = await fill(page, '.bf-story-banner svg rect');
  expect(near(field, [33, 12, 7], 6), `banner field ${field}`).toBe(false);
});

test('the open menu is mostly the page colour, so the video only tints it', async ({ page }) => {
  await page.goto('/');
  await page.click('.bf-header__toggle');
  await expect(page.locator('body')).toHaveClass(/is-menu-open/);
  await page.waitForTimeout(600); // let the menu finish fading in
  const style = await page.locator('.bf-menu').evaluate((el) => {
    const s = getComputedStyle(el);
    return { bg: s.backgroundColor, blur: s.backdropFilter };
  });
  expect(style.blur).toContain('blur');
  // The page colour at 80%, over whatever plays behind.
  expect(style.bg).toMatch(/^(color\(srgb .* \/ 0\.8\)|rgba\(11, 3, 1, 0\.8\))$/);
});
