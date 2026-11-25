<?php
/**
 * What the sections need that is not their own words: the event itself and
 * the home video's sources.
 *
 * The words of every page live in the page, as section blocks (blocks/), and
 * their starting content is in includes/seed.php.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The event itself: what Add to Calendar and the home page show.
 *
 * It runs 7pm to 4am Ibiza time, which is 6pm to 3am in the UK (client, 9 Nov
 * 2026). On the Save the Date its note is the copy document's line about the
 * invitation.
 *
 * @return array{title: string, date: string, time: string, timezone: string, end: string, location: string, description: string}
 */
function brodfest_event() {
	return (array) apply_filters(
		'brodfest_event',
		array(
			'title'       => 'Brodfest Ibiza',
			'date'        => '2027-06-05',
			'time'        => '19:00',
			'timezone'    => 'Europe/Madrid',
			'end'         => '2027-06-06 04:00',
			'location'    => 'Ibiza',
			'description' => brodfest_is_invite() ? __( 'Full invitation and information coming soon.', 'blueworx-client-brodfest' ) : 'To celebrate Peter’s birthday in 2027, Brodfest arrives at Can Sakhara, his home on the iconic island of Ibiza. (7pm to 4am CEST)',
		)
	);
}

/**
 * The video an editor chose for the current site on the settings screen, or
 * ''.
 *
 * @return string URL.
 */
function brodfest_hero_video() {
	return (string) apply_filters( 'brodfest_hero_video', '', brodfest_site() );
}

/**
 * The hero video's sources, widest first: the editor's choice on the settings
 * screen when there is one; otherwise the files on the home video block
 * (for the Save the Date, the cuts the plugin ships: wide from tablets up,
 * square on phones, Luke 6 Nov 2026). None while still to come.
 *
 * @param array<int, array{src?: string, media?: string}> $block_videos The files listed on the block.
 * @return array<int, array{src: string, media: string}>
 */
function brodfest_hero_videos( $block_videos = array() ) {
	$chosen = brodfest_hero_video();

	if ( '' !== $chosen ) {
		$videos = array(
			array(
				'src'   => $chosen,
				'media' => '',
			),
		);
	} else {
		$videos = array();

		foreach ( (array) $block_videos as $video ) {
			$src = is_array( $video ) ? brodfest_media_url( $video['src'] ?? '' ) : '';

			if ( '' !== $src ) {
				$videos[] = array(
					'src'   => $src,
					'media' => trim( (string) ( $video['media'] ?? '' ) ),
				);
			}
		}
	}

	return (array) apply_filters( 'brodfest_hero_videos', $videos, brodfest_site() );
}
