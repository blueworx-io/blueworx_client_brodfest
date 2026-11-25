<?php
/**
 * The starting content of every page, as section blocks.
 *
 * This is the site as it is today, written out once as the blocks a page
 * holds: the Save the Date's real copy (the client's, 5 Nov 2026) and the
 * Invite's placeholders. A page gets its starting content when it is first
 * created or first moved onto blocks (see includes/pages.php) and is never
 * overwritten after that: from then on the words live in the page, and an
 * editor changes them in WordPress.
 *
 * Images the plugin ships are written as `plugin:path` (see
 * brodfest_media_url()), so the content holds no site address.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A block, in the form serialize_blocks() takes.
 *
 * @param string $name   Block name without the namespace.
 * @param array  $attrs  Attributes.
 * @param array  $inner  Blocks inside it.
 * @return array
 */
function brodfest_seed_block( $name, $attrs = array(), $inner = array() ) {
	return array(
		'blockName'    => 'brodfest/' . $name,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => '',
		'innerContent' => array_fill( 0, count( $inner ), null ),
	);
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
 * The Save the Date site's copy, as the client sent it (5 Nov 2026).
 *
 * @param string $key Which piece.
 * @return mixed
 */
function brodfest_seed_copy( $key ) {
	$copy = array(
		'celebrate'  => array(
			'Ibiza is calling. From Surrey to the Mediterranean…welcome to Brodfest, the Ibiza edition.',
			'To celebrate Peter’s birthday in 2027, Brodfest arrives at Can Sakhara, his home on the iconic island of Ibiza. A stunningly contemporary villa, Can Sakhara carries a quiet echo of something far more historic, with subtle design references drawn from ancient cultures. The night will be a glorious celebration of your host, the island’s vibrant spirit and Can Sakhara’s timeless roots.',
		),
		'invitation' => 'Your invitation and the full Brodfest Ibiza website will land before Christmas',
		'dress_code' => 'Black, Gold & Ibiza Bold',
		'transport'  => 'To make your evening as effortless as possible (and to make sure the neighbours don’t alert the police!), it’s essential that we provide transfers to and from the party. Guests are warmly invited to join a vehicle from any of our recommended hotels below, and our guest liaison team will be in touch in due course to make personal arrangements with you. There will be no vehicle access or parking at the house on the evening of the party, so we ask that guests do not arrive in private cars or by taxi. If you are staying elsewhere on the island, simply take a taxi to the nearest listed hotel and join one of our organised transfers from there. We look forward to welcoming you.',
		'stay'       => 'If you’re looking for somewhere to stay, we’ve gathered a few of our favourite hotels across three of the island’s loveliest areas, each chosen for its comfort, character and easy connection to the celebration.',
		'enquiries'  => 'Should you have any questions about the celebration, or wish to share further details with us, please email info@brodfest.net. Our guest liaison team will be happy to help in any way they can.',
		'concierge'  => 'Should you need a helping hand with the smooth running of your Ibiza stay, whether that’s childcare, restaurant recommendations and reservations, club bookings or private transport, please contact concierge@brodfest.net. Concierge services are offered privately and charged directly to guests. Our team can provide full details on request.',
	);

	return $copy[ $key ] ?? '';
}

/**
 * The hotels, with the client's photos (6 Nov 2026). Each: area key, name,
 * text, link, photo file, alt text and optionally the offer line.
 *
 * @return array<string, array<int, array>>
 */
function brodfest_seed_hotels() {
	$offer = 'For up to 25% discount on bookings made before 15/02/2027, quote BRODFEST';
	$hotel = static function ( $name, $text, $url, $photo, $alt, $discount = '' ) {
		return array(
			'name'            => $name,
			'text'            => $text,
			'url'             => $url,
			'image'           => BRODFEST_PLUGIN_IMAGE . 'assets/img/hotels/' . $photo . '.jpg',
			'imageId'         => 0,
			'imageAlt'        => $alt,
			'imageDecorative' => false,
			'offer'           => $discount,
		);
	};

	return array(
		'bossa'     => array(
			$hotel( 'BLESS IBIZA', 'BLESS Ibiza redefines Mediterranean luxury. A dazzling five-star hotel that opened in 2026, it features interiors by Lázaro Rosa-Violán. Guests can indulge in breathtaking sea views, curated gastronomy, a rooftop bar, a Natura Bissé spa, and restaurants including COYA, all making for a glamorous stay in a coastal paradise.', 'https://www.thesiteibiza.com/en/hotels/bless-ibiza-the-site/', 'bless', 'BLESS Ibiza hotel' ),
			$hotel( 'TORRE DEL MAR', 'Hotel Torre del Mar offers the best of both worlds. Situated on a prime beachfront stretch, this polished resort marries energetic island life with serene downtime. Highlights include spectacular sea views, the impressive Sal Rossa Spa, and an iconic infinity pool wrapped around a historic stone tower.', 'https://www.hoteltorredelmar.com/en', 'torre-del-mar', 'Hotel Torre del Mar' ),
			$hotel( 'AMA IBIZA', 'AMA Ibiza Suites is an adults only, beachfront hideaway right on the sands of Playa d’en Bossa. Its stylish suites come with kitchenettes and private balconies or terraces, many with garden or sea views. Days revolve around the outdoor pool overlooking the beach, with DJ sessions setting a laid back rhythm, while the beach club and Mediterranean restaurant make it easy to stay put from breakfast until sunset.', 'https://www.amaibiza.com/', 'ama', 'AMA Ibiza Suites' ),
		),
		'talamanca' => array(
			$hotel( 'NOBU HOTEL', 'Nobu Hotel Ibiza Bay brings effortless beachfront luxury to the shores of Talamanca Bay. This five-star resort combines Nobu’s famous Japanese cuisine, immersive wellness treatments, and sleek pool decks, making for long, indulgent days by the water and elegant evenings.', 'https://www.nobuhotelibizabay.com/', 'nobu', 'Nobu Hotel Ibiza Bay' ),
			$hotel( 'HOTEL SIMBAD', 'Hotel Simbad is a restful four-star retreat on the quiet north end of the bay. This refined hotel matches easy access to Ibiza Town with a peaceful coastal atmosphere. Just steps from the beach it features an indoor spa, fine dining terraces, and panoramic sea-facing rooms.', 'https://www.hotelsimbad.com/en/hotel-simbad-playa-talamanca-ibiza', 'simbad', 'Hotel Simbad' ),
			$hotel( 'HOTEL LUX ISLA', 'Hotel Lux Isla offers an intimate boutique experience inside a simple yet charming complex. Emphasising personal service, well-tended garden spaces, private terraces, and a relaxing ambience, this hideaway is a tranquil oasis a short stroll from the bay.', 'https://www.luxisla.com/', 'lux-isla', 'Hotel Lux Isla' ),
		),
		'cala'      => array(
			$hotel( 'HYDE IBIZA', 'Hyde Ibiza is a high-concept, bohemian hotel above the bay, with warm, natural interiors and balconies overlooking either the hills or the shores of Cala Llonga. This sprawling property is a compelling, vibrant lifestyle destination, designed for spending your days between the pools and beach club, with Mexican dining at Cuyo and music setting the mood.', 'https://hydehotels.com/ibiza/', 'hyde', 'Hyde Ibiza', $offer ),
			$hotel( 'MONDRIAN', 'Mondrian Ibiza stands as a striking, design-led haven, blending mid-century style with a contemporary Mediterranean feel. Overlooking a sweeping bay, this property brings clean architectural geometry together with organic textures and a museum-quality art collection. Featuring wellness programmes, acclaimed Japanese dining, and pristine infinity pools, it’s a culturally rich island escape.', 'https://www.mondrianhotels.com/ibiza', 'mondrian', 'Mondrian Ibiza', $offer ),
		),
	);
}

/**
 * Placeholder cards for the Invite: the hotels in one area, or the places in
 * one category.
 *
 * @param int    $count How many.
 * @param string $name  The card name.
 * @return array<int, array>
 */
function brodfest_seed_placeholder_cards( $count, $name ) {
	$cards = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$cards[] = array(
			'name'            => $name,
			// The Figma card copy, with its desktop line breaks (see brodfest_text()).
			'text'            => "Lorem ipsum dolor sit amet, consectetur adip-\niscing elit, sed do eiusmod tempor incididunt\nut labore et dolore magna aliqua. Ut enim ad\nminim veniam, quis nostrud exercitation ul-\nlamco laboris nisi ut aliquip ex ea commodo",
			'url'             => '#',
			'image'           => '',
			'imageId'         => 0,
			'imageAlt'        => '',
			'imageDecorative' => true,
			'offer'           => '',
		);
	}

	return $cards;
}

