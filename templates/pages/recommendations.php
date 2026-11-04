<?php
/**
 * Ibiza Recommendations (Invite only). Figma: Invite – Recommendations.
 *
 * Categories come from brodfest_recommendation_categories(), so adding one
 * (nightclubs, attractions…) needs no template change.
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
		'title'      => 'RECOMMENDATIONS',
		'paragraphs' => array( "INFO ON RECS..Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea" ),
	)
);

foreach ( brodfest_recommendation_categories() as $brodfest_category ) {
	?>
	<section class="bf-category">
		<h2 class="bf-area__name"><?php echo esc_html( $brodfest_category['name'] ); ?></h2>
		<?php
		brodfest_part(
			'cards',
			array(
				'cards' => $brodfest_category['cards'],
				'label' => $brodfest_category['name'],
			)
		);
		?>
	</section>
	<?php
}

brodfest_document_close();
