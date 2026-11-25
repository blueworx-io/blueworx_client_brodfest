/**
 * The home date and lettering in the block editor. The date comes from the
 * event; the lettering is one of the plugin's two artworks; the Add to
 * Calendar button sits inside this block.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Home date and lettering' ),
			E.__( 'The date, the lettering under it, the button and the line below.' ),
			el(
				E.Fragment,
				null,
				E.choice(
					E.__( 'Lettering' ),
					a.artwork,
					[
						{ label: E.__( 'Save the date' ), value: 'save-the-date' },
						{ label: E.__( 'Invitation' ), value: 'invitation' },
					],
					function ( v ) { set( { artwork: v } ); }
				),
				E.text( E.__( 'Lettering words (for screen readers)' ), a.letteringLabel, function ( v ) { set( { letteringLabel: v } ); } ),
				E.text( E.__( 'Line under the button' ), a.note, function ( v ) { set( { note: v } ); }, {
					multiline: true,
					help: E.__( 'A new line here is a line break on a computer screen.' ),
				} ),
				el( E.InnerBlocks, { allowedBlocks: [ 'brodfest/add-to-calendar' ], template: [], templateLock: false } )
			)
		);
	}

	E.register( 'brodfest/home-lettering', Edit, function () { return el( E.InnerBlocks.Content ); } );
}() );
