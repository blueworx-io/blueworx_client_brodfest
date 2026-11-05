<?php
/**
 * RSVP replies: the form handler and where replies are kept.
 *
 * Each reply is a private "RSVP" record, listed under RSVPs in the admin with
 * who is coming, whether they bring a guest and how to reach them. The site's
 * admin email is told about each one.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_RSVP_TYPE   = 'brodfest_rsvp';
const BRODFEST_RSVP_ACTION = 'brodfest_rsvp';

/**
 * The dietary choices offered in the form.
 *
 * @return array<string, string>
 */
function brodfest_rsvp_dietary_options() {
	return array(
		'none'        => __( 'No requirements', 'blueworx-client-brodfest' ),
		'vegetarian'  => __( 'Vegetarian', 'blueworx-client-brodfest' ),
		'vegan'       => __( 'Vegan', 'blueworx-client-brodfest' ),
		'pescatarian' => __( 'Pescatarian', 'blueworx-client-brodfest' ),
		'gluten-free' => __( 'Gluten free', 'blueworx-client-brodfest' ),
		'other'       => __( 'Other (tell us under allergies)', 'blueworx-client-brodfest' ),
		'dairy-free'  => __( 'Dairy Free', 'blueworx-client-brodfest' ),
	);
}

/**
 * The country codes a phone number can start with, +44 first as most guests'
 * and the rest in number order. One entry per code, as the field shows only
 * the code.
 *
 * @return string[]
 */
function brodfest_rsvp_dial_codes() {
	$codes = array( 1, 7, 20, 27, 30, 31, 32, 33, 34, 36, 39, 40, 41, 43, 45, 46, 47, 48, 49, 51, 52, 54, 55, 56, 57, 60, 61, 62, 63, 64, 65, 66, 81, 82, 84, 86, 90, 91, 92, 94, 212, 216, 233, 234, 254, 255, 256, 260, 263, 351, 352, 353, 354, 356, 357, 358, 359, 370, 371, 372, 376, 377, 380, 381, 385, 386, 420, 421, 423, 852, 886, 961, 962, 965, 966, 968, 971, 972, 973, 974 );

	return array_merge( array( '+44' ), array_map( static fn( $code ) => '+' . $code, $codes ) );
}

/**
 * Registers the RSVP record type: private, admin-only, read-only in spirit.
 *
 * @return void
 */
