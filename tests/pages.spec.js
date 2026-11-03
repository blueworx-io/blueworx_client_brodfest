import { test, expect } from '@playwright/test';

test('the front page is the plugin Home page, not the WordPress blog roll', async ({ page }) => {
  const response = await page.goto('/');
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).toHaveClass(/brodfest-page-home/);
});

test('the plugin renders the whole document with its header and footer', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('header.brodfest-header')).toHaveCount(1);
  await expect(page.locator('main#content')).toHaveCount(1);
  await expect(page.locator('footer.brodfest-footer')).toHaveCount(1);
  // A theme's own wrapper would appear here if get_header() were being used.
  await expect(page.locator('#page, .wp-site-blocks')).toHaveCount(0);
});

test('the page has a title and loads only the plugin stylesheet', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/.+/);
  await expect(page.locator('link[rel="stylesheet"][id="brodfest-public-css"]')).toHaveCount(1);
  // Theme styles are swept off the plugin's pages; core's block styles are the
  // usual stowaway.
  await expect(page.locator('link[rel="stylesheet"][id="wp-block-library-css"]')).toHaveCount(0);
});
