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

// The home page's first-visit opening covers the page; most specs are not
// about it, so they arrive as a returning visitor whose browser does not play
// video by itself (as Low Power Mode on an iPhone does), which leaves the page
// in view.
export async function skipIntro(page) {
  await page.addInitScript(() => {
    window.localStorage.setItem('brodfest-entered', '1');
    HTMLMediaElement.prototype.play = () => Promise.reject(new DOMException('Autoplay refused', 'NotAllowedError'));
  });
}

// Signs in with a plain form post, without loading the dashboard. For specs that
// only need the signed-in cookie: the dashboard's page load, which waits on
// every one of its scripts and on feeds from other sites, is slow on a test
// site that answers one request at a time.
export async function loginQuietly(page, baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8881', credentials = {}) {
  // WordPress refuses a sign-in from a browser that has not been given this.
  await page.context().addCookies([{ name: 'wordpress_test_cookie', value: 'WP Cookie check', url: baseURL }]);
  const response = await page.request.post(LOGIN_PATH, {
    form: { log: credentials.user ?? ADMIN_USER, pwd: credentials.pass ?? ADMIN_PASS, 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' },
    maxRedirects: 0,
  });
  if (response.status() !== 302) throw new Error(`Sign-in did not go through (HTTP ${response.status()}).`);
}
