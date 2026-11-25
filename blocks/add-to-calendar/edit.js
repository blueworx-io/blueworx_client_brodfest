/**
 * The Add to Calendar button in the block editor: its words. The choice of
 * calendars is drawn by the plugin from the event.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Add to Calendar' ),
			E.__( 'Hidden on the site until it is switched on.' ),
			E.text( E.__( 'Button words' ), a.label, function ( v ) { set( { label: v } ); }, { placeholder: 'Add to Calendar' } )
		);
	}

	E.register( 'brodfest/add-to-calendar', Edit );
}() );
