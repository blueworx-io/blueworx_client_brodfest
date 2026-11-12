import { test, expect } from '@playwright/test';
import { fillInvitee, comingAlone } from './helpers/rsvp.js';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';

// Mark's review of the Save the Date site (Pastel, 5 Nov 2026) and the copy
// he sent with it.

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

// Desktop is drawn at three-quarters of Figma's size (Luke, 6 Nov 2026), so
// its 170 header is 127.5 there, and tablets share it.
test('the header is 127.5 tall from tablets up, and phones keep the mobile 207', async ({ page }) => {
  for (const [width, height] of [[1920, 127.5], [1280, 127.5], [1024, 127.5], [900, 127.5], [768, 127.5], [767, 207]]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    const box = await page.locator('.bf-header').boundingBox();
    expect(box.height, `header at ${width}`).toBe(height);
  }
});

test('the header keeps at least the mobile 48 (36 on desktop) from either edge', async ({ page }) => {
  for (const width of [768, 900, 1023, 1024, 1280, 1199]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    const toggle = await page.locator('.bf-header__toggle').boundingBox();
    const pill = await page.locator('.bf-header .bf-pill').boundingBox();
    expect(Math.round(toggle.x), `menu button at ${width}`).toBeGreaterThanOrEqual(36);
    expect(Math.round(width - pill.x - pill.width), `RSVP at ${width}`).toBeGreaterThanOrEqual(36);
  }
});

test('the menu button and RSVP sit level with the logo, centred in the header', async ({ page }) => {
  for (const width of [1920, 1280, 1024, 900, 768]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    const header = await page.locator('.bf-header').boundingBox();
    const middle = header.y + header.height / 2;
    for (const selector of ['.bf-header__toggle', '.bf-header .bf-pill', '.bf-header__logo']) {
      const box = await page.locator(selector).boundingBox();
      expect(Math.abs(box.y + box.height / 2 - middle), `${selector} at ${width}`).toBeLessThanOrEqual(1);
    }
  }
});

test('the open menu blurs the page behind it and has no mountain', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/');
  await page.click('.bf-header__toggle');
  await expect(page.locator('body')).toHaveClass(/is-menu-open/);
  expect(await page.locator('.bf-menu').evaluate((el) => getComputedStyle(el).backdropFilter)).toContain('blur');
  await expect(page.locator('.bf-menu__mountain')).toHaveCount(0);
});

test('a menu item turns white on rollover, without going bold', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/');
  await page.click('.bf-header__toggle');
  const item = page.locator('.bf-menu__items a').nth(1);
  const before = await item.evaluate((el) => getComputedStyle(el).fontWeight);
  await item.hover();
  await expect(item).toHaveCSS('color', 'rgb(255, 255, 255)');
  expect(await item.evaluate((el) => getComputedStyle(el).fontWeight)).toBe(before);
});

test('the home page plays the Save the Date video, wide from tablets up and square on phones', async ({ page }) => {
  await page.goto('/');
  const sources = page.locator('.bf-hero__video source');
  await expect(sources).toHaveCount(2);
  await expect(sources.first()).toHaveAttribute('src', /BF_SAVE_THE_DATE_1920X840/);
  await expect(sources.first()).toHaveAttribute('media', '(min-width: 768px)');
  await expect(sources.last()).toHaveAttribute('src', /BF_SAVE_THE_DATE_1080X1080/);
  const ok = await page.request.get(await sources.first().getAttribute('src'));
  expect(ok.status()).toBe(200);
});

// The calendar entry has its own line since 6 Nov (review-6nov-pm.spec.js).
test('the home page uses the copy document line about the invitation', async ({ page }) => {
  await page.goto('/');
  // On two lines, breaking after "Ibiza" (review-6nov-pm.spec.js).
  await expect(page.locator('.bf-home__note')).toHaveText(/Your invitation and the full Brodfest Ibiza\s*website will land before Christmas/);
});

test('Celebration has the new copy and the dress code; transport has moved to Information', async ({ page }) => {
  await page.goto('/celebrate/');
  await expect(page.locator('h1')).toHaveText('CELEBRATION');
  await expect(page.locator('.bf-body').first()).toContainText('Ibiza is calling.');
  await expect(page.locator('h2')).toHaveText(['DRESS CODE']);
  await expect(page.locator('main')).toContainText('Black, Gold & Ibiza Bold');
  await expect(page.locator('main')).toContainText('will land before Christmas');
  // The subtitles take the area-header style (as TALAMANCA).
  await expect(page.locator('h2').first()).toHaveClass(/bf-area__name/);
});

