// The shared page lock (blueworx-page-lock/) on the site's pages: a client login
// can change words, images, links and list items and show or hide a section,
// but cannot add, move, remove or unlock a section; a save through the REST API
// (which is what a connected tool uses) that changes the structure is refused.
// Also: each page type has a whole-page layout, and a page draws only what is
// saved in it.
import { test, expect } from '@playwright/test';
import { loginQuietly, skipIntro } from './helpers/wp.js';

const LOCK_META = '_blueworx_page_lock';
const PAGES = ['home', 'celebrate', 'accommodation', 'enquiries', 'rsvp'];
const CLIENT = { user: 'brodfest-test-client', pass: 'Cl13nt-test-pass-9731!' };

// A REST helper for whoever is signed in on this page: the nonce rides along.
async function restFor(page, credentials) {
  await loginQuietly(page, test.info().project.use.baseURL, credentials);
  const nonce = (await (await page.request.get('/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  expect(nonce, 'signed in').not.toBe('0');
  const raw = async (method, path, data) => {
    const response = await page.request.fetch(`/wp-json${path}`, { method, headers: { 'X-WP-Nonce': nonce }, ...(data ? { data } : {}) });
    return { status: response.status(), body: await response.json().catch(() => null) };
  };
  return {
    raw,
    async call(method, path, data) {
      const out = await raw(method, path, data);
      expect(out.status, `${method} ${path}`).toBeLessThan(300);
      return out.body;
    },
    async page(slug) {
      const out = await raw('GET', `/wp/v2/pages?slug=${slug}&context=edit&status=publish`);
      const [found] = out.body;
      return { id: found.id, raw: found.content.raw, meta: found.meta };
    },
  };
}

// Runs a test body with a client login (an Editor, as Mark's is) that exists
// for the length of the test only.
async function withClient(browser, admin, body) {
  const made = await admin.call('POST', '/wp/v2/users', {
    username: CLIENT.user,
    email: 'brodfest-test-client@example.com',
    password: CLIENT.pass,
    roles: ['editor'],
  });
  const context = await browser.newContext();
  try {
    const clientPage = await context.newPage();
    const client = await restFor(clientPage, CLIENT);
    await body(client, clientPage);
  } finally {
    await context.close();
    await admin.raw('DELETE', `/wp/v2/users/${made.id}?force=true&reassign=1`);
  }
}

// A page's saved blocks one by one (this page has none inside another).
const blocksOf = (raw) => raw.split(/(?=<!-- wp:)/);

test('every page and its held-back page is locked from the start', async ({ page }) => {
  const admin = await restFor(page);

  for (const slug of PAGES) {
    const found = await admin.page(slug);
    expect(found.meta[LOCK_META], `${slug} is locked`).toBe(true);
  }

  const held = await admin.call('GET', '/wp/v2/brodfest-held-pages?status=private&per_page=20&context=edit');
  expect(held.length).toBeGreaterThanOrEqual(PAGES.length);
  for (const one of held) {
    expect(one.meta?.[LOCK_META], `${one.title.raw} is locked`).toBe(true);
  }
});

test('a client can change words and hide a section, but not add, move, remove or unlock one', async ({ page, browser }) => {
  const admin = await restFor(page);
  const accommodation = await admin.page('accommodation');
  const original = accommodation.raw;

  await withClient(browser, admin, async (client) => {
    const save = (content) => client.raw('POST', `/wp/v2/pages/${accommodation.id}`, { content });
    const sections = blocksOf(original);
    expect(sections.length).toBeGreaterThan(3);

    try {
      // Words: allowed.
      const reworded = original.replace('"title":"INFORMATION"', '"title":"INFORMATION, EDITED"');
      expect(reworded).not.toBe(original);
      const worded = await save(reworded);
      expect(worded.status).toBe(200);
      expect((await admin.page('accommodation')).raw).toBe(reworded);

      // Show or hide a section: allowed, and the page leaves it out.
      const hidden = reworded.replace('<!-- wp:brodfest/text-section {"title":"INFORMATION, EDITED"', '<!-- wp:brodfest/text-section {"hidden":true,"title":"INFORMATION, EDITED"');
      expect(hidden).not.toBe(reworded);
      expect((await save(hidden)).status).toBe(200);
      const visitor = await browser.newContext();
      try {
        const seen = await (await visitor.request.get('/accommodation/')).text();
        expect(seen).not.toContain('INFORMATION, EDITED');
        expect(seen).toContain('TRANSPORT');
      } finally {
        await visitor.close();
      }
      expect((await save(original)).status).toBe(200);

      // Add a section: refused.
      const added = await save(`${original}\n\n<!-- wp:brodfest/text-section {"title":"ADDED"} /-->`);
      expect(added.status).toBe(403);
      expect(added.body.code).toBe('blueworx_page_locked');

      // Remove a section: refused.
      expect((await save(sections.slice(1).join(''))).status).toBe(403);

      // Move a section: refused.
      const moved = [sections[sections.length - 1], ...sections.slice(1, -1), sections[0]];
      expect((await save(moved.join(''))).status).toBe(403);

      // Unlock the page: refused, and it stays locked.
      const unlocked = await client.raw('POST', `/wp/v2/pages/${accommodation.id}`, { meta: { [LOCK_META]: false } });
      expect(unlocked.status).toBeGreaterThanOrEqual(400);
      expect((await admin.page('accommodation')).meta[LOCK_META]).toBe(true);

      // Nothing the refused saves tried is on the page.
      expect((await admin.page('accommodation')).raw).toBe(original);
    } finally {
      await admin.raw('POST', `/wp/v2/pages/${accommodation.id}`, { content: original, meta: { [LOCK_META]: true } });
    }
  });
});

test('a held-back page is locked the same way', async ({ page, browser }) => {
  const admin = await restFor(page);
  const held = (await admin.call('GET', '/wp/v2/brodfest-held-pages?status=private&per_page=20&context=edit')).find((h) => h.title.raw.startsWith('Celebrate ('));
  const original = held.content.raw;

  await withClient(browser, admin, async (client) => {
    const save = (content) => client.raw('POST', `/wp/v2/brodfest-held-pages/${held.id}`, { content });
    try {
      expect((await save(original.replace('"title":"CELEBRATE"', '"title":"CELEBRATE, EDITED"'))).status).toBe(200);
      expect((await save(`${original}\n\n<!-- wp:brodfest/text-section {"title":"ADDED"} /-->`)).status).toBe(403);
    } finally {
      await admin.raw('POST', `/wp/v2/brodfest-held-pages/${held.id}`, { content: original });
    }
  });
});

test('an administrator can still change the structure, and unlock a page', async ({ page }) => {
  const admin = await restFor(page);
  const enquiries = await admin.page('enquiries');

  try {
    const added = await admin.raw('POST', `/wp/v2/pages/${enquiries.id}`, { content: `${enquiries.raw}\n\n<!-- wp:brodfest/text-section {"title":"ADDED BY AN ADMINISTRATOR"} /-->` });
    expect(added.status).toBe(200);
    const unlocked = await admin.raw('POST', `/wp/v2/pages/${enquiries.id}`, { meta: { [LOCK_META]: false } });
    expect(unlocked.status).toBe(200);
    expect((await admin.page('enquiries')).meta[LOCK_META]).toBe(false);
  } finally {
    await admin.raw('POST', `/wp/v2/pages/${enquiries.id}`, { content: enquiries.raw, meta: { [LOCK_META]: true } });
  }
  expect((await admin.page('enquiries')).raw).toBe(enquiries.raw);
});

test('switching the live site works with the lock on, and leaves every page locked', async ({ page }) => {
  const admin = await restFor(page);
  const before = (await admin.page('celebrate')).raw;

  const choose = async (label) => {
    await page.goto('/wp-admin/admin.php?page=brodfest');
    await page.getByRole('radio', { name: label }).check();
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByText('Everything is saved')).toBeVisible();
  };

  await choose('Invite');
  try {
    expect((await admin.page('celebrate')).raw).toContain('"title":"CELEBRATE"');
    expect((await admin.page('can-sakhara-story')).raw).toContain('wp:brodfest/story-banner');
    for (const slug of [...PAGES, 'can-sakhara-story', 'recommendations']) {
      expect((await admin.page(slug)).meta[LOCK_META], `${slug} is still locked`).toBe(true);
    }
  } finally {
    await choose('Save the Date');
  }
  expect((await admin.page('celebrate')).raw).toBe(before);
});

test('a client opens the editor with only the Brodfest sections, no code editor and no add button', async ({ page, browser }) => {
  const admin = await restFor(page);
  const celebrate = await admin.page('celebrate');

  await withClient(browser, admin, async (_client, clientPage) => {
    await clientPage.goto(`/wp-admin/post.php?post=${celebrate.id}&action=edit`);
    await clientPage.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0);

    const seen = await clientPage.evaluate(() => {
      const settings = window.wp.data.select('core/block-editor').getSettings();
      return {
        code: window.wp.data.select('core/editor').getEditorSettings().codeEditingEnabled,
        lock: settings.templateLock,
        allowed: settings.allowedBlockTypes,
      };
    });
    expect(seen.code).toBe(false);
    expect(seen.lock).toBe('all');
    expect(Array.isArray(seen.allowed)).toBe(true);
    expect(seen.allowed.length).toBeGreaterThan(0);
    for (const name of seen.allowed) expect(name, 'only section blocks').toMatch(/^brodfest\//);

    await expect(clientPage.locator('.editor-document-tools__inserter-toggle, .edit-post-header-toolbar__inserter-toggle')).toBeHidden();

    // What a client may still do in the editor: type on the section, and show
    // or hide it from the block tab, then save.
    try {
      await clientPage.evaluate(() => {
        window.wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
        window.wp.data.dispatch('core/preferences').set('core/edit-post', 'fullscreenMode', false);
        const [first] = window.wp.data.select('core/block-editor').getBlocks();
        window.wp.data.dispatch('core/block-editor').selectBlock(first.clientId);
      });
      const canvas = clientPage.frameLocator('iframe[name="editor-canvas"]');
      await canvas.getByLabel('Title', { exact: true }).first().fill('CELEBRATION, BY A CLIENT');
      await clientPage.getByRole('checkbox', { name: 'Show this section on the site' }).uncheck();
      await clientPage.getByRole('button', { name: 'Save', exact: true }).click();
      await expect
        .poll(async () => (await admin.page('celebrate')).raw, { timeout: 15000 })
        .toContain('"hidden":true');
      const saved = (await admin.page('celebrate')).raw;
      expect(saved).toContain('CELEBRATION, BY A CLIENT');
      expect(blocksOf(saved).length, 'the same sections').toBe(blocksOf(celebrate.raw).length);
    } finally {
      await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: celebrate.raw });
    }
  });
});

