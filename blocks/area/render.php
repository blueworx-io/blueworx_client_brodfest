<?php
/**
 * One accommodation area, drawn on the server: its name, banner, introduction
 * and hotels. Figma: Section/Area Header, then the area's cards.
 *
 * The hotels are a list; with none, only the header is drawn.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_name     = brodfest_attr( $attributes, 'name' );
$brodfest_level    = max( 2, min( 5, (int) ( $attributes['headingLevel'] ?? 2 ) ) );
$brodfest_distance = brodfest_attr( $attributes, 'distance' );
$brodfest_desktop  = brodfest_media_url( $attributes['image'] ?? '', $attributes['imageId'] ?? 0 );
$brodfest_phone    = brodfest_media_url( $attributes['imagePhone'] ?? '', $attributes['imagePhoneId'] ?? 0 );
$brodfest_label    = brodfest_attr( $attributes, 'imageLabel' );
$brodfest_alt      = brodfest_alt( $attributes['imageAlt'] ?? '', ! empty( $attributes['imageDecorative'] ) );
$brodfest_intro    = brodfest_attr( $attributes, 'intro' );

if ( '' === $brodfest_name ) {
	return;
}
?>
<section class="bf-area">
	<div class="bf-area__header">
		<h<?php echo (int) $brodfest_level; ?> class="bf-area__name"><?php echo esc_html( $brodfest_name ); ?></h<?php echo (int) $brodfest_level; ?>>
		<?php if ( '' !== $brodfest_distance ) : ?>
			<p class="bf-body bf-area__distance"><?php echo esc_html( $brodfest_distance ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $brodfest_desktop || '' !== $brodfest_phone || '' !== $brodfest_label ) : ?>
			<div class="bf-banner">
				<?php if ( '' !== $brodfest_desktop && '' !== $brodfest_phone ) : ?>
					<picture>
						<source media="(min-width: 1024px)" srcset="<?php echo esc_url( $brodfest_desktop ); ?>" />
						<img src="<?php echo esc_url( $brodfest_phone ); ?>" alt="<?php echo $brodfest_alt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" loading="lazy" />
					</picture>
				<?php elseif ( '' !== $brodfest_desktop || '' !== $brodfest_phone ) : ?>
					<img src="<?php echo esc_url( '' !== $brodfest_desktop ? $brodfest_desktop : $brodfest_phone ); ?>" alt="<?php echo $brodfest_alt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" loading="lazy" />
				<?php else : ?>
					<span class="bf-placeholder-label"><?php echo esc_html( $brodfest_label ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $brodfest_intro ) : ?>
			<p class="bf-body bf-area__intro"><?php echo brodfest_text( $brodfest_intro ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
		<?php endif; ?>
	</div>
	<?php
	brodfest_print_cards(
		brodfest_list( $attributes, 'hotels' ),
		array(
			/* translators: %s: area name. */
			'label'      => sprintf( __( 'Hotels in %s', 'blueworx-client-brodfest' ), $brodfest_name ),
			'quote_code' => brodfest_attr( $attributes, 'quoteCode' ),
			'level'      => $brodfest_level + 1,
			'book'       => brodfest_attr( $attributes, 'bookLabel' ),
		)
	);
	?>
</section>
