<?php
/**
 * Accommodation. Figma: STD – Accommodation and Invite – Accommodation, which
 * are the same page.
 *
 * Getting here sits on this page now that Transport is gone, and the map
 * follows the intro. Both are placeholders until the copy and SVG arrive.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();

brodfest_part(
	'text-section',
	array(
		'title'      => 'ACCOMMODATION',
		'paragraphs' => array( "INFO ON ACCOM..Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea" ),
	)
);
?>
<div class="bf-map">
	<span class="bf-placeholder-label"><?php esc_html_e( 'MAP', 'blueworx-client-brodfest' ); ?></span>
</div>
<?php
foreach ( brodfest_accommodation_areas() as $brodfest_area ) {
	brodfest_part( 'area', $brodfest_area );
}
?>
<div class="bf-section-gap">
	<?php
	brodfest_part(
		'text-section',
		array(
			'title'      => 'GETTING HERE',
			'heading'    => 'h2',
			'paragraphs' => array( brodfest_lorem( 3 ) ),
		)
	);
	?>
</div>
<?php
brodfest_document_close();
