import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  // The specs mutate site-wide state; parallel workers against one WordPress
  // make one spec's "off" another spec's "on".
  workers: 1,
  // The test site is a single PHP process, so a page and its assets queue
  // behind each other; signing in and loading a page can pass 30s on CI.
  timeout: 60000,
  reporter: [['list'], ['json', { outputFile: 'test-results/results.json' }]],
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8881',
  },
});
