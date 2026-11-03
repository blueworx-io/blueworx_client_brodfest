<?php
/**
 * Site header, on every page.
 *
 * Placeholder until the Figma design is built.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<header class="brodfest-header">
	<div class="mx-auto flex max-w-screen-xl items-center justify-between px-6 py-4">
		<a href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>" class="font-display text-xl">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		</a>
	</div>
</header>
