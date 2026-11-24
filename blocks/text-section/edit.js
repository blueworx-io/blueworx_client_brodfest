/**
 * The text section in the block editor: a title and a list of paragraphs.
 * How it looks on the site is decided by render.php, not here.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Text section' ),
			E.__( 'A title, then the paragraphs under it.' ),
			el(
				E.Fragment,
				null,
				E.text( E.__( 'Title' ), a.title, function ( v ) { set( { title: v } ); } ),
				E.choice(
					E.__( 'Heading level' ),
					String( a.headingLevel ),
					[
						{ label: E.__( 'Heading 1 (the one main heading of the page)' ), value: '1' },
						{ label: E.__( 'Heading 2' ), value: '2' },
						{ label: E.__( 'Heading 3' ), value: '3' },
					],
					function ( v ) { set( { headingLevel: parseInt( v, 10 ) } ); }
				),
				E.choice(
					E.__( 'Title style' ),
					a.titleStyle,
					[
						{ label: E.__( 'Page title' ), value: 'page' },
						{ label: E.__( 'Smaller sub-title' ), value: 'subtitle' },
					],
					function ( v ) { set( { titleStyle: v } ); }
				),
				E.list( {
					label: E.__( 'Paragraphs' ),
					items: a.paragraphs,
					min: 0,
					max: 8,
					addLabel: E.__( 'Add a paragraph' ),
					blank: '',
					title: function ( item, i ) { return E.__( 'Paragraph' ) + ' ' + ( i + 1 ) + ': ' + String( item ).slice( 0, 32 ); },
					fields: function ( item, i, update ) {
						return E.text( E.__( 'Words' ), item, function ( v ) { update( v ); }, {
							multiline: true,
							rows: 6,
							help: E.__( 'An email address in the words becomes a link.' ),
						} );
					},
					onChange: function ( items ) { set( { paragraphs: items } ); },
				} ),
				E.text( E.__( 'Email address (shown as a link under the paragraphs)' ), a.email, function ( v ) { set( { email: v } ); }, { type: 'email' } ),
				E.text( E.__( 'Closing line' ), a.note, function ( v ) { set( { note: v } ); } )
			)
		);
	}

	E.register( 'brodfest/text-section', Edit );
}() );
