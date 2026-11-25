/**
 * The recommendations section in the block editor: a group's name and its
 * places as a list (up to nine; with none, only the name shows).
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Recommendations section' ),
			E.__( 'A group of places, such as beach clubs or restaurants.' ),
			el(
				E.Fragment,
				null,
				E.text( E.__( 'Group name' ), a.name, function ( v ) { set( { name: v } ); } ),
				E.choice(
					E.__( 'Heading level' ),
					String( a.headingLevel ),
					[
						{ label: E.__( 'Heading 2' ), value: '2' },
						{ label: E.__( 'Heading 3 (when a Heading 2 sits above it)' ), value: '3' },
					],
					function ( v ) { set( { headingLevel: parseInt( v, 10 ) } ); }
				),
				E.text( E.__( 'Button words' ), a.bookLabel, function ( v ) { set( { bookLabel: v } ); }, { placeholder: 'BOOK' } ),
				E.list( {
					label: E.__( 'Places' ),
					items: a.places,
					min: 0,
					max: 9,
					addLabel: E.__( 'Add a place' ),
					blank: function () { return { name: '', text: '', url: '', image: '', imageId: 0, imageAlt: '', imageDecorative: false }; },
					title: function ( item, i ) { return item.name || E.__( 'Place' ) + ' ' + ( i + 1 ); },
					fields: function ( item, i, update ) {
						return el(
							E.Fragment,
							null,
							E.text( E.__( 'Name' ), item.name, function ( v ) { update( { name: v } ); } ),
							E.text( E.__( 'About the place' ), item.text, function ( v ) { update( { text: v } ); }, { multiline: true, rows: 6 } ),
							E.text( E.__( 'Booking link' ), item.url, function ( v ) { update( { url: v } ); }, { type: 'url' } ),
							E.image( E.__( 'Photo' ), item, update, { url: 'image', id: 'imageId', alt: 'imageAlt', decorative: 'imageDecorative' } )
						);
					},
					onChange: function ( items ) { set( { places: items } ); },
				} )
			)
		);
	}

	E.register( 'brodfest/category', Edit );
}() );
