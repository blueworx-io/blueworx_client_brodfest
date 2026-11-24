<?php
/**
 * The site header and the menu it opens, drawn on the server on every page.
 *
 * Figma: Header (Device × Menu) and Menu/Panel (Site). The header hides as the
 * visitor scrolls down and comes back as they scroll up — see header.js.
 * On the page that holds the RSVP form it shows a close button instead of the
 * menu, as the design draws it.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_slug  = (string) brodfest_document( 'slug' );
$brodfest_close = (bool) brodfest_document( 'close' );
$brodfest_rsvp  = brodfest_attr( $attributes, 'rsvpLabel', 'RSVP' );
?>
<header class="bf-header" data-bf-header>
	<?php if ( $brodfest_close ) : ?>
		<a class="bf-header__toggle" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>" data-bf-close>
			<?php brodfest_inline_svg( 'icon-close' ); ?>
			<span class="sr-only"><?php echo esc_html( brodfest_attr( $attributes, 'closeLabel', __( 'Close', 'blueworx-client-brodfest' ) ) ); ?></span>
		</a>
	<?php else : ?>
		<button class="bf-header__toggle" type="button" aria-expanded="false" aria-controls="bf-menu" data-bf-menu-toggle>
			<span class="bf-header__icon bf-header__icon--menu"><?php brodfest_inline_svg( 'icon-menu' ); ?></span>
			<span class="bf-header__icon bf-header__icon--close"><?php brodfest_inline_svg( 'icon-close' ); ?></span>
			<span class="sr-only"><?php echo esc_html( brodfest_attr( $attributes, 'menuLabel', __( 'Menu', 'blueworx-client-brodfest' ) ) ); ?></span>
		</button>
	<?php endif; ?>

	<a class="bf-header__logo" href="<?php echo esc_url( brodfest_page_url( 'home' ) ); ?>">
		<?php brodfest_inline_svg( 'logo', brodfest_attr( $attributes, 'homeLabel', __( 'Brodfest Ibiza — home', 'blueworx-client-brodfest' ) ) ); ?>
	</a>

	<a class="bf-pill<?php echo 'rsvp' === $brodfest_slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( brodfest_page_url( 'rsvp' ) ); ?>"<?php echo 'rsvp' === $brodfest_slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $brodfest_rsvp ); ?></a>
</header>

<?php if ( ! $brodfest_close ) : ?>
<nav class="bf-menu" id="bf-menu" aria-label="<?php echo esc_attr( brodfest_attr( $attributes, 'navLabel', __( 'Main', 'blueworx-client-brodfest' ) ) ); ?>" hidden data-bf-menu>
	<span class="bf-menu__divider" aria-hidden="true"></span>
	<ul class="bf-menu__items">
		<?php foreach ( brodfest_menu() as $brodfest_i => $brodfest_item ) : ?>
			<li style="--i: <?php echo (int) $brodfest_i; ?>">
				<a href="<?php echo esc_url( brodfest_page_url( $brodfest_item ) ); ?>"<?php echo $brodfest_item === $brodfest_slug ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( strtoupper( brodfest_menu_label( $brodfest_item ) ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
<?php endif; ?>