function brodfest_register_rsvp_type() {
	register_post_type(
		BRODFEST_RSVP_TYPE,
		array(
			'labels'          => array(
				'name'          => __( 'RSVPs', 'blueworx-client-brodfest' ),
				'singular_name' => __( 'RSVP', 'blueworx-client-brodfest' ),
				'all_items'     => __( 'All RSVPs', 'blueworx-client-brodfest' ),
				'edit_item'     => __( 'RSVP', 'blueworx-client-brodfest' ),
				'search_items'  => __( 'Search RSVPs', 'blueworx-client-brodfest' ),
				'not_found'     => __( 'No RSVPs yet.', 'blueworx-client-brodfest' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title', 'editor' ),
			// Replies hold guests' phones, emails and allergies, so only
			// administrators see them — not every editor on the site.
			'capability_type' => 'post',
			'capabilities'    => array(
				'create_posts'         => 'do_not_allow',
				'edit_posts'           => 'manage_options',
				'edit_others_posts'    => 'manage_options',
				'edit_private_posts'   => 'manage_options',
				'edit_published_posts' => 'manage_options',
				'read_private_posts'   => 'manage_options',
				'delete_posts'         => 'manage_options',
				'delete_others_posts'  => 'manage_options',
				'delete_private_posts' => 'manage_options',
				'publish_posts'        => 'manage_options',
			),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'brodfest_register_rsvp_type' );

/**
 * The person fields of one submission, cleaned.
 *
 * @param array  $source The posted data.
 * @param string $prefix '' or 'guest_'.
 * @return array<string, string>
 */
function brodfest_rsvp_person( $source, $prefix ) {
	$get     = static function ( $key ) use ( $source, $prefix ) {
		return isset( $source[ $prefix . $key ] ) ? sanitize_text_field( wp_unslash( $source[ $prefix . $key ] ) ) : '';
	};
	$dietary = $get( 'dietary' );

	return array(
		'name'      => mb_substr( $get( 'name' ), 0, 120 ),
		'phone'     => trim( preg_replace( '/[^0-9+ ]/', '', $get( 'phone_prefix' ) . ' ' . $get( 'phone' ) ) ),
		'email'     => sanitize_email( $get( 'email' ) ),
		'dietary'   => isset( brodfest_rsvp_dietary_options()[ $dietary ] ) ? $dietary : '',
		'allergies' => mb_substr( $get( 'allergies' ), 0, 300 ),
	);
}

/**
 * Turns a posted form into a reply, or null when it is incomplete.
 * Pure, so it can be tested without WordPress saving anything.
 *
 * @param array $source The posted data.
 * @return array|null
 */
function brodfest_rsvp_from_post( $source ) {
	$guest_yes = isset( $source['with_guest'] ) && 'yes' === $source['with_guest'];
	$reply     = array(
		'person'        => brodfest_rsvp_person( $source, '' ),
		'attending'     => isset( $source['attending'] ) && in_array( $source['attending'], array( 'yes', 'no' ), true ) ? $source['attending'] : '',
		'with_guest'    => $guest_yes ? 'yes' : 'no',
		'guest'         => $guest_yes ? brodfest_rsvp_person( $source, 'guest_' ) : null,
		'accessibility' => ! empty( $source['accessibility'] ),
	);

	if ( '' === $reply['person']['name'] || ! is_email( $reply['person']['email'] ) || '' === $reply['attending'] ) {
		return null;
	}

	return $reply;
}

/**
 * The reply written out as plain lines, for the record and the email.
 *
 * @param array $reply A reply from brodfest_rsvp_from_post().
 * @return string
 */
function brodfest_rsvp_summary( $reply ) {
	$diet  = brodfest_rsvp_dietary_options();
	$lines = array();
	$add   = static function ( $who, $p ) use ( &$lines, $diet ) {
		$lines[] = $who . ': ' . $p['name'];
		$lines[] = 'Email: ' . $p['email'];
		$lines[] = 'Phone: ' . $p['phone'];
		$lines[] = 'Dietary: ' . ( '' === $p['dietary'] ? '—' : $diet[ $p['dietary'] ] );
		$lines[] = 'Allergies: ' . ( '' === $p['allergies'] ? '—' : $p['allergies'] );
	};

	$lines[] = 'Attending: ' . ucfirst( $reply['attending'] );
	$lines[] = 'With a guest: ' . ucfirst( $reply['with_guest'] );
	$lines[] = 'Accessibility requirements: ' . ( $reply['accessibility'] ? 'Yes' : 'No' );
	$lines[] = '';
	$add( 'Name', $reply['person'] );

	if ( $reply['guest'] ) {
		$lines[] = '';
		$add( 'Guest', $reply['guest'] );
	}

	return implode( "\n", $lines );
}

/**
 * Whether this visitor has sent too many replies in the last hour. Counts the
 * attempt as it checks. The address is hashed, never stored.
 *
 * @return bool
 */
function brodfest_rsvp_rate_limited() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'brodfest_rsvp_' . substr( wp_hash( $ip ), 0, 20 );
	$count = (int) get_transient( $key );

	/**
	 * Filters how many replies one address may send in an hour.
	 *
	 * @param int $limit Replies per hour.
	 */
	if ( $count >= (int) apply_filters( 'brodfest_rsvp_hourly_limit', 5 ) ) {
		return true;
	}

	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	return false;
}

/**
 * The RSVP page is never cached: its form carries a nonce, and a cached copy
 * outlives it, after which every genuine reply would be turned away.
 *
 * @return void
 */
function brodfest_rsvp_no_cache() {
	if ( brodfest_is_owned_request() && 'rsvp' === brodfest_page_slug( get_queried_object_id() ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'brodfest_rsvp_no_cache', 1 );

/**
 * Handles the RSVP form: checks it, saves it, emails the site, sends the
 * visitor back to the RSVP page with a message.
 *
 * The form posts to the RSVP page itself rather than to admin-post.php: the
 * Enhance theme closes wp-admin to signed-out visitors, which is everyone
 * replying to an invitation.
 *
 * @return void
 */
function brodfest_handle_rsvp() {
	// The nonce is checked below, once we know this is our form.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['brodfest_action'] ) || BRODFEST_RSVP_ACTION !== $_POST['brodfest_action'] ) {
		return;
	}

	$back = brodfest_page_url( 'rsvp' );

	if ( ! isset( $_POST['brodfest_rsvp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brodfest_rsvp_nonce'] ) ), BRODFEST_RSVP_ACTION ) ) {
		wp_safe_redirect( add_query_arg( 'rsvp', 'expired', $back ) );
		exit;
	}

	// Filled in only by bots, as is a flood from one address. Pretend it
	// worked so they learn nothing.
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'rsvp', 'sent', $back ) );
		exit;
	}

	$reply = brodfest_rsvp_from_post( $_POST );

	if ( null === $reply ) {
		wp_safe_redirect( add_query_arg( 'rsvp', 'error', $back ) );
		exit;
	}

	// Only complete replies count towards the limit, so a guest fixing a
	// mistake is never locked out.
	if ( brodfest_rsvp_rate_limited() ) {
		wp_safe_redirect( add_query_arg( 'rsvp', 'sent', $back ) );
		exit;
	}

	$summary = brodfest_rsvp_summary( $reply );
	$post_id = wp_insert_post(
		array(
			'post_type'    => BRODFEST_RSVP_TYPE,
			'post_status'  => 'private',
			'post_title'   => $reply['person']['name'],
			'post_content' => $summary,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'rsvp', 'error', $back ) );
		exit;
	}

	update_post_meta( $post_id, '_brodfest_attending', $reply['attending'] );
	update_post_meta( $post_id, '_brodfest_with_guest', $reply['with_guest'] );
	update_post_meta( $post_id, '_brodfest_email', $reply['person']['email'] );
	update_post_meta( $post_id, '_brodfest_reply', $reply );

	/**
	 * Filters who is told about each RSVP. Return '' to send nothing.
	 *
	 * @param string $to Email address.
	 */
	$to = (string) apply_filters( 'brodfest_rsvp_notify', get_option( 'admin_email' ) );

	if ( '' !== $to ) {
		/* translators: %s: the invitee's name. */
		wp_mail( $to, sprintf( __( 'New RSVP: %s', 'blueworx-client-brodfest' ), $reply['person']['name'] ), $summary );
	}

	wp_safe_redirect( add_query_arg( 'rsvp', 'sent', $back ) );
	exit;
}
add_action( 'template_redirect', 'brodfest_handle_rsvp', 5 );

