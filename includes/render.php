<?php
/**
 * Full-document rendering for the plugin's own pages.
 *
 * The Enhance theme draws no public pages of its own — client plugins do. So
 * this plugin renders the whole document for its pages, and the design is
 * the same whichever theme is active (the CI test site runs without Enhance).
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this plugin — rather than the theme — turned title-tag support on.
 *
 * @var bool
 */
$GLOBALS['brodfest_added_title_tag'] = false;

/**
 * Declares title-tag support so an owned page always emits a <title>.
 *
 * Enhance declares it already; this covers any other theme. It must happen
 * before `wp_loaded`, long before the query says whose page this is, so it is
 * declared early and withdrawn on requests that turn out not to be ours.
 *
 * @return void
 */
function brodfest_ensure_title_tag_support() {
	if ( current_theme_supports( 'title-tag' ) ) {
		return;
	}

	add_theme_support( 'title-tag' );
	$GLOBALS['brodfest_added_title_tag'] = true;
}
add_action( 'after_setup_theme', 'brodfest_ensure_title_tag_support', 99 );

/**
 * Withdraws that support again on pages this plugin does not own, so a theme
 * printing its own <title> never gets two. Only removes what this plugin added.
 *
 * @return void
 */
function brodfest_scope_title_tag_support() {
	if ( ! $GLOBALS['brodfest_added_title_tag'] || brodfest_is_owned_request() ) {
		return;
	}

	remove_theme_support( 'title-tag' );
	$GLOBALS['brodfest_added_title_tag'] = false;
}
add_action( 'wp', 'brodfest_scope_title_tag_support' );

/**
 * Takes over rendering for pages this plugin owns.
 *
 * @param string $template Template path WordPress resolved.
 * @return string Template path to use.
 */
function brodfest_template_include( $template ) {
	if ( ! is_singular( 'page' ) ) {
		return $template;
	}

	$slug = brodfest_page_slug( get_queried_object_id() );

	if ( '' === $slug ) {
		return $template;
	}

	$path = BRODFEST_DIR . 'templates/' . brodfest_pages()[ $slug ]['template'];

	return file_exists( $path ) ? $path : $template;
}
add_filter( 'template_include', 'brodfest_template_include' );

/**
 * Opens a complete HTML document for a plugin-rendered page, with the site
 * header.
 *
 * Deliberately does not call get_header(). wp_head() and wp_footer() still run
 * so other plugins, the admin bar and SEO output keep working.
 *
 * @param array $args Optional. 'body_class' => string; 'close' => bool, to show
 *                    the header's close button in place of the menu, as the
 *                    RSVP page does; 'opening' => bool, for the home page's
 *                    first-visit opening, where the logo draws itself and Enter
 *                    starts the video with sound.
 * @return void
 */
