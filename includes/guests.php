<?php
/**
 * Guests: RSVP replies kept as WordPress users.
 *
 * Each reply is a person in the Users list rather than a record in a list of
 * its own, so there is one place to look (Luke, 9 Nov 2026). Whoever replies
 * is a "Primary"; the plus-one they bring is a person of their own, a
 * "Guest", and the two are linked. Every answer from the form is kept on the
 * person and shown as a column in the Users list and on their profile.
 *
 * These are records, not sign-ins. The role has no capabilities, no password
 * is ever sent, signing in and password resets are refused, and a reply never
 * touches an account that is not a guest: the form is open to anyone.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_GUEST_ROLE = 'brodfest_guest';

/**
 * What is kept on each guest, with its label: every answer from the form.
 * The person's profile shows them all; the Users list shows the first three
 * and one Email column (Luke, 9 Nov 2026).
 *
 * @return array<string, string> Meta key => label.
 */
function brodfest_guest_fields() {
	return array(
		'brodfest_type'          => __( 'Type', 'blueworx-client-brodfest' ),
		'brodfest_attending'     => __( 'Attending', 'blueworx-client-brodfest' ),
		'brodfest_linked'        => __( 'Linked to', 'blueworx-client-brodfest' ),
		'brodfest_email'         => __( 'Email', 'blueworx-client-brodfest' ),
		'brodfest_phone'         => __( 'Phone', 'blueworx-client-brodfest' ),
		'brodfest_dietary'       => __( 'Dietary', 'blueworx-client-brodfest' ),
		'brodfest_allergies'     => __( 'Allergies', 'blueworx-client-brodfest' ),
		'brodfest_accessibility' => __( 'Accessibility', 'blueworx-client-brodfest' ),
		'brodfest_replied'       => __( 'Replied', 'blueworx-client-brodfest' ),
	);
}

/**
 * Adds the guest role, with no capabilities at all.
 *
 * @return void
 */
function brodfest_register_guest_role() {
	if ( null === get_role( BRODFEST_GUEST_ROLE ) ) {
		add_role( BRODFEST_GUEST_ROLE, __( 'Brodfest guest', 'blueworx-client-brodfest' ), array() );
	}
}
add_action( 'init', 'brodfest_register_guest_role' );

/**
 * Whether a user is a guest and nothing else. Anything a reply changes or
 * removes is checked against this first.
 *
 * @param WP_User|false|null $user The user.
 * @return bool
 */
function brodfest_is_guest( $user ) {
	return $user instanceof WP_User && array( BRODFEST_GUEST_ROLE ) === array_values( $user->roles );
}

/**
 * The primary guest who replied from an email address, or 0.
 *
 * @param string $email The reply's email.
 * @return int User id.
 */
function brodfest_guest_find_primary( $email ) {
	$found = get_users(
		array(
			'role'       => BRODFEST_GUEST_ROLE,
			'number'     => 1,
			'fields'     => 'ID',
			'orderby'    => 'ID',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One lookup per reply.
			'meta_query' => array(
				array(
					'key'   => 'brodfest_email',
					'value' => strtolower( $email ),
				),
				array(
					'key'   => 'brodfest_type',
					'value' => 'primary',
				),
			),
		)
	);

	return $found ? (int) $found[0] : 0;
}

/**
 * Creates or updates one guest from a person's answers.
 *
 * The person's email becomes the account's email only when no other account
 * has it; it is always kept as the reply email. So a plus-one sharing an
 * address, or a reply sent with somebody else's, still gets a record.
 *
 * @param array  $person  From brodfest_rsvp_person().
 * @param array  $answers Meta key => value, as in brodfest_guest_fields().
 * @param int    $user_id The guest to update, or 0 for a new one.
 * @param string $when    When the reply was sent, as a MySQL date in UTC.
 * @return int|WP_Error User id.
 */
