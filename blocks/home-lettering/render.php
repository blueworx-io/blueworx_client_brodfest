<?php
/**
 * The home page's date and lettering, drawn on the server: the date (the
 * page's main heading), the lettering under it, the Add to Calendar button
 * when it is on, and the line below. Figma: STD – Home and Invite – Home.
 *
 * The button is a block of its own, inside this one, so it keeps its place in
 * the layout. Both artworks are the plugin's own, drawn inline so they take
 * the theme's colours.
 *
 * @package Brodfest
 *
 * @var array  $attributes The block's attributes.
 * @var string $content    The blocks inside this one, already drawn.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_invite = 'invitation' === ( $attributes['artwork'] ?? '' );
$brodfest_event  = brodfest_event();
$brodfest_note   = brodfest_attr( $attributes, 'note' );

if ( $brodfest_invite ) {
	$brodfest_lettering = brodfest_attr( $attributes, 'letteringLabel', __( 'Invitation', 'blueworx-client-brodfest' ) );
} else {
	$brodfest_lettering = brodfest_attr( $attributes, 'letteringLabel', __( 'Save the date', 'blueworx-client-brodfest' ) );
}
?>
<section class="bf-home<?php echo '' === trim( $content ) ? ' bf-home--no-calendar' : ''; ?>">
	<h1 class="bf-home__date">
		<?php brodfest_inline_svg( 'date', gmdate( 'j F Y', strtotime( $brodfest_event['date'] ) ) ); ?>
	</h1>
	<p class="bf-home__lettering bf-home__lettering--<?php echo $brodfest_invite ? 'invite' : 'std'; ?>">
		<?php brodfest_inline_svg( $brodfest_invite ? 'invitation' : 'save-the-date', $brodfest_lettering ); ?>
	</p>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blocks drawn by this plugin, each escaping its own words. ?>
	<?php if ( '' !== $brodfest_note ) : ?>
		<p class="bf-home__note">
			<?php echo wp_kses( str_replace( "\n", '<br />', esc_html( $brodfest_note ) ), array( 'br' => array() ) ); ?>
		</p>
	<?php endif; ?>
</section>
