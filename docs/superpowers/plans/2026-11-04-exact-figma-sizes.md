# Exact Figma sizes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Every page at Figma's exact sizes from 1600 up and in the mobile design; the desktop design shrunk evenly between 1024 and 1600; phones zoom the 767 design.

**Architecture:** `--u` becomes 1px (mobile) or `min(100vw / 1600, 1px)` (desktop). A new `--bf-shift`, written with `100%` so it resolves against each element's containing block (no scrollbar error), centres the design: `(100% − 1920u) / 2` on desktop, `max(0px, (100% − 767px) / 2)` on mobile. Gutters add it. A head script sets the viewport to 767 on phones.

**Tech Stack:** Tailwind 4 CSS build, PHP templates, Playwright.

**Spec:** docs/superpowers/specs/2026-11-04-exact-figma-sizes-design.md

## Global Constraints

- Breakpoint stays 1024 (desktop design at 1024 and up).
- Tolerance: 1px boxes, 2px text.
- Pinned rows keep their `why`; no new pins outside the spec's categories.
- Version 0.4.0 (behaviour change), changelog in plain words.
- Lint run once at the end; findings reported, not fixed.

## Review Focus

- Classic scrollbars (Windows): 100vw includes the scrollbar; nothing may scroll sideways at 1024 or 1600 with one.
- The pill's 18px floor between 1600 and 1738.
- Full-width media keep Figma's height at 2560 and 1023 (hero, banner, map, strip, story banner) and crop, never stretch.
- A phone without the head script still renders (exact size, scrolls sideways).
- Figma line breaks at 1024: no extra wrapped lines when the text is scaled.

---

### Task 1: Geometry check at every width

**Files:** Modify `tests/design-geometry.spec.js`.

- [ ] Each 1920 frame also runs at 2560, 1600, 1440 and 1024; each 767 frame also at 1023. Expected box for width V: desktop `s = min(V/1600, 1)`, full-width rows (Figma w ≥ 1900) `[0, y·s, V, h·s]`, others `[(x − 960)·s + V/2, y·s, w·s, h·s]`; the pill and its label (Figma x ≥ 1600, y < 270) move left by `max(0, min(69, −91 − (V − 1920)/2))` in Figma units first. Mobile: full-width rows (w ≥ 760) `[0, y, V, h]`, others `x + max(0, (V − 767)/2)`.
- [ ] Tolerance 1px for boxes, 2px for text.
- [ ] No-sideways-scroll test widths: 390, 767, 1023, 1024, 1440, 1599, 1600, 1920, 2560.
- [ ] Run: fails at the new widths (fluid unit). Commit.

### Task 2: Exact sizes in the stylesheet

**Files:** Modify `assets/css/src/app.css`, `assets/css/public.css` (built).

- [ ] `--u` and `--bf-shift` as above; gutters `48/96 + shift` (mobile), `193u/178u + shift` (desktop).
- [ ] Every `max(calc(N u), Npx)` floor and the field's 44px min-height go.
- [ ] Positions measured from the left edge add `--bf-shift`: header toggle, pill (`right: max(109u + shift, 18u)`; mobile `29 + shift`), menu items, menu divider.
- [ ] Full-width media use Figma heights, not aspect ratios: hero 767 / 840, mobile banner and map 384, story banner artwork fixed width centred.
- [ ] Line breaks hidden below 1024 only; `:has(.bf-br)` max-width rule from 1024.
- [ ] Task 1 tests pass; full suite passes. Commit.

### Task 3: Phones zoom the 767 design

**Files:** Modify `includes/render.php`; Test `tests/design-geometry.spec.js`.

- [ ] Test (390×844 mobile context): layout width is 767 and the home and RSVP frames match at 767. Run: fails.
- [ ] Inline script after the viewport meta: when `screen.width < 767`, set the meta to `width=767`. Run: passes. Commit.

### Task 4: Containers in the check

**Files:** `tests/fixtures/design-geometry.json`, possibly `templates/parts/area.php` and `assets/css/src/app.css`.

- [ ] Add rows (scratch generator, same row format) for: Content → `.bf-main`; frame height → `html`; Section/Area Header → a new `.bf-area__header` wrapper; card Image → `.bf-card__image`; Cards rows / Swipe viewport → `.bf-cards__track`; mobile Cards → `.bf-cards`; Dots → `.bf-cards__dots`; Menu/Panel → `.bf-menu`; Items → `.bf-menu__items`; Form → `.bf-form`; Fields → `.bf-fields`; Form/Radio Group → `.bf-radios`; Yes/No → `.bf-radios label`; Form/Radio → radio and checkbox inputs; Checkbox Row → `.bf-check`.
- [ ] Run: fix every mismatch in the site; pin only spec categories. Commit.

### Task 5: Release prep

- [ ] Version 0.4.0 in header, constant, package.json, readme, CHANGELOG. Full suite with and without the theme. Zip via `npm run build:zip`, listed. Lint once. Push, PR into `theme-design-system`.
