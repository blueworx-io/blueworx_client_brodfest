<?php
/**
 * The photo strip, drawn on the server: one wide photo across the page.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_src = brodfest_media_url( $attributes['image'] ?? '', $attributes['imageId'] ?? 0 );

// No photo, no strip.
if ( '' === $brodfest_src ) {
	return;
}
?>
<div class="bf-strip">
	<img src="<?php echo esc_url( $brodfest_src ); ?>" alt="<?php echo brodfest_alt( $attributes['imageAlt'] ?? '', ! empty( $attributes['imageDecorative'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" />
</div>
