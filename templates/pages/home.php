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
<section class="bf-home<?php echo brodfest_show_calendar() ? '' : ' bf-home--no-calendar'; ?>">
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
	<?php
	// Hidden for now (Luke, 9 Nov 2026); see brodfest_show_calendar().
	// Without JavaScript the button is the calendar file itself.
	?>
	<?php if ( brodfest_show_calendar() ) : ?>
	<div class="bf-calendar" data-bf-calendar>
		<a class="bf-button bf-button--primary" href="<?php echo esc_url( brodfest_calendar_url() ); ?>" aria-expanded="false" aria-controls="bf-calendar-menu" data-bf-calendar-toggle>
			<?php esc_html_e( 'Add to Calendar', 'blueworx-client-brodfest' ); ?>
		</a>
		<ul class="bf-calendar__menu" id="bf-calendar-menu" hidden data-bf-calendar-menu>
			<?php foreach ( brodfest_calendar_links( $brodfest_event, brodfest_calendar_url() ) as $brodfest_name => $brodfest_url ) : ?>
				<li>
					<?php if ( 'Apple' === $brodfest_name ) : ?>
						<a href="<?php echo esc_url( $brodfest_url ); ?>"><?php echo esc_html( $brodfest_name ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( $brodfest_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $brodfest_name ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
	<p class="bf-home__note">
		<?php
		if ( $brodfest_invite ) {
			esc_html_e( 'Please RSVP to confirm attendance', 'blueworx-client-brodfest' );
		} else {
			// Two lines, breaking after "Ibiza" (client, 6 Nov 2026).
			echo str_replace( 'Brodfest Ibiza ', 'Brodfest Ibiza<br />', esc_html( brodfest_std_copy( 'invitation' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped; only the break is added.
		}
		?>
	</p>
</section>
<?php
brodfest_document_close();
