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
			'background'   => '#0b0603',
			'text'         => '#d1954e',
			'heading_font' => 'futura-pt',
			'body_font'    => 'aller',
		),
	);

	return $presets;
}
add_filter( 'blueworx_design_presets', 'brodfest_design_presets' );

/**
 * Moves a site still on the old Brodfest page colour to the new one (client,
 * 6 Nov 2026), so the change needs no visit to the Design screen. Only the
 * old preset value is replaced, so a colour someone chose by hand is kept.
 * Runs once.
 *
 * @return void
 */
function brodfest_design_migrate() {
	if ( (int) get_option( 'brodfest_design_migrated', 0 ) >= 1 ) {
		return;
	}

	$design = get_option( 'blueworx_design' );
	if ( is_array( $design ) && isset( $design['background'] ) && '#0b0301' === strtolower( (string) $design['background'] ) ) {
		$design['background'] = '#0b0603';
		update_option( 'blueworx_design', $design );
	}

	update_option( 'brodfest_design_migrated', 1 );
}
add_action( 'init', 'brodfest_design_migrate' );
