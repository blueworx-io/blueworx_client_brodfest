<?php
/**
 * A titled block of copy — the shape of every text page. Figma: Title – Page
 * then Body, as on Celebrate and Enquiries.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type string   $title      Title text. Omitted for a body-only block.
 *     @type string   $heading    Heading element, 'h1' (default) or 'h2'.
 *     @type string[] $paragraphs Body paragraphs, plain text.
 *     @type string   $email      Optional email address, shown as a link.
 *     @type string   $note       Optional closing line, set slightly apart.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_heading = isset( $args['heading'] ) && 'h2' === $args['heading'] ? 'h2' : 'h1';
?>
<section class="bf-text">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<<?php echo esc_html( $brodfest_heading ); ?> class="bf-title"><?php echo esc_html( $args['title'] ); ?></<?php echo esc_html( $brodfest_heading ); ?>>
	<?php endif; ?>
	<?php foreach ( (array) ( $args['paragraphs'] ?? array() ) as $brodfest_p ) : ?>
		<p class="bf-body"><?php echo brodfest_text( $brodfest_p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
	<?php endforeach; ?>
	<?php if ( ! empty( $args['email'] ) ) : ?>
		<p class="bf-body">
			<?php esc_html_e( 'Email :', 'blueworx-client-brodfest' ); ?>
			<a href="<?php echo esc_url( 'mailto:' . $args['email'] ); ?>"><?php echo esc_html( $args['email'] ); ?></a>
		</p>
	<?php endif; ?>
	<?php if ( ! empty( $args['note'] ) ) : ?>
		<p class="bf-body bf-body--note"><?php echo esc_html( $args['note'] ); ?></p>
	<?php endif; ?>
</section>
