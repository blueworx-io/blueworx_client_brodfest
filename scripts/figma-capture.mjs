// Renders Brodfest pages from the local test site (npm run wp:up) into
// self-contained HTML files for Figma's HTML import, so Figma can be brought
// back in line with the site: `node scripts/figma-capture.mjs [ids…]`, then
// hand each file in .figma-capture/ to the Figma import (html_to_figma).
// Each file has styles inlined with only the media rules that
// apply at the capture width, images and the video's frame as data URIs, no
// scripts. Desktop is drawn at Figma's full size (--u: 1px), as the Figma
// frames are; the site itself draws desktop at 0.75.
import { createRequire } from 'node:module';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const require = createRequire(import.meta.url);
const { chromium } = require('@playwright/test');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8881';
const LOGIN = process.env.WP_LOGIN_PATH || '/wp-login.php';
const OUT = join(import.meta.dirname, '..', '.figma-capture');
mkdirSync(OUT, { recursive: true });

const only = process.argv.slice(2);
const pages = [];
for (const site of ['std', 'inv']) {
  const q = site === 'inv' ? '?brodfest-site=invite' : '';
  const list = site === 'std'
    ? [['home', '/'], ['menu', '/', 'menu'], ['celebrate', '/celebrate/'], ['accommodation', '/accommodation/'], ['enquiries', '/enquiries/'], ['rsvp', '/rsvp/', 'rsvp'], ['rsvp-thanks', '/rsvp/', 'thanks']]
    : [['home', '/'], ['menu', '/', 'menu'], ['celebrate', '/celebrate/'], ['story', '/can-sakhara-story/'], ['accommodation', '/accommodation/'], ['recommendations', '/recommendations/'], ['enquiries', '/enquiries/']];
  for (const [name, path, mode] of list) for (const width of [1920, 767]) {
    let url = path + q;
    if (mode === 'thanks') url += (url.includes('?') ? '&' : '?') + 'rsvp=sent';
    pages.push({ id: `${site}-${name}_${width}`, site, url, mode, width });
  }
}

