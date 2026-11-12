<?php
/**
 * The guest list as a spreadsheet: one row per reply, the partner on the same
 * row, in exactly the columns and order Mark asked for (11 Nov 2026).
 *
 * Downloaded from a button above the Users list. Columns the form does not
 * ask about stay in, empty, for the client to fill in.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_GUEST_LIST_ACTION = 'brodfest_guest_list';

/**
 * The spreadsheet's columns, in order. Their names are Mark's, word for word.
 *
 * @return string[]
 */
function brodfest_guest_list_columns() {
	return array(
		'Attending? Yes / No',
		'First Name',
		'Last Name',
		'Phone Number',
		'Email',
		'Dietary',
		'Allergy',
		'Accessibility',
		'Attending with Partner? Yes / No',
		'Partner First Name',
		'Partner Last Name',
		'Partner Phone Number',
		'Partner Email',
		'Partner Dietary',
		'Partner Allergy',
		'Extra Guests',
		'Hotel',
		'Transport',
		'Concierge',
	);
}

/**
 * One row of the spreadsheet. Pure, so it can be tested without WordPress.
 *
 * Someone not coming was never asked about a partner or accessibility, so
 * those cells are left empty rather than answered "No" for them.
 *
 * @param array<string, string>      $primary Who replied: attending, with_guest, accessibility,
 *                                            first_name, last_name, phone, email, dietary, allergies.
 * @param array<string, string>|null $partner Their partner, the same person keys, or null.
 * @return string[] One cell per column, in order.
 */
function brodfest_guest_list_row( $primary, $partner ) {
	$yes_no = static function ( $value ) {
		if ( 'yes' === $value ) {
			return 'Yes';
		}

		return 'no' === $value ? 'No' : '';
	};
	$diet   = brodfest_rsvp_dietary_options();
	$cell   = static function ( $person, $key ) use ( $diet ) {
		$value = (string) ( $person[ $key ] ?? '' );

		return 'dietary' === $key ? ( $diet[ $value ] ?? '' ) : $value;
	};
	$coming = 'yes' === ( $primary['attending'] ?? '' );
	$with   = $coming ? ( null !== $partner ? 'yes' : (string) ( $primary['with_guest'] ?? '' ) ) : '';
	$row    = array(
		$yes_no( $primary['attending'] ?? '' ),
		$cell( $primary, 'first_name' ),
		$cell( $primary, 'last_name' ),
		$cell( $primary, 'phone' ),
		$cell( $primary, 'email' ),
		$cell( $primary, 'dietary' ),
		$cell( $primary, 'allergies' ),
		$coming ? $yes_no( $primary['accessibility'] ?? '' ) : '',
		$yes_no( $with ),
	);

	foreach ( array( 'first_name', 'last_name', 'phone', 'email', 'dietary', 'allergies' ) as $key ) {
		$row[] = null === $partner ? '' : $cell( $partner, $key );
	}

	// Extra Guests, Hotel, Transport, Concierge: for the client to fill in.
	return array_merge( $row, array( '', '', '', '' ) );
}

/**
 * A cell made safe to open in a spreadsheet. Anything a guest typed that
 * starts like a formula is kept as text, so opening the file runs nothing.
 * A phone number such as +44 7700 900123 is left as it is.
 *
 * @param string $value The cell.
 * @return string
 */
function brodfest_guest_list_cell( $value ) {
	if ( preg_match( '/^[=@\t\r]|^[+-][^0-9 ]/', $value ) ) {
		return "'" . $value;
	}

	return $value;
}

/**
 * Writes rows as CSV. Pure apart from the stream it is handed.
 *
 * @param resource $out  Where to write.
 * @param array[]  $rows Rows from brodfest_guest_list_row().
 * @return void
 */
function brodfest_guest_list_write( $out, $rows ) {
	// So Excel reads names with accents correctly.
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing to the download stream.
	fputcsv( $out, brodfest_guest_list_columns(), ',', '"', '' );

	foreach ( $rows as $row ) {
		fputcsv( $out, array_map( 'brodfest_guest_list_cell', $row ), ',', '"', '' );
	}
}

/**
 * One guest's answers, keyed as brodfest_guest_list_row() takes them.
 *
 * @param int $user_id The guest.
 * @return array<string, string>
 */
function brodfest_guest_list_person( $user_id ) {
	$meta = static fn( $key ) => (string) get_user_meta( $user_id, $key, true );

	return array(
		'attending'     => $meta( 'brodfest_attending' ),
		'with_guest'    => $meta( 'brodfest_with_guest' ),
		'accessibility' => $meta( 'brodfest_accessibility' ),
		'first_name'    => $meta( 'first_name' ),
		'last_name'     => $meta( 'last_name' ),
		'phone'         => $meta( 'brodfest_phone' ),
		'email'         => $meta( 'brodfest_email' ),
		'dietary'       => $meta( 'brodfest_dietary' ),
		'allergies'     => $meta( 'brodfest_allergies' ),
	);
}

/**
 * Every reply as a row, oldest first.
 *
 * @return array[]
 */
function brodfest_guest_list_rows() {
	$primaries = get_users(
		array(
			'role'       => BRODFEST_GUEST_ROLE,
			'fields'     => 'ID',
			'orderby'    => 'registered',
			'order'      => 'ASC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Only on download.
			'meta_key'   => 'brodfest_type',
			'meta_value' => 'primary', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	$rows      = array();

	foreach ( $primaries as $primary_id ) {
		$partner_id = brodfest_guest_plus_one( (int) $primary_id );
		$rows[]     = brodfest_guest_list_row(
			brodfest_guest_list_person( (int) $primary_id ),
			$partner_id ? brodfest_guest_list_person( $partner_id ) : null
		);
	}

	return $rows;
}

/**
 * The download link, nonce and all.
 *
 * @return string
 */
function brodfest_guest_list_url() {
	return wp_nonce_url( admin_url( 'admin-post.php?action=' . BRODFEST_GUEST_LIST_ACTION ), BRODFEST_GUEST_LIST_ACTION );
}

/**
 * A "Download guest list" button above the Users list.
 *
 * @param string $which 'top' or 'bottom'.
 * @return void
 */
function brodfest_guest_list_button( $which ) {
	if ( 'top' !== $which || ! current_user_can( 'list_users' ) ) {
		return;
	}

	echo '<div class="alignleft actions"><a class="button" id="brodfest-guest-list" href="' . esc_url( brodfest_guest_list_url() ) . '">' . esc_html__( 'Download guest list', 'blueworx-client-brodfest' ) . '</a></div>';
}
add_action( 'manage_users_extra_tablenav', 'brodfest_guest_list_button' );

/**
 * Sends the spreadsheet. Only to someone who may see the Users list.
 *
 * @return void
 */
function brodfest_guest_list_download() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to download the guest list.', 'blueworx-client-brodfest' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( BRODFEST_GUEST_LIST_ACTION );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="brodfest-guest-list-' . gmdate( 'Y-m-d' ) . '.csv"' );

	brodfest_guest_list_write( fopen( 'php://output', 'w' ), brodfest_guest_list_rows() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- The download stream.
	exit;
}
add_action( 'admin_post_' . BRODFEST_GUEST_LIST_ACTION, 'brodfest_guest_list_download' );
