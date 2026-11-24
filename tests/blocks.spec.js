// The site's sections are blocks (blocks/), drawn on the server. These tests
// check what the Foundation rules ask of them: namespaced blocks that save only
// their words, switched-off colour, type and spacing, lists that hide when
// empty, alt text on every image, headings in order, and an Invite that stays
// private until it is switched on.
import { test, expect } from '@playwright/test';
import { loginQuietly, skipIntro } from './helpers/wp.js';

// What each page holds, in order, on the Save the Date.
const STD_PAGES = {
  home: ['opening', 'hero-video', 'home-lettering', 'add-to-calendar'],
  celebrate: ['text-section', 'text-section'],
  accommodation: ['text-section', 'text-section', 'text-section', 'map', 'area', 'area', 'area'],
  enquiries: ['text-section', 'text-section'],
  rsvp: ['rsvp-form'],
};

const SECTION_BLOCKS = [
  'add-to-calendar',
  'area',
  'category',
  'feature-image',
  'hero-video',
  'home-lettering',
  'map',
  'opening',
  'photo-strip',
  'rsvp-form',
  'site-footer',
  'site-header',
  'story-banner',
  'text-section',
];

// Signed in as an admin, a REST helper that carries the nonce.
async function restFor(page) {
  await loginQuietly(page, test.info().project.use.baseURL);
  const nonce = (await (await page.request.get('/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  expect(nonce, 'signed in').not.toBe('0');
  const call = async (method, path, data) => {
    const response = await page.request.fetch(`/wp-json${path}`, {
      method,
      headers: { 'X-WP-Nonce': nonce },
      ...(data ? { data } : {}),
    });
    expect(response.status(), `${method} ${path}`).toBeLessThan(300);
    return response.json();
  };
  return {
    get: (path) => call('GET', path),
    post: (path, data) => call('POST', path, data),
    // A page's saved content and id, by slug.
    async page(slug) {
      const [found] = await call('GET', `/wp/v2/pages?slug=${slug}&context=edit&status=publish`);
      return { id: found.id, raw: found.content.raw };
    },
    // What a block draws, given its attributes.
    async render(name, attributes) {
      const out = await call('POST', `/wp/v2/block-renderer/brodfest/${name}`, { context: 'edit', attributes });
      return out.rendered;
    },
  };
}

const blockNames = (raw) => [...raw.matchAll(/<!-- wp:brodfest\/([\w-]+)[ /]/g)].map((m) => m[1]);

test('the Save the Date pages hold section blocks, and only their words are saved', async ({ page }) => {
  const api = await restFor(page);

  for (const [slug, names] of Object.entries(STD_PAGES)) {
    const { raw } = await api.page(slug);
    expect(blockNames(raw), slug).toEqual(names);
    // Block comments and nothing else: no markup is saved in the page.
    expect(raw.replace(/<!--[\s\S]*?-->/g, '').trim(), `${slug} holds only block comments`).toBe('');
  }
});

test('every section is a Brodfest block that switches off colour, type and spacing', async ({ page }) => {
  const api = await restFor(page);
  const types = await api.get('/wp/v2/block-types/brodfest');
  expect(types.map((t) => t.name).sort()).toEqual(SECTION_BLOCKS.map((n) => `brodfest/${n}`).sort());

  for (const type of types) {
    expect(type.supports.color, type.name).toBe(false);
    expect(type.supports.typography, type.name).toBe(false);
    expect(type.supports.spacing, type.name).toBe(false);
    // So no style, colour or size attribute exists to be saved on a page.
    expect(Object.keys(type.attributes), type.name).not.toEqual(expect.arrayContaining(['style']));
    expect(Object.keys(type.attributes), type.name).not.toEqual(expect.arrayContaining(['fontSize']));
    expect(Object.keys(type.attributes), type.name).not.toEqual(expect.arrayContaining(['backgroundColor']));
  }
});

test('a list with nothing in it, or a section with no words, leaves itself out', async ({ page }) => {
  const api = await restFor(page);

  const noHotels = await api.render('area', { name: 'TALAMANCA', hotels: [] });
  expect(noHotels).toContain('bf-area__name');
  expect(noHotels).not.toContain('bf-cards');

  const oneHotel = await api.render('area', { name: 'TALAMANCA', hotels: [{ name: 'HOTEL ONE', text: 'Words', url: 'https://example.com/' }] });
  expect(oneHotel).toContain('HOTEL ONE');
  // One card needs no dots.
  expect(oneHotel).not.toContain('bf-cards__dots');

  expect((await api.render('text-section', {})).trim()).toBe('');
  expect((await api.render('category', { name: '' })).trim()).toBe('');
  expect((await api.render('photo-strip', {})).trim()).toBe('');
  expect((await api.render('map', {})).trim()).toBe('');
  expect((await api.render('feature-image', {})).trim()).toBe('');
});

test('an image has its alt text, or is marked decorative', async ({ page }) => {
  const api = await restFor(page);
  const hotel = (extra) => ({ name: 'HOTEL ONE', text: 'Words', url: 'https://example.com/', image: 'https://example.com/a.jpg', ...extra });

  const described = await api.render('area', { name: 'AREA', hotels: [hotel({ imageAlt: 'The hotel pool at dusk' })] });
  expect(described).toContain('alt="The hotel pool at dusk"');

  const decorative = await api.render('area', { name: 'AREA', hotels: [hotel({ imageAlt: 'Ignored', imageDecorative: true })] });
  expect(decorative).toContain('alt=""');
  expect(decorative).not.toContain('Ignored');

  const strip = await api.render('photo-strip', { image: 'https://example.com/a.jpg', imageAlt: 'People dancing' });
  expect(strip).toContain('alt="People dancing"');

  const map = await api.render('map', { imageDesktop: 'https://example.com/d.png', imageMobile: 'https://example.com/m.png', alt: 'Map of the island' });
  expect(map).toContain('alt="Map of the island"');
});

test('every image the starting content uses has alt text or is marked decorative', async ({ page }) => {
  const api = await restFor(page);

  // Every object in a block's attributes that names an image.
  const images = (value, found = []) => {
    if (Array.isArray(value)) value.forEach((v) => images(v, found));
    else if (value && typeof value === 'object') {
      if (value.image || value.imageDesktop || value.imageMobile) found.push(value);
      Object.values(value).forEach((v) => images(v, found));
    }
    return found;
  };

  let checked = 0;
  for (const slug of Object.keys(STD_PAGES)) {
    const { raw } = await api.page(slug);
    for (const [, name, json] of raw.matchAll(/<!-- wp:brodfest\/([\w-]+) (\{[\s\S]*?\}) \/?-->/g)) {
      for (const image of images(JSON.parse(json))) {
        const alt = image.imageAlt ?? image.alt ?? '';
        const decorative = image.imageDecorative ?? image.decorative ?? false;
        expect(alt !== '' || decorative, `${slug}: ${name} image without alt text`).toBe(true);
        checked++;
      }
    }
  }
  // The area banners, the hotels and the map.
  expect(checked).toBeGreaterThanOrEqual(12);
});

// One main heading, then headings that never skip a level.
async function expectHeadingsInOrder(page, path) {
  await page.goto(path);
  const levels = await page.$$eval('main h1, main h2, main h3, main h4, main h5, main h6', (els) => els.map((el) => Number(el.tagName[1])));
  expect(levels.length, `${path} has headings`).toBeGreaterThan(0);
  expect(levels[0], `${path} starts with the main heading`).toBe(1);
  expect(levels.filter((l) => l === 1), `${path} has one main heading`).toHaveLength(1);
  levels.slice(1).forEach((level, i) => {
    expect(level, `${path}: heading after level ${levels[i]}`).toBeLessThanOrEqual(levels[i] + 1);
  });
}

test('headings are in order on every page of both sites', async ({ page }) => {
  await skipIntro(page);
  for (const path of ['/', '/celebrate/', '/accommodation/', '/enquiries/', '/rsvp/']) {
    await expectHeadingsInOrder(page, path);
  }

  await loginQuietly(page, test.info().project.use.baseURL);
  for (const path of ['/', '/celebrate/', '/can-sakhara-story/', '/accommodation/', '/recommendations/', '/enquiries/', '/rsvp/']) {
    await expectHeadingsInOrder(page, `${path}?brodfest-site=invite`);
  }
});

test('the Invite stays private while the Save the Date is live', async ({ browser }) => {
  const visitor = await browser.newContext();
  try {
    // Its pages hold nothing of the Invite's.
    const pages = await (await visitor.request.get('/wp-json/wp/v2/pages?per_page=100')).json();
    expect(JSON.stringify(pages)).not.toContain('Lorem ipsum');
    const story = pages.find((p) => p.slug === 'can-sakhara-story');
    expect(story.content.rendered.trim()).toBe('');

    // The held-back copies are private: not listed, not searchable.
    const held = await visitor.request.get('/wp-json/wp/v2/brodfest-held-pages?status=private');
    expect([400, 401, 403]).toContain(held.status());
    const listed = await (await visitor.request.get('/wp-json/wp/v2/brodfest-held-pages')).json();
    expect(JSON.stringify(listed)).not.toContain('Lorem ipsum');
    const search = await (await visitor.request.get('/wp-json/wp/v2/search?search=Lorem')).json();
    expect(JSON.stringify(search)).not.toContain('Lorem');
  } finally {
    await visitor.close();
  }
});

async function chooseSite(page, label) {
  await page.goto('/wp-admin/admin.php?page=brodfest');
  await page.getByRole('radio', { name: label }).check();
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText('Everything is saved')).toBeVisible();
}

test('switching the live site swaps the blocks, and switching back restores them exactly', async ({ page, browser }) => {
  const api = await restFor(page);
  const before = {};
  for (const slug of ['celebrate', 'can-sakhara-story']) before[slug] = (await api.page(slug)).raw;
  expect(before.celebrate).toContain('CELEBRATION');
  expect(before['can-sakhara-story']).toBe('');

  await chooseSite(page, 'Invite');
  const visitor = await browser.newContext();
  try {
    // Now the pages themselves hold the Invite's blocks.
    const celebrate = (await api.page('celebrate')).raw;
    expect(celebrate).toContain('"title":"CELEBRATE"');
    expect(celebrate).toContain('wp:brodfest/photo-strip');
    expect((await api.page('can-sakhara-story')).raw).toContain('wp:brodfest/story-banner');

    // And a visitor sees the Invite, with no preview needed.
    await skipIntro(await visitor.newPage());
    const seen = await visitor.request.get('/can-sakhara-story/');
    expect(await seen.text()).toContain('CAN SAKHARA STORY');
  } finally {
    await visitor.close();
    await chooseSite(page, 'Save the Date');
  }

  for (const slug of Object.keys(before)) {
    expect((await api.page(slug)).raw, `${slug} is back as it was`).toBe(before[slug]);
  }
});

test('the block editor opens a page with every block valid, and an edit to a title reaches the site', async ({ page }) => {
  const api = await restFor(page);
  const celebrate = await api.page('celebrate');
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));

  try {
    await page.goto(`/wp-admin/post.php?post=${celebrate.id}&action=edit`);
    await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0);
    await page.evaluate(() => {
      window.wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
      window.wp.data.dispatch('core/preferences').set('core/edit-post', 'fullscreenMode', false);
    });

    // Each block is known to the editor and matches what was saved.
    const blocks = await page.evaluate(() => window.wp.data.select('core/block-editor').getBlocks().map((b) => ({ name: b.name, valid: b.isValid })));
    expect(blocks).toEqual([
      { name: 'brodfest/text-section', valid: true },
      { name: 'brodfest/text-section', valid: true },
    ]);

    // Its words are edited on the canvas, in the section's own fields.
    const canvas = page.frameLocator('iframe[name="editor-canvas"]');
    await canvas.getByLabel('Title', { exact: true }).first().fill('CELEBRATION, EDITED');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect.poll(async () => (await api.page('celebrate')).raw, { timeout: 15000 }).toContain('"title":"CELEBRATION, EDITED"');
    const site = await page.request.get('/celebrate/');
    expect(await site.text()).toContain('CELEBRATION, EDITED');
    expect(errors).toEqual([]);
  } finally {
    await api.post(`/wp/v2/pages/${celebrate.id}`, { content: celebrate.raw });
  }
});

