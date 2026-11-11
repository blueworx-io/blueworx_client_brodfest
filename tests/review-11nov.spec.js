import { test, expect } from '@playwright/test';
import { skipIntro } from './helpers/wp.js';

// Mark's pass over the RSVP form (Pastel, 11 Nov 2026).

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
  await page.goto('/rsvp/');
});

test('the plus-one is called a partner throughout the form', async ({ page }) => {
  await expect(page.locator('.bf-radios legend').nth(1)).toHaveText('Will you attend with a partner?');
  await page.locator('input[name="with_guest"][value="yes"]').check();
  await expect(page.locator('#bf-guest_name')).toHaveAttribute('placeholder', 'Partner Full Name');
  await expect(page.locator('#bf-guest_phone')).toHaveAttribute('placeholder', 'Partner’s Mobile phone number');
  await expect(page.locator('#bf-guest_email')).toHaveAttribute('placeholder', 'Partner’s Email address');
  await expect(page.locator('#bf-guest_dietary option').first()).toHaveText('Partner’s Dietary requirements');
  await expect(page.locator('#bf-guest_allergies')).toHaveAttribute('placeholder', 'Partner’s Allergies');
  await expect(page.locator('.bf-check span')).toHaveText('Click here if you or your partner have any accessibility requirements');
  await expect(page.getByLabel('Partner Full Name')).toBeVisible();
});

test('a line under the accessibility tick says who to email about more guests', async ({ page }) => {
  const note = page.locator('.bf-form__note');
  await expect(note).toHaveText('If you are attending with more than one additional guest please email our guest liaison team at info@brodfest.net');
  const link = note.locator('a');
  await expect(link).toHaveAttribute('href', 'mailto:info@brodfest.net');
  expect(Number(await link.evaluate((a) => getComputedStyle(a).fontWeight))).toBeGreaterThanOrEqual(600);
  const order = await page.evaluate(() => {
    const top = (sel) => document.querySelector(sel).getBoundingClientRect().top;
    return [top('.bf-check'), top('.bf-form__note'), top('.bf-form .bf-button--send')];
  });
  expect(order).toEqual([...order].sort((a, b) => a - b));
});

test('the accessibility tick sits closer to the fields above it', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.locator('input[name="with_guest"][value="yes"]').check();
  const gap = await page.evaluate(() => document.querySelector('.bf-check').getBoundingClientRect().top - document.querySelector('#bf-guest_allergies').getBoundingClientRect().bottom);
  // Was 74 on screen (Figma's 99 at three-quarters).
  expect(gap).toBeGreaterThan(40);
  expect(gap).toBeLessThan(56);
});
