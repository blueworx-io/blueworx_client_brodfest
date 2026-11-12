import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';
import { loginAsAdmin, skipIntro } from './helpers/wp.js';
import { fillInvitee, attendingRadio, partnerRadio } from './helpers/rsvp.js';

// The guest list spreadsheet: Mark's 19 columns in his order, one row per
// reply with the partner on the same row, nothing else (Mark, 11 Nov 2026).

const COLUMNS = [
  'Attending? Yes / No', 'First Name', 'Last Name', 'Phone Number', 'Email', 'Dietary', 'Allergy', 'Accessibility',
  'Attending with Partner? Yes / No', 'Partner First Name', 'Partner Last Name', 'Partner Phone Number', 'Partner Email',
  'Partner Dietary', 'Partner Allergy', 'Extra Guests', 'Hotel', 'Transport', 'Concierge',
];

// Enough CSV for this file: quoted cells, doubled quotes, no line breaks in cells.
function parseCsv(text) {
  return text.replace(/^﻿/, '').trim().split(/\r?\n/).map((line) => {
    const cells = [];
    let cell = '';
    let quoted = false;
    for (let i = 0; i < line.length; i++) {
      const c = line[i];
      if (quoted) {
        if ('"' === c && '"' === line[i + 1]) { cell += '"'; i++; } else if ('"' === c) { quoted = false; } else { cell += c; }
      } else if ('"' === c) { quoted = true; } else if (',' === c) { cells.push(cell); cell = ''; } else { cell += c; }
    }
    cells.push(cell);
    return cells;
  });
}

test('Allergies asks for details, for the guest and the partner', async ({ page }) => {
  await skipIntro(page);
  await page.goto('/rsvp/');
  await expect(page.locator('#bf-allergies')).toHaveAttribute('placeholder', 'Allergies (Please type details)');
  await attendingRadio(page, 'yes').check();
  await partnerRadio(page, 'yes').check();
  await expect(page.locator('#bf-guest_allergies')).toHaveAttribute('placeholder', 'Partner’s Allergies (Please type details)');
});

test('the guest list downloads with Mark’s columns, partner on the same row', async ({ page, browser }) => {
  test.setTimeout(120000);
  const stamp = Date.now();
  const visitor = await (await browser.newContext({ baseURL: test.info().project.use.baseURL })).newPage();
  await skipIntro(visitor);
  await visitor.goto('/rsvp/');
  await fillInvitee(visitor, { name: `Ada List${stamp}`, email: `list${stamp}@example.com`, dietary: 'vegan', allergies: 'Peanuts' });
  await attendingRadio(visitor, 'yes').check();
  await partnerRadio(visitor, 'yes').check();
  await visitor.locator('#bf-guest_name').fill(`Bob Partner${stamp}`);
  await visitor.locator('#bf-guest_phone').fill('7700 900456');
  await visitor.locator('#bf-guest_email').fill(`partner${stamp}@example.com`);
  await visitor.locator('#bf-guest_dietary').selectOption('vegetarian');
  await visitor.locator('#bf-guest_allergies').fill('=1+1');
  await visitor.locator('input[name="accessibility"]').check();
  await visitor.getByRole('button', { name: 'SEND' }).click();
  await expect(visitor.getByRole('status')).toContainText('Thank you for your RSVP');
  await visitor.context().close();

  await loginAsAdmin(page);
  await page.goto('/wp-admin/users.php');
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByRole('link', { name: 'Download guest list' }).click(),
  ]);
  expect(download.suggestedFilename()).toMatch(/^brodfest-guest-list-\d{4}-\d{2}-\d{2}\.csv$/);
  const rows = parseCsv(readFileSync(await download.path(), 'utf8'));

  expect(rows[0]).toEqual(COLUMNS);
  // One row per reply: the partner is not a row of their own.
  expect(rows.filter((r) => r.join(',').includes(`Partner${stamp}`))).toHaveLength(1);
  expect(rows.filter((r) => r[1] === 'Bob')).toHaveLength(0);
  const row = rows.find((r) => r[4] === `list${stamp}@example.com`);
  expect(row).toEqual([
    'Yes', 'Ada', `List${stamp}`, '+44 7700 900123', `list${stamp}@example.com`, 'Vegan', 'Peanuts', 'Yes',
    'Yes', 'Bob', `Partner${stamp}`, '+44 7700 900456', `partner${stamp}@example.com`, 'Vegetarian',
    // Typed like a formula, kept as text.
    "'=1+1",
    '', '', '', '',
  ]);
});

test('the guest list cannot be downloaded without signing in', async ({ page }) => {
  const res = await page.request.get('/wp-admin/admin-post.php?action=brodfest_guest_list', { maxRedirects: 0 });
  expect(res.headers()['content-type'] || '').not.toContain('text/csv');
});
