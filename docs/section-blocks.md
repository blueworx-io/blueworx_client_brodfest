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
- **One swap at a time.** The lock is a single INSERT into the options table: of
  two requests at once, only one gets it (a lock older than two minutes is taken
  over).
- **All or nothing.** Every page's held-back copy has to exist first (a deleted
  one is made again from the starting content, and an administrator notice says
  so). Each write is checked; if any fails, the pages already swapped are put
  back, the switch stays unfinished, an administrator notice names the page, and
  it is tried again after five minutes. Pages are drawn correctly meanwhile,
  because each page says which site its own content is for.
- **No saving over the wrong site.** Each page counts its swaps. The block
  editor sends the count it opened with (`blocks/_shared/guard.js`); a save from a
  tab opened before a switch is refused (409) and nothing is written. Revisions
  are tagged with the site they were made for, and restoring one made for the
  other site is refused on the revisions screen.
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

## Left for later

- The header's RSVP button words and the Save the Date's two menu names are set under Brodfest › Site › Header words (the blocks take them as attributes through the `brodfest_global_attributes` filter). The footer has none to set. Invite menu names are the page titles.
- The page lock (task 03) has to treat the held-back pages as locked too.
- Add to Calendar stays off the page until `brodfest_show_calendar()` is turned
  on, as agreed on 9 Nov.
