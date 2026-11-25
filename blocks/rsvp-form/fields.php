<?php
/**
 * One person's RSVP fields: the invitee, or (with the guest_ prefix) their
 * guest. Figma: Form/Field (Text, Phone, Dropdown).
 *
 * Labels are drawn inside the fields, as in the design, and are also real
 * labels for screen readers.
 *
 * @package Brodfest
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints one person's fields.
 *
 * @param string $prefix '' for the invitee, 'guest_' for their guest.
 * @return void
 */
function brodfest_print_rsvp_person_fields( $prefix ) {
	$p     = 'guest_' === $prefix ? 'guest_' : '';
	$guest = 'guest_' === $p;
	// Every field is needed (Mark, 12 Nov 2026). The partner's are marked as
	// needed by the page's script, and only while they show.
	$req = $guest ? '' : ' required';
	$l   = $guest
		? array(
			'name'      => __( 'Partner full name', 'blueworx-client-brodfest' ),
			'phone'     => __( 'Partner’s mobile phone number', 'blueworx-client-brodfest' ),
			'email'     => __( 'Partner’s email address', 'blueworx-client-brodfest' ),
			'dietary'   => __( 'Partner’s dietary requirements', 'blueworx-client-brodfest' ),
			'allergies' => __( 'Partner’s allergies (Please type details)', 'blueworx-client-brodfest' ),
		)
		: array(
			'name'      => __( 'Full name', 'blueworx-client-brodfest' ),
			'phone'     => __( 'Mobile phone number', 'blueworx-client-brodfest' ),
			'email'     => __( 'Email address', 'blueworx-client-brodfest' ),
			'dietary'   => __( 'Dietary requirements', 'blueworx-client-brodfest' ),
			'allergies' => __( 'Allergies (Please type details)', 'blueworx-client-brodfest' ),
		);
	$id  = static function ( $field ) use ( $p ) {
		return 'bf-' . $p . $field;
	};
	?>
<div class="bf-fields">
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $id( 'name' ) ); ?>"><?php echo esc_html( $l['name'] ); ?></label>
		<input id="<?php echo esc_attr( $id( 'name' ) ); ?>" name="<?php echo esc_attr( $p . 'name' ); ?>" type="text" autocomplete="<?php echo $guest ? 'off' : 'name'; ?>" placeholder="<?php echo esc_attr( $l['name'] ); ?>" maxlength="120"<?php echo esc_attr( $req ); ?> />
	</p>
	<p class="bf-field bf-field--phone">
		<label class="sr-only" for="<?php echo esc_attr( $id( 'phone_prefix' ) ); ?>"><?php esc_html_e( 'Country code', 'blueworx-client-brodfest' ); ?></label>
		<select class="bf-field__prefix" id="<?php echo esc_attr( $id( 'phone_prefix' ) ); ?>" name="<?php echo esc_attr( $p . 'phone_prefix' ); ?>">
			<?php foreach ( brodfest_rsvp_dial_codes() as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>"<?php selected( '+44', $code ); ?>><?php echo esc_html( $code ); ?></option>
			<?php endforeach; ?>
		</select>
		<label class="sr-only" for="<?php echo esc_attr( $id( 'phone' ) ); ?>"><?php echo esc_html( $l['phone'] ); ?></label>
		<input id="<?php echo esc_attr( $id( 'phone' ) ); ?>" name="<?php echo esc_attr( $p . 'phone' ); ?>" type="tel" autocomplete="<?php echo $guest ? 'off' : 'tel-national'; ?>" placeholder="<?php echo esc_attr( $l['phone'] ); ?>" maxlength="30"<?php echo esc_attr( $req ); ?> />
	</p>
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $id( 'email' ) ); ?>"><?php echo esc_html( $l['email'] ); ?></label>
		<input id="<?php echo esc_attr( $id( 'email' ) ); ?>" name="<?php echo esc_attr( $p . 'email' ); ?>" type="email" autocomplete="<?php echo $guest ? 'off' : 'email'; ?>" placeholder="<?php echo esc_attr( $l['email'] ); ?>" maxlength="190"<?php echo esc_attr( $req ); ?> />
	</p>
	<p class="bf-field bf-field--select">
		<label class="sr-only" for="<?php echo esc_attr( $id( 'dietary' ) ); ?>"><?php echo esc_html( $l['dietary'] ); ?></label>
		<select id="<?php echo esc_attr( $id( 'dietary' ) ); ?>" name="<?php echo esc_attr( $p . 'dietary' ); ?>"<?php echo esc_attr( $req ); ?>>
			<option value=""><?php echo esc_html( $l['dietary'] ); ?></option>
			<?php foreach ( brodfest_rsvp_dietary_options() as $value => $option ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="bf-field">
		<label class="sr-only" for="<?php echo esc_attr( $id( 'allergies' ) ); ?>"><?php echo esc_html( $l['allergies'] ); ?></label>
		<input id="<?php echo esc_attr( $id( 'allergies' ) ); ?>" name="<?php echo esc_attr( $p . 'allergies' ); ?>" type="text" autocomplete="off" placeholder="<?php echo esc_attr( $l['allergies'] ); ?>" maxlength="300"<?php echo esc_attr( $req ); ?> />
	</p>
</div>
	<?php
}
