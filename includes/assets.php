<?php
/**
 * Front-end assets for the plugin's own pages.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The client's Adobe Fonts kit id. */
const BRODFEST_FONTS_KIT = 'vjw6kra';

/**
 * Enqueues the built stylesheet and bundle on owned pages only.
 *
 * Both are committed build output (npm run build), so a plain checkout and the
 * release zip render without a Node step.
 *
 * @return void
 */
function brodfest_enqueue_assets() {
	if ( ! brodfest_is_owned_request() ) {
		return;
	}

	// The client's Adobe Fonts kit: Futura PT and Aller. Adobe licenses them
	// for the web only through its own host, so they are not shipped. No
	// version on purpose — a ?ver= suffix breaks the kit's caching.
	wp_enqueue_style(
		'brodfest-fonts',
		'https://use.typekit.net/' . BRODFEST_FONTS_KIT . '.css',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	);

	// The theme's named colours and fonts (BlueWorx › Design) come first when
	// the Enhance theme is active. Only then: WordPress silently drops a
	// stylesheet whose dependency is not registered, and without the theme the
	// stylesheet falls back to the Brodfest values on its own.
	$deps = array( 'brodfest-fonts' );

	if ( wp_style_is( 'blueworx-design', 'registered' ) ) {
		$deps[] = 'blueworx-design';
	}

	wp_enqueue_style(
		'brodfest-public',
		BRODFEST_URL . 'assets/css/public.css',
		$deps,
		BRODFEST_VERSION
	);

	wp_enqueue_script(
		'brodfest-public',
		BRODFEST_URL . 'assets/js/public.js',
		array(),
		BRODFEST_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'brodfest_enqueue_assets', 20 );

/**
 * Drops theme and third-party front-end styles on owned pages.
 *
 * The design is guaranteed only if nothing else can reach these pages. The
 * admin bar's own styles are kept, since a signed-in editor still needs it,
 * and so are the theme's named styles, which only define colours and fonts
 * and style nothing. The theme's base site styles are dropped.
 *
 * @return void
 */
function brodfest_sweep_foreign_assets() {
	if ( ! brodfest_is_owned_request() ) {
		return;
	}

	/**
	 * Filters the stylesheet handles the sweep leaves alone on owned pages.
	 *
	 * Foreign scripts are kept, so a consent banner or chat widget still runs,
	 * but its stylesheet is dropped. Add its handle here to keep it.
	 *
	 * @param string[] $keep Stylesheet handles to keep.
	 */
	$keep = (array) apply_filters(
		'brodfest_keep_styles',
		array( 'brodfest-fonts', 'brodfest-public', 'blueworx-design', 'admin-bar', 'dashicons' )
	);

	foreach ( wp_styles()->queue as $handle ) {
		if ( ! in_array( $handle, $keep, true ) ) {
			wp_dequeue_style( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'brodfest_sweep_foreign_assets', 100 );
