<?php
/**
 * Celebrate. Figma: STD – Celebrate and Invite – Celebrate.
 *
 * The Save the Date version is a short note; the Invite opens with a photo
 * strip and adds the dress code.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();

if ( brodfest_is_invite() ) {
	?>
	<div class="bf-strip">
		<img src="<?php echo esc_url( brodfest_img( 'celebrate-strip.jpg' ) ); ?>" alt="" />
	</div>
	<div class="bf-after-media">
		<?php
		brodfest_part(
			'text-section',
			array(
				'title'      => 'CELEBRATE',
				'paragraphs' => array( brodfest_lorem( 3 ), brodfest_lorem( 3 ) ),
			)
		);
		brodfest_part(
			'text-section',
			array(
				'title'      => 'DRESS CODE',
				'heading'    => 'h2',
				'paragraphs' => array( 'Black, gold Ibiza bold' ),
			)
		);
		?>
	</div>
	<?php
} else {
	brodfest_part(
		'text-section',
		array(
			'title'      => 'CELEBRATE',
			'paragraphs' => array( brodfest_lorem( 3 ) ),
			'note'       => __( 'Full invitation and information coming soon', 'blueworx-client-brodfest' ),
		)
	);
}

brodfest_document_close();
