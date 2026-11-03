<?php
/**
 * Site footer, on every page.
 *
 * Placeholder until the Figma design is built.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="brodfest-footer">
	<div class="mx-auto max-w-screen-xl px-6 py-8 text-sm">
		&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?>
	</div>
</footer>
