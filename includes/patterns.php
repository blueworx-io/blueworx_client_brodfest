<?php
/**
 * Whole-page layouts: one block pattern for each page type, so a new page
 * starts in one step (choose the layout when the page is made, or have a
 * connected tool create the page with the layout's content).
 *
 * A layout is a page's starting content (includes/seed.php) exactly as the
 * live pages were given it: section blocks only, with today's words, images
 * and links already in them. There is one for each site a page belongs to,
 * since the Save the Date's and the Invite's pages differ.
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

	foreach ( brodfest_pages() as $slug => $page ) {
		foreach ( $page['sites'] as $site ) {
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
