# Theme design system and pixel-perfect review — implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Brodfest takes its colours and fonts from the Enhance theme's BlueWorx › Design screen, and every page matches the Figma design at 1920 and 767.

**Architecture:** The plugin's `:root` tokens in `assets/css/src/app.css` become `var(--color-*, <brodfest hex>)` lookups into the theme's named styles, so every existing rule follows the theme without being rewritten. The plugin keeps the theme's `blueworx-design` stylesheet on its pages, registers its fonts and a Brodfest preset with the theme's filters, and moves the SVG artwork's colours to CSS. A geometry spec pinned to Figma measurements then drives the pixel fixes.

**Tech Stack:** WordPress plugin (PHP 8.1), Tailwind v4 CLI plus hand-written CSS, esbuild, Playwright.

**Spec:** `docs/superpowers/specs/2026-11-04-theme-design-system-and-pixel-review-design.md`

## Global Constraints

- Brodfest values, used as fallbacks and as the preset: background `#0b0301`, gold `#d1954e`, orange `#a24300`, rust `#632800`, field `#210c07`, white `#ffffff`.
- Rust worked out exactly; field within 6 per RGB channel of `#210c07`.
- Orange titles use `--color-accent` as-is, never `--color-accent-text`.
- Sizes, spacing and letter spacing stay in the plugin.
- Agreed text sizes win over Figma's decimals: area 34, card title 30, card button 30, mobile RSVP pill 25, mobile page title 37, mobile area 25, mobile card title 22, mobile card button 22, mobile note 18, mobile menu 27. Gaps between elements match Figma; drift further down is accepted.
- Match = within 2px at the artboard width, colours exact, same fonts, weights, tracking, line heights.
- No new dependencies (`approved-deps.json`). No theme repo changes.
- Built CSS/JS is committed (`npm run build`). Version 0.3.0 with a changelog entry, in `package.json`, the plugin header and `BRODFEST_VERSION`.
- Tests run locally with the theme: `npm run wp:up`, then `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8881 WP_ADMIN_USER=admin WP_ADMIN_PASS=admin WP_LOGIN_PATH=/admin_login/ npx playwright test --workers=1` (Git Bash: prefix `MSYS_NO_PATHCONV=1`). CI runs without the theme; every spec must still execute tests there.

## Review Focus

1. **The theme is not active** (CI, or a site that switches theme): the page must look exactly as today. Pinned by Task 1's fallback test.
2. **The preset has not been applied yet** on a site with the theme: the theme defaults (blue on white) show. Nothing in code can stop this; the PR description and the changelog must say "apply the Brodfest preset on BlueWorx › Design after updating". Owned by Task 7.
3. **A colour changed on the Design screen leaves something behind** (an SVG, a translucent overlay, a field, a hover state). Pinned by Task 1 and Task 3's "follows the theme" tests, which cover every token, including the worked-out rust and field.
4. **The theme's `--gutter` overrides Brodfest's spacing** once the theme's stylesheet is on the page. Pinned by Task 2's test that the desktop text gutter still measures 178 at 1920.
5. **The menu over a bright video** must stay the page colour. Pinned by Task 4's test with the placeholder behind it.

---

### Task 1: Colours and fonts read the theme's names

**Files:**
- Modify: `assets/css/src/app.css` (`:root` block, lines 17–49, and the three `rgb(11 3 1 / …)` uses)
- Test: `tests/design-tokens.spec.js` (new)

