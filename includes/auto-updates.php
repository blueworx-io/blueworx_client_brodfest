<?php
/**
 * The plugin keeps itself up to date.
 *
 * The update checker in the main plugin file tells WordPress a newer version
 * exists. This decides that WordPress installs it, rather than leaving the
 * offer on the Plugins screen for somebody to press (Luke, 11 Nov 2026).
 *
 * Forced, not a setting: there is one answer for every site this plugin is on.
 * The cost is that a bad release reaches the site within about twelve hours
 * with nobody in the loop. What stands in its way is the CI on every merge and
 * a release that refuses to publish when the tag and the version disagree. A
 * bad version is answered by releasing a better one.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns automatic updates on for this plugin, and this plugin only.
 *
 * Every plugin, theme and translation passes through this filter, so the
 * decision is returned untouched for all the others. Themes and translations
 * carry no `plugin` property, hence the isset().
 *
 * @param bool|null $update Whether to update, as decided so far.
 * @param object    $item   The update offer; plugins carry their basename.
 * @return bool|null True for this plugin, otherwise the decision as it came.
 */
function brodfest_force_own_auto_update( $update, $item ) {
	if ( ! is_object( $item ) || ! isset( $item->plugin ) ) {
		return $update;
	}

	if ( plugin_basename( BRODFEST_FILE ) !== $item->plugin ) {
		return $update;
	}

	return true;
}
add_filter( 'auto_update_plugin', 'brodfest_force_own_auto_update', 10, 2 );
