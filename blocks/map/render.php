<?php
/**
 * The map, drawn on the server: the client's wide map on desktop and their
 * squarer one on mobile, or the placeholder label while it is still to come.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_desktop = brodfest_media_url( $attributes['imageDesktop'] ?? '', $attributes['imageDesktopId'] ?? 0 );
$brodfest_mobile  = brodfest_media_url( $attributes['imageMobile'] ?? '', $attributes['imageMobileId'] ?? 0 );
$brodfest_label   = brodfest_attr( $attributes, 'placeholderLabel' );

// One picture alone serves both sizes.
if ( '' === $brodfest_mobile ) {
	$brodfest_mobile = $brodfest_desktop;
}
if ( '' === $brodfest_desktop ) {
	$brodfest_desktop = $brodfest_mobile;
}

/**
 * A picture's width and height attributes, or nothing when they are not known.
 *
 * @param array  $attributes The block's attributes.
 * @param string $which      'desktop' or 'mobile'.
 * @return string
 */
$brodfest_size = static function ( $attributes, $which ) {
	$width  = (int) ( $attributes[ $which . 'Width' ] ?? 0 );
	$height = (int) ( $attributes[ $which . 'Height' ] ?? 0 );

	return $width > 0 && $height > 0 ? ' width="' . $width . '" height="' . $height . '"' : '';
};

if ( '' !== $brodfest_mobile ) :
	?>
<picture class="bf-map bf-map--image">
	<source media="(min-width: 1024px)" srcset="<?php echo esc_url( $brodfest_desktop ); ?>"<?php echo $brodfest_size( $attributes, 'desktop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Whole numbers only. ?> />
	<img src="<?php echo esc_url( $brodfest_mobile ); ?>"<?php echo $brodfest_size( $attributes, 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Whole numbers only. ?> alt="<?php echo brodfest_alt( $attributes['alt'] ?? '', ! empty( $attributes['decorative'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" />
</picture>
<?php elseif ( '' !== $brodfest_label ) : ?>
<div class="bf-map">
	<span class="bf-placeholder-label"><?php echo esc_html( $brodfest_label ); ?></span>
</div>
<?php endif; ?>
