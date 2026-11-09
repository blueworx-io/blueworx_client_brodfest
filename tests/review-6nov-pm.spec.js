import { test, expect } from '@playwright/test';
import { skipIntro } from './helpers/wp.js';

// Mark's third pass over the Save the Date site (Pastel, afternoon of 6 Nov
// 2026).

test.beforeEach(async ({ page }) => {
  await skipIntro(page);
});

test('Enquiries: one ENQUIRIES title, Private Concierge as a subtitle, emails in the copy as bold links', async ({ page }) => {
  await page.goto('/enquiries/');
  await expect(page.locator('h1')).toHaveText('ENQUIRIES');
  await expect(page.locator('h1')).toHaveClass(/bf-title/);
  await expect(page.locator('h2')).toHaveText(['PRIVATE CONCIERGE']);
  await expect(page.locator('h2')).toHaveClass(/bf-area__name/);
  await expect(page.locator('main')).not.toContainText('GENERAL');
  await expect(page.locator('main')).not.toContainText('Email :');
  const links = page.locator('main .bf-body a[href^="mailto:"]');
  await expect(links).toHaveText(['info@brodfest.net', 'concierge@brodfest.net']);
  await expect(links.first()).toHaveAttribute('href', 'mailto:info@brodfest.net');
  expect(Number(await links.first().evaluate((a) => getComputedStyle(a).fontWeight))).toBeGreaterThanOrEqual(600);
});

test('Nobu and Mondrian are named without "Ibiza Bay" and "Ibiza"', async ({ page }) => {
  await page.goto('/accommodation/');
  await expect(page.locator('.bf-card__name').nth(3)).toHaveText('NOBU HOTEL');
  await expect(page.locator('.bf-card__name').nth(7)).toHaveText('MONDRIAN');
});

test('the calendar entry is "Brodfest Ibiza", with the line about Can Sakhara', async ({ page }) => {
  const ics = await (await page.request.get('/?brodfest-calendar=1')).text();
  expect(ics).toContain('SUMMARY:Brodfest Ibiza');
  expect(ics.replace(/\r\n /g, '')).toContain('DESCRIPTION:To celebrate Peter’s birthday in 2027\\, Brodfest arrives at Can Sakhara\\, his home on the iconic island of Ibiza.');
});

// The event runs 7pm to 4am in Ibiza, 6pm to 3am UK time, and its note gives the Ibiza times
// as the client worded them (Luke, 9 Nov 2026).
test('the calendar event runs 7pm to 4am Ibiza time, and its note says so', async ({ page }) => {
  const ics = (await (await page.request.get('/?brodfest-calendar=1')).text()).replace(/\r\n /g, '');
  expect(ics).toContain('DTSTART:20270605T170000Z');
  expect(ics).toContain('DTEND:20270606T020000Z');
  expect(ics).toContain('his home on the iconic island of Ibiza. (7pm to 4am CEST)\r\n');

  await page.goto('/');
  const links = page.locator('[data-bf-calendar-menu] a');
  const google = new URL(new URL(await links.nth(1).getAttribute('href')).searchParams.get('continue'));
  expect(google.searchParams.get('dates')).toBe('20270605T170000Z/20270606T020000Z');
  expect(google.searchParams.get('details')).toMatch(/\(7pm to 4am CEST\)$/);
  const outlook = new URL(await links.nth(2).getAttribute('href'));
  expect(outlook.searchParams.get('startdt')).toBe('2027-06-05T17:00:00Z');
  expect(outlook.searchParams.get('enddt')).toBe('2027-06-06T02:00:00Z');
  expect(outlook.searchParams.get('body')).toMatch(/\(7pm to 4am CEST\)$/);
});

test('on Celebration the "A" never sits alone at the end of a line', async ({ page }) => {
  await page.goto('/celebrate/');
  // A no-break space ties it to the next word.
  expect(await page.locator('main').evaluate((el) => el.textContent.includes('Ibiza. A stunningly'))).toBe(true);
});

test('the home note breaks after "Ibiza", across two lines', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/');
  const note = page.locator('.bf-home__note');
  await expect(note.locator('br')).toHaveCount(1);
  const lines = await note.evaluate((el) => {
    const range = document.createRange();
    range.selectNodeContents(el);
    return new Set([...range.getClientRects()].map((r) => Math.round(r.top))).size;
  });
  expect(lines).toBe(2);
  expect(await note.innerText()).toMatch(/Brodfest Ibiza\nwebsite will land/);
});

