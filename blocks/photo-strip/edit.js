/**
 * The photo strip in the block editor: one wide photo and its alt text.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Photo strip' ),
			E.__( 'One wide photo across the page.' ),
			E.image( E.__( 'Photo' ), a, set, { url: 'image', id: 'imageId', alt: 'imageAlt', decorative: 'imageDecorative' } )
		);
	}

	E.register( 'brodfest/photo-strip', Edit );
}() );