const browser = await chromium.launch();
for (const p of pages.filter((p) => !only.length || only.includes(p.id))) {
  const ctx = await browser.newContext({ viewport: { width: p.width, height: p.width === 1920 ? 2529 : 1640 } });
  const page = await ctx.newPage();
  await page.addInitScript(() => {
    localStorage.setItem('brodfest-entered', '1');
    HTMLMediaElement.prototype.play = () => Promise.reject(new DOMException('no', 'NotAllowedError'));
  });
  if (p.site === 'inv') {
    await page.goto(BASE + LOGIN);
    await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
  }
  await page.goto(BASE + p.url, { waitUntil: 'networkidle' });
  await page.addStyleTag({ content: '#wpadminbar{display:none!important} html{margin-top:0!important} *,*::before,*::after{transition:none!important;animation:none!important}' + (p.width === 1920 ? ' *{--u:1px!important}' : '') });
  await page.evaluate(() => document.fonts.ready);
  if (p.mode === 'menu') {
    await page.click('.bf-header__toggle');
    await page.waitForTimeout(300);
  }
  if (p.mode === 'rsvp') {
    await page.locator('input[name="attending"][value="yes"]').check();
    await page.locator('input[name="with_guest"][value="yes"]').check();
  }
  await page.waitForTimeout(300);

  const html = await page.evaluate(async () => {
    const toData = async (url) => {
      const r = await fetch(url);
      const b = await r.blob();
      return await new Promise((done) => { const f = new FileReader(); f.onload = () => done(f.result); f.readAsDataURL(b); });
    };
    // The video's opening frame, as a still.
    for (const v of document.querySelectorAll('video')) {
      await new Promise((done) => { if (v.readyState >= 2) return done(); v.addEventListener('loadeddata', done, { once: true }); setTimeout(done, 5000); });
      v.currentTime = 4;
      await new Promise((done) => { v.addEventListener('seeked', done, { once: true }); setTimeout(done, 3000); });
      const c = document.createElement('canvas');
      c.width = v.videoWidth; c.height = v.videoHeight;
      c.getContext('2d').drawImage(v, 0, 0);
      const img = document.createElement('img');
      img.src = c.toDataURL('image/jpeg', 0.85);
      img.className = v.className;
      img.setAttribute('style', 'width:100%;height:100%;object-fit:cover;display:block');
      v.replaceWith(img);
    }
    for (const b of document.querySelectorAll('.bf-hero__play, .bf-hero__sound, .bf-rsvp-panel, .bf-opening, .bf-skip')) b.remove();
    // Anything the page is not showing.
    for (const el of [...document.body.querySelectorAll('*')]) {
      if (!el.isConnected) continue;
      const cs = getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') el.remove();
    }
    // Inline SVGs take their colours from the stylesheet; write them onto
    // each shape so the import keeps them.
    for (const svg of document.querySelectorAll('svg')) {
      const r = svg.getBoundingClientRect();
      svg.setAttribute('width', r.width);
      svg.setAttribute('height', r.height);
      for (const el of [svg, ...svg.querySelectorAll('*')]) {
        const cs = getComputedStyle(el);
        for (const prop of ['fill', 'stroke', 'stroke-width', 'fill-opacity', 'stroke-opacity', 'stroke-linecap', 'stroke-linejoin', 'opacity']) {
          const v = cs.getPropertyValue(prop);
          if (v) el.setAttribute(prop, v);
        }
        el.removeAttribute('class');
      }
    }
    // Images: whichever source the browser chose, inlined.
    for (const img of document.querySelectorAll('img')) {
      if (img.src.startsWith('data:')) continue;
      const src = img.currentSrc || img.src;
      if (!src) continue;
      img.src = await toData(src);
      img.removeAttribute('srcset');
      img.removeAttribute('loading');
      const pic = img.closest('picture');
      if (pic) pic.querySelectorAll('source').forEach((s) => s.remove());
    }
    // Styles: every same-origin rule, media rules kept only where they apply now.
    const take = (rules) => {
      let out = '';
      for (const r of rules) {
        if (r instanceof CSSMediaRule) { if (matchMedia(r.conditionText).matches) out += take(r.cssRules); }
        else if (r instanceof CSSSupportsRule) { if (CSS.supports(r.conditionText)) out += take(r.cssRules); }
        else if (r instanceof CSSImportRule) { try { out += take(r.styleSheet.cssRules); } catch (e) { /* cross-origin */ } }
        else if (r instanceof CSSFontFaceRule) { /* fonts are named, not shipped */ }
        else out += r.cssText + '\n';
      }
      return out;
    };
    let css = '';
    for (const s of document.styleSheets) { try { css += take(s.cssRules); } catch (e) { /* cross-origin, e.g. the font kit */ } }
    const urls = [...new Set([...css.matchAll(/url\(["']?([^"')]+)["']?\)/g)].map((m) => m[1]).filter((u) => !u.startsWith('data:')))];
    for (const u of urls) { try { css = css.split(u).join(await toData(new URL(u, location.href).href)); } catch (e) { /* leave */ } }
    // 100vh is the artboard's height.
    css = css.replace(/100vh/g, innerHeight + 'px');
    document.querySelectorAll('link[rel="stylesheet"], style, script, noscript, #wpadminbar, link[rel="preload"], link[rel="modulepreload"]').forEach((n) => n.remove());
    const st = document.createElement('style');
    st.textContent = css + `\nhtml,body{width:${innerWidth}px;margin:0} html{margin-top:0!important}`;
    document.head.appendChild(st);
    return '<!doctype html>\n' + document.documentElement.outerHTML;
  });
  writeFileSync(join(OUT, p.id + '.html'), html);
  console.log(p.id, Math.round(html.length / 1024) + 'kB');
  await ctx.close();
}
await browser.close();
