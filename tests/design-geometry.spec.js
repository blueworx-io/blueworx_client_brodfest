import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

// Every page against the Figma file: at its two artboard widths, 1920 and
// 767, and at the widths where the design moves without changing size.
//
// fixtures/design-geometry.json holds, for each frame, the Figma box of every
// text layer, component and container, paired with the element on the page
// that shows it. Boxes must land within 1px, text within 2px (the browser and
// Figma draw letters slightly differently). A row with "expect" is a
// difference Luke signed off — a whole-number text size, a corrected spelling,
// Figma not yet re-wrapped — and is held to the box in "expect", with the
// reason in "why", so it still cannot drift.
//
// How the pinned rows were sorted: each row more than 2px from Figma was given
// a "why" only if it fitted one narrow rule — its Figma font size is one of
// the agreed whole-number changes; its Figma text carries a corrected
// spelling; it is a BlueWorx placeholder rather than the designer's; it is the
// footer of an artboard drawn taller than the page; on a 767 frame whose text
// Figma has not re-wrapped to the 96 margins, it is off only up or down, and
// the drift steps by exactly one line at each re-wrapped paragraph; or it is
// SEND, set in Futura per the design comment. Anything else had to be fixed.
// When Figma is re-wrapped (opened with the fonts), re-export the frames and
// drop the "re-wrapped" pins.

const ROWS = JSON.parse(readFileSync(new URL('./fixtures/design-geometry.json', import.meta.url), 'utf8'))
  .map((r, row) => ({ ...r, row, figma: r.expect || r.figma }));
const TOLERANCE = { box: 1, text: 2 };

// The desktop design keeps Figma's sizes from 1600 up, centred; the mobile
// design keeps Figma's sizes, centred. Full-width pieces span the window. The
// menu button and RSVP pill keep at least 48px from the edges (the mobile
// header's margin), so below 1820 the pill moves in, and below 1630 the
// button and the menu's items do too. A 390 phone is told it is 767 wide, so
// it lays out the mobile design exactly as at 767 (834 tall, so the zoomed
// screen is the artboard's 1640). Laptops (1024 to 1599) keep Figma's
// heights but not its widths; they are checked further down.
const PHONE = 390;
const WIDTHS = { 1920: [1920, 2560, 1600], 767: [767, 1023, PHONE] };
const LAPTOPS = [1440, 1024];
const PAGE_H = Object.fromEntries(ROWS.filter((r) => r.node === 'Page').map((r) => [r.frame, r.figma[3]]));

// From tablets up the header is 170 tall, not the mobile design's 207 (the
// client asked, 5 Nov): on the 767 design at a tablet width the logo is a
// fifth smaller and centred in it, the header's other contents rise 33.5,
// and everything under it the whole 37 — except the footer of a page that
// fits the screen, which stays at the bottom, and the open menu's panel,
// which grows to fill.
function tablet(r, [x, y, w, h]) {
  const d = 37;
  const fits = (PAGE_H[r.frame] ?? 1640) <= 1640;
  if (r.node === 'Page') return [x, y, w, fits ? h : h - d];
  if (r.node === 'Header') return [x, y, w, 170];
  if (r.node === 'Brand/Logo' && /bf-header/.test(r.sel)) return [252.7, 53.4, 261.6, 63.2];
  if (y < 207) return [x, y - 33.5, w, h];
  if (r.node === 'Menu/Panel') return [x, y - d, w, h + d];
  if (fits && /bf-footer/.test(r.sel)) return [x, y, w, h];
  return [x, y - d, w, h];
}

