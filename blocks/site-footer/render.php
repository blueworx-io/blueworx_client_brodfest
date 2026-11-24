<?php
/**
 * The site footer, drawn on the server: the mountain mark. Figma: Footer
 * (Size). Mobile has no mountain, as Mark drew it: the styles keep the space
 * only.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="bf-footer">
	<span class="bf-footer__mountain"><?php brodfest_inline_svg( 'mountain', brodfest_attr( $attributes, 'markLabel' ) ); ?></span>
</footer>
