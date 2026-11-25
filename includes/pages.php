<?php
/**
 * The pages this plugin owns, how it claims them, and where their section
 * blocks are kept.
 *
 * Every owned page is a real WordPress page whose content is a list of
 * section blocks (blocks/). Two sites share the pages: the Save the Date and
 * the Invite, each with its own blocks for a page. The blocks of the site
 * that is live sit in the page itself, so the page, its REST address and any
 * SEO plugin see only what visitors see. The other site's blocks are kept in
 * a private "held-back page" linked to it, and the two swap places when the
 * live site is switched — see brodfest_sync_live_content().
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

/** The post type that keeps the other site's blocks for a page. */
const BRODFEST_HOLD_TYPE = 'brodfest_held_page';

/** Page meta: the id of the held-back page that goes with it. */
const BRODFEST_HOLD_META = '_brodfest_held_page';

/** Page and held-back page meta: which site the blocks in that post are for. */
const BRODFEST_CONTENT_SITE_META = '_brodfest_content_site';

/** Option: the site whose blocks the pages held when the swap last ran. */
const BRODFEST_SYNCED_OPTION = 'brodfest_synced_site';

/** Option row that is the lock while a swap runs: only one request can insert it. */
const BRODFEST_LOCK_OPTION = 'brodfest_sync_lock';

/** Option: what the administrator has to be told about the last swap. */
const BRODFEST_NOTICE_OPTION = 'brodfest_sync_notice';

/** Page and held-back page meta: counts the swaps, so a stale editor tab can be told. */
const BRODFEST_GEN_META = '_brodfest_swap_gen';

/**
 * The pages this plugin owns and renders.
 *
 * Real WordPress pages are created for these so permalinks, menus and SEO
 * plugins behave normally. Rendering is taken over in includes/render.php.
 *
 * Adding a page is one entry here plus its starting blocks in
 * includes/seed.php. Live sites pick it up on their next update — see
 * brodfest_maybe_upgrade().
 *
 * `description` is stored as the page's excerpt on creation, where an SEO
 * plugin can read or override it, rather than printed as a meta tag here.
 *
 * `sites` says which of the two sites a page belongs to — see includes/site.php.
 *
 * @return array<string, array{title: string, description: string, sites: string[]}>
 */
