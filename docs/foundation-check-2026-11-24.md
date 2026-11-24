# BrodFest P2 | 01. Check against Foundation rules and the V5.01 design

Checked on 24 Nov 2026, plugin version 0.15.2, against `bluegroup_core_foundation`
`CLAUDE.md.template`. This is a read-only check: no plugin code changed.

**Which task is which.** The card says "task 02, 03, 14 or 15" but not what each
one is. Used here: **02** = bring the plugin up to the rules (section blocks,
named styles, list fields, alt text, headings, tidy-up); **03** = page lock
(remove anything BrodFest built itself, add the shared lock); **14** = build the
Invite pages Home, Celebration, Can Sakhara Story; **15** = build the Invite
pages Information, Hotspots, Enquiries (and check RSVP). If the numbers mean
something else, only the labels need moving.

**The V5.01 design file was not available** (no Figma or email access in this
run). Part B is built from what the card lists plus what each Invite page holds
today. Anything marked "confirm" needs a look at the design file when the task
starts.

---

## Part A: gap list (the rules)

### Pages and editing (the big gap)

| # | Gap | Task |
|---|---|---|
| A1 | **Pages are fixed PHP templates, not section blocks.** `templates/pages/*.php` and `templates/parts/*.php` draw everything. The 7 pages are created with empty content (`post_content => ''`). There is no `blocks/` folder, no `block.json`, no `register_block_type`, and no block-pattern layout for new pages. | 02 |
| A2 | **Every word lives in PHP, so nobody can edit the site in WordPress.** Copy is in `includes/content.php` (`brodfest_std_copy()`, lorem placeholders, hotel text, links). Its own comment says "when editing moves into the block editor". A page is not "created with its words saved into it". | 02 |
| A3 | **Two sites in one set of templates.** Every template branches on `brodfest_is_invite()`. Once pages are blocks, the Save the Date and the Invite need to be separate page content (or separate layouts), not `if` branches. Also decides what "preview the other site" means after the move. | 02 |
| A4 | **Pages are not locked by the shared lock** (see Part C). | 03 |
| A5 | **Page ownership is a plugin-only mechanism**: `_brodfest_page` meta, `brodfest_install_pages()`, `brodfest_maybe_upgrade()`. Pages already exist on the live site, so the move to blocks must write block content into those same pages (never new pages, so URLs and the front page setting hold). | 02 |

### Lists, headings, images

| # | Gap | Task |
|---|---|---|
| A6 | **Repeating items are code arrays, not list fields**: hotels (`areas[].cards[]`), photos (celebrate strip, area banners), recommendation cards (`brodfest_recommendation_categories()`), enquiry contacts. No minimum/maximum, no empty-list handling. The card also lists "map pins": there are none today (the map is one flat PNG, `map.png` / `map-desktop.png`), so pins arrive with the glowing map (Part B). | 02 (hotels, enquiries) / 15 (map pins, hotspots) |
| A7 | **No alt text field beside any image.** Every `<img>` in the templates has `alt=""`: hotel photos (`cards.php`), area banners (`area.php`), the Celebrate strip (`celebrate.php`). Only the map has alt text, hard-coded. Hotel and area photos carry meaning, so they need real alt text or an explicit "decorative" mark. | 02 |
| A8 | **Heading order.** Mostly sound (one H1 per page, then H2, H3). To fix: (a) Home's only H1 is the date drawing (an SVG with an aria-label, no real text); (b) Enquiries on the Invite adds a hidden H1 and then every real title is an H2; (c) on Information (Save the Date) the three areas are H2 beside "Where to stay" (H2) when they belong under it as H3, with hotels as H4; (d) heading level is chosen per template, not per section. | 02 |
| A9 | Footer "mountain" and menu are drawn by code in `templates/parts/header.php` and `footer.php`. They are globals, so fine as code, but their words need to be editable (menu labels are in `brodfest_menu_label()`). | 02 |

### Styling

