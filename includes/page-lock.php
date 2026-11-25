<?php
/**
 * How this plugin uses the shared page lock.
 *
 * The lock itself is the foundation's, copied whole into blueworx-page-lock/
 * and never edited here (CI compares it with the foundation's). It locks every
 * page from the moment the page exists: anyone who is not an administrator, and
 * any connected tool, can change a page's words, images, links and list items
 * and show or hide a section, but cannot add, move or remove one, and the
 * server refuses a save that tries to. This file only tells the lock what the
 * plugin has that it should cover, and makes the plugin's own writes (the
 * starting blocks, the switch between the Save the Date and the Invite) go
 * through it.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option: the plugin version the pages were last checked for a lock at. */
const BRODFEST_LOCKED_OPTION = 'brodfest_page_lock_applied';

/**
 * Makes the lock cover the held-back pages too.
 *
 * A held-back page holds the other site's blocks and goes live in place of the
 * page's own, so it has to keep the same sections the page would have.
 *
 * @param string[] $types Post types the lock covers.
 * @return string[]
 */
function brodfest_lock_post_types( $types ) {
	return array_values( array_unique( array_merge( (array) $types, array( BRODFEST_HOLD_TYPE ) ) ) );
}
add_filter( 'blueworx_page_lock_post_types', 'brodfest_lock_post_types' );

/**
 * Offers a client only this plugin's section blocks on a locked page, never
 * another plugin's.
 *
 * @return string[]
 */
function brodfest_lock_section_namespaces() {
	return array( 'brodfest' );
}
add_filter( 'blueworx_page_lock_section_namespaces', 'brodfest_lock_section_namespaces' );

/**
 * Runs a write the plugin makes on purpose, with the lock stood aside.
 *
 * Swapping the Save the Date's blocks for the Invite's changes a page's
 * sections, which the lock exists to stop, and it runs for whoever happens to
 * make the request (an administrator saving the setting, or a visitor whose
 * request retries a switch that failed). Only the plugin's own writes come
 * through here; nobody's save from the editor or the REST API does.
 *
 * @param callable $write Does the write and returns its result.
 * @return mixed
 */
function brodfest_without_page_lock( $write ) {
	$off = static function () {
		return array();
	};

	add_filter( 'blueworx_page_lock_post_types', $off, PHP_INT_MAX );

	try {
		return $write();
	} finally {
		remove_filter( 'blueworx_page_lock_post_types', $off, PHP_INT_MAX );
	}
}

/**
 * Locks the pages this plugin owns, and their held-back pages, that were made
 * before the lock was in place (pages that exist already on a live site, and
 * pages made while the plugin was being activated, before the lock had started).
 * A page made afterwards is locked by the lock itself, as it is created.
 *
 * A page that already has a lock setting, locked or not, is left as it is, so
 * a page an administrator unlocked stays unlocked.
 *
 * @return void
 */
function brodfest_lock_owned_pages() {
	if ( get_option( BRODFEST_LOCKED_OPTION ) === BRODFEST_VERSION ) {
		return;
	}

	$lock = '\Blueworx\PageLock\v1\Lock';

	// The lock starts on after_setup_theme. If it has not, try again next time.
	if ( ! class_exists( $lock, false ) ) {
		return;
	}

	$ids = array();

	foreach ( brodfest_page_ids() as $page_id ) {
		$ids[] = $page_id;
		$ids[] = brodfest_held_id( $page_id );
	}

	foreach ( array_filter( $ids ) as $post_id ) {
		$post = get_post( $post_id );

		if ( $post && ! metadata_exists( 'post', $post_id, $lock::META ) ) {
			// The lock's own step for a page being created: it sets the flag
			// whoever is signed in, and honours the site's own filters.
			$lock::lock_new_page( $post_id, $post, false );
		}
	}

	update_option( BRODFEST_LOCKED_OPTION, BRODFEST_VERSION );
}
add_action( 'init', 'brodfest_lock_owned_pages', 20 );
