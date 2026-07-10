import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: 'webGame/tests',
  timeout: 30_000,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['list'], ['github']] : 'list',
  use: {
    baseURL: 'http://127.0.0.1:4173',
  },
  webServer: {
    command: 'node webGame/tests/static-server.mjs 4173',
    url: 'http://127.0.0.1:4173/tests/harness.html',
    reuseExistingServer: !process.env.CI,
  },
  projects: [
    // The game targets desktops and phones with different card widths;
    // cards must be uniformly sized in both layouts.
    { name: 'desktop', use: { browserName: 'chromium', viewport: { width: 1280, height: 800 } } },
    { name: 'mobile', use: { browserName: 'chromium', viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true } },
  ],
});
