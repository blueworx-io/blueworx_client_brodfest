/**
 * The map in the block editor: a wide picture for desktop, a squarer one for
 * mobile, one alt text, and the label shown until pictures are chosen.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	/** Remembers a picture's size, so the page can reserve its space. */
	function sized( set, prefix ) {
		return function ( patch ) {
			var next = Object.assign( {}, patch );
			var url = patch[ 'image' + prefix ];
			if ( typeof url === 'string' && url !== '' ) {
				var probe = new window.Image();
				probe.onload = function () {
					var size = {};
					size[ prefix.toLowerCase() + 'Width' ] = probe.naturalWidth;
					size[ prefix.toLowerCase() + 'Height' ] = probe.naturalHeight;
					set( size );
				};
				probe.src = E.resolve( url );
			} else if ( url === '' ) {
				next[ prefix.toLowerCase() + 'Width' ] = 0;
				next[ prefix.toLowerCase() + 'Height' ] = 0;
			}
			set( next );
		};
	}

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Map' ),
			E.__( 'The map of the island.' ),
			el(
				E.Fragment,
				null,
				E.image( E.__( 'Desktop map (wide)' ), a, sized( set, 'Desktop' ), { url: 'imageDesktop', id: 'imageDesktopId' } ),
				E.image( E.__( 'Mobile map (squarer)' ), a, sized( set, 'Mobile' ), { url: 'imageMobile', id: 'imageMobileId' } ),
				E.toggle( E.__( 'Decorative: no alt text needed' ), a.decorative, function ( v ) { set( { decorative: v } ); } ),
				a.decorative ? null : E.text( E.__( 'Alt text for the map' ), a.alt, function ( v ) { set( { alt: v } ); }, { multiline: true } ),
				E.text( E.__( 'Placeholder label (shown until a map is chosen)' ), a.placeholderLabel, function ( v ) { set( { placeholderLabel: v } ); } )
			)
		);
	}

	E.register( 'brodfest/map', Edit );
}() );
