/**
 * The RSVP form in the block editor: the words on the form. The fields, the
 * checks and what happens to a reply are the plugin's, not the page's.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		function field( label, key, placeholder, multiline ) {
			return E.text( label, a[ key ], function ( v ) { var p = {}; p[ key ] = v; set( p ); }, { placeholder: placeholder, multiline: multiline } );
		}

		return E.section(
			E.__( 'RSVP form' ),
			E.__( 'Replies are saved as guests. Leave a box empty to use the usual words.' ),
			el(
				E.Fragment,
				null,
				field( E.__( 'Main heading' ), 'heading', 'RSVP' ),
				field( E.__( 'Question: attending' ), 'attendingQuestion', 'Will you be attending?' ),
				field( E.__( 'Question: partner' ), 'partnerQuestion', 'Will you attend with a partner?' ),
				field( E.__( 'Accessibility tick box' ), 'accessibilityLabel', 'Click here if you or your partner have any accessibility requirements', true ),
				field( E.__( 'Note under the form (an email address becomes a link)' ), 'formNote', '', true ),
				field( E.__( 'Send button' ), 'sendLabel', 'SEND' ),
				field( E.__( 'Thank-you message' ), 'thanks', 'Thank you for your RSVP' ),
				field( E.__( 'Close button (after sending)' ), 'closeLabel', 'CLOSE' )
			)
		);
	}

	E.register( 'brodfest/rsvp-form', Edit );
}() );
