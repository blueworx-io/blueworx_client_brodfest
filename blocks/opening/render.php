<?php
/**
 * The opening screen, drawn on the server: black, the logo draws itself,
 * then Enter lifts the screen away (and starts the video with sound).
 *
 * Whether it shows is decided before the page first paints, by the script the
 * page prints in its head when it holds this block (see includes/render.php),
 * so a first-time visitor sees black, never a flash of the page, and a
 * returning one, or anyone who asks for less motion, never sees it.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_logo = brodfest_attr( $attributes, 'logoLabel', 'Brodfest Ibiza' );
?>
<div class="bf-opening" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( brodfest_attr( $attributes, 'dialogLabel', __( 'Welcome to Brodfest', 'blueworx-client-brodfest' ) ) ); ?>" data-bf-opening>
	<span class="bf-opening__logo">
		<?php
		// Each letter's outline is measured as 1, so the drawing runs the same
		// length whatever the letter's size.
		ob_start();
		brodfest_inline_svg( 'logo', $brodfest_logo );
		echo str_replace( '<path ', '<path pathLength="1" ', (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG, from brodfest_inline_svg().
		?>
	</span>
	<button class="bf-opening__enter bf-pill" type="button" data-bf-enter><?php echo esc_html( brodfest_attr( $attributes, 'enterLabel', __( 'ENTER', 'blueworx-client-brodfest' ) ) ); ?></button>
</div>
