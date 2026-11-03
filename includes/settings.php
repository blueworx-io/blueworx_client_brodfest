<?php
/**
 * The site owner's settings: which site is live, the two videos, and who hears
 * about RSVPs.
 *
 * Declared as a field schema and drawn by the shared page editor library, so
 * the screen is the same one every BlueWorx plugin has. Page content is not
 * here: it moves into the block editor in a later release.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_SETTINGS_SLUG   = 'brodfest';
const BRODFEST_SETTINGS_OPTION = 'brodfest_settings';

/**
 * One saved setting, or the default.
 *
 * @param string $key     Field id.
 * @param mixed  $fallback Value when nothing is saved.
 * @return mixed
 */
function brodfest_setting( $key, $fallback = '' ) {
	$saved = get_option( BRODFEST_SETTINGS_OPTION, array() );

	return is_array( $saved ) && isset( $saved[ $key ] ) && '' !== $saved[ $key ] ? $saved[ $key ] : $fallback;
}

/**
 * The settings screen's schema.
 *
 * @return array
 */
function brodfest_settings_screen() {
	return array(
		'slug'        => BRODFEST_SETTINGS_SLUG,
		'title'       => __( 'Brodfest', 'blueworx-client-brodfest' ),
		'menu_title'  => __( 'Brodfest', 'blueworx-client-brodfest' ),
		'icon'        => 'dashicons-calendar-alt',
		'eyebrow'     => __( 'Website', 'blueworx-client-brodfest' ),
		'lede'        => __( 'Which site visitors see, and the videos on its home page.', 'blueworx-client-brodfest' ),
		'capability'  => 'manage_options',
		'store'       => 'option',
		'option_name' => BRODFEST_SETTINGS_OPTION,
		'tabs'        => array(
			array(
				'id'     => 'site',
				'label'  => __( 'Site', 'blueworx-client-brodfest' ),
				'panels' => array(
					array(
						'id'     => 'live',
						'title'  => __( 'Live site', 'blueworx-client-brodfest' ),
						'fields' => array(
							array(
								'id'      => 'site',
								'kind'    => 'radio',
								'label'   => __( 'Visitors see', 'blueworx-client-brodfest' ),
								'default' => BRODFEST_SITE_STD,
								'options' => array(
									array(
										'value' => BRODFEST_SITE_STD,
										'label' => __( 'Save the Date', 'blueworx-client-brodfest' ),
									),
									array(
										'value' => BRODFEST_SITE_INVITE,
										'label' => __( 'Invite — adds the Can Sakhara Story and Recommendations pages', 'blueworx-client-brodfest' ),
									),
								),
							),
							array(
								'id'    => 'preview_invite',
								'kind'  => 'link',
								'label' => __( 'Preview the Invite before switching', 'blueworx-client-brodfest' ),
								'url'   => add_query_arg( BRODFEST_PREVIEW_ARG, BRODFEST_SITE_INVITE, home_url( '/' ) ),
							),
						),
					),
					array(
						'id'     => 'videos',
						'title'  => __( 'Home page videos', 'blueworx-client-brodfest' ),
						'note'   => __( 'Shown 1920 × 840 on desktop and square on mobile. Plays once with sound when a visitor first arrives, then offers a replay button.', 'blueworx-client-brodfest' ),
						'fields' => array(
							array(
								'id'    => 'video_std',
								'kind'  => 'file',
								'label' => __( 'Save the Date video', 'blueworx-client-brodfest' ),
							),
							array(
								'id'    => 'video_invite',
								'kind'  => 'file',
								'label' => __( 'Invite video', 'blueworx-client-brodfest' ),
							),
						),
					),
					array(
						'id'     => 'replies',
						'title'  => __( 'RSVP replies', 'blueworx-client-brodfest' ),
						'fields' => array(
							array(
								'id'     => 'rsvp_email',
								'kind'   => 'text',
								'format' => 'email',
								'label'  => __( 'Email each reply to', 'blueworx-client-brodfest' ),
								'help'   => __( 'Leave empty to use the site admin address. Every reply is also kept under RSVPs.', 'blueworx-client-brodfest' ),
							),
						),
					),
				),
			),
		),
	);
}

/**
 * Hands the schema to the page editor library.
 *
 * @return void
 */
function brodfest_register_settings_screen() {
	if ( class_exists( '\Blueworx\PageEditor\v1\Editor' ) ) {
		\Blueworx\PageEditor\v1\Editor::register( brodfest_settings_screen() );
	}
}
add_action( 'init', 'brodfest_register_settings_screen', 20 );

/**
 * The live site comes from the settings screen.
 *
 * @return string
 */
function brodfest_saved_site() {
	return (string) brodfest_setting( 'site', BRODFEST_SITE_STD );
}

/**
 * The saved video for a site, as a URL.
 *
 * @param string $url  URL another filter gave, or ''.
 * @param string $site Which site.
 * @return string
 */
function brodfest_saved_video( $url, $site ) {
	$id = (int) brodfest_setting( BRODFEST_SITE_INVITE === $site ? 'video_invite' : 'video_std', 0 );

	if ( '' !== $url || $id <= 0 ) {
		return $url;
	}

	return (string) wp_get_attachment_url( $id );
}
add_filter( 'brodfest_hero_video', 'brodfest_saved_video', 5, 2 );

/**
 * RSVP emails go to the saved address when there is one.
 *
 * @param string $to Default address.
 * @return string
 */
function brodfest_saved_rsvp_email( $to ) {
	$saved = sanitize_email( (string) brodfest_setting( 'rsvp_email', '' ) );

	return '' !== $saved ? $saved : $to;
}
add_filter( 'brodfest_rsvp_notify', 'brodfest_saved_rsvp_email', 5 );
