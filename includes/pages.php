<?php
/**
 * The pages this plugin owns, and how it claims them.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta stamped onto every page this plugin creates.
 *
 * Ownership is read from this stamp and never inferred from the slug. A site
 * with its own page slugged "home" would otherwise have it adopted and
 * rendered by this plugin. A slug is a coincidence; the stamp is a fact.
 */
const BRODFEST_PAGE_META = '_brodfest_page';

/**
 * The pages this plugin owns and renders.
 *
 * Real WordPress pages are created for these so permalinks, menus and SEO
 * plugins behave normally. Rendering is taken over in includes/render.php.
 *
 * Adding a page is one entry here plus its template in templates/pages/. Live
 * sites pick it up on their next update — see brodfest_maybe_upgrade().
 *
 * `description` is stored as the page's excerpt on creation, where an SEO
 * plugin can read or override it, rather than printed as a meta tag here.
 *
 * `sites` says which of the two sites a page belongs to — see includes/site.php.
 *
 * @return array<string, array{title: string, template: string, description: string, sites: string[]}>
 */
function brodfest_pages() {
	$both   = array( BRODFEST_SITE_STD, BRODFEST_SITE_INVITE );
	$invite = array( BRODFEST_SITE_INVITE );

	return array(
		'home'              => array(
			'title'       => __( 'Home', 'blueworx-client-brodfest' ),
			'template'    => 'pages/home.php',
			'description' => '',
			'sites'       => $both,
		),
		'celebrate'         => array(
			'title'       => __( 'Celebrate', 'blueworx-client-brodfest' ),
			'template'    => 'pages/celebrate.php',
			'description' => '',
			'sites'       => $both,
		),
		'can-sakhara-story' => array(
			'title'       => __( 'Can Sakhara Story', 'blueworx-client-brodfest' ),
			'template'    => 'pages/story.php',
			'description' => '',
			'sites'       => $invite,
		),
		'accommodation'     => array(
			'title'       => __( 'Accommodation', 'blueworx-client-brodfest' ),
			'template'    => 'pages/accommodation.php',
			'description' => '',
			'sites'       => $both,
		),
		'recommendations'   => array(
			'title'       => __( 'Ibiza Recommendations', 'blueworx-client-brodfest' ),
			'template'    => 'pages/recommendations.php',
			'description' => '',
			'sites'       => $invite,
		),
		'enquiries'         => array(
			'title'       => __( 'Enquiries', 'blueworx-client-brodfest' ),
			'template'    => 'pages/enquiries.php',
			'description' => '',
			'sites'       => $both,
		),
		'rsvp'              => array(
			'title'       => __( 'RSVP', 'blueworx-client-brodfest' ),
			'template'    => 'pages/rsvp.php',
			'description' => '',
			'sites'       => $both,
		),
	);
}

/**
 * Creates any missing owned pages, stamps them, and (on activation) makes
 * Home the front page.
 *
 * Idempotent: an existing stamped page is reused rather than duplicated, so
 * reactivating the plugin never leaves a second copy behind.
 *
 * @param bool $set_front_page Whether to point the site's front page at Home.
 *                             True on activation; false when an update merely
 *                             adds a page.
 * @return void
 */
function brodfest_install_pages( $set_front_page = true ) {
	$ids = (array) get_option( 'brodfest_page_ids', array() );

	foreach ( brodfest_pages() as $slug => $page ) {
		$existing = isset( $ids[ $slug ] ) ? (int) $ids[ $slug ] : 0;

		if ( $existing > 0 && 'page' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) && get_post_meta( $existing, BRODFEST_PAGE_META, true ) === $slug ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $page['title'],
				'post_name'      => $slug,
				'post_content'   => '',
				'post_excerpt'   => $page['description'],
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, BRODFEST_PAGE_META, $slug );
		$ids[ $slug ] = (int) $post_id;
	}

	update_option( 'brodfest_page_ids', $ids );

	if ( $set_front_page && isset( $ids['home'] ) && (int) $ids['home'] > 0 ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $ids['home'] );
	}
}

/**
 * The permalink of an owned page.
 *
 * Internal links must resolve the page this plugin actually created, not a
 * guessed URL: on a site that already had a page slugged "about", ours is
 * stored as "about-2", and plain permalinks break the guess just as badly.
 *
 * @param string $slug Owned-page slug.
 * @return string Permalink.
 */
function brodfest_page_url( $slug ) {
	$slug = (string) $slug;
	$ids  = (array) get_option( 'brodfest_page_ids', array() );
	$id   = isset( $ids[ $slug ] ) ? (int) $ids[ $slug ] : 0;

	$url = '';

	if ( $id > 0 && brodfest_page_slug( $id ) === $slug ) {
		$url = (string) get_permalink( $id );
	}

	if ( '' === $url ) {
		$url = home_url( 'home' === $slug ? '/' : '/' . $slug . '/' );
	}

	// An editor previewing the other site stays in it as they click around.
	$preview = brodfest_preview_site();

	return '' !== $preview ? add_query_arg( BRODFEST_PREVIEW_ARG, $preview, $url ) : $url;
}

/**
 * Whether a page was created by this plugin.
 *
 * @param int $post_id Page ID.
 * @return bool
 */
function brodfest_page_is_ours( $post_id ) {
	return '' !== brodfest_page_slug( $post_id );
}

/**
 * The owned-page slug for a post, or '' when the post is not ours.
 *
 * @param int $post_id Page ID.
 * @return string
 */
function brodfest_page_slug( $post_id ) {
	$post_id = (int) $post_id;

	if ( $post_id <= 0 ) {
		return '';
	}

	$slug = (string) get_post_meta( $post_id, BRODFEST_PAGE_META, true );

	return isset( brodfest_pages()[ $slug ] ) ? $slug : '';
}

/**
 * Whether the current request is one of this plugin's pages.
 *
 * @return bool
 */
function brodfest_is_owned_request() {
	return is_singular( 'page' ) && brodfest_page_is_ours( get_queried_object_id() );
}