**Interfaces:**
- Produces: the plugin tokens keep their names (`--bf-bg`, `--bf-gold`, `--bf-orange`, `--bf-rust`, `--bf-field`, `--bf-white`, `--bf-display`, `--bf-sans`), now defined as:
  - `--bf-bg: var(--color-bg, #0b0301)`
  - `--bf-gold: var(--color-text, #d1954e)`
  - `--bf-primary: var(--color-primary, #d1954e)` (new: RSVP pill, SEND, and their hovers keep using orange)
  - `--bf-orange: var(--color-accent, #a24300)`
  - `--bf-rust: color-mix(in srgb, var(--bf-orange) 58%, var(--bf-bg))` (tune the percentage to hit `#632800`)
  - `--bf-field:` a `color-mix()` of theme names that lands within 6/channel of `#210c07` (builder's recipe)
  - `--bf-display: var(--font-heading, "futura-pt", "Jost", "Futura", sans-serif)`
  - `--bf-sans: var(--font-body, "aller", "Lato", sans-serif)`
  - translucent backgrounds: `color-mix(in srgb, var(--bf-bg) N%, transparent)` with the same N as today

- [ ] **Step 1: Write the failing tests** in `tests/design-tokens.spec.js`, at 1920×1080 on `/celebrate/`, `/` and `/rsvp/` (use `skipIntro`):
  - `with no theme names set, the page shows the Brodfest colours`: after `page.addStyleTag({ content: ':root{--color-bg:initial;--color-text:initial;--color-primary:initial;--color-accent:initial;--font-heading:initial;--font-body:initial}' })`, assert computed: body background `rgb(11, 3, 1)`; `.bf-title` colour `rgb(162, 67, 0)`; `.bf-pill` background `rgb(209, 149, 78)`; `.bf-button--primary` background `rgb(99, 40, 0)`; an RSVP text field background within 6/channel of `rgb(33, 12, 7)`; `.bf-title` font-family starts with `futura-pt`; body font-family starts with `aller`.
  - `the colours follow the theme's names`: add `:root{--color-bg:#000010;--color-text:#00ff00;--color-primary:#ff0000;--color-accent:#0000ff;--font-heading:serif;--font-body:monospace}` and assert body background `rgb(0, 0, 16)`, body colour `rgb(0, 255, 0)`, `.bf-pill` background `rgb(255, 0, 0)`, `.bf-title` colour `rgb(0, 0, 255)`, `.bf-button--primary` background is not `rgb(99, 40, 0)`, `.bf-title` font-family `serif`, body font-family `monospace`.
- [ ] **Step 2: Run** `npx playwright test tests/design-tokens.spec.js` — expected: the "follow" test FAILS (colours are hard-coded).
- [ ] **Step 3: Rewrite the `:root` tokens** as in Interfaces; switch the pill/SEND fills to `--bf-primary`; replace the three `rgb(11 3 1 / …)` values.
- [ ] **Step 4: `npm run build`, rerun the spec** — expected: PASS, and the full suite still passes.
- [ ] **Step 5: Commit** `app.css`, built `assets/css/public.css`, the spec — "Take Brodfest's colours and fonts from the theme's design system".

### Task 2: The theme's styles reach Brodfest pages; fonts and preset registered

**Files:**
- Modify: `includes/assets.php` (sweep keep-list; `brodfest-public` dependencies)
- Create: `includes/design.php` (fonts and preset filters), required from `blueworx-client-brodfest.php` after `assets.php`
- Modify: `assets/css/src/app.css` — rename `--gutter` → `--bf-gutter`, `--text-gutter` → `--bf-text-gutter` everywhere
- Test: `tests/design-tokens.spec.js` (extend), `tests/pages.spec.js:24-30` (stylesheet test)

**Interfaces:**
- Produces: `brodfest_design_fonts( array $fonts ): array` on `blueworx_design_fonts` adding ids `futura-pt` (label `Futura PT`, family `futura-pt`, stack `"futura-pt", "Jost", "Futura", sans-serif`, files `array()`) and `aller` (label `Aller`, family `aller`, stack `"aller", "Lato", sans-serif`, files `array()`).
- Produces: `brodfest_design_presets( array $presets ): array` on `blueworx_design_presets` adding id `brodfest`, label `Brodfest`, description `Brodfest Ibiza: gold on near-black, Futura PT and Aller.`, settings `primary #d1954e, accent #a24300, background #0b0301, text #d1954e, heading_font futura-pt, body_font aller`.
- `brodfest-public` depends on `blueworx-design` only when `wp_style_is( 'blueworx-design', 'registered' )`; the sweep keeps `blueworx-design`.

- [ ] **Step 1: Write the failing tests:**
  - `the theme's design styles stay on Brodfest pages` (in `design-tokens.spec.js`): if `style#blueworx-design-inline-css` is absent on a non-Brodfest URL too (`/wp-login.php` is not suitable — use `/?p=1`), `test.skip` with "theme not active"; otherwise assert it is present on `/` and that `:root` `--color-bg` is non-empty there.
  - `Brodfest's fonts and preset are on the Design screen`: log in, go to `/wp-admin/admin.php?page=blueworx-design`; skip if the response is not 200; assert the page text contains `Futura PT`, `Aller` and `Brodfest`.
  - `the desktop text gutter is unchanged by the theme`: at 1920 on `/celebrate/`, the first `.bf-text` element's left padding is 178px.
  - Update `pages.spec.js` "loads only the plugin stylesheets" so it allows `blueworx-design` and still rejects `wp-block-library` and `blueworx-site-css`.
- [ ] **Step 2: Run against the theme harness** — expected: the first two FAIL.
- [ ] **Step 3: Implement** the keep-list change, the conditional dependency, `includes/design.php`, and the gutter rename.
- [ ] **Step 4: Build and run the full suite with the theme, then once with `PLAYWRIGHT_BASE_URL` pointed at a harness started without `--theme`** — expected: all PASS with the theme; without it the theme-only tests skip and the rest pass.
- [ ] **Step 5: Commit** — "Keep the theme's design styles on Brodfest pages and offer a Brodfest preset".

### Task 3: Artwork follows the theme

**Files:**
- Modify: `assets/img/{logo,date,mountain,icon-menu,icon-close,save-the-date,invitation,story-banner}.svg`
- Modify: `assets/css/src/app.css` (colour on the SVG containers)
- Test: `tests/design-tokens.spec.js` (extend)

**Interfaces:**
- Gold fills/strokes become `currentColor`; the lettering's orange becomes `currentColor` with `color: var(--bf-orange)` on `.bf-home__lettering`; the story banner's field fill gets `class="bf-fill-field"` with `.bf-fill-field { fill: var(--bf-field); }`. Shapes must stay identical (only colour attributes change).

- [ ] **Step 1: Failing test** `the artwork follows the theme`: with `:root{--color-text:#00ff00;--color-accent:#0000ff}`, assert the computed `fill` of the first `path` in `.bf-header__logo svg`, `.bf-footer__mountain svg` and `.bf-home__date svg` is `rgb(0, 255, 0)`, and of `.bf-home__lettering svg path` is `rgb(0, 0, 255)`; on the Invite preview (`/can-sakhara-story/?brodfest-site=invite` as admin) the story banner's field shape fill follows `--bf-field`.
- [ ] **Step 2: Run** — expected: FAIL (`rgb(209, 149, 78)`).
- [ ] **Step 3: Edit the SVGs and CSS.**
- [ ] **Step 4: Build, run the suite** — expected: PASS. Screenshot home, menu and story at 1920 before and after (scratchpad) and confirm no visible change with Brodfest colours.
- [ ] **Step 5: Commit** — "Let the logo, icons and lettering follow the theme's colours".

### Task 4: Solid menu background

**Files:**
- Modify: `assets/css/src/app.css:380-390` (`.bf-menu`)
- Figma: file `qVR5fdmsduo89gSF99GsQp`, Menu/Panel variants `10:38`, `10:45`, `24:71`, `24:77` — fill back to solid `#0b0301`, remove the background blur
- Test: `tests/design-tokens.spec.js` (extend)

- [ ] **Step 1: Failing test** `the open menu is the page colour over the video`: at 1920 on `/` (intro skipped), click `.bf-header__toggle`, wait for `.is-menu-open`; assert `.bf-menu` computed background equals body background with alpha 1 and `backdrop-filter` is `none`; take an element screenshot of `.bf-menu` and assert a pixel at its centre is `rgb(11, 3, 1)` (use `page.evaluate` on a canvas from the screenshot buffer, or compare to a solid-colour region — builder's choice).
- [ ] **Step 2: Run** — expected: FAIL (0.88 alpha, blur).
- [ ] **Step 3: Make `.bf-menu` background `var(--bf-bg)`, remove both `backdrop-filter` lines.** Update the comment.
- [ ] **Step 4: Build, run** — expected: PASS. Revert the four Figma variants with `use_figma` (load the figma-use skill first) and return their fills/effects as evidence.
- [ ] **Step 5: Commit** — "Give the open menu the page colour, as in the design".

### Task 5: Comparison tooling (scratch, not committed)

**Files:**
- Create in the scratchpad `tools/`: `figma-frames.mjs` (Figma REST, token at `C:/Users/LukeMcfarland/Downloads/blueworx_client_brodfest_figma_token.txt`, never printed), `site-shots.mjs` (Playwright), `overlay.html` or equivalent

**Interfaces:**
- Produces, per frame in the spec's page list (13 frames × desktop/mobile, Invite has no RSVP): a Figma PNG at scale 1 (`GET /v1/images/:key?ids=…&scale=1&format=png`), the site PNG at the same width (1920 or 767, full page, intro skipped, Invite pages via `?brodfest-site=invite` as admin, menu frames with the menu open), and a JSON list of every visible Figma TEXT/FRAME/RECTANGLE/INSTANCE with its box relative to the frame.
- Produces an overlay view (Figma at 50% over the site, plus a difference blend) for eyeballing, and a report of DOM boxes vs Figma boxes for the elements Task 6 pins.

- [ ] **Step 1:** Start the harness with the theme, sign in, apply the Brodfest preset (BlueWorx › Design, "Apply" on Brodfest), and confirm `/` looks as before.
- [ ] **Step 2:** Build the three tools; run them for Save the Date Home desktop; check the overlay lines up at the header.
- [ ] **Step 3:** Generate all frames. Nothing to commit.

### Task 6: Pixel-perfect pass

**Files:**
- Modify: `assets/css/src/app.css`, `templates/**` as the review finds
- Test: `tests/design-geometry.spec.js` (new)

**Interfaces:**
- Consumes: Task 5's boxes and overlays.
- Produces: `design-geometry.spec.js` — for each frame, at its artboard width, assertions that key element boxes match Figma within 2px: header height, logo box, toggle and RSVP pill boxes, page title top and size, first body text box, media/banner boxes, card width and gaps, button boxes, footer mountain. Values are copied from Figma (rounded), with the agreed text sizes where they differ; the gaps-not-positions rule applies below a resized line of text.

Work page by page in this order: Home, Menu, Celebrate, Enquiries, Accommodation, RSVP (Save the Date), then Invite Home, Celebrate, Story, Accommodation, Recommendations, Enquiries. For each page:

- [ ] **Step 1:** Add that page's geometry tests (desktop and mobile); run — expected: failures list exactly the mismatches.
- [ ] **Step 2:** Review the overlay for things geometry can't catch (colour, weight, tracking, line height, wrapping, icon artwork) and add a test for each real mismatch you fix where it can be measured (computed style).
- [ ] **Step 3:** Fix CSS/markup; build; rerun until the page's tests pass; regenerate its overlay and confirm visually.
- [ ] **Step 4:** Check 375, 1024, 1440 and 2560 for sideways scroll (`document.documentElement.scrollWidth <= innerWidth`) and overlaps; add the scroll check to the spec once, looping all pages.
- [ ] **Step 5:** Commit per page group — "Match <page> to the design".

Mismatches that are agreed differences (spec: Transport gone, map and Getting Here placeholders, placeholder copy, intro/animation) are not changed; list any other difference you decide not to fix, with the reason, for the final report.

### Task 7: Release prep

**Files:**
- Modify: `blueworx-client-brodfest.php` (header Version and `BRODFEST_VERSION` → 0.3.0), `package.json` (0.3.0), `readme.txt` (Stable tag, changelog), `CHANGELOG.md`

- [ ] **Step 1:** Bump to 0.3.0; changelog entry in the user's words: colours and fonts now come from BlueWorx › Design, with a Brodfest preset; the menu background fixed; every page matched to the design. Include: "After updating, apply the Brodfest preset on BlueWorx › Design, or the site shows the theme's default colours."
- [ ] **Step 2:** `npm run build`; full suite with the theme and once without; lint once (`npm run lint` and the PHPCS command from CI) — present findings, don't loop.
- [ ] **Step 3:** `npm run build:zip` (writes `../blueworx-client-brodfest-0.3.0.zip`, removes older ones); `unzip -l` and confirm every entry is `blueworx-client-brodfest/…` with forward slashes.
- [ ] **Step 4:** Commit; push the branch; open a PR into `build-site` whose description states the preset step plainly.
