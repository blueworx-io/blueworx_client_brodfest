# Brodfest: exact Figma sizes

Date: 2026-11-04. Branch `exact-sizes`, stacked on `theme-design-system` (PR #3).

## Why

The client does not want a responsive site. If Figma draws something 50 × 50,
the page shows it 50 × 50 — text, components and the containers around them,
widths and heights alike. Today every size scales with the window (a fluid
unit of window ÷ 1920 or ÷ 767), so the site is exact only at those two
widths, and some text has a "readable floor" that breaks even that.

## Decisions (Luke's)

1. **Desktop:** the 1920 design at Figma's exact sizes. On a narrower window
   only the empty side margins shrink, until the widest component no longer
   fits. *Revised 2026-11-05, at the client's request:* heights never change
   with the window. Content stays within 1564px. On laptops widths give way,
   heights and text sizes do not (this replaces shrinking the whole design
   evenly). Edge-to-edge video and banners stay edge to edge; the hotel cards
   keep their size and swipe.
2. **Phones:** the 767 design, built at exactly 767px and zoomed by the phone
   to fit its screen, like a PDF.
3. **Wider than 1920:** exact sizes, centred; backgrounds, video and artwork
   run to the edges.

## How it behaves

- **Window 1600 and wider: desktop design.** Every element keeps Figma's
  size. The design stays centred, so each element sits at its Figma position
  plus half the difference between the window and 1920 (−160 at 1600, +320
  at 2560). 1600 is the switch because the widest component — the card rows,
  the map and the area headers, 1564 wide — then has 18px either side. *(My
  call: 18px.)*
- **The RSVP pill** is the one element nearer the edge than the card rows
  (109px from the right in Figma). It keeps at least 18px from the window
  edge, so between 1738 and 1600 wide it moves in on its own; at 1600 it
  sits at 1397–1582, well clear of the logo (585–1015). *(My call.)*
- **Window 1024 to 1599: the desktop design at Figma's heights.** Every
  height, gap and text size stays Figma's. The side margins stop at 18px
  *(my call: the same 18px as at 1600)*, so the content column is the window
  less 36px, and only widths give way: the map, the feature image and the area
  headers narrow to the column, their images cropping; text wraps to the
  column. The hotel cards keep Figma's size in one row that swipes, with the
  mobile dots under it *(my call)*.
- **Window below 1024: mobile design** at Figma's exact sizes, centred
  (positions plus half the difference between the window and 767).
- **Phones** (a screen narrower than 767): the page tells the phone its width
  is 767, so the phone shows the 767 design zoomed out to fit. Tablets and
  windows 767 to 1023 get the mobile design at exact size, centred, with no
  zoom. *(My call: set by a small script in the page head from the screen
  width; without it, a phone falls back to exact size and scrolls sideways.)*
- **Full-width pieces** — what Figma draws edge to edge: the page background,
  header bar, hero video, gold rule under it, photo strip, story banner,
  footer and open menu panel; in the mobile design also the banners, the map,
  the area headers and the card carousels. They span the whole window at
  every width, at Figma's height. Photos and video crop to fill, never
  stretch. What sits inside them (logo, lettering, mountain, cards) keeps its
  Figma size and its place relative to the centre; a card carousel on a wide
  window starts its first card at the column's left gutter and scrolls the
  full window width. *(My call.)*
- A phone turned sideways keeps the zoomed 767 design (the width is set once,
  at load). An Android phone that loads a page already sideways gets the
  unzoomed 767 column, centred. *(My call: acceptable.)*
- The current fluid unit, its caps (1.25 / 1.3px) and every readable floor
  go. Figma's desktop line breaks show from 1600 up, where text has Figma's
  width, and inside the hotel cards at every desktop width, since the cards
  keep their size. Elsewhere on a laptop the text wraps on its own.

## The review

- Re-export every Figma frame (13 pages × 2 designs) so the check runs against
  the file as it is today.
- Extend the check from text and named components to **containers**: card
  images, card rows, carousel viewports and dot rows, area headers, banner
  frames, the map, the menu panel and its item list, the RSVP form, field
  group, radio groups, radio circles, checkbox row, and each page's total
  height. Spacer frames are covered by the position of what follows them.
- Tolerance: **1px** for boxes (Figma stores fractions like 193.2); **2px** for
  text, whose letters the browser and Figma draw slightly differently.
- Widths checked: 1920 and 767 exactly as Figma; 2560 and 1600 for the desktop
  design shifted as above; at 1440 and 1024 every box keeps Figma's height
  and nothing is wider than the window or than 1564; 1023 for the centred mobile
  design; a 390 phone for the zoomed 767 design.
- Differences Luke already signed off stay pinned with their reason: the
  whole-number text sizes, corrected spellings, SEND in Futura, BlueWorx
  placeholders, artboards drawn taller than the page, and Figma's mobile text
  that has not re-wrapped. Each pin is re-checked against the new sizes by
  the same narrow rules; anything a rule does not cover is fixed in the site,
  never newly pinned.
- The same fixture must pass with and without the Enhance theme.
- Figma re-exported 2026-11-04 with the scratch export script: identical to
  the export the current fixture was built from.

## Non-goals

- No Figma edits; no colour, font, content or copy changes.
- No new layouts for tablets or laptops beyond the two designs.

## Known cost

On laptops (1024–1599 wide) widths are narrower than Figma's: the map,
feature image and area banners crop at the sides, text can wrap onto an extra
line, and the third hotel card is partly off screen until swiped. Heights and
text sizes are Figma's at every width.

## Done when

The geometry tests pass at every width above, the full suite passes with and
without the Enhance theme, and nothing scrolls sideways at 390, 767, 1023,
1024, 1440, 1599, 1600, 1920 or 2560.
