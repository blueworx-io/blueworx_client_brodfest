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
 * @param array $args Optional. 'body_class' => string.
 * @return void
 */
function brodfest_document_open( $args = array() ) {
	$body_class = isset( $args['body_class'] ) ? (string) $args['body_class'] : '';
	$slug       = brodfest_page_slug( get_queried_object_id() );
	$classes    = 'brodfest';

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
	<?php wp_head(); ?>
</head>
<body <?php body_class( $classes ); ?>>
	<?php wp_body_open(); ?>
	<a class="sr-only focus:not-sr-only" href="#content"><?php echo esc_html__( 'Skip to the content', 'blueworx-client-brodfest' ); ?></a>
	<?php
	brodfest_part( 'header' );
	?>
	<main id="content">
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