| # | Gap | Task |
|---|---|---|
| A10 | **Almost nothing uses the theme's named styles.** `assets/css/src/app.css` (1,868 lines) reads only four theme names (`--color-bg`, `--color-text`, `--color-primary`, `--color-accent`, plus `--font-heading`/`--font-body`), each with a hard-coded fallback hex. `--space-*` and `--text-*` are used 0 times. About 270 sizes are typed as `calc(N * var(--u))` (Figma pixels), including every font size. `--bf-white: #ffffff` and the pink/blue placeholder colours (`#d800ba`, `#009ad6`) are hard-coded. The foundation check (`check-section-styles.mjs`) only judges files under `blocks/`, so it passes today, but it will fail the section blocks as soon as they are written in this style. | 02 |
| A11 | **The "Figma-pixel" scale (`--u`) and the zoom-to-767 viewport script** (`render.php`) are a Brodfest-specific design system of their own. Not a rule break on its own, but sections cannot use `--text-*` / `--space-*` while sizes are `calc(N * var(--u))`. Needs a decision: map the design's sizes onto the theme's names (and add any missing name to the theme and Figma first). | 02 (decision first) |
| A12 | Fonts come from the client's Adobe Fonts kit (`use.typekit.net/vjw6kra.css`), not the theme. Intentional (licence) and already named through `blueworx_design_fonts`. No action unless the theme changes. | none |

### Dependencies, CI, updates, WordPress 7.2

