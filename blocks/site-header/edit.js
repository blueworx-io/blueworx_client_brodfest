/**
 * The site header block. The plugin draws it on every page, so it is not
 * placed by hand; this only describes it and keeps its words editable.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		function field( label, key, placeholder ) {
			return E.text( label, a[ key ], function ( v ) { var p = {}; p[ key ] = v; set( p ); }, { placeholder: placeholder } );
		}

		return E.section(
			E.__( 'Site header' ),
			E.__( 'Drawn on every page by the plugin. The menu lists the pages of the live site.' ),
			el(
				E.Fragment,
				null,
				field( E.__( 'RSVP button' ), 'rsvpLabel', 'RSVP' ),
				field( E.__( 'Menu button (for screen readers)' ), 'menuLabel', 'Menu' ),
				field( E.__( 'Close button (for screen readers)' ), 'closeLabel', 'Close' ),
				field( E.__( 'Logo link (for screen readers)' ), 'homeLabel', 'Brodfest Ibiza — home' ),
				field( E.__( 'Menu name (for screen readers)' ), 'navLabel', 'Main' )
			)
		);
	}

	E.register( 'brodfest/site-header', Edit );
}() );
