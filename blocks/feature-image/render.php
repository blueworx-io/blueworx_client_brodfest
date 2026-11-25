<?php
/**
 * The feature image, drawn on the server: a large picture, or its
 * placeholder label while the picture is still to come.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_src   = brodfest_media_url( $attributes['image'] ?? '', $attributes['imageId'] ?? 0 );
$brodfest_label = brodfest_attr( $attributes, 'placeholderLabel' );

if ( '' === $brodfest_src && '' === $brodfest_label ) {
	return;
}
?>
<div class="bf-feature">
	<?php if ( '' !== $brodfest_src ) : ?>
		<img src="<?php echo esc_url( $brodfest_src ); ?>" alt="<?php echo brodfest_alt( $attributes['imageAlt'] ?? '', ! empty( $attributes['imageDecorative'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" loading="lazy" />
	<?php else : ?>
		<span class="bf-placeholder-label"><?php echo esc_html( $brodfest_label ); ?></span>
	<?php endif; ?>
</div>
