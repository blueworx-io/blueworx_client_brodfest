/**
 * The opening screen in the block editor: the words for screen readers and on
 * the Enter button. The logo is the plugin's own artwork.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Opening screen' ),
			E.__( 'The logo draws itself, then Enter lifts the screen away.' ),
			el(
				E.Fragment,
				null,
				E.text( E.__( 'Enter button' ), a.enterLabel, function ( v ) { set( { enterLabel: v } ); }, { placeholder: 'ENTER' } ),
				E.text( E.__( 'Screen name (for screen readers)' ), a.dialogLabel, function ( v ) { set( { dialogLabel: v } ); }, { placeholder: 'Welcome to Brodfest' } ),
				E.text( E.__( 'Logo words (for screen readers)' ), a.logoLabel, function ( v ) { set( { logoLabel: v } ); }, { placeholder: 'Brodfest Ibiza' } )
			)
		);
	}

	E.register( 'brodfest/opening', Edit );
}() );