const ourLayouts = async (admin) => (await admin.call('GET', '/wp/v2/block-patterns/patterns')).filter((p) => p.name.startsWith('brodfest/page-'));

test('every page type of the live site has a whole-page layout, and the Invite layouts stay private until it is live', async ({ page }) => {
  const admin = await restFor(page);
  const ours = await ourLayouts(admin);
  const names = ours.map((p) => p.name);

  for (const slug of ['home', 'celebrate', 'accommodation', 'enquiries', 'rsvp']) {
    expect(names.some((n) => n.startsWith(`brodfest/page-${slug}-`)), `${slug} has a layout`).toBe(true);
  }
  // Nothing of the Invite's is in the pattern list, which anyone who can write a post can read.
  expect(names.filter((n) => n.endsWith('-invite'))).toEqual([]);
  expect(names.some((n) => /can-sakhara-story|recommendations/.test(n))).toBe(false);
  expect(JSON.stringify(ours)).not.toContain('Lorem ipsum');
  for (const pattern of ours) {
    // Section blocks and nothing else, with the words already in them.
    expect(pattern.content.replace(/<!--[\s\S]*?-->/g, '').trim(), pattern.name).toBe('');
    expect(pattern.content, pattern.name).toMatch(/<!-- wp:brodfest\//);
    expect(pattern.content, pattern.name).not.toMatch(/<!-- wp:(?!brodfest\/)/);
    expect(pattern.post_types, pattern.name).toContain('page');
    expect(pattern.block_types, pattern.name).toContain('core/post-content');
  }
});

test('once the Invite is live, every page type has a layout for it', async ({ page }) => {
  const admin = await restFor(page);
  await page.goto('/wp-admin/admin.php?page=brodfest');
  await page.getByRole('radio', { name: 'Invite' }).check();
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText('Everything is saved')).toBeVisible();
  try {
    const names = (await ourLayouts(admin)).map((p) => p.name);
    for (const slug of ['home', 'celebrate', 'can-sakhara-story', 'accommodation', 'recommendations', 'enquiries', 'rsvp']) {
      expect(names.some((n) => n.startsWith(`brodfest/page-${slug}-`)), `${slug} has a layout`).toBe(true);
    }
  } finally {
    await page.goto('/wp-admin/admin.php?page=brodfest');
    await page.getByRole('radio', { name: 'Save the Date' }).check();
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByText('Everything is saved')).toBeVisible();
  }
});

