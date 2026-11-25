<?php
/**
 * The section blocks: registration and the helpers their render files share.
 *
 * Every section of the site is a block in `blocks/<name>/` — a block.json
 * (namespaced `brodfest/`), a render.php that draws it on the server and an
 * edit.js. The saved page holds only a block's name and its attributes; the
 * markup and styling live here, so fixing a section once fixes every page.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prefix for an image kept in the plugin itself, e.g. `plugin:assets/img/map.png`.
 *
 * The starting content points at the images the plugin ships by this token
 * rather than by a full address, so it survives a change of site address. An
 * image an editor picks from the Media Library is an ordinary address.
 */
const BRODFEST_PLUGIN_IMAGE = 'plugin:';

/**
 * Registers the shared editor script and every block in blocks/.
 *
 * @return void
 */
function brodfest_register_blocks() {
	$helpers = require BRODFEST_DIR . 'blocks/_shared/editor.asset.php';

	wp_register_script(
		'brodfest-block-editor',
		BRODFEST_URL . 'blocks/_shared/editor.js',
		$helpers['dependencies'],
		$helpers['version'],
		true
	);

	wp_add_inline_script(
		'brodfest-block-editor',
		'window.brodfestBlocks = ' . wp_json_encode(
			array(
				'pluginUrl'   => BRODFEST_URL,
				'pluginToken' => BRODFEST_PLUGIN_IMAGE,
			)
		) . ';',
		'before'
	);

	foreach ( (array) glob( BRODFEST_DIR . 'blocks/*/block.json' ) as $file ) {
		register_block_type( dirname( $file ) );
	}
}
add_action( 'init', 'brodfest_register_blocks', 9 );

/**
 * The blocks the plugin draws around every page itself (not part of a page's
 * own sections): they cannot be shown or hidden.
 *
 * @return string[]
 */
function brodfest_global_blocks() {
	return array( 'brodfest/site-header', 'brodfest/site-footer' );
}

/**
 * Gives every section block a "hidden" setting, which a client may change on a
 * locked page: a hidden section stays on the page, and in its place in the
 * list, but visitors do not see it.
 *
 * @param array $metadata The block's block.json, as an array.
 * @return array
 */
function brodfest_block_visibility_attribute( $metadata ) {
	$name = (string) ( $metadata['name'] ?? '' );

	if ( 0 === strpos( $name, 'brodfest/' ) && ! in_array( $name, brodfest_global_blocks(), true ) ) {
		$metadata['attributes']['hidden'] = array(
			'type'    => 'boolean',
			'default' => false,
		);
	}

	return $metadata;
}
add_filter( 'block_type_metadata', 'brodfest_block_visibility_attribute' );

/**
 * Whether a parsed block is a section that has been hidden.
 *
 * @param array $block A block as parse_blocks() gives it.
 * @return bool
 */
function brodfest_block_is_hidden( $block ) {
	$name = (string) ( $block['blockName'] ?? '' );

	return 0 === strpos( $name, 'brodfest/' ) && ! in_array( $name, brodfest_global_blocks(), true ) && ! empty( $block['attrs']['hidden'] );
}

/**
 * Draws nothing for a hidden section (and so nothing inside it).
 *
 * @param string|null $pre   What a filter has drawn already, or null.
 * @param array       $block The block being drawn.
 * @return string|null
 */
function brodfest_skip_hidden_section( $pre, $block ) {
	return null === $pre && brodfest_block_is_hidden( $block ) ? '' : $pre;
}
add_filter( 'pre_render_block', 'brodfest_skip_hidden_section', 10, 2 );

/**
 * Whether serialised blocks hold a section of this name that visitors will see:
 * one that is not hidden, and not inside one that is.
 *
 * @param string $name    Block name, with the namespace.
 * @param string $content Serialised blocks.
 * @return bool
 */
function brodfest_has_visible_block( $name, $content ) {
	$seen   = array();
	$search = static function ( $blocks ) use ( &$search, &$seen, $name ) {
		foreach ( $blocks as $block ) {
			if ( brodfest_block_is_hidden( $block ) ) {
				continue;
			}

			// A synced pattern is only a reference on the page: look inside it, once.
			if ( 'core/block' === ( $block['blockName'] ?? '' ) ) {
				$ref     = (int) ( $block['attrs']['ref'] ?? 0 );
				$pattern = $ref > 0 && ! isset( $seen[ $ref ] ) ? get_post( $ref ) : null;

				$seen[ $ref ] = true;

				if ( $pattern && 'wp_block' === $pattern->post_type && 'publish' === $pattern->post_status && '' === $pattern->post_password && $search( parse_blocks( $pattern->post_content ) ) ) {
					return true;
				}
			}

			if ( ( $block['blockName'] ?? '' ) === $name || $search( (array) ( $block['innerBlocks'] ?? array() ) ) ) {
				return true;
			}
		}

		return false;
	};

	return $search( parse_blocks( (string) $content ) );
}

/**
 * Gives the section blocks their own group in the inserter.
 *
 * @param array $categories Block categories.
 * @return array
 */
function brodfest_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'brodfest',
			'title' => __( 'Brodfest sections', 'blueworx-client-brodfest' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'brodfest_block_category' );

/**
 * One attribute as a string, or the fallback when it is missing or not text.
 *
 * @param array  $attributes The block's attributes.
 * @param string $key        Attribute name.
 * @param string $fallback   Used when the attribute is empty.
 * @return string
 */
function brodfest_attr( $attributes, $key, $fallback = '' ) {
	$value = isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ? trim( (string) $attributes[ $key ] ) : '';

	return '' !== $value ? $value : (string) $fallback;
}

