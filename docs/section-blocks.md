# Section blocks

How the site is built since 0.16.0. Written for whoever builds the next pages
(the V5.01 Invite pages, the page lock).

## What a page is

Every owned page (Home, Celebrate, Can Sakhara Story, Accommodation, Ibiza
Recommendations, Enquiries, RSVP) is a WordPress page whose content is a list of
**section blocks**. `templates/page.php` draws a page: it renders the blocks on
the server, between the site header and footer (also blocks).

A block lives in `blocks/<name>/`:

| File | What it is |
|---|---|
| `block.json` | Name (`brodfest/<name>`), the attributes (only words, images and links), `supports` with colour, typography and spacing switched off |
| `render.php` | Draws the section on the server. No WordPress block wrapper, so its markup is the design's own |
| `edit.js` + `edit.asset.php` | The editor fields, plain JavaScript on `wp.element` (no build step), built from `blocks/_shared/editor.js` |
| `style.css` | The section's styles, where it has its own (see "Styles") |

The saved page holds only each block's name and attributes (`"save": null`), so
fixing a render file fixes every page already built.

Blocks: `opening`, `hero-video`, `home-lettering` (holds `add-to-calendar`),
`text-section`, `photo-strip`, `story-banner`, `feature-image`, `map`, `area`
(hotels are a list), `category` (places are a list), `rsvp-form`, `site-header`,
`site-footer`. The header and footer are drawn by the plugin on every page.

## Lists, images and headings

- A repeating item (hotels, places, paragraphs, video files) is a list
  attribute with a smallest and largest size set in its edit script. A list with
  nothing in it hides the part it feeds (no empty row of cards).
- Every image has an alt text field, or a "decorative" switch. Images the plugin
  ships are written as `plugin:assets/img/...` (see `brodfest_media_url()`), so
  saved content holds no site address.
- Headings: the page's top section carries the one H1. A text section and an
  area say their own level (`headingLevel`); an area's hotels are one level
  below it. The Save the Date's areas sit under "Where to stay" as H3 with
  hotels as H4.

## The two sites share the pages

The Save the Date and the Invite each have their own blocks for a page.

- The **live** site's blocks are the page's own content, so the page, the REST
  API and any SEO plugin see only what visitors see.
- The **other** site's blocks sit in a private post of the type
  `brodfest_held_page` ("Held-back pages", under Pages), linked to the page. It
  has no address, and its posts are private.
- Switching the live site (Brodfest › Site) swaps each page's blocks with its
  held-back copy (`brodfest_sync_live_content()`); switching back restores them
  exactly. A page's meta `_brodfest_content_site` says which site its own content
  is for.
- An editor previewing the other site (`?brodfest-site=`) sees the held-back
  blocks for that request only.
- Invite-only pages hold nothing while the Save the Date is live.

What keeps the swap safe (`includes/pages.php`, `includes/held-pages.php`):

- **Private for good.** A held-back page is forced to `private` on every save,
  whatever Quick Edit or the REST API asks for, so it is never published or
  readable without a sign-in.
- **One lock for everything that writes blocks.** The lock is a single INSERT
  into the options table: of two requests at once, only one gets it (a lock
  older than two minutes is taken over; each request releases only its own).
  Giving pages their starting blocks, swapping, every editor save to a page or
  held-back page, and every restored revision all take it, so none of them can
  overlap. A request that gets the lock forgets what it read before
  (`brodfest_forget_cached()`), since a switch may have finished while it waited.
- **The website changes site only when the pages have.** The site visitors see is
  the site the pages were last moved onto (`brodfest_live_site()`), not the one
  just chosen (`brodfest_wanted_site()`). Until the pages have first been set up
  it is the Save the Date, which is what they hold. A switch that fails leaves the
  website and the public REST API on the site they were on.