/**
 * The Save the Date's area blocks. Areas sit under "Where to stay" (heading
 * 2), so they are heading 3 and their hotels heading 4.
 *
 * @return array[]
 */
function brodfest_seed_std_areas() {
	$hotels = brodfest_seed_hotels();
	$area   = static function ( $name, $distance, $file, $alt, $intro, $cards ) {
		return brodfest_seed_block(
			'area',
			array(
				'name'            => $name,
				'headingLevel'    => 3,
				'distance'        => $distance,
				'image'           => BRODFEST_PLUGIN_IMAGE . 'assets/img/areas/' . $file . '-desktop.jpg',
				'imagePhone'      => BRODFEST_PLUGIN_IMAGE . 'assets/img/areas/' . $file . '-mobile.jpg',
				'imageAlt'        => $alt,
				'imageDecorative' => false,
				'intro'           => $intro,
				'hotels'          => $cards,
			)
		);
	};

	return array(
		$area( 'PLAYA D’EN BOSSA', '(15 mins away)', 'bossa', 'Playa d’en Bossa, Ibiza', 'Playa d’en Bossa features Ibiza’s longest beachfront, lined with upscale beach clubs and open-air dining, making it ideal for anyone seeking high-energy days and sophisticated evening entertainment. This buzzing strip, close to Ibiza Town, balances premium relaxation with world-class nightlife venues, ensuring a spirited atmosphere right at your doorstep.', $hotels['bossa'] ),
		$area( 'TALAMANCA', '(20 mins away)', 'talamanca', 'Talamanca Bay, Ibiza', 'Talamanca is a relaxed beachside escape just minutes from the smart Marina Botafoch and the heart of Ibiza Town. Its shallow, calm bay is lined with excellent waterfront restaurants and stylish lounges. Easygoing by day and close to the action by night, it’s the ideal mix of beach time and nightlife.', $hotels['talamanca'] ),
		$area( 'CALA LLONGA', '(25 mins away)', 'cala-llonga', 'Cala Llonga, Ibiza', 'Cala Llonga, on the island’s east coast, is a sheltered, pine-fringed bay that pairs a stunning natural landscape with a thriving culinary and social scene. It offers a perfect blend of peaceful seclusion and a vibrant, upscale resort lifestyle, making it a fantastic base for those who love great food and beautiful views.', $hotels['cala'] ),
	);
}

