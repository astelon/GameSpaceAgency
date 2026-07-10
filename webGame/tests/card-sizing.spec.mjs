// All cards must render at the same size wherever the player is shown a
// choice of cards: the hand, the card market, and the mission offer.
// The harness page renders the full card database into the real game markup
// with the production stylesheet, then we measure the rendered boxes.
import { test, expect } from '@playwright/test';

const ZONES = {
  hand: '#hand-area .card',
  market: '#market-cards .card',
  missions: '#mission-cards .card',
};

// Sub-pixel layout differences are fine; anything visible is not.
const TOLERANCE = 0.5;

// On phones the layout is tabbed (js/main.js sets body[data-mtab]) and only
// the active tab's column is visible, so select the tab a zone lives in
// before measuring it — like a player would.
async function measure(page, zone, selector) {
  await page.evaluate(tab => { document.body.dataset.mtab = tab; }, zone === 'hand' ? 'map' : 'deals');
  return page.$$eval(selector, els => els.map(el => {
    const r = el.getBoundingClientRect();
    return { id: el.querySelector('.c-name')?.textContent ?? '?', w: r.width, h: r.height };
  }));
}

test.beforeEach(async ({ page }) => {
  await page.goto('/tests/harness.html');
  await page.waitForFunction(() => window.__ready === true);
});

for (const [zone, selector] of Object.entries(ZONES)) {
  test(`every card in the ${zone} has identical width and height`, async ({ page }) => {
    const sizes = await measure(page, zone, selector);
    expect(sizes.length).toBeGreaterThan(1);
    const first = sizes[0];
    expect(first.w).toBeGreaterThan(50);
    expect(first.h).toBeGreaterThan(50);
    for (const s of sizes) {
      expect(Math.abs(s.w - first.w), `${s.id} width ${s.w} != ${first.w}`).toBeLessThanOrEqual(TOLERANCE);
      expect(Math.abs(s.h - first.h), `${s.id} height ${s.h} != ${first.h}`).toBeLessThanOrEqual(TOLERANCE);
    }
  });
}

test('hand, market and mission cards are all the same size as each other', async ({ page }) => {
  const ref = (await measure(page, 'hand', ZONES.hand))[0];
  for (const [zone, selector] of Object.entries(ZONES)) {
    for (const s of await measure(page, zone, selector)) {
      expect(Math.abs(s.w - ref.w), `${s.id} width ${s.w} != hand ${ref.w}`).toBeLessThanOrEqual(TOLERANCE);
      expect(Math.abs(s.h - ref.h), `${s.id} height ${s.h} != hand ${ref.h}`).toBeLessThanOrEqual(TOLERANCE);
    }
  }
});
