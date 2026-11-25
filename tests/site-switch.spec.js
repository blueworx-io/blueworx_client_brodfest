// Switching the live site when things go wrong: the Invite is never readable
// before the whole switch is done, nothing is lost, and an interrupted switch or
// setup is finished later. Faults are made by tests/fixtures/switch-faults.php.
import { test, expect } from '@playwright/test';
import { loginQuietly } from './helpers/wp.js';
import { fault, installFaults, removeFaults, report } from './helpers/switch.js';

test.describe.configure({ timeout: 180000 });

// Only in the Invite's Ibiza Recommendations page, one of the last to be moved.
const LATE_BLOCK = 'wp:brodfest/category';

async function adminFor(page) {
  await loginQuietly(page, test.info().project.use.baseURL);
  const nonce = (await (await page.request.get('/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  expect(nonce, 'signed in').not.toBe('0');
  const raw = async (method, path, data, headers = {}) => {
    const response = await page.request.fetch(`/wp-json${path}`, { method, headers: { 'X-WP-Nonce': nonce, ...headers }, ...(data ? { data } : {}) });
    return { status: response.status(), body: await response.json().catch(() => null) };
  };
  const act = async (what, value = 0) => {
    const out = await raw('POST', '/brodfest-test/v1/do', { what, value });
    expect(out.status, what).toBe(200);
    return out.body;
  };
  return {
    raw,
    act,
    async page(slug) {
      const [found] = (await raw('GET', `/wp/v2/pages?slug=${slug}&context=edit&status=publish`)).body;
      return { id: found.id, raw: found.content.raw, count: found.brodfest_swap_count };
    },
    async held(title) {
      const held = (await raw('GET', '/wp/v2/brodfest-held-pages?status=private&per_page=50&context=edit')).body;
      return held.find((h) => h.title.raw.startsWith(`${title} (`));
    },
    // What every page and held-back page holds, as it is stored, and which site is live.
    async stored() {
      const { posts, synced } = await act('state');
      return { synced, posts };
    },
  };
}

const contents = (stored) => stored.posts.map((p) => p.post_content);

// Saves the choice of live site, as the settings screen does (tests/settings.spec.js
// and tests/blocks.spec.js go through the screen itself).
const chooseSite = (admin, site) => admin.act('site', site);

// Nothing a signed-out reader can ask for has any of the Invite's words.
async function expectNoInvite(browser) {
  const visitor = await browser.newContext({ baseURL: test.info().project.use.baseURL });
  try {
    for (const path of [
      '/',
      '/celebrate/',
      '/accommodation/',
      '/can-sakhara-story/',
      '/recommendations/',
      '/celebrate/feed/',
      '/celebrate/embed/',
      '/wp-json/wp/v2/pages?per_page=100',
      '/feed/?post_type=page',
      '/?s=sakhara',
      '/?s=ipsum&feed=rss2',
      '/wp-json/wp/v2/brodfest-held-pages?per_page=100',
    ]) {
      const text = await (await visitor.request.get(path)).text();
      expect(text, path).not.toContain('Lorem ipsum');
      expect(text, path).not.toContain('CAN SAKHARA STORY');
    }
    for (const path of ['/wp-json/wp/v2/pages?search=ipsum', '/wp-json/wp/v2/search?search=ipsum']) {
      expect(await (await visitor.request.get(path)).json(), `${path} finds nothing`).toEqual([]);
    }
    const pages = await (await visitor.request.get('/wp-json/wp/v2/pages?per_page=100')).json();
    for (const one of pages) {
      expect(await (await visitor.request.get(`/wp-json/wp/v2/pages/${one.id}`)).text(), one.slug).not.toContain('Lorem ipsum');
    }
    for (const kind of ['revisions', 'autosaves']) {
      expect([401, 403, 404]).toContain((await visitor.request.get(`/wp-json/wp/v2/pages/${pages[0].id}/${kind}`)).status());
    }
    expect(await (await visitor.request.get('/')).text()).toContain('brodfest-site-save-the-date');
  } finally {
    await visitor.close();
  }
}

test.beforeAll(() => installFaults());
test.afterAll(() => removeFaults());

// Whatever a test did, the site ends on the Save the Date with nothing held.
test.afterEach(async ({ page }) => {
  fault(null);
  const admin = await adminFor(page);
  await admin.act('alterOnWrite', 0);
  await admin.act('unlock');
  await admin.act('expire');
  await chooseSite(admin, 'save-the-date');
  await admin.act('expire');
  await page.request.get('/');
  expect((await admin.act('state')).synced).toBe('save-the-date');
});

test('nobody can read the Invite at any moment of a switch before it is done', async ({ page }) => {
  const admin = await adminFor(page);
  fault({ watch: true });
  await chooseSite(admin, 'invite');
  const seen = report();
  fault(null);
  expect(seen.looks.length, 'the switch was watched').toBeGreaterThan(5);
  expect(seen.early ?? []).toEqual([]);
  expect((await admin.act('state')).synced).toBe('invite');
  expect((await admin.page('recommendations')).raw).toContain(LATE_BLOCK);
});

test('a switch that fails part way shows nobody the Invite, changes nothing, and is finished later', async ({ page, browser }) => {
  const admin = await adminFor(page);
  const before = await admin.stored();
  fault({ watch: true, onPostWrite: LATE_BLOCK });
  await chooseSite(admin, 'invite');

  expect(report().early ?? [], 'read by a visitor during the failed switch').toEqual([]);
  expect(await admin.stored(), 'everything is exactly as it was').toEqual(before);
  await expectNoInvite(browser);
  await page.goto('/wp-admin/edit.php?post_type=page');
  await expect(page.getByText('The live site could not be switched')).toBeVisible();

  // The next try, after the delay, finishes it.
  fault(null);
  await admin.act('expire');
  await page.request.get('/');
  expect((await admin.act('state')).synced).toBe('invite');
  expect((await admin.page('recommendations')).raw).toContain(LATE_BLOCK);
  await page.goto('/wp-admin/edit.php?post_type=page');
  await expect(page.getByText('The live site could not be switched')).toHaveCount(0);

  await chooseSite(admin, 'save-the-date');
  expect(contents(await admin.stored()), 'and switching back restores it all').toEqual(contents(before));
});

test('a page the database stored differently is put back before the switch gives up', async ({ page, browser }) => {
  const admin = await adminFor(page);
  const before = await admin.stored();
  await admin.act('alterOnWrite', (await admin.page('celebrate')).id);
  await chooseSite(admin, 'invite');

  expect(await admin.stored(), 'everything is exactly as it was').toEqual(before);
  await expectNoInvite(browser);
});

test('a plugin that rewrites pages as they are saved cannot change the words a switch moves', async ({ page }) => {
  const admin = await adminFor(page);
  const before = await admin.stored();
  const invite = (await admin.held('Celebrate')).content.raw;
  fault({ rewriteOnSave: true });
  await chooseSite(admin, 'invite');
  expect((await admin.page('celebrate')).raw).toBe(invite);
  await chooseSite(admin, 'save-the-date');
  expect(contents(await admin.stored())).toEqual(contents(before));
});

test('a switch cut off part way leaves the site as it was, and the next try finishes it', async ({ page, browser }) => {
  const admin = await adminFor(page);
  const before = await admin.stored();
  const invite = (await admin.held('Ibiza Recommendations')).content.raw;
  const celebrate = await admin.page('celebrate');
  expect(invite).toContain(LATE_BLOCK);

  // The request that saves the choice ends in the middle of the switch.
  fault({ onPostWrite: LATE_BLOCK, crash: true });
  await admin.raw('POST', '/brodfest-test/v1/do', { what: 'site', value: 'invite' });

  // Before anything is tried again, the site is exactly as it was.
  fault({ noRetry: true });
  expect(await admin.stored()).toEqual(before);
  await expectNoInvite(browser);

  fault(null);
  await admin.act('unlock');
  await admin.act('expire');
  await page.request.get('/');

  const state = await admin.act('state');
  expect(state.synced).toBe('invite');
  expect((await admin.page('recommendations')).raw).toBe(invite);
  expect((await admin.held('Celebrate')).content.raw).toBe(celebrate.raw);
  expect(state.posts.filter((p) => p.post_title.startsWith('Recovery')), 'nothing left lying about').toEqual([]);

  await chooseSite(admin, 'save-the-date');
  expect(contents(await admin.stored())).toEqual(contents(before));
  await expectNoInvite(browser);
});

test('a save is checked against the swap count as it is once the lock is held', async ({ page }) => {
  const admin = await adminFor(page);
  // The count has to be there to be changed: one switch there and back.
  await chooseSite(admin, 'invite');
  await chooseSite(admin, 'save-the-date');
  const celebrate = await admin.page('celebrate');
  expect(celebrate.count).toBeGreaterThan(0);

  fault({ switchedWhileWaiting: celebrate.id });
  const stale = await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { title: 'Celebrate' }, { 'X-Brodfest-Swap-Count': String(celebrate.count) });
  fault(null);
  expect(stale.status).toBe(409);
  expect(stale.body.code).toBe('brodfest_site_switched');
  expect((await admin.page('celebrate')).raw).toBe(celebrate.raw);
});

test('restoring a revision holds the lock, and waits its turn behind a switch', async ({ page }) => {
  const admin = await adminFor(page);
  const celebrate = await admin.page('celebrate');
  const edited = celebrate.raw.replace('"title":"CELEBRATION"', '"title":"CELEBRATION REVISED"');
  await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: edited });
  await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: celebrate.raw });
  const revisions = (await admin.raw('GET', `/wp/v2/pages/${celebrate.id}/revisions?per_page=5&context=edit`)).body;
  const old = revisions.find((r) => r.content.raw === edited);
  const restore = async () => page.request.get(`/wp-admin/revision.php?revision=${old.id}&action=restore&_wpnonce=${await admin.act('restoreNonce', old.id)}`, { maxRedirects: 0 });

  try {
    // Somebody else's switch is running: nothing is restored.
    await admin.act('lock');
    const refused = await restore();
    expect(refused.status()).toBe(409);
    expect((await admin.page('celebrate')).raw).toBe(celebrate.raw);
    await admin.act('unlock');

    // Otherwise it goes through, with the lock held throughout and let go after.
    fault({});
    expect((await restore()).status()).toBe(302);
    expect((await admin.page('celebrate')).raw).toBe(edited);
    expect(report().lockDuringRestore).toEqual([true]);
    expect((await admin.act('state')).lock).toBeNull();
  } finally {
    await admin.act('unlock');
    await admin.raw('POST', `/wp/v2/pages/${celebrate.id}`, { content: celebrate.raw });
  }
});