/**
 * The Invite's area blocks, still on placeholders: areas are heading 2 and
 * their hotels heading 3.
 *
 * @return array[]
 */
function brodfest_seed_invite_areas() {
	// Name, short name, banner label and card count, as in the Figma file.
	$areas = array(
		array( 'PLAYA D’EN BOSSA', 'BOSSA', 'BOSSA', 3 ),
		array( 'TALAMANCA', 'TALAMANCA', 'TALAMANCA', 3 ),
		array( 'CALA LLONGA', 'CALA LLONGA', 'CL', 2 ),
	);

	return array_map(
		static function ( $area ) {
			return brodfest_seed_block(
				'area',
				array(
					'name'            => $area[0],
					'headingLevel'    => 2,
					'imageLabel'      => 'PICTURE OF ' . $area[2],
					'imageDecorative' => true,
					'intro'           => 'INFO ON ' . $area[1] . " AREA... Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut\nlabore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea",
					'quoteCode'       => 'BRODFEST',
					'hotels'          => brodfest_seed_placeholder_cards( $area[3], 'HOTEL NAME' ),
				)
			);
		},
		$areas
	);
}

/**
 * One text section.
 *
 * @param string   $title      Title.
 * @param string[] $paragraphs Paragraphs.
 * @param array    $more       More attributes (headingLevel, titleStyle, email, note).
 * @return array
 */
