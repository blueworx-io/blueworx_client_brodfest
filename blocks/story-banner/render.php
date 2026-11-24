<?php
/**
 * The story banner, drawn on the server: the plugin's own artwork across the
 * top of the Can Sakhara Story page. Drawn inline so its parts take the
 * theme's colours.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

// Decorative unless someone gives it alt text and turns decorative off.
$brodfest_label = ! isset( $attributes['decorative'] ) || $attributes['decorative'] ? '' : brodfest_attr( $attributes, 'alt' );
?>
<div class="bf-story-banner">
	<?php brodfest_inline_svg( 'story-banner', $brodfest_label ); ?>
</div>