function brodfest_guest_save( $person, $answers, $user_id, $when ) {
	$existing = $user_id ? get_userdata( $user_id ) : false;

	if ( $user_id && ! brodfest_is_guest( $existing ) ) {
		return new WP_Error( 'brodfest_not_a_guest', 'Only a guest can be changed by a reply.' );
	}

	$email = strtolower( $person['email'] );
	$owner = '' === $email ? 0 : (int) email_exists( $email );
	$parts = explode( ' ', $person['name'], 2 );
	$data  = array(
		'display_name' => $person['name'],
		'nickname'     => $person['name'],
		'first_name'   => $parts[0],
		'last_name'    => $parts[1] ?? '',
		'user_email'   => ( 0 === $owner || $owner === (int) $user_id ) ? $email : ( $existing ? $existing->user_email : '' ),
		'role'         => BRODFEST_GUEST_ROLE,
	);

	// A guest is never told about their record: nothing here is theirs to use.
	add_filter( 'send_email_change_email', '__return_false' );
	add_filter( 'send_password_change_email', '__return_false' );

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$saved      = wp_update_user( $data );
	} else {
		$data['user_login']      = 'brodfest-' . strtolower( wp_generate_password( 14, false ) );
		$data['user_pass']       = wp_generate_password( 40, true, true );
		$data['user_registered'] = $when;
		$saved                   = wp_insert_user( $data );
	}

	remove_filter( 'send_email_change_email', '__return_false' );
	remove_filter( 'send_password_change_email', '__return_false' );

	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	brodfest_guest_login_from_email( (int) $saved );

	$answers['brodfest_email']     = $email;
	$answers['brodfest_phone']     = $person['phone'];
	$answers['brodfest_dietary']   = $person['dietary'];
	$answers['brodfest_allergies'] = $person['allergies'];
	$answers['brodfest_replied']   = $when;

	foreach ( $answers as $key => $value ) {
		update_user_meta( $saved, $key, $value );
	}

	return (int) $saved;
}

/**
 * Makes a guest's username their account email (Luke, 11 Nov 2026).
 *
 * Only for an email the account itself holds, so somebody else's address is
 * never taken, and never over a username already in use. A guest with no
 * email of their own keeps, or goes back to, a made-up one.
 *
 * WordPress has no way to rename a user, so the one column is written
 * directly. Guests cannot sign in, so nothing depends on the old name.
 *
 * @param int $user_id The guest.
 * @return void
 */
