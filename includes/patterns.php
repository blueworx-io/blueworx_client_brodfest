<?php
/**
 * Whole-page layouts: one block pattern for each page type, so a new page
 * starts in one step (choose the layout when the page is made, or have a
 * connected tool create the page with the layout's content).
 *
 * A layout is a page's starting content (includes/seed.php) exactly as the
 * live pages were given it: section blocks only, with today's words, images
 * and links already in them. Only the live site's are offered: the Invite's
 * copy is private until the Invite is live.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the layouts.
 *
 * @return void
 */
function brodfest_register_page_layouts() {
	register_block_pattern_category(
		'brodfest-pages',
		array( 'label' => __( 'Brodfest pages', 'blueworx-client-brodfest' ) )
	);

	// Only the live site's layouts. A layout carries a page's words, and the
	// pattern list is open to everyone who can write a post, so the Invite's
	// stay out of it until the Invite is live (the held-back pages are private
	// for the same reason). Switching the live site changes which are offered.
	$live = brodfest_live_site();

	foreach ( brodfest_pages() as $slug => $page ) {
		foreach ( array_intersect( $page['sites'], array( $live ) ) as $site ) {
			$content = brodfest_seed_content( $slug, $site );

			if ( '' === $content ) {
				continue;
			}

			$site_name = BRODFEST_SITE_INVITE === $site ? __( 'Invite', 'blueworx-client-brodfest' ) : __( 'Save the Date', 'blueworx-client-brodfest' );

			register_block_pattern(
				'brodfest/page-' . $slug . '-' . $site,
				array(
					/* translators: 1: page name, 2: the site it is for (Save the Date or Invite). */
					'title'       => sprintf( __( '%1$s page (%2$s)', 'blueworx-client-brodfest' ), $page['title'], $site_name ),
					'description' => __( 'The whole page: every section in its place, with the words, images and links it has today.', 'blueworx-client-brodfest' ),
					'categories'  => array( 'brodfest-pages' ),
					'postTypes'   => array( 'page' ),
					// Offered when a new page is made, before its first section is added.
					'blockTypes'  => array( 'core/post-content' ),
					'content'     => $content,
				)
			);
		}
	}
}
add_action( 'init', 'brodfest_register_page_layouts', 20 );
