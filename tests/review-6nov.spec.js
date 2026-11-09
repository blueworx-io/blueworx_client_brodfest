import { test, expect } from '@playwright/test';
import { skipIntro } from './helpers/wp.js';

// Mark's second review of the Save the Date site (Pastel, 6 Nov 2026) and
// Luke's notes with it.

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

test('the whole site, and the browser around it, is #0b0603', async ({ page }) => {
  await page.goto('/');
  const bg = await page.evaluate(() => [document.documentElement, document.body].map((el) => getComputedStyle(el).backgroundColor));
  expect(bg).toEqual(['rgb(11, 6, 3)', 'rgb(11, 6, 3)']);
  await expect(page.locator('meta[name="theme-color"]')).toHaveAttribute('content', '#0b0603');
});

test('the video box is the page colour while the video loads, never the pink stand-in', async ({ page }) => {
  await page.goto('/');
  expect(await page.locator('.bf-hero').evaluate((el) => getComputedStyle(el).backgroundColor)).toBe('rgb(11, 6, 3)');
});

test('Information opens with transport, then Where to Stay as a subtitle, then the map', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('h1')).toHaveText('INFORMATION');
  const subtitles = page.locator('h2.bf-area__name');
  await expect(subtitles.nth(0)).toHaveText('TRANSPORT FOR BRODFEST IBIZA');
  await expect(subtitles.nth(1)).toHaveText('WHERE TO STAY');
  await expect(page.locator('main')).toContainText('it’s essential that we provide transfers');
  const order = await page.evaluate(() => {
    const top = (el) => el.getBoundingClientRect().top;
    const [transport, stay] = document.querySelectorAll('h2.bf-area__name');
    return [top(document.querySelector('h1')), top(transport), top(stay), top(document.querySelector('.bf-map'))];
  });
  expect([...order].sort((a, b) => a - b)).toEqual(order);
});

test('Bless Ibiza has lost The Site', async ({ page }) => {
  await page.goto('/accommodation/');
  const card = page.locator('.bf-card').first();
  await expect(card.locator('.bf-card__name')).toHaveText('BLESS IBIZA');
  await expect(card.locator('.bf-card__text')).toContainText('BLESS Ibiza redefines');
  await expect(page.locator('main')).not.toContainText('The Site');
});

test('each area says how far away it is, on the line under its name', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('.bf-area__distance')).toHaveText(['(15 mins away)', '(20 mins away)', '(20 mins away)']);
  const gap = await page.locator('.bf-area').first().evaluate((area) => {
    const name = area.querySelector('.bf-area__name').getBoundingClientRect();
    const distance = area.querySelector('.bf-area__distance').getBoundingClientRect();
    return distance.top - name.bottom;
  });
  expect(gap).toBeGreaterThanOrEqual(0);
  expect(gap).toBeLessThan(30);
});

// A carousel's dots and swipe come in the moment its cards no longer fit
// the window, at whatever width that is (Luke, 6 Nov 2026).
for (const path of ['/accommodation/', '/recommendations/']) {
  test(`on desktop the cards on ${path} swipe, with dots, exactly when they no longer fit`, async ({ page }) => {
    for (const width of [1920, 1700, 1600, 1500, 1300, 1200, 1100, 1024]) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto(path);
      const rows = await page.locator('.bf-cards').evaluateAll((carousels) => carousels.map((carousel) => {
        const track = carousel.querySelector('[data-bf-track]');
        const cards = [...track.children];
        const gap = parseFloat(getComputedStyle(track).columnGap);
        const needed = cards.reduce((sum, card) => sum + card.getBoundingClientRect().width, 0) + gap * (cards.length - 1);
        const dots = carousel.querySelector('[data-bf-dots]');
        return {
          // 18 either side, at the desktop's three-quarter size.
          fits: needed <= document.documentElement.clientWidth - 27,
          scrolls: track.scrollWidth > track.clientWidth + 1,
          dots: !!dots && getComputedStyle(dots).display !== 'none',
          oneRow: new Set(cards.map((card) => Math.round(card.getBoundingClientRect().top))).size === 1,
        };
      }));
      for (const row of rows) {
        expect(row.oneRow, `${path} at ${width}`).toBe(true);
        expect(row.scrolls, `${path} at ${width}`).toBe(!row.fits);
        expect(row.dots, `${path} at ${width}`).toBe(!row.fits);
      }
    }
  });
}

