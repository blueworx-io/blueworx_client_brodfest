<?php
/**
 * One accommodation area: its name, banner, intro and hotels.
 * Figma: Section/Area Header, then the area's cards.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type string $name        Area name.
 *     @type string $image       Banner image URL, or '' while still to come.
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
	<h2 class="bf-area__name"><?php echo esc_html( $args['name'] ); ?></h2>
	<div class="bf-banner">
		<?php if ( ! empty( $args['image'] ) ) : ?>
			<img src="<?php echo esc_url( $args['image'] ); ?>" alt="" loading="lazy" />
		<?php else : ?>
			<span class="bf-placeholder-label"><?php echo esc_html( $args['image_label'] ); ?></span>
		<?php endif; ?>
	</div>
	<p class="bf-body bf-area__intro"><?php echo brodfest_text( $args['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
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
