<?php
/**
 * The site's words, links and image slots, in one place.
 *
 * Templates never hold copy. Each section part takes its content as $args, and
 * this file supplies it. When editing moves into the block editor, each section
 * becomes a block whose attributes default to the same values — so nothing in
 * templates/parts/ has to change.
 *
 * The Save the Date copy is the client's (brodfest_std_copy()); the Invite's is
 * placeholder until the client supplies it.
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
 * It starts at 7pm Ibiza time. On the Save the Date its note is the copy
 * document's line about the invitation.
 *
 * @return array{title: string, date: string, time: string, timezone: string, location: string, description: string}
 */
function brodfest_event() {
	return (array) apply_filters(
		'brodfest_event',
		array(
			'title'       => 'Brodfest Ibiza',
			'date'        => '2027-06-05',
			'time'        => '19:00',
			'timezone'    => 'Europe/Madrid',
			'location'    => 'Ibiza',
			'description' => brodfest_is_invite() ? __( 'Full invitation and information coming soon.', 'blueworx-client-brodfest' ) : brodfest_std_copy( 'calendar' ),
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
 * The hero video's sources, widest first: the editor's choice when there is
 * one; otherwise, for the Save the Date, the cuts the plugin ships — wide
 * from tablets up, square on phones (Luke, 6 Nov 2026). None while still to come.
 *
 * @return array<int, array{src: string, media: string}>
 */
function brodfest_hero_videos() {
	$chosen = brodfest_hero_video();
	$videos = '' !== $chosen
		? array(
			array(
				'src'   => $chosen,
				'media' => '',
			),
		)
		: ( brodfest_is_invite() ? array() : array(
			array(
				'src'   => BRODFEST_URL . 'assets/video/BF_SAVE_THE_DATE_1920X840_V3.01.mp4',
				'media' => '(min-width: 768px)',
			),
			array(
				'src'   => BRODFEST_URL . 'assets/video/BF_SAVE_THE_DATE_1080X1080_V3.01.mp4',
				'media' => '',
			),
		) );

	return (array) apply_filters( 'brodfest_hero_videos', $videos, brodfest_site() );
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
 * The Save the Date site's copy, as the client sent it (5 Nov 2026). The
 * Invite keeps its placeholders until it is reviewed in turn.
 *
 * @param string $key Which piece.
 * @return mixed
 */
function brodfest_std_copy( $key ) {
	$discount = 'For up to 25% discount on bookings made before 15/02/2027, quote BRODFEST';
	// The client's photos (6 Nov 2026), by hotel; AMA's is still to come.
	$photos = array(
		'BLESS IBIZA'          => 'bless',
		'HOTEL TORRE DEL MAR'  => 'torre-del-mar',
		'NOBU HOTEL'           => 'nobu',
		'HOTEL SIMBAD'         => 'simbad',
		'HOTEL LUX ISLA'       => 'lux-isla',
		'HYDE IBIZA'           => 'hyde',
		'MONDRIAN'             => 'mondrian',
	);
	$hotel    = static function ( $name, $text, $url, $discount = '' ) use ( $photos ) {
		return array(
			'name'     => $name,
			'text'     => $text,
			'url'      => $url,
			'image'    => isset( $photos[ $name ] ) ? brodfest_img( 'hotels/' . $photos[ $name ] . '.jpg' ) : '',
			'discount' => $discount,
		);
	};

	$copy = array(
		'celebrate'  => array(
			'Ibiza is calling. From Surrey to the Mediterranean…welcome to Brodfest, the Ibiza edition.',
			'To celebrate Peter’s birthday in 2027, Brodfest arrives at Can Sakhara, his home on the iconic island of Ibiza. A stunningly contemporary villa, Can Sakhara carries a quiet echo of something far more historic, with subtle design references drawn from ancient cultures. The night will be a glorious celebration of your host, the island’s vibrant spirit and Can Sakhara’s timeless roots.',
		),
		'invitation' => 'Your invitation and the full Brodfest Ibiza website will land before Christmas.',
		// The calendar entry's note (client, 6 Nov 2026).
		'calendar'   => 'To celebrate Peter’s birthday in 2027, Brodfest arrives at Can Sakhara, his home on the iconic island of Ibiza.',
		'dress_code' => 'Black, Gold & Ibiza Bold',
		'transport'  => 'To make your evening as effortless as possible (and to make sure the neighbours don’t alert the police!), it’s essential that we provide transfers to and from the party. Guests are warmly invited to join a vehicle from any of our recommended hotels below, and our guest liaison team will be in touch in due course to make personal arrangements with you. There will be no vehicle access or parking at the house on the evening of the party, so we ask that guests do not arrive in private cars or by taxi. If you are staying elsewhere on the island, simply take a taxi to the nearest listed hotel and join one of our organised transfers from there. We look forward to welcoming you.',
		'stay'       => 'If you’re looking for somewhere to stay, we’ve gathered a few of our favourite hotels across three of the island’s loveliest areas, each chosen for its comfort, character and easy connection to the celebration.',
		'areas'      => array(
			array(
				'name'        => 'PLAYA D’EN BOSSA',
				'distance'    => '(15 mins away)',
				'image'       => brodfest_img( 'areas/bossa-desktop.jpg' ),
				'image_phone' => brodfest_img( 'areas/bossa-mobile.jpg' ),
				'image_label' => 'PICTURE OF BOSSA',
				'intro'       => 'Playa d’en Bossa features Ibiza’s longest beachfront, lined with upscale beach clubs and open-air dining, making it ideal for anyone seeking high-energy days and sophisticated evening entertainment. This buzzing strip, close to Ibiza Town, balances premium relaxation with world-class nightlife venues, ensuring a spirited atmosphere right at your doorstep.',
				'cards'       => array(
					$hotel( 'BLESS IBIZA', 'BLESS Ibiza redefines Mediterranean luxury. A dazzling five-star hotel that opened in 2026, it features interiors by Lázaro Rosa-Violán. Guests can indulge in breathtaking sea views, curated gastronomy, a rooftop bar, a Natura Bissé spa, and restaurants including COYA, all making for a glamorous stay in a coastal paradise.', 'https://www.thesiteibiza.com/en/hotels/bless-ibiza-the-site/' ),
					$hotel( 'HOTEL TORRE DEL MAR', 'Hotel Torre del Mar offers the best of both worlds. Situated on a prime beachfront stretch, this polished resort marries energetic island life with serene downtime. Highlights include spectacular sea views, the impressive Sal Rossa Spa, and an iconic infinity pool wrapped around a historic stone tower.', 'https://www.hoteltorredelmar.com/en' ),
					$hotel( 'AMA IBIZA SUITES', 'AMA Ibiza Suites is an adults only, beachfront hideaway right on the sands of Playa d’en Bossa. Its stylish suites come with kitchenettes and private balconies or terraces, many with garden or sea views. Days revolve around the outdoor pool overlooking the beach, with DJ sessions setting a laid back rhythm, while the beach club and Mediterranean restaurant make it easy to stay put from breakfast until sunset.', 'https://www.amaibiza.com/' ),
				),
			),
			array(
				'name'        => 'TALAMANCA',
				'distance'    => '(20 mins away)',
				'image'       => brodfest_img( 'areas/talamanca-desktop.jpg' ),
				'image_phone' => brodfest_img( 'areas/talamanca-mobile.jpg' ),
				'image_label' => 'PICTURE OF TALAMANCA',
				'intro'       => 'Talamanca is a relaxed beachside escape just minutes from the smart Marina Botafoch and the heart of Ibiza Town. Its shallow, calm bay is lined with excellent waterfront restaurants and stylish lounges. Easygoing by day and close to the action by night, it’s the ideal mix of beach time and nightlife.',
				'cards'       => array(
					$hotel( 'NOBU HOTEL', 'Nobu Hotel Ibiza Bay brings effortless beachfront luxury to the shores of Talamanca Bay. This five-star resort combines Nobu’s famous Japanese cuisine, immersive wellness treatments, and sleek pool decks, making for long, indulgent days by the water and elegant evenings.', 'https://www.nobuhotelibizabay.com/' ),
					$hotel( 'HOTEL SIMBAD', 'Hotel Simbad is a restful four-star retreat on the quiet north end of the bay. This refined hotel matches easy access to Ibiza Town with a peaceful coastal atmosphere. Just steps from the beach it features an indoor spa, fine dining terraces, and panoramic sea-facing rooms.', 'https://www.hotelsimbad.com/en/hotel-simbad-playa-talamanca-ibiza' ),
					$hotel( 'HOTEL LUX ISLA', 'Hotel Lux Isla offers an intimate boutique experience inside a simple yet charming complex. Emphasising personal service, well-tended garden spaces, private terraces, and a relaxing ambience, this hideaway is a tranquil oasis a short stroll from the bay.', 'https://www.luxisla.com/' ),
				),
			),
			array(
				'name'        => 'CALA LLONGA',
				'distance'    => '(20 mins away)',
				'image'       => brodfest_img( 'areas/cala-llonga-desktop.jpg' ),
				'image_phone' => brodfest_img( 'areas/cala-llonga-mobile.jpg' ),
				'image_label' => 'PICTURE OF CL',
				'intro'       => 'Cala Llonga, on the island’s east coast, is a sheltered, pine-fringed bay that pairs a stunning natural landscape with a thriving culinary and social scene. It offers a perfect blend of peaceful seclusion and a vibrant, upscale resort lifestyle, making it a fantastic base for those who love great food and beautiful views.',
				'cards'       => array(
					$hotel( 'HYDE IBIZA', 'Hyde Ibiza is a high-concept, bohemian hotel above the bay, with warm, natural interiors and balconies overlooking either the hills or the shores of Cala Llonga. This sprawling property is a compelling, vibrant lifestyle destination, designed for spending your days between the pools and beach club, with Mexican dining at Cuyo and music setting the mood.', 'https://hydehotels.com/ibiza/', $discount ),
					$hotel( 'MONDRIAN', 'Mondrian Ibiza stands as a striking, design-led haven, blending mid-century style with a contemporary Mediterranean feel. Overlooking a sweeping bay, this property brings clean architectural geometry together with organic textures and a museum-quality art collection. Featuring wellness programmes, acclaimed Japanese dining, and pristine infinity pools, it’s a culturally rich island escape.', 'https://www.mondrianhotels.com/ibiza', $discount ),
				),
			),
		),
		'enquiries'  => array(
			array(
				// The emails are in the copy, as links (client, 6 Nov 2026).
				'title' => 'ENQUIRIES',
				'text'  => 'Should you have any questions about the celebration, or wish to share further details with us, please email info@brodfestibiza.net. Our guest liaison team will be happy to help in any way they can.',
				'email' => '',
			),
			array(
				'title' => 'PRIVATE CONCIERGE',
				'text'  => 'Should you need a helping hand with the smooth running of your Ibiza stay, whether that’s childcare, restaurant recommendations and reservations, club bookings or private transport, please contact concierge@brodfestibiza.net. Concierge services are offered privately and charged directly to guests. Our team can provide full details on request.',
				'email' => '',
			),
		),
	);

	return $copy[ $key ] ?? null;
}

/**
 * The accommodation areas, each with its banner, intro and hotels.
 *
 * @return array<int, array{name: string, image: string, image_label: string, intro: string, cards: array}>
 */
function brodfest_accommodation_areas() {
	if ( ! brodfest_is_invite() ) {
		return brodfest_std_copy( 'areas' );
	}

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
	if ( ! brodfest_is_invite() ) {
		return brodfest_std_copy( 'enquiries' );
	}

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