function brodfest_guest_login_from_email( $user_id ) {
	global $wpdb;

	$user = get_userdata( $user_id );

	if ( ! brodfest_is_guest( $user ) ) {
		return;
	}

	// As WordPress itself would store it for a new user.
	$login = sanitize_user( $user->user_email, true );

	if ( '' === $login && false !== strpos( $user->user_login, '@' ) ) {
		$login = 'brodfest-' . strtolower( wp_generate_password( 14, false ) );
	}

	if ( '' === $login || $login === $user->user_login || strlen( $login ) > 60 || username_exists( $login ) ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No API renames a user; the cache is cleared below.
	$wpdb->update( $wpdb->users, array( 'user_login' => $login ), array( 'ID' => $user->ID ) );
	clean_user_cache( $user );
}

/**
 * Renames the guests made before usernames were emails. Once.
 *
 * @return void
 */
function brodfest_guests_rename_to_email() {
	if ( '1' === get_option( 'brodfest_guests_renamed_to_email' ) ) {
		return;
	}

	update_option( 'brodfest_guests_renamed_to_email', '1', false );

	$guests = get_users(
		array(
			'role'   => BRODFEST_GUEST_ROLE,
			'fields' => 'ID',
		)
	);

	foreach ( $guests as $guest_id ) {
		brodfest_guest_login_from_email( (int) $guest_id );
	}
}
add_action( 'init', 'brodfest_guests_rename_to_email', 30 );

/**
 * The plus-one linked to a primary guest, or 0. Only ever a guest who points
 * back at that primary, so nothing else can be changed or removed through it.
 *
 * @param int $primary_id The primary guest.
 * @return int User id.
 */
function brodfest_guest_plus_one( $primary_id ) {
	$guest_id = (int) get_user_meta( $primary_id, 'brodfest_linked', true );
	$guest    = $guest_id ? get_userdata( $guest_id ) : false;

	if ( ! brodfest_is_guest( $guest ) || 'guest' !== get_user_meta( $guest_id, 'brodfest_type', true ) || (int) get_user_meta( $guest_id, 'brodfest_linked', true ) !== (int) $primary_id ) {
		return 0;
	}

	return $guest_id;
}

/**
 * Keeps a reply as people: the one who replied, and their plus-one.
 *
 * A second reply from the same email changes the same person. Their plus-one
 * is changed with them, or removed when no longer brought.
 *
 * @param array  $reply From brodfest_rsvp_from_post().
 * @param string $when  When it was sent, as a MySQL date in UTC. Now, if ''.
 * @return int|WP_Error The primary guest's user id.
 */
function brodfest_guests_store_reply( $reply, $when = '' ) {
	$when          = '' === $when ? gmdate( 'Y-m-d H:i:s' ) : $when;
	$accessibility = $reply['accessibility'] ? 'yes' : 'no';
	$primary_id    = brodfest_guest_save(
		$reply['person'],
		array(
			'brodfest_type'          => 'primary',
			'brodfest_attending'     => $reply['attending'],
			'brodfest_with_guest'    => $reply['with_guest'],
			'brodfest_accessibility' => $accessibility,
		),
		brodfest_guest_find_primary( $reply['person']['email'] ),
		$when
	);

	if ( is_wp_error( $primary_id ) ) {
		return $primary_id;
	}

	$guest_id = brodfest_guest_plus_one( $primary_id );

	if ( empty( $reply['guest'] ) ) {
		if ( $guest_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $guest_id );
		}

		delete_user_meta( $primary_id, 'brodfest_linked' );

		return $primary_id;
	}

	$person = $reply['guest'];

	if ( '' === $person['name'] ) {
		/* translators: %s: the name of the person bringing a guest. */
		$person['name'] = sprintf( __( 'Guest of %s', 'blueworx-client-brodfest' ), $reply['person']['name'] );
	}

	// The accessibility box on the form is ticked once, for the two of them.
	$guest_id = brodfest_guest_save(
		$person,
		array(
			'brodfest_type'          => 'guest',
			'brodfest_attending'     => $reply['attending'],
			'brodfest_accessibility' => $accessibility,
			'brodfest_linked'        => $primary_id,
		),
		$guest_id,
		$when
	);

	if ( ! is_wp_error( $guest_id ) ) {
		update_user_meta( $primary_id, 'brodfest_linked', $guest_id );
	}

	return $primary_id;
}

/**
 * Brings replies kept the old way, as RSVP records, into Users. Once: the
 * old records are left where they were, out of sight, as a copy.
 *
 * @return void
 */
