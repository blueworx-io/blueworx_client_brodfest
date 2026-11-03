// Shared WordPress helpers for the Playwright suite. Every test that needs a
// signed-in admin goes through here, so the credentials live in one place.
const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'admin';
// Enhance moves the login screen to /admin_login/. CI runs without the theme,
// so the default stays WordPress's own.
const LOGIN_PATH = process.env.WP_LOGIN_PATH ?? '/wp-login.php';

export async function loginAsAdmin(page) {
  await page.goto(LOGIN_PATH);
  await page.fill('#user_login', ADMIN_USER);
  await page.fill('#user_pass', ADMIN_PASS);
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
}

// The home page's first-visit intro covers the page; most specs are not
// about it, so they arrive as a returning visitor.
export async function skipIntro(page) {
  await page.addInitScript(() => window.localStorage.setItem('brodfest-intro-seen', '1'));
}
