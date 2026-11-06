import { test, expect } from '@playwright/test';

// The site's motion (Luke, 6 Nov 2026): each section eases in once as it
// scrolls into view, nothing loops, and anyone who asks for less motion gets
// the page still and in place. animations.js does the work with GSAP.

// A returning visitor, so the home page's opening does not cover it.
async function visit(page, path, { width = 1440, reduce = false } = {}) {
  await page.setViewportSize({ width, height: 900 });
  if (reduce) await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.addInitScript(() => window.localStorage.setItem('brodfest-entered', '1'));
  await page.goto(path);
}

const opacity = (locator) => locator.evaluate((el) => Number(getComputedStyle(el).opacity));
const transform = (locator) => locator.evaluate((el) => getComputedStyle(el).transform);

test('a page title eases in, its wide letter spacing settling into place', async ({ page }) => {
  await visit(page, '/celebrate/');
  const title = page.locator('main .bf-title').first();
  await expect.poll(() => opacity(title), { timeout: 4000 }).toBe(1);
  const spacing = await title.evaluate((el) => [parseFloat(getComputedStyle(el).letterSpacing), parseFloat(getComputedStyle(el).fontSize)]);
  expect(spacing[0]).toBeCloseTo(spacing[1] * 0.54, 1);
});

test('a section further down waits out of sight, then eases in as it scrolls into view', async ({ page }) => {
  await visit(page, '/accommodation/');
  // The photo's frame waits above its slot, and slides down into it.
  const frame = page.locator('.bf-area .bf-banner picture').last();
  // Well below the window, so not yet revealed.
  await page.waitForTimeout(1500);
  expect(await transform(frame)).not.toMatch(/^(none|matrix\(1, 0, 0, 1, 0, 0\))$/);
  await frame.scrollIntoViewIfNeeded();
  // Early on, the curtain's edge is still near the top: the bottom of the
  // slot shows no photo yet.
  await page.waitForTimeout(200);
  const hidden = await frame.evaluate((el) => {
    const box = el.parentElement.getBoundingClientRect();
    return document.elementFromPoint(box.left + box.width / 2, box.bottom - 4)?.tagName !== 'IMG';
  });
  expect(hidden).toBe(true);
  await expect.poll(() => transform(frame), { timeout: 4000 }).toMatch(/^(none|matrix\(1, 0, 0, 1, 0, 0\))$/);
});

// Smoothness (Luke, 6 Nov 2026): everything moves by sliding, fading or
// scaling, which the graphics card does on its own, never by re-spacing text
// or re-cutting a shape, which makes the browser redo the page every frame.
const watch = (locator, prop, ms) => locator.evaluate((el, [prop, ms]) => new Promise((done) => {
  const seen = new Set();
  const start = performance.now();
  const look = () => {
    seen.add(getComputedStyle(el)[prop]);
    if (performance.now() - start < ms) requestAnimationFrame(look); else done([...seen]);
  };
  look();
}), [prop, ms]);

test('titles and the menu settle without their letters being re-spaced', async ({ page }) => {
  await visit(page, '/celebrate/', { width: 767 });
  expect(await watch(page.locator('main .bf-title').first(), 'letterSpacing', 1500)).toHaveLength(1);
  await page.locator('[data-bf-menu-toggle]').click();
  expect(await watch(page.locator('.bf-menu__items a').last(), 'letterSpacing', 1200)).toHaveLength(1);
});

// Luke, 6 Nov 2026: titles looked to start wide and jump to narrow.
test('a title\'s letters close up gradually, with no jump at the start or the end', async ({ page }) => {
  await visit(page, '/accommodation/');
  await page.waitForTimeout(1500);
  const track = await page.evaluate(() => new Promise((done) => {
    const el = document.querySelectorAll('.bf-area__name')[2];
    window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 500);
    // The first letter's left edge, each frame, whether split or put back.
    const left = () => el.querySelector('.bf-char').getBoundingClientRect().left;
    const seen = [];
    const start = performance.now();
    const look = () => {
      seen.push([performance.now(), left()]);
      if (performance.now() - start < 2800) requestAnimationFrame(look); else done(seen);
    };
    look();
  }));
  // As speed, so one slow frame is not mistaken for a jump: a quarter of a
  // pixel a millisecond is about 4 a frame. Putting the letters back together
  // at the end used to move them 100 in one frame.
  const speeds = track.slice(1).map(([t, x], i) => Math.abs(x - track[i][1]) / Math.max(t - track[i][0], 1000 / 60));
  expect(Math.max(...speeds)).toBeLessThan(0.25);
  expect(track[0][1]).not.toBeCloseTo(track[track.length - 1][1], 0);
});

test('the area photos are revealed without re-cutting their shape', async ({ page }) => {
  await visit(page, '/accommodation/');
  const banner = page.locator('.bf-area .bf-banner').first();
  await banner.scrollIntoViewIfNeeded();
  expect(await watch(banner, 'clipPath', 1800)).toEqual(['none']);
});

