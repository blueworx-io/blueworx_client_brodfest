/**
 * The area section in the block editor: the area's words and banner, and its
 * hotels as a list (up to six; with none, the row is left out).
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Area section' ),
			E.__( 'An area to stay in, with its hotels.' ),
			el(
				E.Fragment,
				null,
				E.text( E.__( 'Area name' ), a.name, function ( v ) { set( { name: v } ); } ),
				E.choice(
					E.__( 'Heading level' ),
					String( a.headingLevel ),
					[
						{ label: E.__( 'Heading 2' ), value: '2' },
						{ label: E.__( 'Heading 3 (when a Heading 2 sits above it)' ), value: '3' },
					],
					function ( v ) { set( { headingLevel: parseInt( v, 10 ) } ); }
				),
				E.text( E.__( 'How far away it is' ), a.distance, function ( v ) { set( { distance: v } ); } ),
				E.image( E.__( 'Banner picture (desktop)' ), a, set, { url: 'image', id: 'imageId', alt: 'imageAlt', decorative: 'imageDecorative' } ),
				E.image( E.__( 'Banner picture (mobile)' ), a, set, { url: 'imagePhone', id: 'imagePhoneId' } ),
				E.text( E.__( 'Banner placeholder label (shown until a picture is chosen)' ), a.imageLabel, function ( v ) { set( { imageLabel: v } ); } ),
				E.text( E.__( 'Introduction' ), a.intro, function ( v ) { set( { intro: v } ); }, { multiline: true, rows: 6 } ),
				E.text( E.__( 'Booking code to quote (shown under hotels that have no offer line)' ), a.quoteCode, function ( v ) { set( { quoteCode: v } ); } ),
				E.text( E.__( 'Button words' ), a.bookLabel, function ( v ) { set( { bookLabel: v } ); }, { placeholder: 'BOOK' } ),
				E.list( {
					label: E.__( 'Hotels' ),
					items: a.hotels,
					min: 0,
					max: 6,
					addLabel: E.__( 'Add a hotel' ),
					blank: function () { return { name: '', text: '', url: '', image: '', imageId: 0, imageAlt: '', imageDecorative: false, offer: '' }; },
					title: function ( item, i ) { return item.name || E.__( 'Hotel' ) + ' ' + ( i + 1 ); },
					fields: function ( item, i, update ) {
						return el(
							E.Fragment,
							null,
							E.text( E.__( 'Hotel name' ), item.name, function ( v ) { update( { name: v } ); } ),
							E.text( E.__( 'About the hotel' ), item.text, function ( v ) { update( { text: v } ); }, { multiline: true, rows: 6 } ),
							E.text( E.__( 'Booking link' ), item.url, function ( v ) { update( { url: v } ); }, { type: 'url' } ),
							E.text( E.__( 'Offer line (optional)' ), item.offer, function ( v ) { update( { offer: v } ); } ),
							E.image( E.__( 'Photo' ), item, update, { url: 'image', id: 'imageId', alt: 'imageAlt', decorative: 'imageDecorative' } )
						);
					},
					onChange: function ( items ) { set( { hotels: items } ); },
				} )
			)
		);
	}

	E.register( 'brodfest/area', Edit );
}() );
