<?php
/**
 * RSVP. Figma: STD – RSVP (Desktop, Mobile).
 *
 * The header shows a close button instead of the menu, as drawn, and the page
 * slides down from under the header as it opens. Replies are saved as RSVP
 * records in the admin — see includes/rsvp.php.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read-only: which message to show after the form posted back here.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$brodfest_status = isset( $_GET['rsvp'] ) ? sanitize_key( wp_unslash( $_GET['rsvp'] ) ) : '';

brodfest_document_open( array( 'close' => true ) );
?>
<div class="bf-rsvp">
	<h1 class="bf-title">RSVP</h1>

	<?php if ( 'sent' === $brodfest_status ) : ?>
		<p class="bf-body bf-rsvp__message" role="status"><?php esc_html_e( 'Thank you for your RSVP', 'blueworx-client-brodfest' ); ?></p>
		<p class="bf-rsvp__close">
			<a class="bf-button bf-button--send" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>"><?php esc_html_e( 'CLOSE', 'blueworx-client-brodfest' ); ?></a>
		</p>
	<?php else : ?>
		<?php if ( 'expired' === $brodfest_status ) : ?>
			<p class="bf-body bf-rsvp__message" role="alert"><?php esc_html_e( 'Sorry, that took too long to send. Please fill the form in again.', 'blueworx-client-brodfest' ); ?></p>
		<?php elseif ( 'error' === $brodfest_status ) : ?>
			<p class="bf-body bf-rsvp__message" role="alert"><?php esc_html_e( 'Please add your name and email address, and say whether you will be attending.', 'blueworx-client-brodfest' ); ?></p>
		<?php endif; ?>

		<form class="bf-form" method="post" action="<?php echo esc_url( brodfest_page_url( 'rsvp' ) ); ?>" data-bf-rsvp>
			<input type="hidden" name="brodfest_action" value="<?php echo esc_attr( BRODFEST_RSVP_ACTION ); ?>" />
			<?php wp_nonce_field( BRODFEST_RSVP_ACTION, 'brodfest_rsvp_nonce' ); ?>
			<p class="bf-form__trap" aria-hidden="true">
				<label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
			</p>

			<?php brodfest_part( 'rsvp-guest', array( 'prefix' => '' ) ); ?>

			<fieldset class="bf-radios">
				<legend><?php esc_html_e( 'Will you be attending?', 'blueworx-client-brodfest' ); ?></legend>
				<label><input type="radio" name="attending" value="yes" required /> <?php esc_html_e( 'Yes', 'blueworx-client-brodfest' ); ?></label>
				<label><input type="radio" name="attending" value="no" /> <?php esc_html_e( 'No', 'blueworx-client-brodfest' ); ?></label>
			</fieldset>

			<fieldset class="bf-radios">
				<legend><?php esc_html_e( 'Will you attend with a guest?', 'blueworx-client-brodfest' ); ?></legend>
				<label><input type="radio" name="with_guest" value="yes" data-bf-guest-toggle /> <?php esc_html_e( 'Yes', 'blueworx-client-brodfest' ); ?></label>
				<label><input type="radio" name="with_guest" value="no" data-bf-guest-toggle /> <?php esc_html_e( 'No', 'blueworx-client-brodfest' ); ?></label>
			</fieldset>

			<div data-bf-guest>
				<?php brodfest_part( 'rsvp-guest', array( 'prefix' => 'guest_' ) ); ?>
			</div>

			<label class="bf-check">
				<input type="checkbox" name="accessibility" value="1" />
				<span><?php esc_html_e( 'Click here if you or your guests have any accessibility requirements', 'blueworx-client-brodfest' ); ?></span>
			</label>

			<button class="bf-button bf-button--send" type="submit"><?php esc_html_e( 'SEND', 'blueworx-client-brodfest' ); ?></button>
		</form>
	<?php endif; ?>
</div>
<?php
brodfest_document_close();