// No box round a link after a tap or click, even where the page moves focus
// itself, as the menu does when it opens; keyboard users still get the ring.
test('a link only shows its focus box when the keyboard is in use', async ({ page }) => {
  await page.setViewportSize({ width: 767, height: 1000 });
  await page.goto('/');
  const outline = () => page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle);
  await page.locator('[data-bf-menu-toggle]').click();
  await expect(page.locator('.bf-menu__items a').first()).toBeFocused();
  expect(await outline()).toBe('none');
  await page.keyboard.press('Tab');
  expect(await outline()).toBe('solid');
});

// The same goes for the menu's white: Safari counts the focus the menu moves
// to its first link as keyboard focus, even after a tap, so that link showed
// white as if it were the current page (Luke, 9 Nov 2026). The browser is
// told to treat the link that way here, as Safari does by itself.
test('the menu\'s first link only turns white on focus when the keyboard is in use', async ({ page }) => {
  await page.setViewportSize({ width: 767, height: 1000 });
  await page.goto('/');
  await page.locator('[data-bf-menu-toggle]').click();
  const links = page.locator('.bf-menu__items a');
  await expect(links.first()).toBeFocused();

  const cdp = await page.context().newCDPSession(page);
  await cdp.send('DOM.enable');
  await cdp.send('CSS.enable');
  const { root } = await cdp.send('DOM.getDocument');
  const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: '.bf-menu__items a' });
  await cdp.send('CSS.forcePseudoState', { nodeId, forcedPseudoClasses: ['focus-visible'] });

  const colour = (i) => links.nth(i).evaluate((el) => {
    el.style.transition = 'none';
    return getComputedStyle(el).color;
  });
  const white = 'rgb(255, 255, 255)';
  expect(await colour(1)).not.toBe(white);
  expect(await colour(0)).toBe(await colour(1));

  await page.keyboard.press('Shift');
  expect(await colour(0)).toBe(white);
});

// A general 25% reduction on desktop only (Luke, 6 Nov 2026): every Figma size
// is drawn at three-quarters from 1024 up; the mobile design is untouched.
test('desktop is three-quarters of the Figma size, and mobile stays at full size', async ({ page }) => {
  const sizes = async () => page.evaluate(() => ({
    title: getComputedStyle(document.querySelector('.bf-title')).fontSize,
    logo: Math.round(document.querySelector('.bf-header__logo').getBoundingClientRect().width * 10) / 10,
  }));
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/celebrate/');
  expect(await sizes()).toEqual({ title: '37.5px', logo: 258 });
  await page.setViewportSize({ width: 900, height: 1080 });
  await page.goto('/celebrate/');
  expect((await sizes()).title).toBe('37px');
});

// One header from tablets to the widest window (Luke, 6 Nov 2026): it only
// changes where phones take over, below 768.
test('the header is the same from 768 up, with one change below it', async ({ page }) => {
  const header = async (width) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    return page.evaluate(() => {
      const box = (sel) => {
        const b = document.querySelector(sel).getBoundingClientRect();
        return [b.width, b.height, b.top].map((v) => Math.round(v * 10) / 10);
      };
      const W = document.documentElement.clientWidth;
      const pill = document.querySelector('.bf-header .bf-pill').getBoundingClientRect();
      return {
        header: box('.bf-header').slice(1),
        logo: box('.bf-header__logo'),
        toggle: [...box('.bf-header__toggle'), Math.round(document.querySelector('.bf-header__toggle').getBoundingClientRect().left)],
        pill: [...box('.bf-header .bf-pill'), Math.round(W - pill.right)],
      };
    });
  };
  const at1024 = await header(1024);
  for (const width of [768, 900, 1023]) expect(await header(width), `at ${width}`).toEqual(at1024);
  expect((await header(767)).header[0]).toBe(207);
});