function expected(r, width) {
  const V = width === PHONE ? 767 : width;
  if (r.width === 767) {
    const [x, y, w, h] = V >= 768 ? tablet(r, r.figma) : r.figma;
    return w >= 760 ? [0, y, V, h] : [x + Math.max(0, (V - 767) / 2), y, w, h];
  }
  const [x, y, w, h] = r.figma;
  if (w >= 1900) return [0, y, V, h];
  const pill = x >= 1600 && y < 170 ? Math.max(0, Math.min(99, -61 - (V - 1920) / 2)) : 0;
  const left = x < 300 && (y < 170 || r.mode === 'menu') ? Math.max(0, 48 - (193 - 960 + V / 2)) : 0;
  return [x - pill + left - 960 + V / 2, y, w, h];
}
const STILL = '#wpadminbar{display:none!important} html{margin-top:0!important} :root{--wp-admin--admin-bar--height:0px!important} *,*::before,*::after{transition:none!important;animation:none!important}';

const frames = [...new Set(ROWS.map((r) => r.frame))];

// Loads a frame's page as Figma draws it: still, fonts in, the menu open or
// the RSVP guest fields showing where the frame has them.
async function open(page, frame, path, mode) {
  await skipIntro(page);
  if (path.includes('brodfest-site=')) await loginAsAdmin(page);
  await page.goto(path, { waitUntil: 'networkidle' });
  await page.addStyleTag({ content: STILL });
  await page.evaluate(() => document.fonts.ready);
  if (mode === 'menu') {
    await page.click('.bf-header__toggle');
    await expect(page.locator('body')).toHaveClass(/is-menu-open/);
    await page.evaluate(() => new Promise((done) => requestAnimationFrame(() => requestAnimationFrame(done))));
  }
  if (frame.includes('rsvp')) await page.locator('input[name="with_guest"][value="yes"]').check();
}

for (const frame of frames) for (const V of WIDTHS[frame.endsWith('_767') ? 767 : 1920]) {
  const rows = ROWS.filter((r) => r.frame === frame).map((r) => ({ ...r, figma: expected(r, V).map((v) => Math.round(v * 10) / 10) }));
  const { path, width, mode } = rows[0];

  test(`${frame} matches the design at ${V === PHONE ? 'a 390 phone' : V}`, async ({ page: desktop, browser }) => {
    let page = desktop;
    if (V === PHONE) {
      page = await (await browser.newContext({ baseURL: test.info().project.use.baseURL, viewport: { width: PHONE, height: 834 }, isMobile: true, hasTouch: true, deviceScaleFactor: 3 })).newPage();
    } else {
      // As tall as the artboard, so a short page's footer sits where Figma's does.
      await page.setViewportSize({ width: V, height: width === 1920 ? 2529 : 1640 });
    }
    await open(page, frame, path, mode);

    const measured = await page.evaluate((rows) => rows.map((r) => {
      const el = document.querySelector(r.sel);
      if (!el) return null;
      const f = (v) => Math.round(v * 10) / 10;
      const e = el.getBoundingClientRect();
      const box = [f(e.x), f(e.y + scrollY), f(e.width), f(e.height)];
      if (r.kind !== 'text') return box;
      // Text: the closer of the element's box and the box of its words alone
      // (less the letter spacing after the last letter), centred vertically
      // when the element is taller than the Figma layer, as for a label
      // centred in a button.
      const s = getComputedStyle(el);
      // A label wrapping its radio or checkbox, or a button carrying hidden
      // screen-reader words: the visible words only.
      const range = document.createRange();
      if (el.querySelector('input, .sr-only')) {
        const words = [...el.childNodes].filter((n) => n.nodeType === 3 && n.textContent.trim());
        range.setStart(words[0], 0);
        range.setEnd(words[words.length - 1], words[words.length - 1].textContent.length);
      } else {
        range.selectNodeContents(el);
      }
      const t = range.getBoundingClientRect();
      const ls = parseFloat(s.letterSpacing) || 0;
      const lines = Math.max(1, Math.round(e.height / (parseFloat(s.lineHeight) || e.height)));
      let text = [f(t.x), box[1], f(t.width - (lines === 1 ? ls : 0)), box[3]];
      // Figma's fixed-width text boxes are wider than their centred words:
      // there the centres have to meet, so the words are reported as a box
      // of the Figma width around their centre.
      const centred = s.textAlign === 'center' || (/flex/.test(s.display) && s.justifyContent === 'center');
      if (centred && r.figma[2] > text[2] + 2) {
        const centre = text[0] + text[2] / 2;
        text = [f(centre - r.figma[2] / 2), text[1], r.figma[2], text[3]];
      }
      // Chosen on position across and width, so a drift down the page does not
      // decide which of the two is meant.
      const worst = (a) => Math.max(Math.abs(a[0] - r.figma[0]), Math.abs(a[2] - r.figma[2]));
      let best = worst(box) < worst(text) ? box : text;
      // Only a label centred inside a taller control (a button, a pill) is
      // compared by its centre; any other text keeps its own height, so a
      // line that grows taller is caught on its own row.
      const control = /flex|grid/.test(s.display) && (s.alignItems === 'center' || s.justifyContent === 'center');
      if (control && best[3] > r.figma[3] + 2) best = [best[0], f(best[1] + (best[3] - r.figma[3]) / 2), best[2], r.figma[3]];
      return best;
    }), rows);

    const off = [];
    rows.forEach((r, i) => {
      const tolerance = TOLERANCE[r.kind] ?? TOLERANCE.box;
      const got = measured[i];
      const label = `${r.node}${r.text ? ` "${r.text}"` : ''}`;
      if (!got) return off.push(`${label}: not on the page (${r.sel})`);
      const d = got.map((v, k) => Math.round((v - r.figma[k]) * 10) / 10);
      if (d.some((v) => Math.abs(v) > tolerance)) off.push(`${label}: figma ${r.figma.join(',')} site ${got.join(',')} (off ${d.join(',')}) [row ${r.row}]`);
    });
    if (page !== desktop) await page.context().close();
    expect(off, off.join('\n')).toEqual([]);
  });
}