function brodfest_seed_text( $title, $paragraphs = array(), $more = array() ) {
	return brodfest_seed_block(
		'text-section',
		array_merge(
			array(
				'title'      => $title,
				'paragraphs' => array_values( $paragraphs ),
			),
			$more
		)
	);
}

/**
 * The blocks of one page on one site.
 *
 * @param string $slug Owned-page slug.
 * @param string $site BRODFEST_SITE_STD or BRODFEST_SITE_INVITE.
 * @return array[] Blocks, empty when the page is not part of that site.
 */
function brodfest_seed_blocks( $slug, $site ) {
	$invite = BRODFEST_SITE_INVITE === $site;
	$sub    = array(
		'headingLevel' => 2,
		'titleStyle'   => 'subtitle',
	);

	if ( ! in_array( $site, brodfest_pages()[ $slug ]['sites'] ?? array(), true ) ) {
		return array();
	}

	switch ( $slug ) {
		case 'home':
			$wide   = 'assets/video/BF_SAVE_THE_DATE_1920X840_V3.01.mp4';
			$square = 'assets/video/BF_SAVE_THE_DATE_1080X1080_V3.01.mp4';

			return array(
				brodfest_seed_block( 'opening' ),
				brodfest_seed_block(
					'hero-video',
					$invite ? array() : array(
						'videos' => array(
							array(
								'src'   => BRODFEST_PLUGIN_IMAGE . $wide,
								'media' => '(min-width: 768px)',
							),
							array(
								'src'   => BRODFEST_PLUGIN_IMAGE . $square,
								'media' => '',
							),
						),
					)
				),
				brodfest_seed_block(
					'home-lettering',
					array(
						'artwork' => $invite ? 'invitation' : 'save-the-date',
						// Two lines, breaking after "Ibiza" (client, 6 Nov 2026).
						'note'    => $invite ? 'Please RSVP to confirm attendance' : str_replace( 'Brodfest Ibiza ', "Brodfest Ibiza\n", brodfest_seed_copy( 'invitation' ) ),
					),
					// Hidden for now (Luke, 9 Nov 2026); see brodfest_show_calendar().
					array( brodfest_seed_block( 'add-to-calendar' ) )
				),
			);

		case 'celebrate':
			if ( $invite ) {
				return array(
					brodfest_seed_block(
						'photo-strip',
						array(
							'image'           => BRODFEST_PLUGIN_IMAGE . 'assets/img/celebrate-strip.jpg',
							'imageDecorative' => true,
						)
					),
					brodfest_seed_text( 'CELEBRATE', array( brodfest_lorem( 3 ), brodfest_lorem( 3 ) ) ),
					brodfest_seed_text( 'DRESS CODE', array( 'Black, gold Ibiza bold' ), array( 'headingLevel' => 2 ) ),
				);
			}

			return array(
				brodfest_seed_text( 'CELEBRATION', brodfest_seed_copy( 'celebrate' ), array( 'note' => brodfest_seed_copy( 'invitation' ) ) ),
				brodfest_seed_text( 'DRESS CODE', array( brodfest_seed_copy( 'dress_code' ) ), $sub ),
			);

		case 'can-sakhara-story':
			return array(
				brodfest_seed_block( 'story-banner' ),
				brodfest_seed_text( 'CAN SAKHARA STORY', array( brodfest_lorem( 3 ), brodfest_lorem( 3 ) ) ),
				brodfest_seed_block( 'feature-image', array( 'placeholderLabel' => 'NIGHT TIME VILLA SHOT' ) ),
			);

		case 'accommodation':
			if ( $invite ) {
				return array_merge(
					array(
						brodfest_seed_text(
							'ACCOMMODATION',
							array(
								"INFO ON ACCOM..Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et\ndolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea",
							)
						),
						brodfest_seed_block( 'map', array( 'placeholderLabel' => 'MAP' ) ),
					),
					brodfest_seed_invite_areas(),
					array( brodfest_seed_text( 'GETTING HERE', array( brodfest_lorem( 3 ) ), array( 'headingLevel' => 2 ) ) )
				);
			}

			return array_merge(
				array(
					brodfest_seed_text( 'INFORMATION' ),
					brodfest_seed_text( 'TRANSPORT', array( brodfest_seed_copy( 'transport' ) ), $sub ),
					brodfest_seed_text( 'WHERE TO STAY', array( brodfest_seed_copy( 'stay' ) ), $sub ),
					// The client's wide map on desktop, and their squarer one on mobile.
					brodfest_seed_block(
						'map',
						array(
							'imageDesktop'  => BRODFEST_PLUGIN_IMAGE . 'assets/img/map-desktop.png',
							'desktopWidth'  => 1564,
							'desktopHeight' => 782,
							'imageMobile'   => BRODFEST_PLUGIN_IMAGE . 'assets/img/map.png',
							'mobileWidth'   => 782,
							'mobileHeight'  => 600,
							'alt'           => 'Map of Ibiza showing Brodfest and the three hotel areas: Playa d’en Bossa, Talamanca and Cala Llonga',
						)
					),
				),
				brodfest_seed_std_areas()
			);

		case 'recommendations':
			return array(
				brodfest_seed_text(
					'RECOMMENDATIONS',
					array(
						"INFO ON RECS..Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et\ndolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea",
					)
				),
				brodfest_seed_block(
					'category',
					array(
						'name'   => 'BEACH CLUBS',
						'places' => brodfest_seed_placeholder_cards( 3, 'NAME' ),
					)
				),
				brodfest_seed_block(
					'category',
					array(
						'name'   => 'RESTAURANTS',
						'places' => brodfest_seed_placeholder_cards( 3, 'NAME' ),
					)
				),
			);

		case 'enquiries':
			if ( $invite ) {
				return array(
					brodfest_seed_text( 'GENERAL ENQUIRIES', array( brodfest_lorem( 2 ) ), array( 'email' => 'info@brodfest.net' ) ),
					brodfest_seed_text(
						'PRIVATE CONCIERGE',
						array( brodfest_lorem( 3 ) ),
						array(
							'headingLevel' => 2,
							'email'        => 'concierge@brodfest.net',
						)
					),
				);
			}

			return array(
				// The emails are in the copy, as links (client, 6 Nov 2026).
				brodfest_seed_text( 'ENQUIRIES', array( brodfest_seed_copy( 'enquiries' ) ) ),
				brodfest_seed_text( 'PRIVATE CONCIERGE', array( brodfest_seed_copy( 'concierge' ) ), $sub ),
			);

		case 'rsvp':
			return array( brodfest_seed_block( 'rsvp-form' ) );
	}

	return array();
}

/**
 * The serialised content of one page on one site, as WordPress stores it.
 *
 * @param string $slug Owned-page slug.
 * @param string $site BRODFEST_SITE_STD or BRODFEST_SITE_INVITE.
 * @return string
 */
function brodfest_seed_content( $slug, $site ) {
	return serialize_blocks( brodfest_seed_blocks( $slug, $site ) );
}
