<?php
/**
 * Can Sakhara Story (Invite only). Figma: Invite – Can Sakhara Story.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();
?>
<div class="bf-story-banner">
	<?php brodfest_inline_svg( 'story-banner' ); ?>
</div>
<div class="bf-after-media">
	<?php
	brodfest_part(
		'text-section',
		array(
			'title'      => 'CAN SAKHARA STORY',
			'paragraphs' => array( brodfest_lorem( 3 ), brodfest_lorem( 3 ) ),
		)
	);
	?>
	<div class="bf-feature">
		<span class="bf-placeholder-label"><?php esc_html_e( 'NIGHT TIME VILLA SHOT', 'blueworx-client-brodfest' ); ?></span>
	</div>
</div>
<?php
brodfest_document_close();
