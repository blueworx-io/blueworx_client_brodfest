# Brodfest

The Brodfest website, as a WordPress plugin on the BlueWorx Enhance theme.
Enhance runs the admin, sign-in and security; this plugin draws every public page.

Every section of every page is a section block in `blocks/`, drawn on the server.
See `docs/section-blocks.md` for how blocks, pages and the two sites (Save the
Date and Invite) fit together.

## Adding a page

1. Add an entry to `brodfest_pages()` in `includes/pages.php`.
2. Add its starting blocks for each site in `brodfest_seed_blocks()` in `includes/seed.php`.
3. A new kind of section is a new folder in `blocks/` (`block.json`, `render.php`,
   `edit.js`, `edit.asset.php`, and its styles imported from `assets/css/src/app.css`).
4. Colours and fonts are the theme's named styles; run `npm run build` after a style change.

Live sites create new pages, with their starting blocks, on their next update. An
existing page's words are never overwritten by an update.

## Local site

`npm run wp:up` starts a local WordPress with Enhance and this plugin (needs the
foundation and theme repos beside this one). Then `npm test`. Under Enhance the
login is at `/admin_login/`, so set `WP_LOGIN_PATH=/admin_login/` for specs that sign in.
