<?php
/**
 * Information (the Accommodation page). Figma: STD – Accommodation and Invite
 * – Accommodation, which are the same page.
 *
 * Transport, then Where to Stay under area-style subtitles, then the map and
 * the hotels, with the client's copy (client, 6 Nov 2026). The Invite shows
 * the same page (Luke, 15 Nov 2026).
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();
?>
<div class="bf-stack">
	<?php
	brodfest_part( 'text-section', array( 'title' => 'INFORMATION' ) );
	brodfest_part(
		'text-section',
		array(
			'title'      => 'TRANSPORT',
			'heading'    => 'h2',
			'subtitle'   => true,
			'paragraphs' => array( brodfest_std_copy( 'transport' ) ),
		)
	);
	brodfest_part(
		'text-section',
		array(
			'title'      => 'WHERE TO STAY',
			'heading'    => 'h2',
			'subtitle'   => true,
			'paragraphs' => array( brodfest_std_copy( 'stay' ) ),
		)
	);
	?>
</div>
<picture class="bf-map bf-map--image">
	<?php // The client's wide map on desktop, and their squarer one on mobile. ?>
	<source media="(min-width: 1024px)" srcset="<?php echo esc_url( brodfest_img( 'map-desktop.png' ) ); ?>" width="1564" height="782" />
	<img src="<?php echo esc_url( brodfest_img( 'map.png' ) ); ?>" width="782" height="600" alt="<?php esc_attr_e( 'Map of Ibiza showing Brodfest and the three hotel areas: Playa d’en Bossa, Talamanca and Cala Llonga', 'blueworx-client-brodfest' ); ?>" />
</picture>
<?php
foreach ( brodfest_accommodation_areas() as $brodfest_area ) {
	brodfest_part( 'area', $brodfest_area );
}

brodfest_document_close();
