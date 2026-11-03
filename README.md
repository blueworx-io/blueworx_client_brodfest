# Brodfest

The Brodfest website, as a WordPress plugin on the BlueWorx Enhance theme.
Enhance runs the admin, sign-in and security; this plugin draws every public page.

## Adding a page

1. Add an entry to `brodfest_pages()` in `includes/pages.php`.
2. Add its template in `templates/pages/`, between `brodfest_document_open()` and
   `brodfest_document_close()`.
3. Shared pieces go in `templates/parts/` and load with `brodfest_part()`.
4. Colours and fonts are tokens in `assets/css/src/app.css`; run `npm run build`.

Live sites create new pages on their next update.

## Local site

`npm run wp:up` starts a local WordPress with Enhance and this plugin (needs the
foundation and theme repos beside this one). Then `npm test`. Under Enhance the
login is at `/admin_login/`, so set `WP_LOGIN_PATH=/admin_login/` for specs that sign in.