test('Information has the real hotels, with only Cala Llonga discounted', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('h1')).toHaveText('INFORMATION');
  await expect(page.locator('.bf-area h2.bf-area__name')).toHaveText(["PLAYA D’EN BOSSA", 'TALAMANCA', 'CALA LLONGA']);
  await expect(page.locator('.bf-card__name')).toHaveText([
    'BLESS IBIZA', 'TORRE DEL MAR', 'AMA IBIZA',
    'NOBU HOTEL', 'HOTEL SIMBAD', 'HOTEL LUX ISLA',
    'HYDE IBIZA', 'MONDRIAN',
  ]);
  await expect(page.locator('.bf-card .bf-button--card').first()).toHaveAttribute('href', 'https://www.thesiteibiza.com/en/hotels/bless-ibiza-the-site/');
  const discounts = page.locator('.bf-card__discount');
  await expect(discounts).toHaveCount(2);
  await expect(discounts.first()).toHaveText('For up to 25% discount on bookings made before 15/02/2027, quote BRODFEST');
  await expect(page.locator('.bf-area').last().locator('.bf-card__discount')).toHaveCount(2);
  await expect(page.getByText('GETTING HERE')).toHaveCount(0);
});

test('each row of hotels has its BOOK buttons in line, whatever the length of the copy', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/accommodation/');
  await expect(page.locator('.bf-card a[href*="amaibiza"]')).toHaveAttribute('href', 'https://www.amaibiza.com/');
  for (const area of await page.locator('.bf-area').all()) {
    const tops = await area.locator('.bf-button--card').evaluateAll((els) => els.map((el) => Math.round(el.getBoundingClientRect().top)));
    expect(new Set(tops).size, tops.join()).toBe(1);
  }
});

// The client's two maps (6 Nov 2026): the wide one on desktop, filling its
// slot, and the squarer one on mobile.
test('the map is the client\'s desktop version on desktop, filling its slot', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/accommodation/');
  const map = page.locator('.bf-map img');
  await expect(map).toHaveAttribute('alt', /Map of Ibiza/);
  await map.scrollIntoViewIfNeeded();
  await expect.poll(() => map.evaluate((img) => img.complete && img.naturalWidth)).toBe(3259);
  const box = await map.boundingBox();
  const slot = await page.locator('.bf-map').boundingBox();
  expect([Math.round(box.width), Math.round(box.height)]).toEqual([Math.round(slot.width), Math.round(slot.height)]);
  await expect(page.locator('.bf-map .bf-placeholder-label')).toHaveCount(0);
});

test('the map is the client\'s mobile version on mobile', async ({ page }) => {
  await page.setViewportSize({ width: 767, height: 1000 });
  await page.goto('/accommodation/');
  const map = page.locator('.bf-map img');
  await map.scrollIntoViewIfNeeded();
  await expect.poll(() => map.evaluate((img) => img.complete && img.naturalWidth)).toBe(1630);
  // It fills the width, and its slot is as tall as the whole map (Luke, 6 Nov 2026).
  const box = await map.boundingBox();
  const slot = await page.locator('.bf-map').boundingBox();
  expect(Math.round(box.width)).toBe(767);
  expect(Math.abs(box.height - (767 * 600) / 782)).toBeLessThanOrEqual(1);
  expect(Math.abs(slot.height - box.height)).toBeLessThanOrEqual(1);
});

test('Enquiries has the new copy and brodfest.net addresses', async ({ page }) => {
  await page.goto('/enquiries/');
  await expect(page.locator('a[href^="mailto:"]')).toHaveText(['info@brodfest.net', 'concierge@brodfest.net']);
  await expect(page.locator('main')).toContainText('Concierge services are offered privately');
});

test('the Invite keeps its own copy until it has its own review', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/accommodation/?brodfest-site=invite');
  await expect(page.locator('h1')).toHaveText('ACCOMMODATION');
  await page.goto('/celebrate/?brodfest-site=invite');
  await expect(page.locator('h1')).toHaveText('CELEBRATE');
});

