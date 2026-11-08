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
  await expect(links).toHaveText(['info@brodfestibiza.net', 'concierge@brodfestibiza.net']);
  await expect(links.first()).toHaveAttribute('href', 'mailto:info@brodfestibiza.net');
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

// Add to Calendar is the calendar file itself again (client, 8 Nov 2026): the
// choice of Apple, Google and Outlook did not open a calendar for him.
test('Add to Calendar is the calendar file, with no choice of calendars', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/');
  const button = page.locator('.bf-calendar a.bf-button');
  await expect(button).toHaveText('Add to Calendar');
  await expect(button).toHaveAttribute('download', 'brodfest.ics');
  expect(await button.getAttribute('href')).toContain('brodfest-calendar=1');
  await expect(page.locator('.bf-calendar a')).toHaveCount(1);
  await expect(page.locator('main')).not.toContainText(/Apple|Google|Outlook/);
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