function brodfest_pages() {
	$both   = array( BRODFEST_SITE_STD, BRODFEST_SITE_INVITE );
	$invite = array( BRODFEST_SITE_INVITE );

	return array(
		'home'              => array(
			'title'       => __( 'Home', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $both,
		),
		'celebrate'         => array(
			'title'       => __( 'Celebrate', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $both,
		),
		'can-sakhara-story' => array(
			'title'       => __( 'Can Sakhara Story', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $invite,
		),
		'accommodation'     => array(
			'title'       => __( 'Accommodation', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $both,
		),
		'recommendations'   => array(
			'title'       => __( 'Ibiza Recommendations', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $invite,
		),
		'enquiries'         => array(
			'title'       => __( 'Enquiries', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $both,
		),
		'rsvp'              => array(
			'title'       => __( 'RSVP', 'blueworx-client-brodfest' ),
			'description' => '',
			'sites'       => $both,
		),
	);
}

/**
 * Registers the private post type that holds the other site's blocks.
 *
 * It has no address of its own and no archive, and its posts are private, so
 * nothing in it is ever served to a visitor or listed by the REST API without
 * a sign-in. Editors open it from the Pages menu, in the block editor.
 *
 * @return void
 */
function brodfest_register_held_pages() {
	register_post_type(
		BRODFEST_HOLD_TYPE,
		array(
			'labels'            => array(
				'name'          => __( 'Held-back pages', 'blueworx-client-brodfest' ),
				'singular_name' => __( 'Held-back page', 'blueworx-client-brodfest' ),
				'edit_item'     => __( 'Edit held-back page', 'blueworx-client-brodfest' ),
				'search_items'  => __( 'Search held-back pages', 'blueworx-client-brodfest' ),
				'not_found'     => __( 'No held-back pages.', 'blueworx-client-brodfest' ),
				'all_items'     => __( 'Held-back pages', 'blueworx-client-brodfest' ),
				'menu_name'     => __( 'Held-back pages', 'blueworx-client-brodfest' ),
			),
			'description'       => __( 'The pages of the site that is not live yet (or no longer), kept private until the live site is switched.', 'blueworx-client-brodfest' ),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => 'edit.php?post_type=page',
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'rest_base'         => 'brodfest-held-pages',
			// Custom fields, so the page lock's setting is part of the record in the REST API.
			'supports'          => array( 'title', 'editor', 'revisions', 'custom-fields' ),
			'capability_type'   => 'page',
			'map_meta_cap'      => true,
			'capabilities'      => array( 'create_posts' => 'do_not_allow' ),
			'has_archive'       => false,
			'rewrite'           => false,
			'query_var'         => false,
			'can_export'        => true,
		)
	);
}
add_action( 'init', 'brodfest_register_held_pages' );

/**
 * The ids of the owned pages, by slug.
 *
 * @return array<string, int>
 */
function brodfest_page_ids() {
	$ids = array();

	foreach ( (array) get_option( 'brodfest_page_ids', array() ) as $slug => $id ) {
		if ( isset( brodfest_pages()[ $slug ] ) && (int) $id > 0 ) {
			$ids[ $slug ] = (int) $id;
		}
	}

	return $ids;
}

/**
 * Runs a write of the plugin's own content with WordPress's HTML filtering off.
 *
 * Moving blocks between a page and its held-back page, or giving a page its
 * starting blocks, must store exactly the words it was given. The filtering
 * (it is on for anyone without the unfiltered_html right, and a signed-out
 * visitor can be the one whose request runs a retry) rewrites text such as an
 * ampersand. It is switched back on, for whoever is signed in, afterwards.
 *
 * @param callable $write Does the write and returns its result.
 * @return mixed
 */
function brodfest_unfiltered( $write ) {
	kses_remove_filters();

	try {
		return $write();
	} finally {
		kses_init();
	}
}

/**
 * Writes some fields of a post and says whether WordPress stored them exactly.
 *
 * @param int   $post_id The post.
 * @param array $fields  Post fields to change (post_content, post_title…).
 * @return bool
 */
function brodfest_write_post( $post_id, $fields ) {
	// The plugin's own write: the page lock (blueworx-page-lock/) is for people
	// editing, and would refuse a swap that changes a page's sections.
	$result = brodfest_without_page_lock(
		static function () use ( $post_id, $fields ) {
			return brodfest_unfiltered(
				static function () use ( $post_id, $fields ) {
					return wp_update_post( wp_slash( array_merge( array( 'ID' => $post_id ), $fields ) ), true );
				}
			);
		}
	);

	if ( is_wp_error( $result ) || (int) $result !== (int) $post_id ) {
		return false;
	}

	// What was stored, read back, must be what was meant to be stored.
	clean_post_cache( $post_id );

	foreach ( $fields as $field => $value ) {
		if ( (string) get_post_field( $field, $post_id, 'raw' ) !== (string) $value ) {
			return false;
		}
	}

	return true;
}

/**
 * Makes a held-back-type post and says which, or 0 when it was not stored exactly.
 *
 * @param array $fields Post fields (post_title, post_content).
 * @return int Post id, or 0.
 */
function brodfest_insert_held( $fields ) {
	$id = brodfest_unfiltered(
		static function () use ( $fields ) {
			return wp_insert_post(
				wp_slash(
					array_merge(
						array(
							'post_type'   => BRODFEST_HOLD_TYPE,
							'post_status' => 'private',
						),
						$fields
					)
				),
				true
			);
		}
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	clean_post_cache( $id );

	foreach ( $fields as $field => $value ) {
		if ( (string) get_post_field( $field, $id, 'raw' ) !== (string) $value ) {
			wp_delete_post( $id, true );

			return 0;
		}
	}

	return (int) $id;
}

/**
 * Gives a page its blocks, the first time it has them, and makes sure its
 * held-back page exists. Runs under the sync lock (see
 * brodfest_sync_live_content()), never on its own.
 *
 * The page keeps the Save the Date's blocks and a held-back page is made for
 * the Invite's (the swap then sorts out a site whose Invite is live already). A
 * page that already has blocks of its own is left exactly as it is; a held-back
 * page someone deleted is made again from the starting content.
 *
 * The page is only marked as seeded once its blocks are stored, so a failed
 * write is tried again on the next run. The marker goes first, so the revision
 * that writing the blocks makes is tagged with the site it is for.
 *
 * @param string $slug    Owned-page slug.
 * @param int    $page_id The page.
 * @return bool Whether the page and its held-back page are in place.
 */
function brodfest_seed_page( $slug, $page_id ) {
	$page = get_post( $page_id );

	if ( ! $page ) {
		return false;
	}

	$holds = (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true );

	if ( '' === $holds ) {
		$holds = BRODFEST_SITE_STD;
		update_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, $holds );

		if ( '' === trim( $page->post_content ) && ! brodfest_write_post( $page_id, array( 'post_content' => brodfest_seed_content( $slug, BRODFEST_SITE_STD ) ) ) ) {
			delete_post_meta( $page_id, BRODFEST_CONTENT_SITE_META );

			return false;
		}
	}

	if ( brodfest_held_id( $page_id ) > 0 ) {
		return true;
	}

	// The held-back page keeps the blocks of the site the page does not hold.
	$other   = BRODFEST_SITE_STD === $holds ? BRODFEST_SITE_INVITE : BRODFEST_SITE_STD;
	$held_id = brodfest_insert_held(
		array(
			'post_title'   => brodfest_held_title( $slug, $other ),
			'post_content' => brodfest_seed_content( $slug, $other ),
		)
	);

	if ( $held_id <= 0 ) {
		return false;
	}

	update_post_meta( $held_id, BRODFEST_CONTENT_SITE_META, $other );
	update_post_meta( $page_id, BRODFEST_HOLD_META, $held_id );

	return true;
}

/**
 * The title of a held-back page: the page, and which site its blocks are for.
 *
 * @param string $slug Owned-page slug.
 * @param string $site Which site the held blocks are for.
 * @return string
 */
function brodfest_held_title( $slug, $site ) {
	$name = BRODFEST_SITE_INVITE === $site ? __( 'Invite', 'blueworx-client-brodfest' ) : __( 'Save the Date', 'blueworx-client-brodfest' );

	/* translators: 1: page name, 2: the site its blocks are for (Invite or Save the Date). */
	return sprintf( __( '%1$s (%2$s, not live)', 'blueworx-client-brodfest' ), brodfest_pages()[ $slug ]['title'] ?? $slug, $name );
}

/**
 * The blocks to draw for a page on the site this request shows.
 *
 * The page's own content when it holds that site's blocks, otherwise the
 * held-back page's. Only ever what is saved: nothing is filled in by code when
 * the page loads. (A page is given its starting blocks once, when it is made or
 * the plugin updates past the release that moved the pages onto blocks, and a
 * held-back page that has gone is made again by the next switch.)
 *
 * @param int     $page_id The page.
 * @param WP_Post $post    Optional. The page as WordPress has it loaded: for a
 *                         WordPress preview that is the unsaved version, which
 *                         is what an editor is there to see.
 * @return string Serialised blocks.
 */
function brodfest_page_blocks( $page_id, $post = null ) {
	$page_id = (int) $page_id;
	$site    = brodfest_site();
	$holds   = (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true );

	// A page that holds the site being shown (or has not been marked yet, so
	// holds nothing but its own words) draws its own content.
	if ( '' === $holds || $holds === $site ) {
		return $post instanceof WP_Post && (int) $post->ID === $page_id ? (string) $post->post_content : (string) get_post_field( 'post_content', $page_id );
	}

	$held_id = brodfest_held_id( $page_id );

	return $held_id > 0 ? (string) get_post_field( 'post_content', $held_id ) : '';
}

/**
 * The held-back page that goes with a page, or 0 when it is missing or in the
 * bin.
 *
 * @param int $page_id The page.
 * @return int
 */
function brodfest_held_id( $page_id ) {
	$held_id = (int) get_post_meta( (int) $page_id, BRODFEST_HOLD_META, true );

	return $held_id > 0 && BRODFEST_HOLD_TYPE === get_post_type( $held_id ) && 'trash' !== get_post_status( $held_id ) ? $held_id : 0;
}

// The lock is a row in the options table that only one request can insert, which
// takes a direct query: add_option() and transients read first and write after,
// so two requests can both get past the read. Every query is prepared.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * The lock this request holds ("time:token"), or '' when it holds none.
 *
 * @param string|null $set Set it when given.
 * @return string
 */
function brodfest_lock_held( $set = null ) {
	static $held = '';

	if ( null !== $set ) {
		$held = $set;
	}

	return $held;
}

/**
 * Takes the lock that keeps swaps, seeding and editor saves from overlapping.
 * A plain INSERT fails if the row is already there, so of two requests at once
 * only one gets it. A lock older than two minutes belongs to a request that
 * died, and is taken over.
 *
 * @param int $wait Seconds to keep trying for it.
 * @return bool Whether this request now holds the lock.
 */
function brodfest_sync_lock( $wait = 0 ) {
	global $wpdb;

	if ( '' !== brodfest_lock_held() ) {
		return false;
	}

	$mine     = time() . ':' . wp_generate_password( 12, false );
	$deadline = microtime( true ) + $wait;

	do {
		$wpdb->suppress_errors( true );
		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", BRODFEST_LOCK_OPTION, $mine ) );
		$wpdb->suppress_errors( false );

		if ( 1 === $result ) {
			brodfest_lock_held( $mine );
			register_shutdown_function( 'brodfest_sync_unlock' );

			return true;
		}

		$current = (string) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BRODFEST_LOCK_OPTION ) );

		if ( '' !== $current && time() - (int) $current > 2 * MINUTE_IN_SECONDS ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", BRODFEST_LOCK_OPTION, $current ) );
			$deadline = max( $deadline, microtime( true ) + 0.5 );
			continue;
		}

		if ( $wait > 0 ) {
			usleep( 150000 );
		}
	} while ( microtime( true ) < $deadline );

	return false;
}

/**
 * Lets go of the lock, if this request holds it. Safe to call twice.
 *
 * @return void
 */
function brodfest_sync_unlock() {
	global $wpdb;

	$mine = brodfest_lock_held();

	if ( '' === $mine ) {
		return;
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", BRODFEST_LOCK_OPTION, $mine ) );
	wp_cache_delete( BRODFEST_LOCK_OPTION, 'options' );
	brodfest_lock_held( '' );
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Swaps one page's blocks with its held-back page's, checking each write.
 *
 * Before anything is written, both versions are kept as "Recovery copy" posts
 * among the held-back pages. They are deleted once the whole switch is done (or
 * the pages are confirmed put back), and kept, and reported, if a page cannot be
 * put back.
 *
 * @param string $slug     Owned-page slug.
 * @param int    $page_id  The page.
 * @param int    $held_id  Its held-back page.
 * @param string $holds    The site the page holds now.
 * @param string $live     The site it should hold.
 * @param bool   $restored Set to false if the swap failed and the page could
 *                         not be put back as it was.
 * @return array|false What it did, for undoing it (see brodfest_unswap_page()), or false.
 */
function brodfest_swap_page( $slug, $page_id, $held_id, $holds, $live, &$restored = true ) {
	$restored = true;
	$title    = brodfest_pages()[ $slug ]['title'] ?? $slug;
	$undo     = array(
		'slug'         => $slug,
		'page_id'      => $page_id,
		'held_id'      => $held_id,
		'holds'        => $holds,
		'live'         => $live,
		'page_content' => (string) get_post_field( 'post_content', $page_id, 'raw' ),
		'held_content' => (string) get_post_field( 'post_content', $held_id, 'raw' ),
		'held_title'   => (string) get_post_field( 'post_title', $held_id, 'raw' ),
		'recovery'     => array(),
	);

	// A copy of each version that survives a failed write, in the held-back pages.
	foreach ( array( 'page_content', 'held_content' ) as $key ) {
		$copy = brodfest_insert_held(
			array(
				/* translators: 1: page name, 2: which version (the page's or the held-back page's), 3: date and time. */
				'post_title'   => sprintf( __( 'Recovery copy: %1$s, %2$s, before the switch on %3$s', 'blueworx-client-brodfest' ), $title, 'page_content' === $key ? __( 'the page', 'blueworx-client-brodfest' ) : __( 'the held-back page', 'blueworx-client-brodfest' ), gmdate( 'Y-m-d H:i' ) ),
				'post_content' => $undo[ $key ],
			)
		);

		if ( $copy <= 0 ) {
			brodfest_discard_recovery( $undo );

			return false;
		}

		$undo['recovery'][] = $copy;
	}

	// The markers go first, so the revisions the writes make are tagged with
	// the site their content is for.
	update_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, $live );
	update_post_meta( $held_id, BRODFEST_CONTENT_SITE_META, $holds );

	$page_ok = brodfest_write_post( $page_id, array( 'post_content' => $undo['held_content'] ) );
	$held_ok = $page_ok && brodfest_write_post(
		$held_id,
		array(
			'post_title'   => brodfest_held_title( $slug, $holds ),
			'post_content' => $undo['page_content'],
		)
	);

	if ( ! $held_ok ) {
		$restored = brodfest_unswap_page( $undo, $page_ok );

		if ( $restored ) {
			brodfest_discard_recovery( $undo );
		}

		return false;
	}

	brodfest_bump_gen( $page_id );
	brodfest_bump_gen( $held_id );

	return $undo;
}

/**
 * Deletes the recovery copies made for a swap.
 *
 * @param array $undo From brodfest_swap_page().
 * @return void
 */
function brodfest_discard_recovery( $undo ) {
	foreach ( (array) $undo['recovery'] as $copy ) {
		wp_delete_post( (int) $copy, true );
	}
}

/**
 * Puts a page and its held-back page back as they were before a swap, and says
 * whether what is stored now is exactly that.
 *
 * @param array $undo       From brodfest_swap_page().
 * @param bool  $page_moved Whether the page's content was already replaced.
 * @return bool Whether everything went back.
 */
function brodfest_unswap_page( $undo, $page_moved = true ) {
	update_post_meta( $undo['page_id'], BRODFEST_CONTENT_SITE_META, $undo['holds'] );
	update_post_meta( $undo['held_id'], BRODFEST_CONTENT_SITE_META, $undo['live'] );

	$ok = true;

	if ( $page_moved ) {
		$ok = brodfest_write_post( $undo['page_id'], array( 'post_content' => $undo['page_content'] ) );
		brodfest_bump_gen( $undo['page_id'] );
	}

	// The held-back page is only ever written after the page, so a failed
	// swap has changed it at most once, and this puts that right.
	$ok = brodfest_write_post(
		$undo['held_id'],
		array(
			'post_title'   => $undo['held_title'],
			'post_content' => $undo['held_content'],
		)
	) && $ok;
	brodfest_bump_gen( $undo['held_id'] );

	return $ok;
}

/**
 * Makes the pages themselves hold the live site's blocks, swapping each with
 * its held-back page when the site wanted (the setting) is not the one they
 * hold. Also the one place pages are given their starting blocks, so seeding
 * and swapping can never overlap.
 *
 * The site visitors see (brodfest_live_site()) only changes once this has
 * finished, so a switch that fails leaves the website, and the public REST
 * API, on the site they were on.
 *
 * All or nothing: every page's held-back copy must exist first (a missing one
 * is made again from the starting content and the administrator is told), and
 * if any write fails the pages already swapped are put back and the switch is
 * left unfinished, to be tried again. Both versions of each page are kept as
 * recovery copies until the switch is done or the pages are confirmed put back.
 *
 * Idempotent: a page that already holds the wanted site's blocks is left alone,
 * so running it twice never swaps back. Until a site is switched to the
 * Invite, no visitor, REST request or search engine can see its copy: it sits
 * in a private post.
 *
 * @return bool Whether the pages now hold the wanted site's blocks.
 */
function brodfest_sync_live_content() {
	if ( ! brodfest_sync_lock( 5 ) ) {
		return false;
	}

	$ok = false;

	try {
		$ok = brodfest_sync_locked();
	} finally {
		brodfest_sync_unlock();
	}

	return $ok;
}

/**
 * The body of brodfest_sync_live_content(), run while holding the lock.
 *
 * @return bool
 */
function brodfest_sync_locked() {
	$wanted = brodfest_wanted_site();
	$ids    = brodfest_page_ids();
	$done   = array();
	$notice = array();

	// What the administrator is told is about this run only.
	delete_option( BRODFEST_NOTICE_OPTION );

	// Nothing to move until the pages exist (the upgrade makes them, then syncs).
	if ( array() === $ids ) {
		return false;
	}

	// Every page has to have its blocks and its held-back copy before anything moves.
	foreach ( $ids as $slug => $page_id ) {
		if ( '' !== (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true ) && 0 === brodfest_held_id( $page_id ) ) {
			$notice['recreated'][] = $slug;
		}

		if ( ! brodfest_seed_page( $slug, $page_id ) ) {
			$notice['failed'] = $slug;
			unset( $notice['recreated'] );
			update_option( BRODFEST_NOTICE_OPTION, $notice, false );

			return false;
		}
	}

	foreach ( $ids as $slug => $page_id ) {
		$holds   = (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true );
		$held_id = brodfest_held_id( $page_id );

		if ( '' === $holds || $holds === $wanted ) {
			continue;
		}

		$restored = true;
		$swap     = $held_id > 0 ? brodfest_swap_page( $slug, $page_id, $held_id, $holds, $wanted, $restored ) : false;

		if ( $swap ) {
			$done[] = $swap;
			continue;
		}

		$notice['failed'] = $slug;

		if ( ! $restored ) {
			$notice['unrestored'][] = $slug;
		}

		// Put back the pages already swapped, newest first; keep the recovery
		// copies of any that cannot be confirmed put back.
		foreach ( array_reverse( $done ) as $undo ) {
			if ( brodfest_unswap_page( $undo ) ) {
				brodfest_discard_recovery( $undo );
			} else {
				$notice['unrestored'][] = $undo['slug'];
			}
		}

		update_option( BRODFEST_NOTICE_OPTION, $notice, false );

		return false;
	}

	foreach ( $done as $undo ) {
		brodfest_discard_recovery( $undo );
	}

	if ( ! empty( $notice ) ) {
		update_option( BRODFEST_NOTICE_OPTION, $notice, false );
	}

	update_option( BRODFEST_SYNCED_OPTION, $wanted );

	return true;
}

/**
 * Runs the swap as soon as the saved site changes.
 *
 * @return void
 */
function brodfest_sync_on_settings_change() {
	if ( ! brodfest_sync_live_content() ) {
		set_transient( 'brodfest_sync_retry', 1, 5 * MINUTE_IN_SECONDS );
	}
}
add_action( 'update_option_' . BRODFEST_SETTINGS_OPTION, 'brodfest_sync_on_settings_change' );
add_action( 'add_option_' . BRODFEST_SETTINGS_OPTION, 'brodfest_sync_on_settings_change' );

/**
 * The same swap, caught on the next request if the setting was changed some
 * other way (the command line, an import) or an earlier try did not finish.
 * A try that failed is not repeated for five minutes.
 *
 * @return void
 */
function brodfest_sync_if_needed() {
	if ( get_option( BRODFEST_SYNCED_OPTION ) === brodfest_wanted_site() || false !== get_transient( 'brodfest_sync_retry' ) ) {
		return;
	}

	if ( ! brodfest_sync_live_content() ) {
		set_transient( 'brodfest_sync_retry', 1, 5 * MINUTE_IN_SECONDS );
	}
}
add_action( 'init', 'brodfest_sync_if_needed', 30 );

/**
 * Creates any missing owned pages, stamps them, and (on activation) makes Home
 * the front page. Their starting blocks are given by the sync that ends this,
 * under its lock.
 *
 * Idempotent: an existing stamped page is reused rather than duplicated, so
 * reactivating the plugin never leaves a second copy behind, and a page that
 * already has blocks keeps them.
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
			wp_slash(
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

	if ( ! brodfest_sync_live_content() ) {
		set_transient( 'brodfest_sync_retry', 1, 5 * MINUTE_IN_SECONDS );
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
