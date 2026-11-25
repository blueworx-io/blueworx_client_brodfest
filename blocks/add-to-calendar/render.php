<?php
/**
 * Add to Calendar, drawn on the server: a button that opens a choice of
 * calendars. Without JavaScript the button is the calendar file itself.
 *
 * It stays off the page while brodfest_show_calendar() says so (hidden for
 * now, Luke 9 Nov 2026); the block can sit on the page all the same.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! brodfest_show_calendar() ) {
	return;
}

$brodfest_event = brodfest_event();
?>
<div class="bf-calendar" data-bf-calendar>
	<a class="bf-button bf-button--primary" href="<?php echo esc_url( brodfest_calendar_url() ); ?>" aria-expanded="false" aria-controls="bf-calendar-menu" data-bf-calendar-toggle>
		<?php echo esc_html( brodfest_attr( $attributes, 'label', __( 'Add to Calendar', 'blueworx-client-brodfest' ) ) ); ?>
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