function brodfest_guests_carry_over() {
	if ( '1' === get_option( 'brodfest_guests_carried_over' ) ) {
		return;
	}

	update_option( 'brodfest_guests_carried_over', '1', false );

	$old = get_posts(
		array(
			'post_type'        => BRODFEST_RSVP_TYPE,
			'post_status'      => array( 'private', 'publish', 'draft', 'pending' ),
			'numberposts'      => -1,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);

	foreach ( $old as $post ) {
		$reply = get_post_meta( $post->ID, '_brodfest_reply', true );

		if ( is_array( $reply ) && isset( $reply['person']['name'], $reply['person']['email'], $reply['attending'] ) ) {
			// Older records may lack an answer added to the form later.
			$blank           = array_fill_keys( array( 'name', 'phone', 'email', 'dietary', 'allergies' ), '' );
			$reply          += array(
				'with_guest'    => 'no',
				'guest'         => null,
				'accessibility' => false,
			);
			$reply['person'] = array_merge( $blank, (array) $reply['person'] );
			$reply['guest']  = is_array( $reply['guest'] ) ? array_merge( $blank, $reply['guest'] ) : null;

			brodfest_guests_store_reply( $reply, $post->post_date_gmt );
		}
	}
}
add_action( 'init', 'brodfest_guests_carry_over', 20 );

/**
 * Guests cannot sign in.
 *
 * @param WP_User|WP_Error|null $user Whoever is signing in.
 * @return WP_User|WP_Error|null
 */
function brodfest_guest_refuse_sign_in( $user ) {
	if ( brodfest_is_guest( $user ) ) {
		return new WP_Error( 'brodfest_guest', __( 'This account cannot sign in.', 'blueworx-client-brodfest' ) );
	}

	return $user;
}
add_filter( 'authenticate', 'brodfest_guest_refuse_sign_in', 100 );

/**
 * Nor can they ask for a password.
 *
 * @param bool|WP_Error $allow   Whether a reset is allowed.
 * @param int           $user_id Whose password.
 * @return bool|WP_Error
 */
function brodfest_guest_refuse_reset( $allow, $user_id ) {
	return brodfest_is_guest( get_userdata( $user_id ) ) ? false : $allow;
}
add_filter( 'allow_password_reset', 'brodfest_guest_refuse_reset', 10, 2 );

/**
 * One answer, as the Users list and the profile show it. Plain text.
 *
 * @param string $key     A key from brodfest_guest_fields().
 * @param int    $user_id The user.
 * @return string '—' when there is nothing to show.
 */
function brodfest_guest_answer( $key, $user_id ) {
	$value = (string) get_user_meta( $user_id, $key, true );

	if ( '' === $value || ! brodfest_is_guest( get_userdata( $user_id ) ) ) {
		return '—';
	}

	switch ( $key ) {
		case 'brodfest_type':
			return 'guest' === $value ? __( 'Guest', 'blueworx-client-brodfest' ) : __( 'Primary', 'blueworx-client-brodfest' );
		case 'brodfest_attending':
		case 'brodfest_accessibility':
			return 'yes' === $value ? __( 'Yes', 'blueworx-client-brodfest' ) : __( 'No', 'blueworx-client-brodfest' );
		case 'brodfest_dietary':
			return brodfest_rsvp_dietary_options()[ $value ] ?? '—';
		case 'brodfest_linked':
			$linked = get_userdata( (int) $value );

			return $linked ? $linked->display_name : '—';
		case 'brodfest_replied':
			return get_date_from_gmt( $value, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		default:
			return $value;
	}
}

/**
 * The Users list's columns: who, whether they are coming and with whom. The
 * rest of the answers are on the person's profile, and Posts goes, as nobody
 * here writes any (Luke, 9 Nov 2026).
 *
 * There is one Email column. It takes the place of WordPress's own, which
 * shows only the account's address: a guest who shares an address with
 * someone else has none on their account, only the one they replied with.
 *
 * @param array<string, string> $columns WordPress's columns.
 * @return array<string, string>
 */
function brodfest_guest_columns( $columns ) {
	$fields = brodfest_guest_fields();
	$kept   = array();

	foreach ( $columns as $key => $label ) {
		if ( 'email' === $key ) {
			$kept['brodfest_contact'] = $label;
		} elseif ( 'posts' !== $key ) {
			$kept[ $key ] = $label;
		}
	}

	foreach ( array( 'brodfest_type', 'brodfest_attending', 'brodfest_linked' ) as $key ) {
		$kept[ $key ] = $fields[ $key ];
	}

	return $kept;
}
add_filter( 'manage_users_columns', 'brodfest_guest_columns' );

/**
 * The Email column still sorts by address.
 *
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function brodfest_guest_sortable_columns( $columns ) {
	$columns['brodfest_contact'] = 'email';

	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'brodfest_guest_sortable_columns' );

/**
 * The address to show for a user: the one a guest replied with, or the
 * account's own for anyone else.
 *
 * @param int $user_id The user.
 * @return string
 */
function brodfest_guest_contact_email( $user_id ) {
	$user  = get_userdata( $user_id );
	$reply = brodfest_is_guest( $user ) ? (string) get_user_meta( $user_id, 'brodfest_email', true ) : '';

	return '' !== $reply ? $reply : ( $user ? (string) $user->user_email : '' );
}

/**
 * One cell of those columns. The linked person is a link to their profile.
 *
 * @param string $output  The cell so far.
 * @param string $column  Column key.
 * @param int    $user_id The user.
 * @return string
 */
function brodfest_guest_column( $output, $column, $user_id ) {
	if ( 'brodfest_contact' === $column ) {
		$email = brodfest_guest_contact_email( $user_id );

		return '' === $email ? '—' : '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
	}

	if ( ! isset( brodfest_guest_fields()[ $column ] ) ) {
		return $output;
	}

	$text = esc_html( brodfest_guest_answer( $column, $user_id ) );

	if ( 'brodfest_linked' === $column && '—' !== $text ) {
		return '<a href="' . esc_url( get_edit_user_link( (int) get_user_meta( $user_id, 'brodfest_linked', true ) ) ) . '">' . $text . '</a>';
	}

	return $text;
}
add_filter( 'manage_users_custom_column', 'brodfest_guest_column', 10, 3 );

/**
 * A guest's profile shows every answer from their reply, under WordPress's
 * own fields and in the same form as them. To read, not to edit: the answers
 * are the guest's, and a new reply from them replaces the lot.
 *
 * @param WP_User $user Whose profile it is.
 * @return void
 */
function brodfest_guest_profile( $user ) {
	if ( ! brodfest_is_guest( $user ) || ! current_user_can( 'list_users' ) ) {
		return;
	}
	?>
	<div id="brodfest-rsvp">
		<h2><?php esc_html_e( 'RSVP', 'blueworx-client-brodfest' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php foreach ( brodfest_guest_fields() as $key => $label ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td>
						<?php
						// Escaped as it is built: plain text, or a link to the linked person.
						echo brodfest_guest_column( '', $key, $user->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<?php
}
add_action( 'edit_user_profile', 'brodfest_guest_profile' );
add_action( 'show_user_profile', 'brodfest_guest_profile' );

/**
 * A guest is never the signed-in user, however the sign-in came about. The
 * check above covers a password; this covers anything that sets a session
 * itself, as the Enhance theme's single sign-on does for a matching email.
 *
 * @param int|false $user_id Whoever WordPress worked out is signed in.
 * @return int|false
 */
function brodfest_guest_never_current( $user_id ) {
	return $user_id && brodfest_is_guest( get_userdata( (int) $user_id ) ) ? false : $user_id;
}
add_filter( 'determine_current_user', 'brodfest_guest_never_current', 9999 );

/**
 * Guests have no public page. WordPress gives every user an author address
 * (/?author=12) that shows their name even with nothing written, which would
 * let anyone read the guest list one number at a time.
 *
 * @return void
 */
function brodfest_guest_no_author_page() {
	if ( ! is_author() || ! brodfest_is_guest( get_queried_object() ) ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'brodfest_guest_no_author_page', 0 );

/**
 * Guests are not sent to the Enhance theme's Klaviyo list: they replied to an
 * invitation, they did not sign up to anything.
 *
 * @param string[] $roles Roles the theme leaves out.
 * @return string[]
 */
function brodfest_guest_not_for_klaviyo( $roles ) {
	$roles[] = BRODFEST_GUEST_ROLE;

	return $roles;
}
add_filter( 'blueworx_klaviyo_user_excluded_roles', 'brodfest_guest_not_for_klaviyo' );
