# Brodfest: theme design system and pixel-perfect review

Date: 2026-11-04. Branch `theme-design-system`, stacked on `build-site` (PR #2).

## Why

Luke wants to change Brodfest's colours and fonts from the Enhance theme's
**BlueWorx › Design** screen and see the site follow. Today the plugin strips
the theme's styles off its pages and hard-codes every colour and font, so that
screen does nothing. He also wants the site pixel perfect against the Figma
file, and the open menu currently shows the wrong background.

## Goals

1. Colours and fonts come from the theme's design system. Changing them on
   BlueWorx › Design changes the site, with no plugin edit.
2. With the Brodfest preset applied, the site looks exactly as it does now
   (same colours, same fonts) — the switch to the theme changes nothing a
   visitor can see.
3. Every page matches Figma at the two artboard widths, 1920 and 767.
4. The open menu has the design's background.

## Non-goals

- Sizes, spacing and letter spacing stay in the plugin. The theme's sizes are
  fixed with one phone step; Brodfest scales smoothly from its two artboards.
  Luke accepted this limit.
- No changes to the theme repo. Colours the theme has no slot for are worked
  out in the plugin (below). Luke accepted this limit.
- No block-editor work, no new content, no real images or videos.

## Anti-goals

- The site turning blue and white (the theme's defaults) on any site where the
  preset has not been applied yet, without anyone noticing. See "Rollout".
- Orange titles drifting from the design's orange because the theme lightens
  colours used as text.

## How colours map (decided)

The theme publishes named styles (`--color-bg`, `--color-text`,
`--color-primary`, `--color-accent`, `--font-heading`, `--font-body`, …) on
every public page under the `blueworx-design` style handle. Reference:
`../blueworx_labs_wordpress_theme/docs/design-system.md`.

| Brodfest colour | Hex | Comes from |
|---|---|---|
| Background | `#0b0301` | `--color-bg` |
| Gold (all text, RSVP pill, SEND) | `#d1954e` | `--color-text`, and `--color-primary` set to the same gold |
| Orange (titles, BOOK, banners) | `#a24300` | `--color-accent`, used as-is, also for titles — **not** `--color-accent-text`, which the theme lightens |
| Rust (Add to Calendar button) | `#632800` | worked out in the plugin from accent and background |
| Field (form fields, map panel) | `#210c07` | worked out in the plugin from the theme's colours |
| White (discount code) | `#ffffff` | stays white |
| Video placeholders (pink, blue) | `#d800ba`, `#009ad6` | stay as they are; they go when the videos arrive |

**Artwork follows too (my call).** The logo, date, mountain, menu and close
icons (gold), the Save the Date and Invitation lettering (orange) and the
story banner (gold and field) are inline SVGs with colours fixed in the files.
Their colours move to the stylesheet (`currentColor` or classes) so they
follow the theme like everything else; otherwise changing gold on the Design
screen would leave the logo behind. The Celebrate strip is a photo and stays
as it is.

"Worked out" means CSS `color-mix()` of theme names, tuned so that with the
Brodfest preset the result is the design hex. Rust can be hit exactly
(about 58% accent into background). Field may not be exact from accent and
background alone; the builder picks the mix (it may also use gold) and it must
land within 6 per RGB channel of `#210c07`.

Every use of a theme name carries the Brodfest value as its CSS fallback
(`var(--color-bg, #0b0301)`), so the plugin still looks right with no theme —
which is how CI's test site runs.

## Fonts (decided)

- The plugin adds Futura PT and Aller to the theme's font list with the
  `blueworx_design_fonts` filter, with no font files (the theme then treats
  them as "every weight the device has").
- The plugin keeps loading the Adobe Fonts kit `vjw6kra` itself, as now. The
  families in that kit are `futura-pt` and `aller`.
- Display text uses `--font-heading`, everything else `--font-body`, each with
  today's stack as fallback.

## The Brodfest preset (decided)

The plugin offers a preset with the `blueworx_design_presets` filter:
`primary` gold, `accent` orange, `background` `#0b0301`, `text` gold,
`heading_font` Futura PT, `body_font` Aller. Other settings left at default.
It shows on BlueWorx › Design for one click.

## Plugin changes (decided)

- The style sweep on Brodfest pages keeps `blueworx-design` (the theme's
  names). It still drops everything else, including the theme's base site
  styles (`blueworx-site`), which would restyle Brodfest's markup.
- The plugin's stylesheet declares `blueworx-design` as a dependency **only
  when that handle is registered**, because WordPress silently drops a
  stylesheet whose dependency is missing (CI has no theme).
- The plugin's own `--gutter` collides with the theme's `--gutter`; rename the
  plugin's spacing names with the `--bf-` prefix.

## Menu background (my call — Luke to check)

The open menu is a 0.88-opacity panel with a blur, so the bright pink video
placeholder behind it turns it purple, and a real video would tint it the
same way. The design's background is the page colour. **My call:** make the
panel solid `--color-bg` and drop the blur. Nothing is visible through a solid
panel, so the "blur the page" comment is moot. I put the Figma menu panel back to
solid myself (the same way it was edited yesterday), and the review compares
against that.

## Pixel-perfect review

For every page below, at 1920 and 767 wide, compare the built page with the
Figma frame and fix every difference.

- Save the Date (Figma page "Save the Date"): Home, Menu open, Celebrate,
  Accommodation, Enquiries, RSVP.
- Invite (Figma page "Invite"): Home, Menu open, Celebrate, Can Sakhara Story,
  Accommodation, Recommendations, Enquiries. The Invite has no RSVP frame; it
  uses the Save the Date one.

What "matches" means:

- Positions and sizes within 2px of Figma at the artboard width.
- Colours exact (or within the 6-per-channel allowance for Field).
- Same fonts, weights, letter spacing and line heights.
- Figma's text sizes are still the old decimals (the fonts were missing when
  Figma was updated). Where Figma and the agreed whole-number sizes differ,
  the agreed sizes win: area 34, card title 30, card button 30, mobile RSVP
  pill 25, mobile page title 37, mobile area 25, mobile card title 22, mobile
  card button 22, mobile note 18, mobile menu 27. Spacing keeps Figma's
  measured values, so where a bigger or smaller size moves what follows it,
  that drift is accepted: the gaps between elements must match Figma, not
  their absolute positions further down the page (my call).
- Known, agreed differences are not defects: Transport is gone (Figma updated),
  the map and Getting Here placeholders are in Accommodation (Figma updated),
  copy is placeholder, the intro and animations are not in Figma.

Also check widths between and beyond the artboards (375, 1024, 1440, 2560):
nothing overflows sideways, nothing overlaps.

Method is left to the builder. Figma renders come from the Figma MCP
(`get_screenshot` on file `qVR5fdmsduo89gSF99GsQp`); the site from Playwright
against the local harness with the theme
(`node ../bluegroup_core_foundation/scripts/wp-test-env.mjs up --plugin . --theme ../blueworx_labs_wordpress_theme --theme-slug blueworx-labs-wordpress-theme`).
Keep before/after evidence in the scratchpad, not the repo.

## Tests

Playwright, following the repo's existing specs:

- The plugin's colours follow the theme's names: set `--color-accent` (etc.) to
  a test colour on the page and assert the matching element changes.
- With no theme, the fallbacks give the Brodfest colours.
- The open menu's background is solid and the page colour.
- Brodfest's fonts and preset appear in the theme's lists (only when the theme
  is active; must not skip the whole suite in CI — CI fails a run of zero
  tests).

## Rollout

- Version 0.3.0 (new feature), changelog entry.
- On staging, apply the Brodfest preset on BlueWorx › Design right after the
  update, then recheck the menu and a page of each site. Until the preset is
  applied the site shows the theme's default colours — say this plainly in the
  PR so nobody updates the live site without applying the preset.

## Left to the builder

- Exact `color-mix()` recipes for rust and field.
- How the visual comparison is done, and in what order pages are fixed.
- Whether the preset also sets type scale, spacing or corners (they do not
  affect Brodfest pages, since sizes stay in the plugin).
