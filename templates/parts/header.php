<?php
/**
 * Site header and the menu it opens, on every page.
 *
 * Figma: Header (Device × Menu) and Menu/Panel (Site). The header hides as the
 * visitor scrolls down and comes back as they scroll up — see header.js.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type string $slug  The owned page being shown.
 *     @type bool   $close Show a close button (back to home) instead of the menu.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_slug  = isset( $args['slug'] ) ? (string) $args['slug'] : '';
$brodfest_close = ! empty( $args['close'] );
$brodfest_pages = brodfest_pages();
?>
<header class="bf-header" data-bf-header>
	<?php if ( $brodfest_close ) : ?>
		<a class="bf-header__toggle" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>" data-bf-close>
			<?php brodfest_inline_svg( 'icon-close' ); ?>
			<span class="sr-only"><?php esc_html_e( 'Close', 'blueworx-client-brodfest' ); ?></span>
		</a>
	<?php else : ?>
		<button class="bf-header__toggle" type="button" aria-expanded="false" aria-controls="bf-menu" data-bf-menu-toggle>
			<span class="bf-header__icon bf-header__icon--menu"><?php brodfest_inline_svg( 'icon-menu' ); ?></span>
			<span class="bf-header__icon bf-header__icon--close"><?php brodfest_inline_svg( 'icon-close' ); ?></span>
			<span class="sr-only"><?php esc_html_e( 'Menu', 'blueworx-client-brodfest' ); ?></span>
		</button>
	<?php endif; ?>

	<a class="bf-header__logo" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>">
		<?php brodfest_inline_svg( 'logo', __( 'Brodfest Ibiza — home', 'blueworx-client-brodfest' ) ); ?>
	</a>

	<a class="bf-pill<?php echo 'rsvp' === $brodfest_slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( brodfest_page_url( 'rsvp' ) ); ?>"<?php echo 'rsvp' === $brodfest_slug ? ' aria-current="page"' : ''; ?>>RSVP</a>
</header>

<?php if ( ! $brodfest_close ) : ?>
<nav class="bf-menu" id="bf-menu" aria-label="<?php esc_attr_e( 'Main', 'blueworx-client-brodfest' ); ?>" hidden data-bf-menu>
	<span class="bf-menu__divider" aria-hidden="true"></span>
	<ul class="bf-menu__items">
		<?php foreach ( brodfest_menu() as $brodfest_i => $brodfest_item ) : ?>
			<li style="--i: <?php echo (int) $brodfest_i; ?>">
				<a href="<?php echo esc_url( brodfest_page_url( $brodfest_item ) ); ?>"<?php echo $brodfest_item === $brodfest_slug ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( strtoupper( $brodfest_pages[ $brodfest_item ]['title'] ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<span class="bf-menu__mountain"><?php brodfest_inline_svg( 'mountain' ); ?></span>
</nav>
<?php endif; ?>
