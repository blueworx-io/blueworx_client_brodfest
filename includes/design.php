<?php
/**
 * Brodfest on the theme's design screen (BlueWorx › Design).
 *
 * The Enhance theme holds the site's colours and fonts; the plugin's styles
 * read them. This adds Brodfest's two typefaces to the theme's font list and
 * a preset that sets every choice to the design in one click. Without the
 * theme these filters never run and the plugin uses the same values as
 * fallbacks.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds Futura PT and Aller to the theme's fonts.
 *
 * No files: the client's Adobe Fonts kit, which the plugin already loads,
 * supplies them, and Adobe licenses them for the web only through its host.
 *
 * @param array $fonts Fonts by id.
 * @return array
 */
function brodfest_design_fonts( $fonts ) {
	$fonts['futura-pt'] = array(
		'label'  => 'Futura PT',
		'family' => 'futura-pt',
		'stack'  => '"futura-pt", "Jost", "Futura", sans-serif',
		'files'  => array(),
	);

	$fonts['aller'] = array(
		'label'  => 'Aller',
		'family' => 'aller',
		'stack'  => '"aller", "Lato", sans-serif',
		'files'  => array(),
	);

	return $fonts;
}
add_filter( 'blueworx_design_fonts', 'brodfest_design_fonts' );

/**
 * Offers the Brodfest preset: the design's colours and fonts.
 *
 * @param array $presets Presets by id.
 * @return array
 */
function brodfest_design_presets( $presets ) {
	$presets['brodfest'] = array(
		'label'       => 'Brodfest',
		'description' => __( 'Brodfest Ibiza: gold on near-black, Futura PT and Aller.', 'blueworx-client-brodfest' ),
		'settings'    => array(
			'primary'      => '#d1954e',
			'accent'       => '#a24300',
			'background'   => '#0b0301',
			'text'         => '#d1954e',
			'heading_font' => 'futura-pt',
			'body_font'    => 'aller',
		),
	);

	return $presets;
}
add_filter( 'blueworx_design_presets', 'brodfest_design_presets' );
