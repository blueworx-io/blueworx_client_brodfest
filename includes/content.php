<?php
/**
 * The site's words, links and image slots, in one place.
 *
 * Templates never hold copy. Each section part takes its content as $args, and
 * this file supplies it. When editing moves into the block editor, each section
 * becomes a block whose attributes default to the same values — so nothing in
 * templates/parts/ has to change.
 *
 * Everything here is placeholder copy until the client supplies the real text.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placeholder paragraph.
 *
 * @param int $sentences How many sentences, 1 to 3.
 * @return string
 */
function brodfest_lorem( $sentences = 3 ) {
	$all = array(
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
		'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
		'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
	);

	return implode( ' ', array_slice( $all, 0, max( 1, min( 3, (int) $sentences ) ) ) );
}

/**
 * The event itself: what Add to Calendar and the home page show.
 *
 * The time is not decided yet, so the calendar entry is all day.
 *
 * @return array{title: string, date: string, location: string, description: string}
 */
function brodfest_event() {
	return (array) apply_filters(
		'brodfest_event',
		array(
			'title'       => 'Brodfest',
			'date'        => '2027-06-05',
			'location'    => 'Ibiza',
			'description' => __( 'Full invitation and information coming soon.', 'blueworx-client-brodfest' ),
		)
	);
}

/**
 * The hero video for the current site, or '' while it is still to come.
 *
 * @return string URL.
 */
function brodfest_hero_video() {
	return (string) apply_filters( 'brodfest_hero_video', '', brodfest_site() );
}

/**
 * Placeholder cards: the hotels in one area, or the places in one category.
 *
 * @param int    $count How many.
 * @param string $name  The card name.
 * @return array<int, array{name: string, text: string, url: string, image: string}>
 */
function brodfest_placeholder_cards( $count, $name ) {
	$cards = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$cards[] = array(
			'name'  => $name,
			// The Figma card copy, with its desktop line breaks (see brodfest_text()).
			'text'  => "Lorem ipsum dolor sit amet, consectetur adip-\niscing elit, sed do eiusmod tempor incididunt\nut labore et dolore magna aliqua. Ut enim ad\nminim veniam, quis nostrud exercitation ul-\nlamco laboris nisi ut aliquip ex ea commodo",
			'url'   => '#',
			'image' => '',
		);
	}

	return $cards;
}

/**
 * The accommodation areas, each with its banner, intro and hotels.
 *
 * @return array<int, array{name: string, image: string, image_label: string, intro: string, cards: array}>
 */
function brodfest_accommodation_areas() {
	// Name, short name, banner label and card count, as in the Figma file.
	$areas = array(
		array( 'PLAYA D’EN BOSSA', 'BOSSA', 'BOSSA', 3 ),
		array( 'TALAMANCA', 'TALAMANCA', 'TALAMANCA', 3 ),
		array( 'CALA LLONGA', 'CALA LLONGA', 'CL', 2 ),
	);

	return array_map(
		static function ( $area ) {
			return array(
				'name'        => $area[0],
				'image'       => '',
				'image_label' => 'PICTURE OF ' . $area[2],
				'intro'       => 'INFO ON ' . $area[1] . " AREA... Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut\nlabore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea",
				'cards'       => brodfest_placeholder_cards( $area[3], 'HOTEL NAME' ),
			);
		},
		$areas
	);
}

/**
 * The recommendation categories. More can be added without touching a
 * template: each one is a title and its cards.
 *
 * @return array<int, array{name: string, cards: array}>
 */
function brodfest_recommendation_categories() {
	return array(
		array(
			'name'  => 'BEACH CLUBS',
			'cards' => brodfest_placeholder_cards( 3, 'NAME' ),
		),
		array(
			'name'  => 'RESTAURANTS',
			'cards' => brodfest_placeholder_cards( 3, 'NAME' ),
		),
	);
}

/**
 * The two enquiry contacts.
 *
 * @return array<int, array{title: string, text: string, email: string}>
 */
function brodfest_enquiry_contacts() {
	return array(
		array(
			'title' => 'GENERAL ENQUIRIES',
			'text'  => brodfest_lorem( 2 ),
			'email' => 'info@brodfest.net',
		),
		array(
			'title' => 'PRIVATE CONCIERGE',
			'text'  => brodfest_lorem( 3 ),
			'email' => 'concierge@brodfest.net',
		),
	);
}