test('an autosave WordPress uses again is tagged with the site it is now for', async ({ page }) => {
  const admin = await adminFor(page);
  const celebrate = await admin.page('celebrate');
  const autosave = async (content) => (await admin.raw('POST', `/wp/v2/pages/${celebrate.id}/autosaves`, { content, title: 'Celebrate' })).body.id;
  const restored = async (id) => (await page.request.get(`/wp-admin/revision.php?revision=${id}&action=restore`)).status();

  const first = await autosave(celebrate.raw.replace('"title":"CELEBRATION"', '"title":"CELEBRATION ONE"'));
  expect(await restored(first)).not.toBe(409);

  await chooseSite(admin, 'invite');
  // Made on the Save the Date: not for this page now.
  expect(await restored(first)).toBe(409);
  const invite = (await admin.page('celebrate')).raw;
  const again = await autosave(invite.replace('"title":"CELEBRATE"', '"title":"CELEBRATE TWO"'));
  expect(again, 'WordPress used the same autosave again').toBe(first);
  expect(await restored(again), 'an Invite autosave restores on the Invite').not.toBe(409);

  await chooseSite(admin, 'save-the-date');
  expect(await restored(again), 'and not on the Save the Date').toBe(409);
});

test('an update whose page setup did not finish is tried again, with no switch of site', async ({ page }) => {
  const admin = await adminFor(page);
  const celebrate = await admin.page('celebrate');
  const held = await admin.held('Celebrate');
  const { version } = await admin.act('state');

  // An update finds one page not set up, but something else has the lock.
  await admin.raw('DELETE', `/wp/v2/brodfest-held-pages/${held.id}?force=true`);
  await admin.act('lock');
  await admin.act('expire');
  await admin.act('version', '0.0.1');
  await page.request.get('/');
  const after = (await admin.act('state')).version;

  // The delay passes, and the setup finishes by itself.
  await admin.act('unlock');
  await admin.act('expire');
  await page.request.get('/');
  const finished = await admin.held('Celebrate');
  await admin.act('version', version);
  expect(finished, 'the held-back page is back').toBeDefined();
  expect(after, 'not recorded as done while it was not').toBe('0.0.1');
  expect((await admin.page('celebrate')).raw).toBe(celebrate.raw);
});

test('choosing the Invite before the pages have ever been set up shows nobody the Invite', async ({ page, browser }) => {
  const admin = await adminFor(page);
  // The lock first, so nothing can finish the setup in between.
  await admin.act('lock');
  await admin.act('neverSynced');
  await chooseSite(admin, 'invite');
  await expectNoInvite(browser);
});
