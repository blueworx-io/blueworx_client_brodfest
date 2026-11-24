/**
 * The story banner in the block editor. The artwork is the plugin's own; only
 * whether it is decorative, and its alt text if not, are edited here.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Story banner' ),
			E.__( 'The banner artwork across the top of the page.' ),
			el(
				E.Fragment,
				null,
				E.toggle( E.__( 'Decorative: no alt text needed' ), a.decorative, function ( v ) { set( { decorative: v } ); } ),
				a.decorative ? null : E.text( E.__( 'Alt text' ), a.alt, function ( v ) { set( { alt: v } ); } )
			)
		);
	}

	E.register( 'brodfest/story-banner', Edit );
}() );
