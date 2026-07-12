// End-to-end smoke test: boots the real HTTP API (php -S) against a temp
// copy of webGame/, drives a full hot-seat game through the browser (lobby
// -> start -> planning -> an acquire action), and asserts the resulting log
// line shows up. This is the one thing the card-sizing harness and the PHP
// test suites can't catch on their own: that the whole stack — frontend JS,
// HTTP API, and the PHP engine — actually boots and takes a turn together.
import { test, expect } from '@playwright/test';
import { spawn } from 'node:child_process';
import { mkdtempSync, cpSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const webGameDir = fileURLToPath(new URL('..', import.meta.url)); // webGame/
let PORT, proc, tmpRoot;

test.beforeAll(async ({}, testInfo) => {
  // One php -S per Playwright worker — desktop and mobile projects run this
  // spec concurrently, so a fixed port would race two servers for one socket.
  PORT = 4174 + testInfo.parallelIndex;
  tmpRoot = mkdtempSync(path.join(tmpdir(), 'sar-smoke-'));
  cpSync(webGameDir, tmpRoot, { recursive: true });
  proc = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', tmpRoot], { stdio: 'ignore' });
  for (let i = 0; i < 50; i++) {
    try {
      const res = await fetch(`http://127.0.0.1:${PORT}/index.html`);
      if (res.ok) return;
    } catch { /* server still starting */ }
    await new Promise(r => setTimeout(r, 100));
  }
  throw new Error('php -S never came up for the smoke test');
});

test.afterAll(() => {
  proc?.kill();
  if (tmpRoot) rmSync(tmpRoot, { recursive: true, force: true });
});

test('a hot-seat game boots, plays through planning, and an acquire action logs', async ({ page }) => {
  await page.goto(`http://127.0.0.1:${PORT}/index.html`);

  await page.getByRole('button', { name: 'Hot-seat (1 device)' }).click();
  await page.getByPlaceholder('Player 1').fill('Ada');
  await page.getByPlaceholder('Player 2').fill('Bo');
  await page.getByRole('button', { name: 'Start hot-seat setup' }).click();
  await page.getByRole('button', { name: /Start game/ }).click();

  // Planning Phase: seat 0 (Ada) is always asked to ready up first; wait for
  // the bar to hand off to Bo before readying them too, so the two ready
  // actions can't race each other. Round 1 always starts at the hand limit
  // (3 starting cards + 2 drawn on Planning reveal = 5) so each player drops
  // one card first, freeing a slot for the Basic Shop purchase below.
  await page.locator('#hand-area .card').first().click();
  await page.getByRole('button', { name: /^Ready/ }).click();
  await expect(page.locator('#action-bar')).toContainText('Bo');
  await page.locator('#hand-area .card').first().click();
  await page.getByRole('button', { name: /^Ready/ }).click();
  await expect(page.locator('#action-bar')).toContainText('command turn');

  // Action Phase: buy a card from the always-available Basic supply.
  await page.getByRole('button', { name: '🛒 Basic shop' }).click();
  await page.locator('.picker-cards .card').first().click();

  await expect(page.locator('#log-body')).toContainText('from the Basic supply');
});