function brodfest_document_open( $args = array() ) {
	$body_class = isset( $args['body_class'] ) ? (string) $args['body_class'] : '';
	$slug       = brodfest_page_slug( get_queried_object_id() );
	$classes    = 'brodfest brodfest-site-' . brodfest_site();

	if ( '' !== $slug ) {
		$classes .= ' brodfest-page-' . sanitize_html_class( $slug );
	}

	if ( '' !== $body_class ) {
		$classes .= ' ' . $body_class;
	}
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="theme-color" content="#0b0603" />
	<?php
	// Motion (animations.js) only for visitors who have not asked for less. The
	// content waits, hidden, until the script has set every section's starting
	// point, and shows anyway after 3 seconds should the script never run.
	?>
	<script>try { if ( ! matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { document.documentElement.classList.add( 'bf-motion' ); setTimeout( function () { document.documentElement.classList.add( 'bf-motion-ready' ); }, 3000 ); } } catch ( e ) {}</script>
	<?php wp_head(); ?>
	<?php
	// A phone shows the 767-wide mobile design zoomed to fit, at Figma's
	// sizes. After wp_head(), and on every viewport tag, since WordPress (for a
	// block theme) or another plugin may print one of its own there.
	?>
	<script>if ( screen.width < 767 ) { document.querySelectorAll( 'meta[name="viewport"]' ).forEach( function ( m ) { m.setAttribute( 'content', 'width=767' ); } ); }</script>
	<?php if ( ! empty( $args['opening'] ) ) : ?>
		<?php
		// Decided before the page first paints, so a first-time visitor sees
		// black, never a flash of the page; a returning one, or anyone who asks
		// for less motion, never sees it.
		?>
	<script>try { if ( localStorage.getItem( 'brodfest-entered' ) !== '1' && ! matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { document.documentElement.classList.add( 'bf-is-opening' ); } } catch ( e ) { document.documentElement.classList.add( 'bf-is-opening' ); }</script>
	<?php endif; ?>
</head>
<body <?php body_class( $classes ); ?>>
	<?php wp_body_open(); ?>
	<?php if ( ! empty( $args['opening'] ) ) : ?>
	<div class="bf-opening" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Welcome to Brodfest', 'blueworx-client-brodfest' ); ?>" data-bf-opening>
		<span class="bf-opening__logo">
			<?php
			// Each letter's outline is measured as 1, so the drawing runs the
			// same length whatever the letter's size.
			ob_start();
			brodfest_inline_svg( 'logo', 'Brodfest Ibiza' );
			echo str_replace( '<path ', '<path pathLength="1" ', (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG, from brodfest_inline_svg().
			?>
		</span>
		<button class="bf-opening__enter bf-pill" type="button" data-bf-enter><?php esc_html_e( 'ENTER', 'blueworx-client-brodfest' ); ?></button>
	</div>
	<?php endif; ?>
	<a class="bf-skip" href="#content"><?php echo esc_html__( 'Skip to the content', 'blueworx-client-brodfest' ); ?></a>
	<?php
	brodfest_part(
		'header',
		array(
			'slug'  => $slug,
			'close' => ! empty( $args['close'] ),
		)
	);
	?>
	<main id="content" class="bf-main">
	<?php
}

/**
 * Closes the document opened by brodfest_document_open(), with the site
 * footer.
 *
 * @return void
 */
function brodfest_document_close() {
	?>
	</main>
	<?php
	brodfest_part( 'footer' );
	wp_footer();
	?>
</body>
</html>
	<?php
}

/**
 * Loads a template part from templates/parts/.
 *
 * @param string $name Part name, without the .php extension.
 * @param array  $args Variables made available to the part as $args.
 * @return void
 */
function brodfest_part( $name, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $args is read by the included part, which phpcs cannot see.
	// A part name reaches an include(), so it is reduced to a bare filename
	// rather than trusted.
	$name = basename( (string) $name, '.php' );
	$path = BRODFEST_DIR . 'templates/parts/' . $name . '.php';

	if ( ! file_exists( $path ) ) {
		return;
	}

	include $path;
}

/**
 * The URL of a file in assets/img/.
 *
 * @param string $file File name inside assets/img/.
 * @return string
 */
function brodfest_img( $file ) {
	return BRODFEST_URL . 'assets/img/' . ltrim( (string) $file, '/' );
}

/**
 * Escapes copy for the page, keeping its desktop line breaks.
 *
 * A newline in the copy is a line break at full desktop size only, and "-" before a
 * newline a hyphenated break; on a phone the text wraps on its own. This is
 * how the placeholder copy carries the Figma file's line breaks.
 *
 * @param string $text Plain text.
 * @return string HTML.
 */
function brodfest_text( $text ) {
	$html = esc_html( (string) $text );
	$html = str_replace( "-\n", '<span class="bf-hy">-<br /></span>', $html );

	return str_replace( "\n", ' <br class="bf-br" />', $html );
}

/**
 * Turns each email address in escaped copy into a bold mailto link (client,
 * 6 Nov 2026).
 *
 * @param string $html Copy already escaped by brodfest_text().
 * @return string
 */
function brodfest_link_emails( $html ) {
	return (string) preg_replace_callback(
		'/[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}/',
		static function ( $m ) {
			return '<a class="bf-email" href="' . esc_url( 'mailto:' . $m[0] ) . '">' . $m[0] . '</a>';
		},
		$html
	);
}

/**
 * Prints one of the plugin's own SVGs inline, so CSS can animate its parts.
 *
 * Only ever reads files this plugin ships in assets/img/, by bare name.
 *
 * @param string $name File name without .svg.
 * @param string $label Accessible name, or '' when the SVG is decorative.
 * @return void
 */
function brodfest_inline_svg( $name, $label = '' ) {
	$path = BRODFEST_DIR . 'assets/img/' . basename( (string) $name, '.svg' ) . '.svg';

	if ( ! file_exists( $path ) ) {
		return;
	}

	$attrs = '' === $label ? 'aria-hidden="true" focusable="false"' : 'role="img" aria-label="' . esc_attr( $label ) . '"';
	$svg   = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file the plugin ships, not a remote request.

	echo preg_replace( '/^<svg /', '<svg ' . $attrs . ' ', trim( $svg ), 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG file; the only interpolation is escaped above.
}
