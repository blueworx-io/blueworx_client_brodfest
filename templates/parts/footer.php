<?php
/**
 * Site footer: the mountain mark. Figma: Footer (Size).
 *
 * Mobile has no mountain, as Mark drew it — the CSS keeps the space only.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="bf-footer">
	<span class="bf-footer__mountain"><?php brodfest_inline_svg( 'mountain' ); ?></span>
</footer>
