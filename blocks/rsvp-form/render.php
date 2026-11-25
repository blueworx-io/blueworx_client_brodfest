<?php
/**
 * The RSVP form, drawn on the server. Figma: STD – RSVP (Desktop, Mobile).
 *
 * The header shows a close button instead of the menu on the page that holds
 * this block, as drawn, and the page slides down from under the header as it
 * opens. Replies are saved as guests (includes/rsvp.php and guests.php), and
 * the guest list can be downloaded from the Users screen.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/fields.php';

// Read-only: which message to show after the form posted back here.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$brodfest_status = isset( $_GET['rsvp'] ) ? sanitize_key( wp_unslash( $_GET['rsvp'] ) ) : '';
?>
<div class="bf-rsvp">
	<h1 class="bf-title"><?php echo esc_html( brodfest_attr( $attributes, 'heading', 'RSVP' ) ); ?></h1>

	<?php if ( 'sent' === $brodfest_status ) : ?>
		<p class="bf-body bf-rsvp__message" role="status"><?php echo esc_html( brodfest_attr( $attributes, 'thanks', __( 'Thank you for your RSVP', 'blueworx-client-brodfest' ) ) ); ?></p>
		<p class="bf-rsvp__close">
			<a class="bf-button bf-button--send" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>"><?php echo esc_html( brodfest_attr( $attributes, 'closeLabel', __( 'CLOSE', 'blueworx-client-brodfest' ) ) ); ?></a>
		</p>
	<?php else : ?>
		<?php if ( 'expired' === $brodfest_status ) : ?>
			<p class="bf-body bf-rsvp__message" role="alert"><?php esc_html_e( 'Sorry, that took too long to send. Please fill the form in again.', 'blueworx-client-brodfest' ); ?></p>
		<?php elseif ( 'error' === $brodfest_status ) : ?>
			<p class="bf-body bf-rsvp__message" role="alert"><?php esc_html_e( 'Please fill in every field, and say whether you will be attending.', 'blueworx-client-brodfest' ); ?></p>
		<?php elseif ( 'busy' === $brodfest_status ) : ?>
			<p class="bf-body bf-rsvp__message" role="alert"><?php echo wp_kses_post( brodfest_link_emails( esc_html__( 'Sorry, a lot of replies have come from here in the last hour. Please try again later, or email info@brodfest.net', 'blueworx-client-brodfest' ) ) ); ?></p>
		<?php endif; ?>

		<form class="bf-form" method="post" action="<?php echo esc_url( brodfest_page_url( 'rsvp' ) ); ?>" data-bf-rsvp>
			<input type="hidden" name="brodfest_action" value="<?php echo esc_attr( BRODFEST_RSVP_ACTION ); ?>" />
			<?php wp_nonce_field( BRODFEST_RSVP_ACTION, 'brodfest_rsvp_nonce' ); ?>
			<p class="bf-form__trap" aria-hidden="true">
				<label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
			</p>

			<?php brodfest_print_rsvp_person_fields( '' ); ?>

			<fieldset class="bf-radios">
				<legend><?php echo esc_html( brodfest_attr( $attributes, 'attendingQuestion', __( 'Will you be attending?', 'blueworx-client-brodfest' ) ) ); ?></legend>
				<label><input type="radio" name="attending" value="yes" required data-bf-attending-toggle /> <?php esc_html_e( 'Yes', 'blueworx-client-brodfest' ); ?></label>
				<label><input type="radio" name="attending" value="no" data-bf-attending-toggle /> <?php esc_html_e( 'No', 'blueworx-client-brodfest' ); ?></label>
			</fieldset>

			<?php // Each piece shown only to someone coming is marked on its own, not wrapped, so the form keeps the design's layout. ?>
			<fieldset class="bf-radios" data-bf-attending>
				<legend><?php echo esc_html( brodfest_attr( $attributes, 'partnerQuestion', __( 'Will you attend with a partner?', 'blueworx-client-brodfest' ) ) ); ?></legend>
				<label><input type="radio" name="with_guest" value="yes" data-bf-guest-toggle /> <?php esc_html_e( 'Yes', 'blueworx-client-brodfest' ); ?></label>
				<label><input type="radio" name="with_guest" value="no" data-bf-guest-toggle /> <?php esc_html_e( 'No', 'blueworx-client-brodfest' ); ?></label>
			</fieldset>

			<div data-bf-guest>
				<?php brodfest_print_rsvp_person_fields( 'guest_' ); ?>
			</div>

			<label class="bf-check" data-bf-attending>
				<input type="checkbox" name="accessibility" value="1" />
				<span><?php echo esc_html( brodfest_attr( $attributes, 'accessibilityLabel', __( 'Click here if you or your partner have any accessibility requirements', 'blueworx-client-brodfest' ) ) ); ?></span>
			</label>

			<p class="bf-form__note" data-bf-attending><?php echo wp_kses_post( brodfest_link_emails( esc_html( brodfest_attr( $attributes, 'formNote', __( 'If you are attending with more than one additional guest please email our guest liaison team at info@brodfest.net', 'blueworx-client-brodfest' ) ) ) ) ); ?></p>

			<button class="bf-button bf-button--send" type="submit"><?php echo esc_html( brodfest_attr( $attributes, 'sendLabel', __( 'SEND', 'blueworx-client-brodfest' ) ) ); ?></button>
		</form>
	<?php endif; ?>
</div>
