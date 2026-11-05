<?php
/**
 * Celebrate. Figma: STD – Celebrate and Invite – Celebrate.
 *
 * The Save the Date version is the welcome, then the dress code and transport
 * under area-style subtitles; the Invite opens with a photo strip and adds the
 * dress code.
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
	?>
	<div class="bf-stack">
		<?php
		brodfest_part(
			'text-section',
			array(
				'title'      => 'CELEBRATION',
				'paragraphs' => brodfest_std_copy( 'celebrate' ),
				'note'       => brodfest_std_copy( 'invitation' ),
			)
		);
		brodfest_part(
			'text-section',
			array(
				'title'      => 'DRESS CODE',
				'heading'    => 'h2',
				'subtitle'   => true,
				'paragraphs' => array( brodfest_std_copy( 'dress_code' ) ),
			)
		);
		brodfest_part(
			'text-section',
			array(
				'title'      => 'TRANSPORT FOR BRODFEST IBIZA',
				'heading'    => 'h2',
				'subtitle'   => true,
				'paragraphs' => array( brodfest_std_copy( 'transport' ) ),
			)
		);
		?>
	</div>
	<?php
}

brodfest_document_close();