// On a laptop the desktop design keeps Figma's heights and gives way only in
// width: every component is exactly Figma's height, a container (whose text
// may wrap onto another line) at least that, full-width pieces span the
// window, and nothing else is wider than 1564 or reaches past the window,
// except the hotel cards waiting to be swiped into view.
for (const frame of frames.filter((f) => f.endsWith('_1920'))) for (const V of LAPTOPS) {
  const rows = ROWS.filter((r) => r.frame === frame);
  const { path, mode } = rows[0];

  test(`${frame} keeps Figma's heights at ${V}`, async ({ page }) => {
    await page.setViewportSize({ width: V, height: 2529 });
    await open(page, frame, path, mode);

    const measured = await page.evaluate((rows) => rows.map((r) => {
      const el = document.querySelector(r.sel);
      if (!el) return null;
      const e = el.getBoundingClientRect();
      return { x: e.x, w: e.width, h: e.height, swiped: !!el.parentElement?.closest('[data-bf-track]') };
    }), rows);

    const off = [];
    rows.forEach((r, i) => {
      const got = measured[i];
      const label = `${r.node}${r.text ? ` "${r.text}"` : ''} [row ${r.row}]`;
      if (!got) return off.push(`${label}: not on the page (${r.sel})`);
      const [, , fw, fh] = r.figma;
      if (r.kind !== 'text') {
        if (r.container ? got.h < fh - 1 : Math.abs(got.h - fh) > 1) off.push(`${label}: ${Math.round(got.h)} tall, Figma ${fh}`);
      }
      if (fw >= 1900) {
        if (Math.abs(got.w - V) > 1) off.push(`${label}: ${Math.round(got.w)} wide in a ${V} window`);
      } else if (!got.swiped && (got.x < -1 || got.x + got.w > V + 1 || got.w > 1565)) {
        off.push(`${label}: spans ${Math.round(got.x)} to ${Math.round(got.x + got.w)} in a ${V} window`);
      }
    });
    expect(off, off.join('\n')).toEqual([]);
  });
}

// Placeholder copy carries Figma's desktop line breaks; on a phone the text
// wraps on its own, so the breaks and their hyphens must not show. The
// Invite still has placeholder hotels.
test('placeholder line breaks show on desktop only', async ({ page }) => {
  await skipIntro(page);
  await loginAsAdmin(page);
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/accommodation/?brodfest-site=invite');
  const desktop = await page.locator('.bf-card__text').first().innerText();
  expect(desktop).toContain('adip-\niscing');

  await page.setViewportSize({ width: 767, height: 1080 });
  const phone = await page.locator('.bf-card__text').first().innerText();
  expect(phone).toContain('consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore');
  expect(phone).not.toContain('\n');
});

