<?php
/**
 * Keeps the held-back pages private, and keeps an editor's save, or a restored
 * revision, from landing in the wrong site's blocks.
 *
 * A page holds one site's blocks at a time and swaps with its held-back page
 * when the live site is switched (see includes/pages.php). Three things could
 * put the wrong words in the wrong place, and are stopped here:
 *
 * - a held-back page being published, which would expose the other site's copy
 *   through the REST API;
 * - an editor tab left open across a switch saving over what is now the other
 *   site's blocks;
 * - an old revision, from before a switch, being restored.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A held-back page is only ever private (or in the bin), whatever Quick Edit,
 * the REST API or an import asks for. A page that is published is exposed by
 * the REST API even though it has no address of its own.
 *
 * @param array $data Post data about to be saved.
 * @return array
 */
function brodfest_hold_private( $data ) {
	if ( BRODFEST_HOLD_TYPE === ( $data['post_type'] ?? '' ) && ! in_array( $data['post_status'] ?? '', array( 'private', 'trash', 'auto-draft' ), true ) ) {
		$data['post_status']   = 'private';
		$data['post_password'] = '';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'brodfest_hold_private' );

/**
 * How many times a page, or its held-back page, has been swapped.
 *
 * @param int $post_id Page or held-back page.
 * @return int
 */
function brodfest_gen( $post_id ) {
	return (int) get_post_meta( (int) $post_id, BRODFEST_GEN_META, true );
}

/**
 * Whether a post is one of the owned pages or a held-back page.
 *
 * @param int $post_id Post.
 * @return bool
 */
function brodfest_is_swappable( $post_id ) {
	return BRODFEST_HOLD_TYPE === get_post_type( $post_id ) || brodfest_page_is_ours( $post_id );
}

/**
 * Shows the swap count on the REST API, for people who can edit the page, so
 * the editor (and the tests) can send it back with a save.
 *
 * @return void
 */
function brodfest_register_gen_field() {
	register_rest_field(
		array( 'page', BRODFEST_HOLD_TYPE ),
		'brodfest_swap_count',
		array(
			'get_callback' => static function ( $post ) {
				return current_user_can( 'edit_post', (int) $post['id'] ) && brodfest_is_swappable( (int) $post['id'] ) ? brodfest_gen( (int) $post['id'] ) : null;
			},
			'schema'       => array(
				'description' => __( 'How many times the live site has been switched since this page was made.', 'blueworx-client-brodfest' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'edit' ),
				'readonly'    => true,
			),
		)
	);
}
add_action( 'rest_api_init', 'brodfest_register_gen_field' );

/**
 * Takes the swap lock for the length of a save to a page or held-back page, and
 * refuses a save from an editor tab that was opened before the live site was
 * switched.
 *
 * Holding the lock keeps the save from overlapping a switch (a switch waits for
 * it, and a save waits, briefly, for a switch), and the swap count is read afresh
 * and checked only once the lock is held, so a count that changes while the
 * request waits cannot slip through. After a switch the page holds the other site's blocks,
 * and the old tab would overwrite them. The editor sends the swap count it
 * opened with (see blocks/_shared/guard.js); a client that sends none takes the
 * lock but is not checked.
 *
 * The lock is let go in brodfest_release_save_lock(), when the response is ready.
 *
 * @param mixed           $result  Response so far; null to carry on.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request The request.
 * @return mixed
 */
function brodfest_refuse_stale_save( $result, $server, $request ) {
	unset( $server );

	if ( null !== $result || in_array( $request->get_method(), array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
		return $result;
	}

	if ( ! preg_match( '#^/wp/v2/(?:pages|' . preg_quote( 'brodfest-held-pages', '#' ) . ')/(\d+)(?:/autosaves)?$#', $request->get_route(), $m ) || ! brodfest_is_swappable( (int) $m[1] ) ) {
		return $result;
	}

	if ( ! brodfest_sync_lock( 5 ) ) {
		return new WP_Error(
			'brodfest_switch_running',
			__( 'The live site is being switched right now. Nothing was saved: wait a moment, then save again.', 'blueworx-client-brodfest' ),
			array( 'status' => 409 )
		);
	}

	$sent = $request->get_header( 'x_brodfest_swap_count' );

	if ( null !== $sent && brodfest_gen( (int) $m[1] ) !== (int) $sent ) {
		brodfest_sync_unlock();

		return new WP_Error(
			'brodfest_site_switched',
			__( 'The live site was switched while this page was open, so it now holds different blocks. Nothing was saved: reload the page, then make the change again.', 'blueworx-client-brodfest' ),
			array( 'status' => 409 )
		);
	}

	return $result;
}
add_filter( 'rest_pre_dispatch', 'brodfest_refuse_stale_save', 10, 3 );

/**
 * Lets go of the lock a save took, once its response is ready.
 *
 * @param mixed $response The response.
 * @return mixed
 */
function brodfest_release_save_lock( $response ) {
	brodfest_sync_unlock();

	return $response;
}
add_filter( 'rest_post_dispatch', 'brodfest_release_save_lock', 100 );

/**
 * Gives the block editor the swap count of the page it opened.
 *
 * @return void
 */
function brodfest_enqueue_guard() {
	$post = get_post();

	if ( ! $post || ! brodfest_is_swappable( $post->ID ) ) {
		return;
	}

	wp_enqueue_script( 'brodfest-editor-guard', BRODFEST_URL . 'blocks/_shared/guard.js', array( 'wp-api-fetch' ), BRODFEST_VERSION, true );
	wp_add_inline_script(
		'brodfest-editor-guard',
		'window.brodfestGuard = ' . wp_json_encode(
			array(
				'id'  => (int) $post->ID,
				'gen' => brodfest_gen( $post->ID ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'brodfest_enqueue_guard' );

/**
 * Tags each revision with the site its blocks are for, as the page says at the
 * moment the revision is made.
 *
 * @param int $revision_id The revision.
 * @return void
 */
function brodfest_tag_revision( $revision_id ) {
	$parent = (int) wp_get_post_parent_id( $revision_id );
	$site   = $parent > 0 && brodfest_is_swappable( $parent ) ? (string) get_post_meta( $parent, BRODFEST_CONTENT_SITE_META, true ) : '';

	if ( '' !== $site ) {
		// Not update_post_meta(), which files a revision's meta under its parent.
		update_metadata( 'post', $revision_id, BRODFEST_CONTENT_SITE_META, $site );
	}
}
add_action( '_wp_put_post_revision', 'brodfest_tag_revision' );

/**
 * Tags an autosave again when WordPress writes over it: each person has one per
 * page, and it is used again for whichever site the page holds by then.
 *
 * @param int $revision_id The revision or autosave.
 * @return void
 */
function brodfest_tag_autosave( $revision_id ) {
	if ( wp_is_post_autosave( $revision_id ) ) {
		brodfest_tag_revision( $revision_id );
	}
}
add_action( 'save_post_revision', 'brodfest_tag_autosave' );

/**
 * Guards the restoring of a revision: takes the swap lock, and refuses one made
 * when the page held the other site's blocks, and one with no tag: it was made
 * before the plugin kept track of which site a revision is for, so it cannot be
 * known to be safe. Every revision made since is tagged, the first one (the
 * starting blocks) included, because a page is marked with its site before its
 * blocks are written.
 *
 * The lock is kept until the request ends, so a switch cannot come between this
 * check and WordPress writing the revision.
 *
 * @param int $revision_id The revision.
 * @return void
 */
function brodfest_guard_restore( $revision_id ) {
	$parent = (int) wp_get_post_parent_id( $revision_id );

	if ( $parent <= 0 || ! brodfest_is_swappable( $parent ) ) {
		return;
	}

	$refuse = static function ( $message ) {
		brodfest_sync_unlock();
		wp_die(
			esc_html( $message ),
			'',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	};

	if ( ! brodfest_sync_lock( 5 ) ) {
		$refuse( __( 'The live site is being switched right now, so the revision was not restored. Wait a moment, then try again.', 'blueworx-client-brodfest' ) );
	}

	if ( get_post_meta( $parent, BRODFEST_CONTENT_SITE_META, true ) !== get_post_meta( $revision_id, BRODFEST_CONTENT_SITE_META, true ) ) {
		$refuse( __( 'That revision cannot be restored here: it is from the other site (the live site has been switched since), or from before revisions were tagged with their site. Restoring it could put the wrong blocks on this page. Open the page it belongs to, or the held-back page, to restore it there.', 'blueworx-client-brodfest' ) );
	}
}

/**
 * Guards a restore from the revisions screen.
 *
 * @return void
 */
function brodfest_guard_screen_restore() {
	// Only reading which revision is being asked for; WordPress checks the nonce and the rights.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['action'], $_GET['revision'] ) && 'restore' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		brodfest_guard_restore( absint( wp_unslash( $_GET['revision'] ) ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
add_action( 'load-revision.php', 'brodfest_guard_screen_restore' );

/**
 * Guards a restore asked for over XML-RPC.
 *
 * @param string $method The method called.
 * @param array  $args   Its arguments; the fourth is the revision.
 * @return void
 */
function brodfest_guard_xmlrpc_restore( $method, $args = array() ) {
	if ( 'wp.restoreRevision' === $method && isset( $args[3] ) ) {
		brodfest_guard_restore( (int) $args[3] );
	}
}
add_action( 'xmlrpc_call', 'brodfest_guard_xmlrpc_restore', 10, 2 );

/**
 * Tells an administrator when a switch did not finish, or had to make a
 * held-back page again from the starting content.
 *
 * @return void
 */
function brodfest_sync_notice() {
	$notice = get_option( BRODFEST_NOTICE_OPTION );

	if ( empty( $notice ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$name = static function ( $slug ) {
		return brodfest_pages()[ $slug ]['title'] ?? $slug;
	};

	if ( ! empty( $notice['failed'] ) ) {
		wp_admin_notice(
			sprintf(
				/* translators: %s: page name. */
				esc_html__( 'The live site could not be switched: the page “%s” could not be saved, so the switch was stopped and the website is still on the site it was on. The switch will be tried again shortly. If this keeps showing, check that the page and its held-back page can be edited.', 'blueworx-client-brodfest' ),
				esc_html( $name( $notice['failed'] ) )
			),
			array(
				'type'               => 'error',
				'additional_classes' => array( 'inline' ),
			)
		);
	}

	if ( ! empty( $notice['stopped'] ) ) {
		wp_admin_notice(
			'tables' === $notice['stopped']
				? esc_html__( 'The live site could not be switched: this website’s database tables are not InnoDB, so the switch could not be made safely in one step. Nothing was changed. Ask your host to convert them.', 'blueworx-client-brodfest' )
				: esc_html__( 'The live site could not be switched: the pages could not be saved. Nothing was changed, and the website is still on the site it was on. The switch will be tried again shortly.', 'blueworx-client-brodfest' ),
			array(
				'type'               => 'error',
				'additional_classes' => array( 'inline' ),
			)
		);
	}

	if ( ! empty( $notice['recreated'] ) ) {
		wp_admin_notice(
			sprintf(
				/* translators: %s: page names. */
				esc_html__( 'These held-back pages had been deleted, so they were made again from the starting content: %s. Restore them from the bin or from a backup if they held changes you need.', 'blueworx-client-brodfest' ),
				esc_html( implode( ', ', array_map( $name, (array) $notice['recreated'] ) ) )
			),
			array(
				'type'               => 'warning',
				'dismissible'        => true,
				'additional_classes' => array( 'inline' ),
			)
		);
	}
}
add_action( 'admin_notices', 'brodfest_sync_notice' );
