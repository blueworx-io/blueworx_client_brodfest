<?php
/**
 * How this plugin sits on the BlueWorx Enhance theme.
 *
 * Enhance runs the admin, sign-in, security and support access; this plugin
 * draws the public site. Nothing here is required for the site to render —
 * every hook below simply goes unused when another theme is active.
 *
 * Theme functions are not loaded when plugins load. Anything that needs to
 * call one hooks `blueworx_theme_ready`, which Enhance fires once it is ready.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Names this plugin's pages in Enhance's Pages list Source column.
 *
 * A page with a source is protected there: it can be viewed and edited, but
 * not trashed or deleted, since the site depends on it.
 *
 * @param string $label   The label another plugin gave, or ''.
 * @param int    $post_id The page.
 * @return string
 */
function brodfest_page_source( $label, $post_id ) {
	return brodfest_page_is_ours( $post_id ) ? __( 'Brodfest page', 'blueworx-client-brodfest' ) : $label;
}
add_filter( 'blueworx_page_source', 'brodfest_page_source', 10, 2 );
