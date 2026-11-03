<?php
/**
 * Which of the two Brodfest sites is showing: Save the Date or the Invite.
 *
 * Both are one plugin and one set of pages. The Invite adds pages (the Can
 * Sakhara story, recommendations) and changes others (the home lettering, the
 * Celebrate page). Which one visitors see is a single stored setting.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_SITE_STD    = 'save-the-date';
const BRODFEST_SITE_INVITE = 'invite';

/**
 * The query argument an editor adds to preview the other site.
 */
const BRODFEST_PREVIEW_ARG = 'brodfest-site';

/**
 * The site visitors see, before any preview.
 *
 * @return string
 */
function brodfest_live_site() {
	$site = brodfest_saved_site();

	return BRODFEST_SITE_INVITE === $site ? BRODFEST_SITE_INVITE : BRODFEST_SITE_STD;
}

/**
 * The site an editor asked to preview with ?brodfest-site=, or ''.
 *
 * Only people who can edit pages get a preview; everyone else sees the live
 * site whatever the URL says.
 *
 * @return string
 */
function brodfest_preview_site() {
	// Read-only display switch, no state is changed, so no nonce.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$asked = isset( $_GET[ BRODFEST_PREVIEW_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ BRODFEST_PREVIEW_ARG ] ) ) : '';

	if ( '' === $asked || ! current_user_can( 'edit_pages' ) ) {
		return '';
	}

	return in_array( $asked, array( BRODFEST_SITE_STD, BRODFEST_SITE_INVITE ), true ) ? $asked : '';
}

/**
 * The site this request shows.
 *
 * @return string
 */
function brodfest_site() {
	$preview = brodfest_preview_site();

	return '' !== $preview ? $preview : brodfest_live_site();
}

/**
 * Whether this request shows the Invite.
 *
 * @return bool
 */
function brodfest_is_invite() {
	return BRODFEST_SITE_INVITE === brodfest_site();
}

/**
 * The menu for the current site, as owned-page slugs in order.
 *
 * Transport was removed from both menus: its copy moves into Accommodation.
 *
 * @return string[]
 */
function brodfest_menu() {
	if ( brodfest_is_invite() ) {
		return array( 'celebrate', 'can-sakhara-story', 'accommodation', 'recommendations', 'enquiries' );
	}

	return array( 'celebrate', 'accommodation', 'enquiries' );
}

/**
 * Sends a page the current site does not have to the home page.
 *
 * Invite-only pages exist all along so their links are stable, but while the
 * Save the Date is live they are not part of it.
 *
 * @return void
 */
function brodfest_redirect_other_site_pages() {
	if ( ! brodfest_is_owned_request() ) {
		return;
	}

	$slug = brodfest_page_slug( get_queried_object_id() );

	if ( ! in_array( brodfest_site(), brodfest_pages()[ $slug ]['sites'], true ) ) {
		wp_safe_redirect( brodfest_page_url( 'home' ) );
		exit;
	}
}
add_action( 'template_redirect', 'brodfest_redirect_other_site_pages' );

/**
 * The owned pages that are not part of the live site.
 *
 * @return int[] Page ids.
 */
function brodfest_hidden_page_ids() {
	$ids  = (array) get_option( 'brodfest_page_ids', array() );
	$live = brodfest_live_site();
	$out  = array();

	foreach ( brodfest_pages() as $slug => $page ) {
		if ( ! in_array( $live, $page['sites'], true ) && ! empty( $ids[ $slug ] ) ) {
			$out[] = (int) $ids[ $slug ];
		}
	}

	return $out;
}

/**
 * Keeps the Invite's pages out of the sitemap until the Invite is live, so
 * search engines do not find them early.
 *
 * @param array  $args      Sitemap query arguments.
 * @param string $post_type Post type.
 * @return array
 */
function brodfest_sitemap_hide_other_site( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$hidden = brodfest_hidden_page_ids();

	if ( $hidden ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $hidden );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'brodfest_sitemap_hide_other_site', 10, 2 );
