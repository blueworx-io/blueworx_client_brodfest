import { test, expect } from '@playwright/test';

// The home page opens on black, the logo draws itself and fades to the page,
// on a visitor's first arrival only (Luke, 5 Nov 2026). The video then plays
// by itself, muted, filling the window with the menu out of the way, until it
// ends or the visitor scrolls, taps or presses Esc.

async function videoState(page) {
  return page.locator('[data-bf-video]').evaluate((v) => ({ playing: !v.paused && !v.ended, muted: v.muted }));
}

async function fillsWindow(page) {
  const box = await page.locator('[data-bf-video]').boundingBox();
  const size = page.viewportSize();
  return [box.x, box.y, box.width, box.height].map(Math.round).join() === [0, 0, size.width, size.height].join();
}

async function waitForOpening(page) {
  await expect(page.locator('.bf-opening')).toHaveCount(0, { timeout: 4000 });
}

test.beforeEach(async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
});

test('the home page opens on black, the logo draws, and it fades to the page within 3 seconds', async ({ page }) => {
  await page.goto('/');
  const opening = page.locator('.bf-opening');
  await expect(opening).toBeVisible();
  expect(await opening.evaluate((el) => getComputedStyle(el).backgroundColor)).toBe('rgb(11, 3, 1)');
  await expect(opening.locator('svg path').first()).toBeVisible();
  await expect(page.locator('[data-bf-enter]')).toHaveCount(0);

  const timing = await page.evaluate(() => {
    const animations = document.querySelector('.bf-opening').getAnimations({ subtree: true });
    return {
      names: [...new Set(animations.map((a) => a.animationName))],
      end: Math.max(...animations.map((a) => a.effect.getComputedTiming().endTime)),
    };
  });
  expect(timing.names).toContain('bf-draw');
  expect(timing.end).toBeLessThanOrEqual(3000);
  await waitForOpening(page);
});

test('the opening plays on the first visit only', async ({ page }) => {
  await page.goto('/');
  await waitForOpening(page);
  await page.reload();
  await expect(page.locator('.bf-opening')).toBeHidden();
});

test('the opening is only on the home page', async ({ page }) => {
  await page.goto('/celebrate/');
  await expect(page.locator('.bf-opening')).toHaveCount(0);
});

test('after the opening the video plays by itself, muted, filling the window over the menu', async ({ page }) => {
  await page.goto('/');
  await waitForOpening(page);
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: true });
  await expect(page.locator('body')).toHaveClass(/is-cinema/);
  expect(await fillsWindow(page)).toBe(true);
  const covered = await page.evaluate(() => {
    const b = document.querySelector('.bf-header__toggle').getBoundingClientRect();
    return document.elementFromPoint(b.x + b.width / 2, b.y + b.height / 2).matches('[data-bf-video]');
  });
  expect(covered).toBe(true);
});

for (const [how, act] of [
  ['scrolls', (page) => page.mouse.wheel(0, 300)],
  ['taps', (page) => page.mouse.click(960, 540)],
  ['presses Esc', (page) => page.keyboard.press('Escape')],
]) {
  test(`the page comes back when the visitor ${how}, and the video plays on in its place`, async ({ page }) => {
    await page.goto('/');
    await waitForOpening(page);
    await expect(page.locator('body')).toHaveClass(/is-cinema/);
    await act(page);
    await expect(page.locator('body')).not.toHaveClass(/is-cinema/);
    expect(await fillsWindow(page)).toBe(false);
    expect((await videoState(page)).playing).toBe(true);
  });
}

test('the sound button turns the sound on without leaving the full-window video', async ({ page }) => {
  await page.goto('/');
  await waitForOpening(page);
  const sound = page.locator('[data-bf-sound]');
  await expect(sound).toBeVisible();
  await expect(sound).toHaveAttribute('aria-pressed', 'false');
  await sound.click();
  await expect(sound).toHaveAttribute('aria-pressed', 'true');
  expect((await videoState(page)).muted).toBe(false);
  await expect(page.locator('body')).toHaveClass(/is-cinema/);
});

test('when the video ends the page comes back and offers a replay, which fills the window again', async ({ page }) => {
  await page.goto('/');
  await waitForOpening(page);
  // Played through at speed: the test server cannot seek within a file.
  await page.locator('[data-bf-video]').evaluate((v) => { v.playbackRate = 16; });
  await expect(page.locator('body')).not.toHaveClass(/is-cinema/, { timeout: 10000 });
  const play = page.locator('[data-bf-play]');
  await expect(play).toBeVisible();
  await play.click();
  await expect(page.locator('body')).toHaveClass(/is-cinema/);
  expect(await videoState(page)).toEqual({ playing: true, muted: false });
});

test('a visitor who asks for less motion goes straight to the page, with no autoplay', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/');
  await expect(page.locator('html')).not.toHaveClass(/bf-is-opening/);
  await waitForOpening(page);
  expect((await videoState(page)).playing).toBe(false);
  await expect(page.locator('[data-bf-play]')).toBeVisible();
  await expect(page.locator('body')).not.toHaveClass(/is-cinema/);
});