// Add to Calendar offers Apple, Google and Outlook, each opening its calendar
// with the event filled in (client, 6 Nov 2026). Nothing is downloaded
// (client, 8 Nov 2026): Apple is a webcal address, which Apple devices hand
// straight to Calendar. Google and Outlook go through their sign-in, which
// keeps the event; opened directly while signed out, both showed a marketing
// page instead (Mark, 8 Nov 2026).
test('Add to Calendar offers Apple, Google and Outlook, with nothing to download', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/');
  const button = page.locator('[data-bf-calendar-toggle]');
  const menu = page.locator('[data-bf-calendar-menu]');
  await expect(menu).toBeHidden();
  await button.click();
  await expect(button).toHaveAttribute('aria-expanded', 'true');
  const links = menu.locator('a');
  await expect(links).toHaveText(['Apple', 'Google', 'Outlook']);
  await expect(page.locator('.bf-calendar [download]')).toHaveCount(0);

  const apple = await links.nth(0).getAttribute('href');
  // A plain web address, never webcal: Calendar treats webcal as a calendar
  // to subscribe to, not an event to add (Luke, 9 Nov 2026).
  expect(apple).toMatch(/^https?:\/\//);
  expect(apple).toContain('brodfest-calendar=1');

  const signIn = new URL(await links.nth(1).getAttribute('href'));
  expect(signIn.origin + signIn.pathname).toBe('https://accounts.google.com/ServiceLogin');
  const google = new URL(signIn.searchParams.get('continue'));
  expect(google.origin + google.pathname).toBe('https://calendar.google.com/calendar/render');
  expect(google.searchParams.get('action')).toBe('TEMPLATE');
  expect(google.searchParams.get('text')).toBe('Brodfest Ibiza');
  expect(google.searchParams.get('dates')).toMatch(/^20270605T170000Z\//);
  expect(google.searchParams.get('details')).toContain('Can Sakhara');

  const outlook = new URL(await links.nth(2).getAttribute('href'));
  expect(outlook.origin + outlook.pathname).toBe('https://outlook.office.com/calendar/0/deeplink/compose');
  expect(outlook.searchParams.get('subject')).toBe('Brodfest Ibiza');
  expect(outlook.searchParams.get('startdt')).toBe('2027-06-05T17:00:00Z');
  for (const i of [1, 2]) await expect(links.nth(i)).toHaveAttribute('target', '_blank');

  await page.keyboard.press('Escape');
  await expect(menu).toBeHidden();
  await expect(button).toBeFocused();
});

// The file itself is served to be opened, not saved.
test('the calendar file is served inline', async ({ page }) => {
  const response = await page.request.get('/?brodfest-calendar=1');
  expect(response.headers()['content-type']).toContain('text/calendar');
  expect(response.headers()['content-disposition']).toMatch(/^inline/);
});

// Luke, 6 Nov 2026: the choices sat over the line below the button.
test('the calendar choices never cover the line below, on desktop or phone', async ({ page }) => {
  for (const width of [1440, 767]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/');
    await page.locator('[data-bf-calendar-toggle]').click();
    const menu = await page.locator('[data-bf-calendar-menu]').boundingBox();
    const note = await page.locator('.bf-home__note').boundingBox();
    expect(menu.y + menu.height, `at ${width}`).toBeLessThanOrEqual(note.y);
  }
});

// The main body copy is 30% bigger on the mobile design (client, 6 Nov 2026):
// 26 in place of Figma's 20. Desktop keeps its size.
test('body copy is 30% bigger on mobile, and unchanged on desktop', async ({ page }) => {
  const size = async (width) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/celebrate/');
    return page.locator('.bf-text .bf-body').first().evaluate((el) => getComputedStyle(el).fontSize);
  };
  expect(await size(767)).toBe('26px');
  expect(await size(1023)).toBe('26px');
  expect(await size(1920)).toBe('15px');
});

// Mark, 9 Nov 2026 (Pastel #94, #95): on desktop a hotel card's description
// and its discount line are 13px. The mobile design keeps its size.
test('hotel card text and discount lines are 13px on desktop, and unchanged on mobile', async ({ page }) => {
  const sizes = async (width) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/accommodation/');
    return page.evaluate(() => ['.bf-card__text', '.bf-card__discount'].map((sel) => getComputedStyle(document.querySelector(sel)).fontSize));
  };
  expect(await sizes(1920)).toEqual(['13px', '13px']);
  expect(await sizes(1024)).toEqual(['13px', '13px']);
  expect(await sizes(767)).toEqual(['20px', '20px']);
});
