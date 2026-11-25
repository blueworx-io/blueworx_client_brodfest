/**
 * The feature image in the block editor: a picture with its alt text, and the
 * label the box shows until a picture is chosen.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Feature image' ),
			E.__( 'A large picture.' ),
			el(
				E.Fragment,
				null,
				E.image( E.__( 'Picture' ), a, set, { url: 'image', id: 'imageId', alt: 'imageAlt', decorative: 'imageDecorative' } ),
				E.text( E.__( 'Placeholder label (shown until a picture is chosen)' ), a.placeholderLabel, function ( v ) { set( { placeholderLabel: v } ); } )
			)
		);
	}

	E.register( 'brodfest/feature-image', Edit );
}() );
