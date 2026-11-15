<?php
/**
 * Enquiries. Figma: STD – Enquiries and Invite – Enquiries, which are the same.
 *
 * The first contact's title is the page title and the rest are area-style
 * subtitles, with the emails linked in the copy (client, 6 Nov 2026). The
 * Invite shows the same page (Luke, 15 Nov 2026).
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
	foreach ( brodfest_enquiry_contacts() as $brodfest_i => $brodfest_contact ) {
		brodfest_part(
			'text-section',
			array(
				'title'      => $brodfest_contact['title'],
				'heading'    => $brodfest_i > 0 ? 'h2' : 'h1',
				'subtitle'   => $brodfest_i > 0,
				'paragraphs' => array( $brodfest_contact['text'] ),
				'email'      => $brodfest_contact['email'],
			)
		);
	}
	?>
</div>
<?php
brodfest_document_close();