test('a page made from the Home layout is drawn with the Brodfest styles and scripts', async ({ page, browser }) => {
  const admin = await restFor(page);
  const layout = (await ourLayouts(admin)).find((p) => p.name === 'brodfest/page-home-save-the-date');
  const made = await admin.raw('POST', '/wp/v2/pages', { title: 'A second home from a layout', status: 'publish', content: layout.content });
  expect(made.status).toBe(201);
  const visitor = await browser.newContext();
  try {
    const html = await (await visitor.request.get(made.body.link)).text();
    expect(html).toMatch(/id=['"]brodfest-public-css['"]/);
    expect(html).toMatch(/id=['"]brodfest-public-js['"]/);
    expect(html).toContain('data-bf-opening');
    expect(html).toContain('bf-hero');
  } finally {
    await visitor.close();
    await admin.raw('DELETE', `/wp/v2/pages/${made.body.id}?force=true`);
  }
});

test('a new page can be made from a layout in one save, and then its sections are fixed', async ({ page, browser }) => {
  const admin = await restFor(page);
  const patterns = await admin.call('GET', '/wp/v2/block-patterns/patterns');
  const layout = patterns.find((p) => p.name === 'brodfest/page-enquiries-save-the-date');
  expect(layout, 'the Enquiries layout').toBeTruthy();

  await withClient(browser, admin, async (client) => {
    const made = await client.raw('POST', '/wp/v2/pages', { title: 'A new page from a layout', status: 'draft', content: layout.content });
    expect(made.status).toBe(201);
    const id = made.body.id;
    try {
      // New pages are locked from the moment they exist, for everyone.
      expect((await admin.raw('GET', `/wp/v2/pages/${id}?context=edit`)).body.meta[LOCK_META]).toBe(true);
      const changed = await client.raw('POST', `/wp/v2/pages/${id}`, { content: `${layout.content}\n\n<!-- wp:brodfest/text-section {"title":"ONE TOO MANY"} /-->` });
      expect(changed.status).toBe(403);
    } finally {
      await admin.raw('DELETE', `/wp/v2/pages/${id}?force=true`);
    }
  });
});

test('a page draws only what is saved in it', async ({ page, browser }) => {
  const admin = await restFor(page);
  const enquiries = await admin.page('enquiries');
  const visitor = await browser.newContext();

  try {
    // With nothing saved the page is empty: the plugin does not fill it in.
    expect((await admin.raw('POST', `/wp/v2/pages/${enquiries.id}`, { content: '' })).status).toBe(200);
    const empty = await (await visitor.request.get('/enquiries/')).text();
    expect(empty).not.toContain('PRIVATE CONCIERGE');
    expect(empty).not.toContain('bf-text');
  } finally {
    await visitor.close();
    await admin.raw('POST', `/wp/v2/pages/${enquiries.id}`, { content: enquiries.raw });
  }
  expect((await admin.page('enquiries')).raw).toBe(enquiries.raw);
});

test('hiding the opening section also leaves out the black screen it needs', async ({ page, browser }) => {
  const admin = await restFor(page);
  const home = await admin.page('home');
  const visitor = await browser.newContext();

  try {
    const seen = async () => (await (await visitor.request.get('/')).text());
    expect(await seen()).toContain('bf-is-opening');

    const hidden = home.raw.replace('<!-- wp:brodfest/opening /-->', '<!-- wp:brodfest/opening {"hidden":true} /-->');
    expect(hidden).not.toBe(home.raw);
    expect((await admin.raw('POST', `/wp/v2/pages/${home.id}`, { content: hidden })).status).toBe(200);

    const html = await seen();
    expect(html).not.toContain('bf-is-opening');
    expect(html).not.toContain('data-bf-opening');
    // The rest of the page is still there.
    expect(html).toContain('bf-hero');
  } finally {
    await visitor.close();
    await admin.raw('POST', `/wp/v2/pages/${home.id}`, { content: home.raw });
  }
});

test('a section the client hid shows again when switched back on', async ({ page }) => {
  await skipIntro(page);
  const admin = await restFor(page);
  const celebrate = await admin.page('celebrate');

  try {
    const hidden = celebrate.raw.replace('<!-- wp:brodfest/text-section {"title":"CELEBRATION"', '<!-- wp:brodfest/text-section {"hidden":true,"title":"CELEBRATION"');
    expect(hidden).not.toBe(celebrate.raw);
    await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: hidden });
    await page.goto('/celebrate/');
    await expect(page.locator('h1')).toHaveCount(0);
  } finally {
    await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: celebrate.raw });
  }
  await page.goto('/celebrate/');
  await expect(page.locator('h1')).toHaveText('CELEBRATION');
});

test('the calendar block inside the Home lettering is locked in the editor like every other section', async ({ page, browser }) => {
  const admin = await restFor(page);
  const home = await admin.page('home');

  await withClient(browser, admin, async (_client, clientPage) => {
    await clientPage.goto(`/wp-admin/post.php?post=${home.id}&action=edit`);
    await clientPage.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0);
    const read = () => clientPage.evaluate(() => {
      const select = window.wp.data.select('core/block-editor');
      const lettering = select.getBlocks().find((b) => b.name === 'brodfest/home-lettering');
      return { inner: lettering.innerBlocks.length, lock: select.getTemplateLock(lettering.clientId) };
    });
    // The inner list takes the page's lock once the section has drawn.
    await expect.poll(async () => (await read()).lock, { timeout: 15000 }).toBe('all');
    expect((await read()).inner).toBe(1);
  });
});