- **All or nothing, in one step.** Every page has its blocks and held-back copy
  first (a deleted held-back page is made again from the starting content, and an
  administrator notice says so). Then every page's blocks, its markers, its swap
  count and the live site are changed in **one database transaction**
  (`brodfest_move_blocks()`), and read back inside it; anything that does not match
  exactly, or fails, drops the lot. Other requests see nothing of it until it is
  committed, and all of it after, so the Invite cannot be read early, whether the
  switch fails or the request dies half way. Nothing is ever half done, so there is
  nothing to put back and no recovery copy to keep. A retry follows after five
  minutes, and a notice tells an administrator.
  - The rows are written directly, not with `wp_update_post()`: that would put the
    new blocks in caches, and run other plugins' save hooks, before the switch is
    certain. The hooks run, and the revisions are made, straight after the commit
    (`brodfest_announce_move()`), without writing the page again.
  - It needs InnoDB tables (WordPress's own default). On any other kind the switch
    is refused with a notice, and nothing changes.
- **Setup that did not finish is tried again** every five minutes, whether or not a
  switch is wanted: the plugin's version is only recorded once every page has its
  blocks and its held-back page.
- **No saving over the wrong site.** Each page counts its swaps. The block
  editor sends the count it opened with (`blocks/_shared/guard.js`); once the
  save holds the lock, a count that no longer matches is refused (409) and
  nothing is written. Every revision is tagged with the site it was made for (a
  page is marked with its site before its blocks are written, so the first
  revision is tagged too, and an autosave is tagged again each time WordPress
  writes over it). A revision for the other site, or one with no tag, is
  refused on the revisions screen and over XML-RPC.
- **Previews.** WordPress Preview draws the unsaved version of the page (the
  page as `get_post()` has it, not `get_queried_object()`, which is stale).

A page is given its starting blocks (`includes/seed.php`) once, when it is
created or the plugin first updates past 0.16.0. After that the words belong to
the page and an update never overwrites them. A page with content already is
left alone. To change what a **new** page starts with, change `seed.php`.

## Styles

Each section's styles are in `blocks/`, in `style.css` or, where sections share
rules, in `blocks/_shared/`. `assets/css/src/app.css` imports them in the order
the page draws them and `npm run build` turns them into the one stylesheet,
`assets/css/public.css`, which is committed. (No per-block stylesheet is loaded:
the Foundation allows this where markup has to match an existing design exactly.)

Fonts and sizes are the theme's named styles with today's value behind each name:
`var(--font-heading, …)` and `var(--text-37, calc(37 * var(--u)))`. The theme has
no `--text-*` names yet, so the fallbacks are what shows. **Before the theme can
take over the sizes**, its names (and Figma's variables) have to be added:
`--text-13, 18, 20, 22, 23, 25, 26, 27, 30, 34, 35, 37, 48, 50`. Sizes are
Figma pixels (`--u`), which the theme does not have either; mapping them onto
`--space-*` is still to decide. `node ../bluegroup_core_foundation/scripts/check-section-styles.mjs`
passes.

## The page lock

Every owned page, and every held-back page, is locked by the foundation's shared
page lock, copied whole into `blueworx-page-lock/` (never edited here: CI compares
it with the foundation's `page-lock/`) and loaded by one `require_once` in the
main plugin file. `includes/page-lock.php` is all this plugin adds:

- **What it covers.** The lock covers pages. This plugin adds the held-back
  pages (`blueworx_page_lock_post_types`), and names `brodfest` as the only
  block namespace a client is offered (`blueworx_page_lock_section_namespaces`),
  so another plugin's blocks never appear on a locked page.
- **What a client can do.** Change words, images, links and list items, and show
  or hide a section (every section has a "Show this section on the site" switch
  in the block tab; it saves a `hidden` attribute, and a hidden section draws
  nothing, with everything inside it). A client cannot add, move, remove or
  unlock a section: the server refuses a save that changes the page's sections
  (403, `blueworx_page_locked`), whether it comes from the editor, the REST
  API or a connected tool. In the editor they get no code editor, no add-block
  button, and the structure panel open.
- **Pages made before the lock existed** are locked once, on the first request
  after an update (`brodfest_lock_owned_pages()`); a page that already has a lock
  setting, locked or not, is left alone, so an unlocked page stays unlocked.
  Pages made afterwards are locked by the lock itself, as they are created.
- **The plugin's own writes** (the starting blocks, and the swap between the
  Save the Date and the Invite) change a page's sections, and run for whoever
  makes the request, including a signed-out visitor whose request retries a
  switch. `brodfest_write_post()` stands the lock aside for the starting blocks
  (`brodfest_without_page_lock()`), and a switch writes straight to the database;
  nobody's editor or REST save does either.
- **Layouts.** Each page type has a whole-page layout (a block pattern, category
  "Brodfest pages", `includes/patterns.php`): the page's starting content, so a
  new page, or a connected tool, starts from it in one step. A new page has no
  sections yet, so its first layout can be saved once; after that its sections
  are fixed. Only the live site's layouts are offered: the pattern list is open
  to everyone who can write a post, so the Invite's copy stays out of it until
  the Invite is live. Any page made only of Brodfest blocks (not only the seven
  owned pages) is drawn with the plugin's document, styles and scripts
  (`brodfest_page_has_sections()`); a page that mixes them with other content,
  or is password-protected, keeps WordPress's own template.
- **Known limits of the shared lock** (the copy is not edited here; to be fixed in
  the foundation): it compares block types and order, not each section, so two
  sections of one type (Celebration and Dress Code) can be swapped; it ignores
  markup outside block comments; and its panel needs `core/editor` panel actions
  that WordPress 6.4 keeps in `core/edit-post`.

A page draws only what is saved in it: nothing is filled in by code when it
loads. `templates/page.php` is the one document shell (header, the page's
blocks, footer), not a page's own template.

## Left for later

- The header's RSVP button words and the Save the Date's two menu names are set under Brodfest › Site › Header words (the blocks take them as attributes through the `brodfest_global_attributes` filter). The footer has none to set. Invite menu names are the page titles.
- Add to Calendar stays off the page until `brodfest_show_calendar()` is turned
  on, as agreed on 9 Nov.