test('a hotel list takes at most six hotels, and its add button stops there', async ({ page }) => {
  const api = await restFor(page);
  const accommodation = await api.page('accommodation');

  await page.goto(`/wp-admin/post.php?post=${accommodation.id}&action=edit`);
  await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0);
  await page.evaluate(() => {
    window.wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
    const blocks = window.wp.data.select('core/block-editor').getBlocks();
    const area = blocks.find((b) => b.name === 'brodfest/area');
    const hotel = { name: 'X', text: '', url: '', image: '', imageId: 0, imageAlt: '', imageDecorative: false, offer: '' };
    window.wp.data.dispatch('core/block-editor').updateBlockAttributes(area.clientId, { hotels: Array.from({ length: 6 }, () => hotel) });
    window.wp.data.dispatch('core/block-editor').selectBlock(area.clientId);
  });

  const canvas = page.frameLocator('iframe[name="editor-canvas"]');
  await expect(canvas.getByRole('button', { name: 'Add a hotel' }).first()).toBeDisabled();
  // The page is left as it was: nothing was saved.
});

test('every page, and every held-back Invite page, opens in the block editor with all its blocks valid', async ({ page }) => {
  const api = await restFor(page);
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));

  const urls = [];
  for (const slug of Object.keys(STD_PAGES)) urls.push(`/wp-admin/post.php?post=${(await api.page(slug)).id}&action=edit`);
  for (const held of await api.get('/wp/v2/brodfest-held-pages?status=private&per_page=20&context=edit')) urls.push(`/wp-admin/post.php?post=${held.id}&action=edit`);
  expect(urls.length).toBe(Object.keys(STD_PAGES).length + 7);

  for (const url of urls) {
    await page.goto(url);
    await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0);
    const blocks = await page.evaluate(() => {
      const walk = (list) => list.flatMap((b) => [b, ...walk(b.innerBlocks)]);
      return walk(window.wp.data.select('core/block-editor').getBlocks()).map((b) => ({ name: b.name, valid: b.isValid }));
    });
    expect(blocks.length, url).toBeGreaterThan(0);
    for (const block of blocks) {
      expect(block.name, url).toMatch(/^brodfest\//);
      expect(block.valid, `${block.name} on ${url}`).toBe(true);
    }
  }
  expect(errors).toEqual([]);
});
