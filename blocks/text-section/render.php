<?php
/**
 * The text section, drawn on the server. Figma: Title – Page then Body, as on
 * Celebrate and Enquiries.
 *
 * The saved page holds only the words (see block.json); everything about how
 * they look is decided here and in style.css. It prints no WordPress block
 * wrapper, so its markup is the design's own.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_title      = brodfest_attr( $attributes, 'title' );
$brodfest_level      = max( 1, min( 4, (int) ( $attributes['headingLevel'] ?? 1 ) ) );
$brodfest_class      = 'subtitle' === ( $attributes['titleStyle'] ?? 'page' ) ? 'bf-area__name' : 'bf-title';
$brodfest_paragraphs = array_filter(
	array_map( 'strval', array_filter( brodfest_list( $attributes, 'paragraphs' ), 'is_scalar' ) ),
	static function ( $text ) {
		return '' !== trim( $text );
	}
);
$brodfest_email      = brodfest_attr( $attributes, 'email' );
$brodfest_note       = brodfest_attr( $attributes, 'note' );

// A section with nothing in it draws nothing.
if ( '' === $brodfest_title && array() === $brodfest_paragraphs && '' === $brodfest_email && '' === $brodfest_note ) {
	return;
}
?>
<section class="bf-text">
	<?php if ( '' !== $brodfest_title ) : ?>
		<h<?php echo (int) $brodfest_level; ?> class="<?php echo esc_attr( $brodfest_class ); ?>"><?php echo esc_html( $brodfest_title ); ?></h<?php echo (int) $brodfest_level; ?>>
	<?php endif; ?>
	<?php foreach ( $brodfest_paragraphs as $brodfest_p ) : ?>
		<p class="bf-body"><?php echo brodfest_copy( $brodfest_p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(); the links are built from addresses already in it. ?></p>
	<?php endforeach; ?>
	<?php if ( '' !== $brodfest_email && is_email( $brodfest_email ) ) : ?>
		<p class="bf-body">
			<?php esc_html_e( 'Email :', 'blueworx-client-brodfest' ); ?>
			<a href="<?php echo esc_url( 'mailto:' . $brodfest_email ); ?>"><?php echo esc_html( $brodfest_email ); ?></a>
		</p>
	<?php endif; ?>
	<?php if ( '' !== $brodfest_note ) : ?>
		<p class="bf-body bf-body--note"><?php echo esc_html( $brodfest_note ); ?></p>
	<?php endif; ?>
</section>
