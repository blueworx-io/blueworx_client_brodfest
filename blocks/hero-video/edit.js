/**
 * The home video in the block editor: up to two video files (a wide one and a
 * square one, each for the screens it fits) and the words on its buttons.
 */
( function () {
	'use strict';

	var E = window.brodfestEditor;
	var el = E.el;

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		return E.section(
			E.__( 'Home video' ),
			E.__( 'A video chosen under Brodfest › Site is used instead of these files.' ),
			el(
				E.Fragment,
				null,
				E.list( {
					label: E.__( 'Video files' ),
					items: a.videos,
					min: 0,
					max: 2,
					addLabel: E.__( 'Add a video file' ),
					blank: function () { return { src: '', media: '' }; },
					title: function ( item, i ) { return E.__( 'Video' ) + ' ' + ( i + 1 ); },
					fields: function ( item, i, update ) {
						return el(
							E.Fragment,
							null,
							E.text( E.__( 'Video file address (.mp4)' ), item.src, function ( v ) { update( { src: v } ); }, { type: 'url' } ),
							E.text(
								E.__( 'Shown when the screen is (optional)' ),
								item.media,
								function ( v ) { update( { media: v } ); },
								{ placeholder: '(min-width: 768px)', help: E.__( 'Leave empty for a video that suits every screen. List the widest first.' ) }
							)
						);
					},
					onChange: function ( items ) { set( { videos: items } ); },
				} ),
				E.text( E.__( 'Words shown while there is no video' ), a.placeholder, function ( v ) { set( { placeholder: v } ); }, { placeholder: 'Video to come' } ),
				E.text( E.__( 'Play button (for screen readers)' ), a.playLabel, function ( v ) { set( { playLabel: v } ); }, { placeholder: 'Play the video' } ),
				E.text( E.__( 'Sound button (for screen readers)' ), a.soundLabel, function ( v ) { set( { soundLabel: v } ); }, { placeholder: 'Sound' } )
			)
		);
	}

	E.register( 'brodfest/hero-video', Edit );
}() );
