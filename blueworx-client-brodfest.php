<?php
/**
 * Plugin Name:       BlueWorx Clients | Brodfest
 * Plugin URI:        https://github.com/blueworx-io/blueworx_client_brodfest
 * Description:       The Brodfest website, as a self-contained WordPress plugin on the BlueWorx Enhance theme.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            BlueWorx
 * Author URI:        https://blueworx.io/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blueworx-client-brodfest
 * Update URI:        https://github.com/blueworx-io/blueworx_client_brodfest
 *
 * @package Brodfest
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BRODFEST_VERSION', '0.4.0' );
define( 'BRODFEST_SLUG', 'blueworx-client-brodfest' );
define( 'BRODFEST_FILE', __FILE__ );
define( 'BRODFEST_DIR', plugin_dir_path( __FILE__ ) );
define( 'BRODFEST_URL', plugin_dir_url( __FILE__ ) );

/*
 * Automatic updates. Sites watch this repo's GitHub Releases and install the
 * zip attached to each one. The library is vendored whole at
 * plugin-update-checker/ and swapped, never edited.
 *
 * At file scope, and not inside a function or hook: the library's "use"
 * import below cannot be wrapped without a parse error.
 */
require_once BRODFEST_DIR . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// The third argument must equal the plugin's folder name on the site and the
// folder inside the release zip. If they disagree, WordPress installs the
// update alongside the original as a second copy and deactivates it.
$brodfest_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/blueworx-io/blueworx_client_brodfest/',
	__FILE__,
	BRODFEST_SLUG
);

/*
 * The repo is private, so a site needs a token to see releases at all. It
 * lives in wp-config.php — never in the plugin, never in the repo:
 *
 *     define( 'BLUEWORX_PLUGIN_UPDATE_TOKEN', 'github_pat_...' );
 */
if ( defined( 'BLUEWORX_PLUGIN_UPDATE_TOKEN' ) && BLUEWORX_PLUGIN_UPDATE_TOKEN ) {
	$brodfest_update_checker->setAuthentication( BLUEWORX_PLUGIN_UPDATE_TOKEN );
}

/*
 * Install the zip attached to the Release, not GitHub's source tarball, whose
 * folder is named <repo>-<version> and carries every development file.
 */
$brodfest_update_checker->getVcsApi()->enableReleaseAssets();

// The BlueWorx page editor library, which also loads the admin design system
// registrar. Vendored from bluegroup_core_foundation and hash-compared against
// it on every pull request — never edited here. Top level, before the plugin's
// own files, because it registers itself on plugins_loaded at priority 0.
require_once BRODFEST_DIR . 'blueworx-page-editor/blueworx-page-editor.php';

require_once BRODFEST_DIR . 'includes/site.php';
require_once BRODFEST_DIR . 'includes/pages.php';
require_once BRODFEST_DIR . 'includes/content.php';
require_once BRODFEST_DIR . 'includes/render.php';
require_once BRODFEST_DIR . 'includes/assets.php';
require_once BRODFEST_DIR . 'includes/design.php';
require_once BRODFEST_DIR . 'includes/enhance.php';
require_once BRODFEST_DIR . 'includes/rsvp.php';
require_once BRODFEST_DIR . 'includes/calendar.php';
require_once BRODFEST_DIR . 'includes/settings.php';

/**
 * Creates the plugin's pages and makes Home the front page on activation.
 *
 * @return void
 */
function brodfest_activate() {
	brodfest_install_pages();
	update_option( 'brodfest_version', BRODFEST_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'brodfest_activate' );

/**
 * Creates pages added by an update, on sites that never reactivate.
 *
 * Updates arrive through the update checker, which does not fire the
 * activation hook — so a page added in a later version would otherwise never
 * exist on a live site. Leaves the front-page setting alone.
 *
 * @return void
 */
function brodfest_maybe_upgrade() {
	if ( get_option( 'brodfest_version' ) === BRODFEST_VERSION ) {
		return;
	}

	brodfest_install_pages( false );
	update_option( 'brodfest_version', BRODFEST_VERSION );
}
add_action( 'init', 'brodfest_maybe_upgrade' );

/**
 * Flushes rewrites on deactivation. Pages are deliberately left in place.
 *
 * @return void
 */
function brodfest_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'brodfest_deactivate' );
