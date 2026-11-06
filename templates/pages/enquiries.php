<?php
/**
 * Enquiries. Figma: STD – Enquiries and Invite – Enquiries, which are the same.
 *
 * On the Save the Date the first contact's title is the page title and the
 * rest are area-style subtitles, with the emails linked in the copy (client,
 * 6 Nov 2026); the Invite keeps its titled sections and email lines.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();
?>
<?php $brodfest_invite = brodfest_is_invite(); ?>
<?php if ( $brodfest_invite ) : ?>
	<h1 class="sr-only"><?php esc_html_e( 'Enquiries', 'blueworx-client-brodfest' ); ?></h1>
<?php endif; ?>
<div class="bf-stack">
	<?php
	foreach ( brodfest_enquiry_contacts() as $brodfest_i => $brodfest_contact ) {
		brodfest_part(
			'text-section',
			array(
				'title'      => $brodfest_contact['title'],
				'heading'    => $brodfest_invite || $brodfest_i > 0 ? 'h2' : 'h1',
				'subtitle'   => ! $brodfest_invite && $brodfest_i > 0,
				'paragraphs' => array( $brodfest_contact['text'] ),
				'email'      => $brodfest_contact['email'],
			)
		);
	}
	?>
</div>
<?php
brodfest_document_close();