test('scrolling the whole Information page never makes the browser redo its layout frame after frame', async ({ page, browserName }) => {
  test.skip(browserName !== 'chromium', 'Layout counts come from Chrome.');
  await visit(page, '/accommodation/');
  await page.waitForTimeout(2000);
  const cdp = await page.context().newCDPSession(page);
  await cdp.send('Performance.enable');
  const layouts = async () => (await cdp.send('Performance.getMetrics')).metrics.find((m) => m.name === 'LayoutCount').value;
  const before = await layouts();
  const height = await page.evaluate(() => document.documentElement.scrollHeight - window.innerHeight);
  for (let y = 0; y < height; y += 120) {
    await page.mouse.wheel(0, 120);
    await page.waitForTimeout(30);
  }
  await page.waitForTimeout(2000);
  // A section measures itself once as it starts and once as it finishes, and
  // puts its text back; re-spacing text every frame gave over 200.
  expect((await layouts()) - before).toBeLessThan(80);
});

test('hotel cards rise in, one after another, and end exactly in place', async ({ page }) => {
  await visit(page, '/accommodation/');
  const cards = page.locator('.bf-area').first().locator('.bf-card');
  await cards.first().scrollIntoViewIfNeeded();
  for (const i of [0, 1, 2]) {
    await expect.poll(() => opacity(cards.nth(i)), { timeout: 4000 }).toBe(1);
    await expect.poll(() => transform(cards.nth(i)), { timeout: 4000 }).toMatch(/^(none|matrix\(1, 0, 0, 1, 0, 0\))$/);
  }
});

test('body copy rises in line by line and is put back as plain text afterwards', async ({ page }) => {
  await visit(page, '/celebrate/');
  const body = page.locator('main .bf-text .bf-body').first();
  await expect.poll(() => body.evaluate((el) => el.querySelector('[data-bf-line], .bf-line') === null && Number(getComputedStyle(el).opacity) === 1), { timeout: 5000 }).toBe(true);
  await expect(body).toContainText('Ibiza is calling.');
});

test('on desktop the area photos drift slower than the page; phones keep them still', async ({ page }) => {
  await visit(page, '/accommodation/');
  const img = page.locator('.bf-area .bf-banner img').first();
  await img.scrollIntoViewIfNeeded();
  await page.waitForTimeout(1500);
  const before = await transform(img);
  await page.mouse.wheel(0, 300);
  await expect.poll(() => transform(img), { timeout: 3000 }).not.toBe(before);

  await visit(page, '/accommodation/', { width: 767 });
  const phone = page.locator('.bf-area .bf-banner img').first();
  await phone.scrollIntoViewIfNeeded();
  // Past its arrival, which settles it from a slight zoom.
  await page.waitForTimeout(2600);
  const still = await transform(phone);
  await page.mouse.wheel(0, 300);
  await page.waitForTimeout(600);
  expect(await transform(phone)).toBe(still);
});

test('the open menu brings its links in one after another', async ({ page }) => {
  await visit(page, '/celebrate/', { width: 767 });
  await page.locator('[data-bf-menu-toggle]').click();
  const links = page.locator('.bf-menu__items a');
  await expect.poll(() => opacity(links.last()), { timeout: 3000 }).toBe(1);
});

test('the pages arrive with a soft fade, and never stay hidden if the script fails', async ({ page }) => {
  await page.route('**/assets/js/public.js*', (route) => route.abort());
  await visit(page, '/celebrate/');
  // The 3 second fallback, then its fade.
  await expect.poll(() => opacity(page.locator('main')), { timeout: 6000 }).toBe(1);
  await expect(page.locator('main .bf-title').first()).toBeVisible();
});

test('a visitor who asks for less motion gets every section still and in place at once', async ({ page }) => {
  await visit(page, '/accommodation/', { reduce: true });
  await expect(page.locator('html')).not.toHaveClass(/bf-motion/);
  const banner = page.locator('.bf-area .bf-banner').last();
  expect(await banner.evaluate((el) => getComputedStyle(el).clipPath)).toBe('none');
  const card = page.locator('.bf-card').last();
  expect(await opacity(card)).toBe(1);
  expect(await transform(card)).toBe('none');
});

// Luke, 6 Nov 2026: the RSVP pill arrived late. Its own hover transition was
// smoothing every step of its arrival again, so it trailed the header.
test('the header pill arrives exactly with the header, never trailing it', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.addInitScript(() => {
    window.localStorage.setItem('brodfest-entered', '1');
    window.__trail = [];
    const look = () => {
      const pill = document.querySelector('.bf-header .bf-pill');
      if (pill?.style.transform) {
        const set = new DOMMatrix(pill.style.transform).f;
        const shown = new DOMMatrix(getComputedStyle(pill).transform).f;
        window.__trail.push(Math.abs(set - shown));
      }
      if (window.__trail.length < 120) requestAnimationFrame(look);
    };
    requestAnimationFrame(look);
  });
  await page.goto('/celebrate/');
  await page.waitForTimeout(2000);
  const trail = await page.evaluate(() => window.__trail);
  expect(trail.length).toBeGreaterThan(10);
  expect(Math.max(...trail)).toBeLessThan(0.5);
});