// The home video has two sizes, as the header does: the square one below 768
// and the wide one, at the desktop's size, from 768 up (Luke, 6 Nov 2026).
test('the home video is one size from 768 up, and the square one below', async ({ page }) => {
  const hero = async (width) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/');
    return page.evaluate(() => {
      const b = document.querySelector('.bf-hero').getBoundingClientRect();
      const play = document.querySelector('[data-bf-play]');
      play.hidden = false;
      const p = play.getBoundingClientRect();
      return {
        height: b.height,
        play: [Math.round(p.width * 10) / 10, Math.round(p.height * 10) / 10],
        wide: document.querySelector('[data-bf-video]').currentSrc.includes('1920X840'),
      };
    });
  };
  const desktop = await hero(1024);
  expect(desktop.height).toBe(630);
  expect(desktop.wide).toBe(true);
  for (const width of [768, 900, 1023]) expect(await hero(width), `at ${width}`).toEqual(desktop);
  const phone = await hero(767);
  expect(phone.height).toBe(767);
  expect(phone.wide).toBe(false);
});

// The block under the video follows suit (Luke, 9 Nov 2026): the desktop one
// from 768 up, the mobile one below, with no tablet size in between.
test('the Save the Date block is one size from 768 up, and the mobile one below', async ({ page }) => {
  const block = async (width) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/');
    return page.evaluate(() => {
      const size = (sel) => {
        const b = document.querySelector(sel).getBoundingClientRect();
        return [b.width, b.height].map((v) => Math.round(v * 10) / 10);
      };
      const top = (sel) => document.querySelector(sel).getBoundingClientRect().top + scrollY;
      return {
        date: size('.bf-home__date'),
        lettering: size('.bf-home__lettering'),
        note: getComputedStyle(document.querySelector('.bf-home__note')).fontSize,
        gaps: ['.bf-home__lettering', '.bf-home__note'].map((sel) => Math.round((top(sel) - top('.bf-home__date')) * 10) / 10),
      };
    });
  };
  // The date is Figma's 412 wide at the block's 0.6 size, and 309 on mobile.
  const desktop = await block(1024);
  expect(desktop.date[0]).toBe(247.2);
  for (const width of [768, 900, 1023]) expect(await block(width), `at ${width}`).toEqual(desktop);
  expect((await block(767)).date[0]).toBe(309);
});

// The client's photos (6 Nov 2026): each area's banner, wide on desktop and
// its own cut on mobile, and each hotel's card. AMA's is still to come.
test('each area banner shows its photo, the desktop cut on desktop and the mobile one on mobile', async ({ page }) => {
  for (const [width, cut] of [[1920, 'desktop'], [767, 'mobile']]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/accommodation/');
    const banners = page.locator('.bf-area .bf-banner img');
    await expect(banners).toHaveCount(3);
    for (const [i, area] of ['bossa', 'talamanca', 'cala-llonga'].entries()) {
      const img = banners.nth(i);
      await img.scrollIntoViewIfNeeded();
      await expect.poll(() => img.evaluate((el) => el.complete && el.naturalWidth > 0 && el.currentSrc)).toContain(`areas/${area}-${cut}.jpg`);
    }
    await expect(page.locator('.bf-area .bf-banner .bf-placeholder-label')).toHaveCount(0);
  }
});

test('each hotel card shows its photo', async ({ page }) => {
  await page.goto('/accommodation/');
  const photos = await page.locator('.bf-card').evaluateAll((cards) => cards.map((card) => {
    const img = card.querySelector('.bf-card__image img');
    return [card.querySelector('.bf-card__name').textContent.trim(), img ? img.getAttribute('src').split('/').pop() : null];
  }));
  expect(photos).toEqual([
    ['BLESS IBIZA', 'bless.jpg'], ['TORRE DEL MAR', 'torre-del-mar.jpg'], ['AMA IBIZA', 'ama.jpg'],
    ['NOBU HOTEL', 'nobu.jpg'], ['HOTEL SIMBAD', 'simbad.jpg'], ['HOTEL LUX ISLA', 'lux-isla.jpg'],
    ['HYDE IBIZA', 'hyde.jpg'], ['MONDRIAN', 'mondrian.jpg'],
  ]);
});