/**
 * The RSVP list's columns: who is coming and with whom, at a glance.
 *
 * @param array<string, string> $columns WordPress's columns.
 * @return array<string, string>
 */
function brodfest_rsvp_columns( $columns ) {
	return array(
		'cb'                  => $columns['cb'] ?? '',
		'title'               => __( 'Name', 'blueworx-client-brodfest' ),
		'brodfest_attending'  => __( 'Attending', 'blueworx-client-brodfest' ),
		'brodfest_with_guest' => __( 'Guest', 'blueworx-client-brodfest' ),
		'brodfest_email'      => __( 'Email', 'blueworx-client-brodfest' ),
		'date'                => __( 'Received', 'blueworx-client-brodfest' ),
	);
}
add_filter( 'manage_' . BRODFEST_RSVP_TYPE . '_posts_columns', 'brodfest_rsvp_columns' );

/**
 * Prints one RSVP column cell.
 *
 * @param string $column  Column key.
 * @param int    $post_id The RSVP.
 * @return void
 */
function brodfest_rsvp_column( $column, $post_id ) {
	$keys = array(
		'brodfest_attending'  => '_brodfest_attending',
		'brodfest_with_guest' => '_brodfest_with_guest',
		'brodfest_email'      => '_brodfest_email',
	);

	if ( ! isset( $keys[ $column ] ) ) {
		return;
	}

	$value = (string) get_post_meta( $post_id, $keys[ $column ], true );

	// Yes / No read better capitalised; an email address must stay as typed.
	echo esc_html( 'brodfest_email' === $column ? $value : ucfirst( $value ) );
}
add_action( 'manage_' . BRODFEST_RSVP_TYPE . '_posts_custom_column', 'brodfest_rsvp_column', 10, 2 );
