<?php
/**
 * Celebrate. Figma: STD – Celebrate and Invite – Celebrate.
 *
 * The welcome, then the dress code under an area-style subtitle (transport is
 * on Information, at the client's request). The Invite opens with a photo
 * strip, and its welcome stays placeholder until the client sends it.
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
	<div class="bf-after-media bf-stack">
		<?php
		// Titled and laid out as the Save the Date's, with its dress code; the
		// welcome stays placeholder until the client sends the Invite's (Luke,
		// 15 Nov 2026).
		brodfest_part(
			'text-section',
			array(
				'title'      => 'CELEBRATION',
				'paragraphs' => array( brodfest_lorem( 3 ), brodfest_lorem( 3 ) ),
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
		?>
	</div>
	<?php
}

brodfest_document_close();