| # | Check | Result | Task |
|---|---|---|---|
| A13 | Approved dependencies (`approved-deps.json`) | Passes (foundation script: "All dependencies are on the approved list"). No WordPress plugins are required. SureRank is not installed, as agreed. | none |
| A14 | Plugin header and `package.json` versions agree | Yes (0.15.2). `readme.txt` Stable tag matches; its changelog stops at 0.12.0 (stale). | 02 (tidy) |
| A15 | CI | `ci.yml` and `release.yml` use the foundation's `ci-wordpress.yml` / `release-wordpress.yml` at `@v1`, with local WordPress. No `wordpress_version` set, so CI does **not** run against 7.2-RC1 and has no Gutenberg run. Workflows are Luke's to change. | 02 (say what is needed) |
| A16 | Plugin update checker | Present and matches the foundation's bootstrap (v5.7 vendored, release assets on, token from `wp-config.php`). The plugin also forces its own auto-update (`includes/auto-updates.php`). Fine. | none |
| A17 | Admin design system copies | `assets/blueworx-admin-design.{css,php}`, `assets/blueworx-admin-icons.js`, `assets/blueworx-page-editor.js`, `blueworx-page-editor/` and `.claude/skills/blueworx-admin-design/` all match the foundation (compared ignoring line endings). The one admin screen (Brodfest settings) is built with the page editor library. | none |
| A18 | `CLAUDE.md` in this repo is an **old copy of the Foundation file**. It is missing the WordPress 7.2 section, the page-lock, section-block, SEO, Figma and Runner-mode sections. Re-pull from `CLAUDE.md.template` (this repo's own wording wins where it differs; there is none today). | 02 |
| A19 | WordPress 7.2 (8 Dec 2026): `readme.txt` says "Tested up to: 7.0"; no 7.2-RC1 run yet. Code check: no `load-scripts.php`/`load-styles.php` links, no React, no `defaultProps`, no hand-built SQL (only `$wpdb->update()` on the users table in `guests.php`, which WordPress prepares itself). Looks ready; needs a 7.2-RC1 test run, then "Tested up to: 7.2". | 02 |
| A20 | Tests | 17 Playwright specs, `workers: 1`. No PHPUnit. Lint is ESLint on `assets/js/src` plus a PHPCS config. Many specs assert the PHP-template markup (`.bf-*` classes, design geometry), so they will need rewriting as pages move to blocks. | 02 |
| A21 | RSVP guests are **WordPress users** (role `brodfest_guest`), not a post type. Rule: record-like things are a registered post type. This was a deliberate choice (Luke, 9 Nov) and the guest-list download depends on it. Decide whether it stays. | 02 (decision) |
| A22 | Theme: the plugin header says it runs on "BlueWorx Enhance"; `package.json` `wp:up` points at `../blueworx_labs_wordpress_theme`. The shared page lock goes in the theme (or the old BlueWorx plugin on sites without it). Confirm which one the live site runs. | 03 |

---

## Part C: what any lock BrodFest built itself does

**BrodFest has no page lock of its own.** There is no code that stops an editor
adding, moving or removing sections (there are no sections). What does restrict
things today, so task 03 knows what to keep or remove:

1. **"Brodfest page" source label** (`includes/enhance.php`, filter `blueworx_page_source`). On the Enhance theme this marks the 7 pages as protected: they can be viewed and edited but not trashed or deleted. Keep until the shared lock is in; then check the shared lock covers it.
2. **Invite pages bounce to Home while Save the Date is live** (`brodfest_redirect_other_site_pages()` in `includes/site.php`, on `template_redirect`). Visitors to `/can-sakhara-story/` or `/recommendations/` land on Home. This is a visibility switch, not an editing lock.
3. **Preview** (`?brodfest-site=invite`) only works for people who can `edit_pages`. Everyone else sees the live site.
4. **Sitemap** hides the other site's pages (`wp_sitemaps_posts_query_args`). Same purpose as 2.
5. **Pages are effectively un-editable**, but only because the content is empty and the PHP templates ignore it. Editors can change the title and nothing visible. Moving to blocks removes this by itself.
6. **Guest accounts** (`includes/guests.php`) have no capabilities and cannot sign in. That is about RSVP records, not pages. Leave.

Nothing here needs removing before the shared lock arrives. Remove item 5's
effect by moving to blocks (02), then add the shared lock (03), and decide
whether items 2 to 4 stay as the "hidden Invite" switch.

---

## Part B: difference list, Invite pages vs V5.01

Previewed on a local test site at `?brodfest-site=invite`. Almost all Invite copy
is lorem ipsum placeholder; the Save the Date copy is real and can be reused.

Menu today (Invite): Celebrate, Can Sakhara Story, Accommodation, Ibiza
Recommendations, Enquiries. V5.01 names: **Celebration, Can Sakhara Story,
Information, Hotspots, Enquiries.** (The Save the Date menu already says
Celebration and Information.) Page slugs (`celebrate`, `accommodation`,
`recommendations`) stay or get redirects: decide in task 14/15 so no link breaks.

### Home (task 14)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| Opening / Enter screen | Same as Save the Date. | Confirm unchanged. | 14 |
| Hero video | "Video to come" (no Invite video uploaded; Brodfest › Site › Invite video is empty). | The invite video. File needed from Mark, then upload in settings. Confirm sizes (1920×840 and square). | 14 |
| Date and lettering | Date drawing ("5 June 2027") over an "INVITATION" drawing (`date.svg` + `invitation.svg`). | "05.06.27 INVITATION" lettering: new artwork. Confirm whether it replaces both drawings. Keep an H1 with real text. | 14 |
| Add to Calendar | Hidden (Luke, 9 Nov). | Confirm. | 14 |
| Note under the button | "Please RSVP to confirm attendance". | Confirm wording. | 14 |

### Celebrate → Celebration (task 14)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| Page name | CELEBRATE (H1). | CELEBRATION. | 14 |
| Image strip | One wide photo (`celebrate-strip.jpg`), no alt text. | **5-image strip**, each with alt text, as a list field (min 5, max 5). Photos needed from Mark. Confirm how it shows on phones. | 14 |
| Welcome copy | Two lorem paragraphs. | Real copy (the Save the Date's "Ibiza is calling…" text exists; confirm it is the same). | 14 |
| Dress code | "Black, gold Ibiza bold" (H2). Save the Date says "Black, Gold & Ibiza Bold". | Confirm which wording. | 14 |
| Transport | Not on this page. | Moves to Information (see below). | 15 |

### Can Sakhara Story (task 14)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| Banner artwork | Placeholder: three plain mountain shapes on a dark block (`story-banner.svg`). | **The gold shapes artwork.** New artwork from Mark. Needs alt text or a decorative mark. | 14 |
| Story copy | Two lorem paragraphs. | Real copy; not in the card or repo. Needed from Mark. | 14 |
| Feature image | Grey box labelled "NIGHT TIME VILLA SHOT". | Real photo and alt text. Confirm it is still in V5.01. | 14 |

### Accommodation → Information (task 15)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| Page name and intro | ACCOMMODATION, one lorem paragraph. | INFORMATION. | 15 |
| Transport | None on the Invite. A "GETTING HERE" lorem block sits at the **bottom**. | Transport text at the **top**. The real text exists on the Save the Date. "Getting here" goes. | 15 |
| Map | Empty "MAP" placeholder box. | **Glowing Ibiza map**: new artwork. Pins for the three areas as a list field (area name, position, link to its section). Needs alt text. Confirm whether the glow is an image or drawn by code. | 15 |
| Areas | Playa d'en Bossa, Talamanca, Cala Llonga. Each: grey banner "PICTURE OF …", lorem intro, no distance line. | **3 areas.** Reuse the Save the Date area photos, intros and distances (15 / 20 / 25 mins). Banner needs alt text. Heading level H3 under "Where to stay". | 15 |
| Hotel cards | 3 + 3 + 2 identical placeholder cards ("HOTEL NAME", BOOK, discount line), in a **row that swipes on small screens, with dots** (`cards.php`, `carousel.js`). | **A grid, not a carousel.** The 8 real hotels, images and links already exist (Save the Date). Cala Llonga has 2, not 3: confirm the grid fills. Discount line only on the two Cala Llonga hotels. Remove the carousel script. | 15 |

### Ibiza Recommendations → Hotspots (task 15)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| Page name and intro | RECOMMENDATIONS, one lorem paragraph. | HOTSPOTS. | 15 |
| Beach clubs | 3 placeholder cards with a BOOK button. | Cards with **Book and Info** buttons. Info is new (the card part has only BOOK). | 15 |
| Restaurants | 3 placeholder cards, BOOK only. | Cards with Book and Info. | 15 |
| Nightclubs | Missing. | New: cards with Book and Info. | 15 |
| Beaches | Missing. | Headings only (no cards). Confirm whether each heading links anywhere. | 15 |
| Activities | Missing. | Headings only. | 15 |
| Landmarks | Missing. | Headings only. | 15 |
| Content | All lorem, links `#`. | Real names, text, photos with alt text and links from Mark. | 15 |

### Enquiries (task 15)

| Section | Today (Invite) | V5.01 | Task |
|---|---|---|---|
| General enquiries | "GENERAL ENQUIRIES", lorem and "Email : info@brodfest.net". | Confirm title and layout; the Save the Date has real copy ("ENQUIRIES"). | 15 |
| Private Concierge | Already there ("PRIVATE CONCIERGE", lorem, concierge@brodfest.net); the Save the Date has the real text. The card says V5.01 "adds" it, so confirm what is actually new about it. | Use the real copy. | 15 |
| Heading | Hidden H1 "Enquiries" plus two H2 titles. | One visible H1. | 02 |

### RSVP (task 15)

Same on both sites. Nothing in the card says it changes. Check it against V5.01
when task 15 starts. It is the only page with logic (form, nonce, guest records),
so it should stay code-drawn or be a single section block.

### Across all Invite pages

- Every Invite page runs on placeholder text and grey "PICTURE OF…" boxes. The
  real text and photos for V5.01 are a content job as well as a build job, and
  no source for them is in the card (task 14 and 15).
- The design is a 1920 desktop / 767 phone artboard. Confirm V5.01 keeps the
  same two sizes (the foundation expects 1440 / 768 / 375 for new client files).
