<?php
/**
 * A row of cards. Figma: Card/Hotel on desktop (rows of three, centred, on the
 * banner's margins) and Carousel/Cards – Mobile (one card in view, the next
 * peeking, dots below — carousel.js keeps the dots in step).
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type array  $cards    Each: name, text, url, image.
 *     @type bool   $discount Show the discount code line.
 *     @type string $label    What the row is, for screen readers.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_cards    = (array) ( $args['cards'] ?? array() );
$brodfest_discount = ! empty( $args['discount'] );
$brodfest_label    = (string) ( $args['label'] ?? '' );
?>
<div class="bf-cards" data-bf-carousel>
	<ul class="bf-cards__track" aria-label="<?php echo esc_attr( $brodfest_label ); ?>" data-bf-track>
		<?php foreach ( $brodfest_cards as $brodfest_card ) : ?>
			<li class="bf-card">
				<div class="bf-card__image">
					<?php if ( ! empty( $brodfest_card['image'] ) ) : ?>
						<img src="<?php echo esc_url( $brodfest_card['image'] ); ?>" alt="" loading="lazy" />
					<?php endif; ?>
				</div>
				<h3 class="bf-card__name"><?php echo esc_html( $brodfest_card['name'] ); ?></h3>
				<p class="bf-card__text"><?php echo brodfest_text( $brodfest_card['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
				<a class="bf-button bf-button--card" href="<?php echo esc_url( $brodfest_card['url'] ); ?>">
					<?php esc_html_e( 'BOOK', 'blueworx-client-brodfest' ); ?>
					<span class="sr-only"><?php echo esc_html( $brodfest_card['name'] ); ?></span>
				</a>
				<?php if ( $brodfest_discount ) : ?>
					<p class="bf-card__discount">
						<?php esc_html_e( 'FOR DISCOUNTED RATE QUOTE:', 'blueworx-client-brodfest' ); ?><br />
						<span class="bf-card__code">BRODFEST</span>
					</p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( count( $brodfest_cards ) > 1 ) : ?>
		<div class="bf-cards__dots" aria-hidden="true" data-bf-dots>
			<?php foreach ( $brodfest_cards as $brodfest_i => $brodfest_card ) : ?>
				<span class="bf-cards__dot<?php echo 0 === $brodfest_i ? ' is-active' : ''; ?>"></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
