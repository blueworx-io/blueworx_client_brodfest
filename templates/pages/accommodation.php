<?php
/**
 * Accommodation. Figma: STD – Accommodation and Invite – Accommodation, which
 * are the same page.
 *
 * The map follows the intro. On the Save the Date this is Where to Stay, with
 * the client's copy and map; the Invite keeps its placeholders, with Getting
 * here, until it has its own.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();

$brodfest_invite = brodfest_is_invite();

brodfest_part(
	'text-section',
	$brodfest_invite
		? array(
			'title'      => 'ACCOMMODATION',
			'paragraphs' => array( "INFO ON ACCOM..Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea" ),
		)
		: array(
			'title'      => 'WHERE TO STAY',
			'paragraphs' => array( brodfest_std_copy( 'stay' ) ),
		)
);
?>
<?php if ( $brodfest_invite ) : ?>
<div class="bf-map">
	<span class="bf-placeholder-label"><?php esc_html_e( 'MAP', 'blueworx-client-brodfest' ); ?></span>
</div>
<?php else : ?>
<div class="bf-map bf-map--image">
	<img src="<?php echo esc_url( brodfest_img( 'map.png' ) ); ?>" width="782" height="600" alt="<?php esc_attr_e( 'Map of Ibiza showing Brodfest and the three hotel areas: Playa d’en Bossa, Talamanca and Cala Llonga', 'blueworx-client-brodfest' ); ?>" />
</div>
<?php endif; ?>
<?php
foreach ( brodfest_accommodation_areas() as $brodfest_area ) {
	brodfest_part( 'area', $brodfest_area );
}
?>
<?php if ( $brodfest_invite ) : ?>
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
<?php endif; ?>
<?php
brodfest_document_close();
