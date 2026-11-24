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
			'supports'          => array( 'title', 'editor', 'revisions' ),
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
 * Gives a page its blocks, the first time it has them, and makes sure its
 * held-back page exists.
 *
 * The page keeps the Save the Date's blocks and a held-back page is made for
 * the Invite's (the swap in brodfest_sync_live_content() then sorts out a site
 * whose Invite is live already). A page that already has blocks of its own is
 * left exactly as it is; a held-back page someone deleted is made again from
 * the starting content.
 *
 * @param string $slug    Owned-page slug.
 * @param int    $page_id The page.
 * @return void
 */
function brodfest_seed_page( $slug, $page_id ) {
	$page = get_post( $page_id );

	if ( ! $page ) {
		return;
	}

	$holds = (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true );

	if ( '' === $holds ) {
		if ( '' === trim( $page->post_content ) ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $page_id,
						'post_content' => brodfest_seed_content( $slug, BRODFEST_SITE_STD ),
					)
				)
			);
		}

		$holds = BRODFEST_SITE_STD;
		update_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, $holds );
	}

	$held_id = (int) get_post_meta( $page_id, BRODFEST_HOLD_META, true );

	if ( $held_id > 0 && BRODFEST_HOLD_TYPE === get_post_type( $held_id ) && 'trash' !== get_post_status( $held_id ) ) {
		return;
	}

	// The held-back page keeps the blocks of the site the page does not hold.
	$other   = BRODFEST_SITE_STD === $holds ? BRODFEST_SITE_INVITE : BRODFEST_SITE_STD;
	$held_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => BRODFEST_HOLD_TYPE,
				'post_status'  => 'private',
				'post_title'   => brodfest_held_title( $slug, $other ),
				'post_content' => brodfest_seed_content( $slug, $other ),
			)
		)
	);

	if ( is_wp_error( $held_id ) || ! $held_id ) {
		return;
	}

	update_post_meta( $page_id, BRODFEST_HOLD_META, (int) $held_id );
	update_post_meta( $held_id, BRODFEST_CONTENT_SITE_META, $other );
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
 * held-back page's. A page nobody has moved onto blocks yet, or whose
 * held-back page has gone, shows its starting content, so the site is never
 * blank.
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

	if ( '' === $holds ) {
		return brodfest_seed_content( brodfest_page_slug( $page_id ), $site );
	}

	if ( $holds === $site ) {
		return $post instanceof WP_Post && (int) $post->ID === $page_id ? (string) $post->post_content : (string) get_post_field( 'post_content', $page_id );
	}

	$held_id = brodfest_held_id( $page_id );

	return $held_id > 0 ? (string) get_post_field( 'post_content', $held_id ) : brodfest_seed_content( brodfest_page_slug( $page_id ), $site );
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
 * Takes the sync lock in one step. A plain INSERT fails if the row is already
 * there, so of two requests at once only one gets the lock; a lock older than
 * two minutes belongs to a request that died, and is taken over.
 *
 * @return bool Whether this request now holds the lock.
 */
function brodfest_sync_lock() {
	global $wpdb;

	$insert = static function () use ( $wpdb ) {
		$wpdb->suppress_errors( true );
		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", BRODFEST_LOCK_OPTION, (string) time() ) );
		$wpdb->suppress_errors( false );

		return 1 === $result;
	};

	if ( $insert() ) {
		return true;
	}

	$since = (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BRODFEST_LOCK_OPTION ) );

	if ( $since > 0 && time() - $since > 2 * MINUTE_IN_SECONDS ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", BRODFEST_LOCK_OPTION, (string) $since ) );

		return $insert();
	}

	return false;
}

/**
 * Lets go of the sync lock.
 *
 * @return void
 */
function brodfest_sync_unlock() {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", BRODFEST_LOCK_OPTION ) );
	wp_cache_delete( BRODFEST_LOCK_OPTION, 'options' );
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Swaps one page's blocks with its held-back page's, checking each write.
 *
 * @param string $slug    Owned-page slug.
 * @param int    $page_id The page.
 * @param int    $held_id Its held-back page.
 * @param string $holds   The site the page holds now.
 * @param string $live    The site it should hold.
 * @return array|false What it did, for undoing it (see brodfest_unswap_page()), or false with nothing changed.
 */
function brodfest_swap_page( $slug, $page_id, $held_id, $holds, $live ) {
	$undo = array(
		'slug'         => $slug,
		'page_id'      => $page_id,
		'held_id'      => $held_id,
		'holds'        => $holds,
		'live'         => $live,
		'page_content' => (string) get_post_field( 'post_content', $page_id ),
		'held_content' => (string) get_post_field( 'post_content', $held_id ),
		'held_title'   => (string) get_post_field( 'post_title', $held_id ),
	);

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
		// Nothing is lost: the page's own words are still in $undo.
		brodfest_unswap_page( $undo, $page_ok );

		return false;
	}

	brodfest_bump_gen( $page_id );
	brodfest_bump_gen( $held_id );

	return $undo;
}

