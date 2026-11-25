<?php
/**
 * Every page of the site: the page's section blocks, drawn on the server
 * between the site header and footer.
 *
 * The blocks are drawn first, so the document around them can tell whether
 * the page has the opening screen or the RSVP form before it prints its head.
 * The saved page holds only each block's name and words; the markup and
 * styling are in blocks/, so a fix there reaches every page.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_blocks = brodfest_page_blocks( get_queried_object_id(), get_post() );
$brodfest_html   = do_blocks( $brodfest_blocks );

brodfest_document_open(
	array(
		'opening' => brodfest_has_visible_block( 'brodfest/opening', $brodfest_blocks ),
		'close'   => brodfest_has_visible_block( 'brodfest/rsvp-form', $brodfest_blocks ),
	)
);

echo $brodfest_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Section blocks drawn by this plugin, each escaping its own words.

brodfest_document_close();
