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
    // On Linux, Chromium hints letters to the pixel grid, so a long line
    // comes out one to two percent wider than on Windows or a Mac, and a
    // paragraph can take an extra line. The design geometry suite measures
    // against Figma to 2px, so CI turns hinting off and lays text out the way
    // the other platforms do.
    launchOptions: {
      args: process.platform === 'linux' ? ['--font-render-hinting=none'] : [],
    },
  },
});
