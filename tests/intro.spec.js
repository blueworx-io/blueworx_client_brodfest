import { test, expect } from '@playwright/test';

test('the intro plays on the first visit only and ends on Enter', async ({ page }) => {
  await page.goto('/');
  const enter = page.locator('[data-bf-enter]');
  await expect(enter).toBeVisible();

  await enter.click();
  await expect(page.locator('.bf-intro')).toHaveCount(0);

  await page.reload();
  await expect(page.locator('.bf-intro')).toHaveCount(0);
  await expect(page.locator('.bf-home__date')).toBeVisible();
});

test('the intro is only on the home page', async ({ page }) => {
  await page.goto('/celebrate/');
  await expect(page.locator('.bf-intro')).toHaveCount(0);
});