test.describe('RSVP', () => {
  test('the country code can be changed, and starts at +44', async ({ page }) => {
    await page.goto('/rsvp/');
    const code = page.locator('#bf-phone_prefix');
    await expect(code).toHaveValue('+44');
    expect(await code.evaluate((el) => el.tagName)).toBe('SELECT');
    await code.selectOption('+34');
    await expect(code).toHaveValue('+34');
  });

  test('the country code and dietary choice sit in the middle of their fields', async ({ page }) => {
    for (const width of [1920, 767]) {
      await page.setViewportSize({ width, height: 1080 });
      await page.goto('/rsvp/');
      for (const [selector, from, to] of [['#bf-phone_prefix', 0, 1], ['#bf-dietary', 0.15, 0.8]]) {
        const field = page.locator(selector);
        await field.scrollIntoViewIfNeeded();
        const box = await field.boundingBox();
        const clip = { x: box.x + box.width * from, y: box.y, width: box.width * (to - from), height: box.height };
        const png = (await page.screenshot({ clip })).toString('base64');
        // The middle of the gold lettering, as a share of the field's height.
        const middle = await page.evaluate(async (data) => {
          const img = new Image();
          img.src = `data:image/png;base64,${data}`;
          await img.decode();
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          const { data: px } = ctx.getImageData(0, 0, img.width, img.height);
          const rows = [];
          for (let y = 0; y < img.height; y++) {
            for (let x = 0; x < img.width; x++) {
              if (px[(y * img.width + x) * 4] > 120) {
                rows.push(y);
                break;
              }
            }
          }
          return (rows[0] + rows[rows.length - 1]) / 2 / img.height;
        }, png);
        expect(Math.abs(middle - 0.5), `${selector} at ${width}`).toBeLessThanOrEqual(0.06);
      }
    }
  });

  test('the phone number placeholder sits in the middle of its field', async ({ page }) => {
    for (const width of [1920, 767]) {
      await page.setViewportSize({ width, height: 1080 });
      await page.goto('/rsvp/');
      const off = await page.evaluate(() => {
        const input = document.querySelector('#bf-phone');
        const field = input.closest('.bf-field').getBoundingClientRect();
        const box = input.getBoundingClientRect();
        const s = getComputedStyle(input);
        const middle = (box.left + parseFloat(s.paddingLeft) + box.right - parseFloat(s.paddingRight)) / 2;
        return middle - (field.left + field.width / 2);
      });
      expect(Math.abs(off), `at ${width}`).toBeLessThanOrEqual(1);
    }
  });

  test('the dietary list ends with Other and opens dark with gold brand lettering', async ({ page }) => {
    await page.goto('/rsvp/');
    const options = page.locator('#bf-dietary option');
    await expect(options.last()).toHaveText('Other (tell us under allergies)');
    await expect(options.nth(-2)).toHaveText('Dairy free');
    const style = await page.locator('#bf-dietary option').last().evaluate((el) => {
      const s = getComputedStyle(el);
      return { bg: s.backgroundColor, color: s.color, font: s.fontFamily };
    });
    expect(style.bg).toBe('rgb(11, 6, 3)');
    expect(style.color).toBe('rgb(209, 149, 78)');
    // The form's own brand font, not the browser's.
    expect(style.font).toMatch(/aller|Lato/);
  });

  test('a sent reply keeps the RSVP title, thanks the guest and offers Close', async ({ page }) => {
    await page.goto('/rsvp/');
    await fillInvitee(page, { name: `Thanks Test ${Date.now()}`, email: 'thanks@example.com', dietary: 'dairy-free' });
    await comingAlone(page);
    await page.getByRole('button', { name: 'SEND' }).click();

    await expect(page.locator('h1')).toHaveText('RSVP');
    await expect(page.getByRole('status')).toHaveText('Thank you for your RSVP');
    const close = page.locator('.bf-rsvp a.bf-button--send');
    await expect(close).toHaveText('CLOSE');
    await close.click();
    await expect(page.locator('body')).toHaveClass(/brodfest-page-home/);
  });
});

test('the calendar file books 7pm Spanish time', async ({ page }) => {
  await page.goto('/');
  const body = await (await page.request.get('/?brodfest-calendar=1')).text();
  // 7pm in Ibiza in June is 17:00 UTC (summer time, UTC+2).
  expect(body).toContain('DTSTART:20270605T170000Z');
  expect(body).not.toContain('VALUE=DATE');
});
