<?php
/**
 * Home page.
 *
 * Placeholder until the Figma design is built. Sections go between
 * brodfest_document_open() and brodfest_document_close(), which carry the
 * document, header and footer.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();
?>
<section class="mx-auto max-w-screen-xl px-6 py-24">
	<h1 class="font-display text-5xl"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
</section>
<?php
brodfest_document_close();
