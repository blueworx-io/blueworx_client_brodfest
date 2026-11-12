<?php
/**
 * One person's RSVP fields: the invitee, or (with the guest_ prefix) their
 * guest. Figma: Form/Field (Text, Phone, Dropdown).
 *
 * Labels are drawn inside the fields, as in the design, and are also real
 * labels for screen readers.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type string $prefix '' for the invitee, 'guest_' for their guest.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_p     = 'guest_' === ( $args['prefix'] ?? '' ) ? 'guest_' : '';
$brodfest_guest = 'guest_' === $brodfest_p;
// Every field is needed (Mark, 12 Nov 2026). The partner's are marked as
// needed by the page's script, and only while they show.
$brodfest_req   = $brodfest_guest ? '' : ' required';
$brodfest_l     = $brodfest_guest
	? array(
		'name'      => __( 'Partner Full Name', 'blueworx-client-brodfest' ),
		'phone'     => __( 'Partner’s Mobile phone number', 'blueworx-client-brodfest' ),
		'email'     => __( 'Partner’s Email address', 'blueworx-client-brodfest' ),
		'dietary'   => __( 'Partner’s Dietary requirements', 'blueworx-client-brodfest' ),
		'allergies' => __( 'Partner’s Allergies (Please type details)', 'blueworx-client-brodfest' ),
	)
	: array(
		'name'      => __( 'Full Name', 'blueworx-client-brodfest' ),
		'phone'     => __( 'Mobile phone number', 'blueworx-client-brodfest' ),
		'email'     => __( 'Email address', 'blueworx-client-brodfest' ),
		'dietary'   => __( 'Dietary requirements', 'blueworx-client-brodfest' ),
		'allergies' => __( 'Allergies (Please type details)', 'blueworx-client-brodfest' ),
	);
$brodfest_id    = static function ( $field ) use ( $brodfest_p ) {
	return 'bf-' . $brodfest_p . $field;
};
?>
<div class="bf-fields">
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'name' ) ); ?>"><?php echo esc_html( $brodfest_l['name'] ); ?></label>
		<input id="<?php echo esc_attr( $brodfest_id( 'name' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'name' ); ?>" type="text" autocomplete="<?php echo $brodfest_guest ? 'off' : 'name'; ?>" placeholder="<?php echo esc_attr( $brodfest_l['name'] ); ?>" maxlength="120"<?php echo esc_attr( $brodfest_req ); ?> />
	</p>
	<p class="bf-field bf-field--phone">
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'phone_prefix' ) ); ?>"><?php esc_html_e( 'Country code', 'blueworx-client-brodfest' ); ?></label>
		<select class="bf-field__prefix" id="<?php echo esc_attr( $brodfest_id( 'phone_prefix' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'phone_prefix' ); ?>">
			<?php foreach ( brodfest_rsvp_dial_codes() as $brodfest_code ) : ?>
				<option value="<?php echo esc_attr( $brodfest_code ); ?>"<?php selected( '+44', $brodfest_code ); ?>><?php echo esc_html( $brodfest_code ); ?></option>
			<?php endforeach; ?>
		</select>
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'phone' ) ); ?>"><?php echo esc_html( $brodfest_l['phone'] ); ?></label>
		<input id="<?php echo esc_attr( $brodfest_id( 'phone' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'phone' ); ?>" type="tel" autocomplete="<?php echo $brodfest_guest ? 'off' : 'tel-national'; ?>" placeholder="<?php echo esc_attr( $brodfest_l['phone'] ); ?>" maxlength="30"<?php echo esc_attr( $brodfest_req ); ?> />
	</p>
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'email' ) ); ?>"><?php echo esc_html( $brodfest_l['email'] ); ?></label>
		<input id="<?php echo esc_attr( $brodfest_id( 'email' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'email' ); ?>" type="email" autocomplete="<?php echo $brodfest_guest ? 'off' : 'email'; ?>" placeholder="<?php echo esc_attr( $brodfest_l['email'] ); ?>" maxlength="190"<?php echo esc_attr( $brodfest_req ); ?> />
	</p>
	<p class="bf-field bf-field--select">
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'dietary' ) ); ?>"><?php echo esc_html( $brodfest_l['dietary'] ); ?></label>
		<select id="<?php echo esc_attr( $brodfest_id( 'dietary' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'dietary' ); ?>"<?php echo esc_attr( $brodfest_req ); ?>>
			<option value=""><?php echo esc_html( $brodfest_l['dietary'] ); ?></option>
			<?php foreach ( brodfest_rsvp_dietary_options() as $brodfest_value => $brodfest_option ) : ?>
				<option value="<?php echo esc_attr( $brodfest_value ); ?>"><?php echo esc_html( $brodfest_option ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $brodfest_id( 'allergies' ) ); ?>"><?php echo esc_html( $brodfest_l['allergies'] ); ?></label>
		<input id="<?php echo esc_attr( $brodfest_id( 'allergies' ) ); ?>" name="<?php echo esc_attr( $brodfest_p . 'allergies' ); ?>" type="text" autocomplete="off" placeholder="<?php echo esc_attr( $brodfest_l['allergies'] ); ?>" maxlength="300"<?php echo esc_attr( $brodfest_req ); ?> />
	</p>
</div>
