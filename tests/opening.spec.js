import { test, expect, chromium } from '@playwright/test';

// The home page opens on black and the logo draws itself (Luke, 5 Nov 2026),
// then an Enter button appears (Luke, 6 Nov 2026), on a visitor's first
// arrival only. Enter is the tap that lets the browser play the video with
// sound, so the video starts then, in its place on the page with the header as
// normal (Luke, 6 Nov 2026). On later visits it plays by itself, muted, with a
// sound button.

async function videoState(page) {
  return page.locator('[data-bf-video]').evaluate((v) => ({ playing: !v.paused && !v.ended, muted: v.muted }));
}

// The video stays in its place: under the header, which stays on top.
async function inPlace(page) {
  return page.evaluate(() => {
    const video = document.querySelector('[data-bf-video]').getBoundingClientRect();
    const header = document.querySelector('.bf-header').getBoundingClientRect();
    const toggle = document.querySelector('.bf-header__toggle').getBoundingClientRect();
    const hit = document.elementFromPoint(toggle.x + toggle.width / 2, toggle.y + toggle.height / 2);
    return !document.body.classList.contains('is-cinema')
      && Math.round(video.top) >= Math.round(header.bottom)
      && !!hit.closest('.bf-header');
  });
}

async function enter(page) {
  await page.goto('/');
  await page.locator('[data-bf-enter]').click();
  await expect(page.locator('.bf-opening')).toHaveCount(0, { timeout: 2000 });
}

// A returning visitor: the opening has been seen. With `refuseSound`, the
// browser refuses sound until the visitor taps or clicks, as most do.
async function returning(page, { refuseSound = false } = {}) {
  await page.addInitScript((refuse) => {
    window.localStorage.setItem('brodfest-entered', '1');
    if (refuse) {
      const play = HTMLMediaElement.prototype.play;
      let tapped = false;
      document.addEventListener('pointerdown', () => { tapped = true; }, true);
      document.addEventListener('keydown', () => { tapped = true; }, true);
      HTMLMediaElement.prototype.play = function () {
        return this.muted || tapped ? play.call(this) : Promise.reject(new DOMException('Sound refused', 'NotAllowedError'));
      };
    }
  }, refuseSound);
  await page.goto('/');
}

test.beforeEach(async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
});

test('the home page opens on black, the logo draws, and Enter appears within 3 seconds', async ({ page }) => {
  await page.goto('/');
  const opening = page.locator('.bf-opening');
  await expect(opening).toBeVisible();
  expect(await opening.evaluate((el) => getComputedStyle(el).backgroundColor)).toBe('rgb(11, 6, 3)');
  await expect(opening.locator('svg path').first()).toBeVisible();

  const timing = await page.evaluate(() => {
    const animations = document.querySelector('.bf-opening').getAnimations({ subtree: true });
    return {
      names: [...new Set(animations.map((a) => a.animationName))],
      end: Math.max(...animations.map((a) => a.effect.getComputedTiming().endTime)),
    };
  });
  expect(timing.names).toContain('bf-draw');
  expect(timing.end).toBeLessThanOrEqual(3000);

  const enterButton = opening.locator('[data-bf-enter]');
  await expect(enterButton).toHaveText('ENTER');
  await expect(enterButton).toBeFocused();
  expect((await videoState(page)).playing).toBe(false);
});

test('the opening waits for Enter', async ({ page }) => {
  await page.goto('/');
  await page.waitForTimeout(3500);
  await expect(page.locator('.bf-opening')).toBeVisible();
  await expect(page.locator('[data-bf-enter]')).toBeVisible();
});

test('Enter plays the video with sound, in its place on the page', async ({ page }) => {
  await enter(page);
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: false });
  expect(await inPlace(page)).toBe(true);
  await expect(page.locator('[data-bf-sound]')).toHaveAttribute('aria-pressed', 'true');
});

test('Enter works from the keyboard', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('[data-bf-enter]')).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page.locator('.bf-opening')).toHaveCount(0, { timeout: 2000 });
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: false });
});

test('the opening plays on the first visit only', async ({ page }) => {
  await enter(page);
  await page.reload();
  await expect(page.locator('.bf-opening')).toHaveCount(0);
});

test('the opening is only on the home page', async ({ page }) => {
  await page.goto('/celebrate/');
  await expect(page.locator('.bf-opening')).toHaveCount(0);
});

// The sound defaults to on (client, 6 Nov 2026), as far as the browser allows:
// here a browser set to allow it.
test('on a later visit the video plays by itself, with sound where the browser allows it, in its place', async () => {
  const browser = await chromium.launch({ args: ['--autoplay-policy=no-user-gesture-required'] });
  const page = await (await browser.newContext({ baseURL: test.info().project.use.baseURL, viewport: { width: 1920, height: 1080 } })).newPage();
  await returning(page);
  await expect(page.locator('.bf-opening')).toHaveCount(0);
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: false });
  await expect(page.locator('[data-bf-sound]')).toHaveAttribute('aria-pressed', 'true');
  expect(await inPlace(page)).toBe(true);
  await browser.close();
});

test('where the browser refuses sound, the video plays muted and the sound comes on at the first click', async ({ page }) => {
  await returning(page, { refuseSound: true });
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: true });
  await page.mouse.click(960, 900);
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: false });
  await expect(page.locator('[data-bf-sound]')).toHaveAttribute('aria-pressed', 'true');
});

test('a visitor who turns the sound off keeps it off', async ({ page }) => {
  await returning(page, { refuseSound: true });
  const sound = page.locator('[data-bf-sound]');
  await sound.click();
  await sound.click();
  await page.mouse.click(960, 900);
  await expect(sound).toHaveAttribute('aria-pressed', 'false');
  expect((await videoState(page)).muted).toBe(true);
});

test('the sound button turns the sound on and off', async ({ page }) => {
  await returning(page, { refuseSound: true });
  const sound = page.locator('[data-bf-sound]');
  await expect(sound).toBeVisible();
  await expect(sound).toHaveAttribute('aria-pressed', 'false');
  await sound.click();
  await expect(sound).toHaveAttribute('aria-pressed', 'true');
  expect((await videoState(page)).muted).toBe(false);
  await sound.click();
  await expect(sound).toHaveAttribute('aria-pressed', 'false');
  expect((await videoState(page)).muted).toBe(true);
});

test('when the video ends it offers a replay, with sound', async ({ page }) => {
  await enter(page);
  // Played through at speed: the test server cannot seek within a file.
  await page.locator('[data-bf-video]').evaluate((v) => { v.playbackRate = 16; });
  const play = page.locator('[data-bf-play]');
  await expect(play).toBeVisible({ timeout: 10000 });
  await play.click();
  await expect.poll(() => videoState(page)).toEqual({ playing: true, muted: false });
  expect(await inPlace(page)).toBe(true);
});

test('a visitor who asks for less motion goes straight to the page, with no autoplay', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/');
  await expect(page.locator('html')).not.toHaveClass(/bf-is-opening/);
  await expect(page.locator('.bf-opening')).toHaveCount(0);
  expect((await videoState(page)).playing).toBe(false);
  await expect(page.locator('[data-bf-play]')).toBeVisible();
});
