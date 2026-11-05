<?php
/**
 * Home. Figma: STD – Home and Invite – Home (Desktop, Mobile).
 *
 * The Save the Date and the Invite share the layout and differ in the
 * lettering, the video and the line under the button.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_invite = brodfest_is_invite();
$brodfest_event  = brodfest_event();

brodfest_document_open( array( 'opening' => true ) );

brodfest_part( 'hero', array( 'videos' => brodfest_hero_videos() ) );
?>
<section class="bf-home">
	<h1 class="bf-home__date">
		<?php brodfest_inline_svg( 'date', gmdate( 'j F Y', strtotime( $brodfest_event['date'] ) ) ); ?>
	</h1>
	<p class="bf-home__lettering bf-home__lettering--<?php echo $brodfest_invite ? 'invite' : 'std'; ?>">
		<?php
		if ( $brodfest_invite ) {
			brodfest_inline_svg( 'invitation', __( 'Invitation', 'blueworx-client-brodfest' ) );
		} else {
			brodfest_inline_svg( 'save-the-date', __( 'Save the date', 'blueworx-client-brodfest' ) );
		}
		?>
	</p>
	<a class="bf-button bf-button--primary" href="<?php echo esc_url( brodfest_calendar_url() ); ?>" download="brodfest.ics">
		<?php esc_html_e( 'Add to Calendar', 'blueworx-client-brodfest' ); ?>
	</a>
	<p class="bf-home__note">
		<?php
		echo esc_html(
			$brodfest_invite
				? __( 'Please RSVP to confirm attendance', 'blueworx-client-brodfest' )
				: brodfest_std_copy( 'invitation' )
		);
		?>
	</p>
</section>
<?php
brodfest_document_close();
