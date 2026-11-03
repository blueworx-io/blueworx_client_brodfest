<?php
/**
 * Enquiries. Figma: STD – Enquiries and Invite – Enquiries, which are the same.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

brodfest_document_open();
?>
<h1 class="sr-only"><?php esc_html_e( 'Enquiries', 'blueworx-client-brodfest' ); ?></h1>
<div class="bf-stack">
	<?php
	foreach ( brodfest_enquiry_contacts() as $brodfest_contact ) {
		brodfest_part(
			'text-section',
			array(
				'title'      => $brodfest_contact['title'],
				'heading'    => 'h2',
				'paragraphs' => array( $brodfest_contact['text'] ),
				'email'      => $brodfest_contact['email'],
			)
		);
	}
	?>
</div>
<?php
brodfest_document_close();