/**
 * One list attribute, as an array of items (each item an array), dropping
 * anything that is not one. An empty list gives an empty array, which the
 * block's render file uses to hide itself cleanly.
 *
 * @param array  $attributes The block's attributes.
 * @param string $key        Attribute name.
 * @return array<int, mixed>
 */
function brodfest_list( $attributes, $key ) {
	return isset( $attributes[ $key ] ) && is_array( $attributes[ $key ] ) ? array_values( $attributes[ $key ] ) : array();
}

/**
 * The address of an image or file attribute.
 *
 * An image picked from the Media Library is followed by its id, so it still
 * resolves if the site's address changes; the plugin's own images are written
 * as `plugin:path`.
 *
 * @param string $src Address, or `plugin:` path, or ''.
 * @param int    $id  Media Library id, or 0.
 * @return string URL, or '' for none.
 */
function brodfest_media_url( $src, $id = 0 ) {
	$id = (int) $id;

	if ( $id > 0 ) {
		$url = wp_get_attachment_url( $id );

		if ( $url ) {
			return (string) $url;
		}
	}

	$src = trim( (string) $src );

	if ( 0 === strpos( $src, BRODFEST_PLUGIN_IMAGE ) ) {
		// Only ever a file inside the plugin: no climbing out of it.
		$path = ltrim( substr( $src, strlen( BRODFEST_PLUGIN_IMAGE ) ), '/' );

		return false !== strpos( $path, '..' ) ? '' : BRODFEST_URL . $path;
	}

	return $src;
}

/**
 * The alt text for an image: nothing when it is marked decorative.
 *
 * @param string $alt        The alt text field.
 * @param bool   $decorative Whether the image is decorative.
 * @return string Already escaped for an attribute.
 */
function brodfest_alt( $alt, $decorative = false ) {
	return $decorative ? '' : esc_attr( trim( (string) $alt ) );
}

/**
 * Copy for the page, with its desktop line breaks (see brodfest_text()) and
 * any email address turned into a link.
 *
 * @param string $text Plain text.
 * @return string HTML, escaped.
 */
function brodfest_copy( $text ) {
	return brodfest_link_emails( brodfest_text( $text ) );
}

/**
 * Prints a row of cards: the hotels of an area, or the places of a category.
 * Figma: Card/Hotel on desktop (rows of three, centred, on the banner's
 * margins) and Carousel/Cards – Mobile (one card in view, the next peeking,
 * dots below — carousel.js keeps the dots in step). An empty list prints
 * nothing.
 *
 * @param array $cards Each card: name, text, url, image, imageId, imageAlt,
 *                     imageDecorative, and optionally offer, that hotel's own
 *                     offer line.
 * @param array $args  {
 *     Optional. How the row is drawn.
 *
 *     @type string $label      What the row is, for screen readers.
 *     @type string $quote_code Show "quote this code" under cards with no offer.
 *     @type int    $level      Heading level of each card's name.
 *     @type string $book       The button's words.
 * }
 * @return void
 */
function brodfest_print_cards( $cards, $args = array() ) {
	$cards = array_values(
		array_filter(
			(array) $cards,
			static function ( $card ) {
				return is_array( $card ) && '' !== trim( (string) ( $card['name'] ?? '' ) );
			}
		)
	);

	if ( array() === $cards ) {
		return;
	}

	$label = (string) ( $args['label'] ?? '' );
	$code  = (string) ( $args['quote_code'] ?? '' );
	$level = max( 2, min( 6, (int) ( $args['level'] ?? 3 ) ) );
	$book  = '' !== trim( (string) ( $args['book'] ?? '' ) ) ? (string) $args['book'] : __( 'BOOK', 'blueworx-client-brodfest' );
	?>
	<div class="bf-cards" data-bf-carousel>
		<ul class="bf-cards__track" aria-label="<?php echo esc_attr( $label ); ?>" data-bf-track>
			<?php foreach ( $cards as $card ) : ?>
				<?php
				$image = brodfest_media_url( $card['image'] ?? '', $card['imageId'] ?? 0 );
				$name  = (string) $card['name'];
				$offer = trim( (string) ( $card['offer'] ?? '' ) );
				?>
				<li class="bf-card">
					<div class="bf-card__image">
						<?php if ( '' !== $image ) : ?>
							<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo brodfest_alt( $card['imageAlt'] ?? '', ! empty( $card['imageDecorative'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_alt(). ?>" loading="lazy" />
						<?php endif; ?>
					</div>
					<h<?php echo (int) $level; ?> class="bf-card__name"><?php echo esc_html( $name ); ?></h<?php echo (int) $level; ?>>
					<p class="bf-card__text"><?php echo brodfest_text( (string) ( $card['text'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by brodfest_text(). ?></p>
					<?php if ( '' !== trim( (string) ( $card['url'] ?? '' ) ) ) : ?>
						<a class="bf-button bf-button--card" href="<?php echo esc_url( (string) $card['url'] ); ?>">
							<?php echo esc_html( $book ); ?>
							<span class="sr-only"><?php echo esc_html( $name ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $offer ) : ?>
						<p class="bf-card__discount bf-card__discount--offer"><?php echo esc_html( $offer ); ?></p>
					<?php elseif ( '' !== $code ) : ?>
						<p class="bf-card__discount">
							<?php esc_html_e( 'FOR DISCOUNTED RATE QUOTE:', 'blueworx-client-brodfest' ); ?><br />
							<span class="bf-card__code"><?php echo esc_html( $code ); ?></span>
						</p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( count( $cards ) > 1 ) : ?>
			<div class="bf-cards__dots" aria-hidden="true" data-bf-dots>
				<?php foreach ( $cards as $i => $card ) : ?>
					<span class="bf-cards__dot<?php echo 0 === $i ? ' is-active' : ''; ?>"></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
