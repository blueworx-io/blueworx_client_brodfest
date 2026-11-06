// Shared WordPress helpers for the Playwright suite. Every test that needs a
// signed-in admin goes through here, so the credentials live in one place.
const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'admin';
// Enhance moves the login screen to /admin_login/. CI runs without the theme,
// so the default stays WordPress's own.
// CI passes an empty value when the project has not moved it, hence ||.
const LOGIN_PATH = process.env.WP_LOGIN_PATH || '/wp-login.php';

export async function loginAsAdmin(page) {
  await page.goto(LOGIN_PATH);
  await page.fill('#user_login', ADMIN_USER);
  await page.fill('#user_pass', ADMIN_PASS);
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
}

// The home page's first-visit opening, and the video that then fills the
// window, cover the page; most specs are not about them, so they arrive as a
// returning visitor whose browser does not play video by itself (as Low Power
// Mode on an iPhone does), which leaves the page in view.
export async function skipIntro(page) {
  await page.addInitScript(() => {
    window.localStorage.setItem('brodfest-entered', '1');
    HTMLMediaElement.prototype.play = () => Promise.reject(new DOMException('Autoplay refused', 'NotAllowedError'));
  });
}