/**
 * Puts a page and its held-back page back as they were before a swap.
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
 * Writes some fields of a post and says whether WordPress saved them.
 *
 * @param int   $post_id The post.
 * @param array $fields  Post fields to change.
 * @return bool
 */
function brodfest_write_post( $post_id, $fields ) {
	$result = wp_update_post( wp_slash( array_merge( array( 'ID' => $post_id ), $fields ) ), true );

	return ! is_wp_error( $result ) && (int) $result === (int) $post_id;
}

/**
 * Makes the pages themselves hold the live site's blocks, swapping each with
 * its held-back page when the live site has changed.
 *
 * All or nothing: every page's held-back copy must exist first (a missing one
 * is made again from the starting content and the administrator is told), and
 * if any write fails the pages already swapped are put back and the switch is
 * left unfinished, to be tried again. Until it finishes, pages are still drawn
 * correctly, because each page says which site its own content is for.
 *
 * Idempotent: a page that already holds the live site's blocks is left alone,
 * so running it twice never swaps back. Until a site is switched to the
 * Invite, no visitor, REST request or search engine can see its copy: it sits
 * in a private post.
 *
 * @return bool Whether the pages now hold the live site's blocks.
 */
function brodfest_sync_live_content() {
	if ( ! brodfest_sync_lock() ) {
		return false;
	}

	// What the administrator is told is about this run only.
	delete_option( BRODFEST_NOTICE_OPTION );

	$live = brodfest_live_site();
	$ids  = brodfest_page_ids();
	$done = array();
	$ok   = true;

	try {
		// Every page has to have its held-back copy before anything moves.
		$recreated = array();

		foreach ( $ids as $slug => $page_id ) {
			if ( '' !== (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true ) && 0 === brodfest_held_id( $page_id ) ) {
				$recreated[] = $slug;
			}

			brodfest_seed_page( $slug, $page_id );
		}

		if ( array() !== $recreated ) {
			update_option( BRODFEST_NOTICE_OPTION, array( 'recreated' => $recreated ), false );
		}

		foreach ( $ids as $slug => $page_id ) {
			$holds   = (string) get_post_meta( $page_id, BRODFEST_CONTENT_SITE_META, true );
			$held_id = brodfest_held_id( $page_id );

			if ( '' === $holds || $holds === $live ) {
				continue;
			}

			$swap = $held_id > 0 ? brodfest_swap_page( $slug, $page_id, $held_id, $holds, $live ) : false;

			if ( ! $swap ) {
				// Put back the pages already swapped, newest first.
				foreach ( array_reverse( $done ) as $undo ) {
					brodfest_unswap_page( $undo );
				}

				update_option(
					BRODFEST_NOTICE_OPTION,
					array(
						'failed'    => $slug,
						'recreated' => $recreated,
					),
					false
				);
				$ok = false;
				break;
			}

			$done[] = $swap;
		}
	} finally {
		brodfest_sync_unlock();
	}

	if ( $ok ) {
		update_option( BRODFEST_SYNCED_OPTION, $live );
	}

	return $ok;
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
	if ( get_option( BRODFEST_SYNCED_OPTION ) === brodfest_live_site() || false !== get_transient( 'brodfest_sync_retry' ) ) {
		return;
	}

	if ( ! brodfest_sync_live_content() ) {
		set_transient( 'brodfest_sync_retry', 1, 5 * MINUTE_IN_SECONDS );
	}
}
add_action( 'init', 'brodfest_sync_if_needed', 30 );

/**
 * Creates any missing owned pages, stamps them, gives each its section
 * blocks, and (on activation) makes Home the front page.
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
			brodfest_seed_page( $slug, $existing );
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
		brodfest_seed_page( $slug, (int) $post_id );
	}

	update_option( 'brodfest_page_ids', $ids );

	if ( $set_front_page && isset( $ids['home'] ) && (int) $ids['home'] > 0 ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $ids['home'] );
	}

	brodfest_sync_live_content();
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
