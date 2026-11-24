/**
 * The site footer block. The plugin draws it on every page, so it is not
 * placed by hand; this only describes it.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Site footer' ),
			E.__( 'The mountain mark at the foot of every page, drawn by the plugin.' ),
			E.text( E.__( 'Words for screen readers (leave empty if the mark is decoration)' ), a.markLabel, function ( v ) { set( { markLabel: v } ); } )
		);
	}

	E.register( 'brodfest/site-footer', Edit );
}() );
