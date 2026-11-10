<?php
/**
 * One accommodation area: its name, banner, intro and hotels.
 * Figma: Section/Area Header, then the area's cards.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type string $name        Area name.
 *     @type string $distance    How far it is from Brodfest, on the line under
 *                               the name; optional.
 *     @type string $image       Banner image URL, or '' while still to come.
 *     @type string $image_phone The mobile design's cut of it; optional.
 *     @type string $image_label Placeholder wording for the missing banner.
 *     @type string $intro       Area introduction.
 *     @type array  $cards       Hotels.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="bf-area">
	<div class="bf-area__header">
		<h2 class="bf-area__name"><?php echo esc_html( $args['name'] ); ?></h2>
		<?php if ( ! empty( $args['distance'] ) ) : ?>
			<p class="bf-body bf-area__distance"><?php echo esc_html( $args['distance'] ); ?></p>
		<?php endif; ?>
		<div class="bf-banner">
			<?php if ( ! empty( $args['image'] ) && ! empty( $args['image_phone'] ) ) : ?>
				<picture>
					<source media="(min-width: 1024px)" srcset="<?php echo esc_url( $args['image'] ); ?>" />
					<img src="<?php echo esc_url( $args['image_phone'] ); ?>" alt="" loading="lazy" />
				</picture>
			<?php elseif ( ! empty( $args['image'] ) ) : ?>
				<img src="<?php echo esc_url( $args['image'] ); ?>" alt="" loading="lazy" />
			<?php else : ?>
				<span class="bf-placeholder-label"><?php echo esc_html( $args['image_label'] ); ?></span>
			<?php endif; ?>
		</div>
		<p class="bf-body bf-area__intro"><?php echo brodfest_text( $args['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
	</div>
	<?php
	brodfest_part(
		'cards',
		array(
			'cards'    => $args['cards'],
			'discount' => true,
			/* translators: %s: area name. */
			'label'    => sprintf( __( 'Hotels in %s', 'blueworx-client-brodfest' ), $args['name'] ),
		)
	);
	?>
</section>