test.describe('RSVP form details at 1920', () => {
  test.beforeEach(async ({ page }) => {
    await skipIntro(page);
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/rsvp/');
    await page.addStyleTag({ content: STILL });
  });

  test('a chosen answer is a solid gold circle', async ({ page }) => {
    const radio = page.locator('input[name="attending"]').first();
    await radio.check();
    const style = await radio.evaluate((el) => ({ shadow: getComputedStyle(el).boxShadow, bg: getComputedStyle(el).backgroundColor }));
    expect(style.shadow).toBe('none');
    expect(style.bg).toBe('rgb(209, 149, 78)');
  });

  test('the dropdown arrow is a solid triangle', async ({ page }) => {
    const arrow = await page.locator('.bf-field--select').first().evaluate((el) => {
      const s = getComputedStyle(el, '::after');
      return { clip: s.clipPath, border: s.borderRightWidth, w: s.width, h: s.height, bg: s.backgroundColor };
    });
    expect(arrow.clip).toContain('polygon');
    expect(arrow.border).toBe('0px');
    expect([arrow.w, arrow.h]).toEqual(['29px', '16px']);
    expect(arrow.bg).toBe('rgb(209, 149, 78)');
  });

  test('the accessibility line stays on one line', async ({ page }) => {
    const lines = await page.locator('.bf-check span').first().evaluate((el) => {
      const range = document.createRange();
      range.selectNodeContents(el);
      return new Set([...range.getClientRects()].map((r) => Math.round(r.top))).size;
    });
    expect(lines).toBe(1);
  });
});

// A phone is told the page is 767 wide, so it shows the whole mobile design
// zoomed to fit, and nothing scrolls sideways.
test.describe('on a 390 phone', () => {
  test.use({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });

  test('every page is laid out 767 wide and fits the screen', async ({ page }) => {
    test.setTimeout(120000);
    await skipIntro(page);
    await loginAsAdmin(page);
    const wrong = [];
    for (const path of [...new Set(ROWS.map((r) => r.path))]) {
      await page.goto(path);
      const [layout, scroll] = await page.evaluate(() => [document.documentElement.clientWidth, document.documentElement.scrollWidth]);
      if (layout !== 767 || scroll > 767) wrong.push(`${path}: laid out ${layout} wide, ${scroll} with overflow`);
    }
    expect(wrong, wrong.join('\n')).toEqual([]);
  });

  // WordPress (for a block theme) or another plugin can print its own
  // viewport tag from wp_head, here beside the plugin's stylesheet; the zoom
  // must win.
  test('the zoom wins over another viewport tag in the head', async ({ page }) => {
    await skipIntro(page);
    await page.route('**/accommodation/', async (route) => {
      const response = await route.fetch();
      const html = (await response.text()).replace("<link rel='stylesheet' id='brodfest-public-css'", `<meta name="viewport" content="width=device-width, initial-scale=1" /><link rel='stylesheet' id='brodfest-public-css'`);
      await route.fulfill({ response, body: html });
    });
    await page.goto('/accommodation/');
    expect(await page.evaluate(() => document.documentElement.clientWidth)).toBe(767);
  });
});

// On a laptop the hotel cards keep Figma's width, so their line breaks hold;
// other text is narrower than Figma's and wraps on its own.
test('on a laptop only the hotel cards keep Figma\'s line breaks', async ({ page }) => {
  await skipIntro(page);
  await loginAsAdmin(page);
  for (const width of LAPTOPS) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/accommodation/?brodfest-site=invite');
    expect(await page.locator('.bf-card__text').first().innerText()).toContain('adip-\niscing');
    expect(await page.locator('.bf-area__intro').first().innerText()).not.toContain('\n');
  }
});
