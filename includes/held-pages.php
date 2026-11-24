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
 * Counts one more swap.
 *
 * @param int $post_id Page or held-back page.
 * @return void
 */
function brodfest_bump_gen( $post_id ) {
	update_post_meta( (int) $post_id, BRODFEST_GEN_META, brodfest_gen( $post_id ) + 1 );
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
 * Refuses a save from an editor tab that was opened before the live site was
 * switched: the page now holds the other site's blocks, and the tab would
 * overwrite them with the old ones. The editor sends the swap count it opened
 * with (see blocks/_shared/guard.js); a client that sends none is not checked.
 *
 * @param mixed           $result  Response so far; null to carry on.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request The request.
 * @return mixed
 */
function brodfest_refuse_stale_save( $result, $server, $request ) {
	unset( $server );

	if ( null !== $result || 'GET' === $request->get_method() || null === $request->get_header( 'x_brodfest_swap_count' ) ) {
		return $result;
	}

	if ( ! preg_match( '#^/wp/v2/(?:pages|' . preg_quote( 'brodfest-held-pages', '#' ) . ')/(\d+)(?:/autosaves)?$#', $request->get_route(), $m ) || ! brodfest_is_swappable( (int) $m[1] ) ) {
		return $result;
	}

	if ( (int) $request->get_header( 'x_brodfest_swap_count' ) !== brodfest_gen( (int) $m[1] ) ) {
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
 * Refuses to restore a revision made when the page held the other site's
 * blocks. Revisions from before they were tagged are let through.
 *
 * @return void
 */
function brodfest_refuse_other_site_revision() {
	// Only reading which revision is being asked for; WordPress checks the nonce and the rights.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['action'], $_GET['revision'] ) || 'restore' !== sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	$revision_id = absint( wp_unslash( $_GET['revision'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$parent = (int) wp_get_post_parent_id( $revision_id );
	$tag    = (string) get_post_meta( $revision_id, BRODFEST_CONTENT_SITE_META, true );

	if ( '' !== $tag && $parent > 0 && brodfest_is_swappable( $parent ) && (string) get_post_meta( $parent, BRODFEST_CONTENT_SITE_META, true ) !== $tag ) {
		wp_die(
			esc_html__( 'That revision is from the other site: the live site has been switched since, and restoring it would put the other site’s blocks on this page. Open the held-back page to restore it there.', 'blueworx-client-brodfest' ),
			'',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}
}
add_action( 'load-revision.php', 'brodfest_refuse_other_site_revision' );

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
				esc_html__( 'The live site could not be switched: the page “%s” could not be saved. Every page has been put back as it was, and the switch will be tried again shortly. If this keeps showing, check that the page and its held-back page can be edited.', 'blueworx-client-brodfest' ),
				esc_html( $name( $notice['failed'] ) )
			),
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
