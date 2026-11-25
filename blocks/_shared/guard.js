/**
 * Sends the page's swap count with every save the block editor makes to it, so
 * the server can refuse a save from a tab opened before the live site was
 * switched (the page now holds the other site's blocks). The count is set in
 * window.brodfestGuard by includes/held-pages.php.
 */
( function () {
	'use strict';

	var guard = window.brodfestGuard;

	if ( ! guard || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	window.wp.apiFetch.use( function ( options, next ) {
		var match = /\/wp\/v2\/(?:pages|brodfest-held-pages)\/(\d+)(?:\/autosaves)?(?:\?.*)?$/.exec( options.path || '' );
		var method = ( options.method || 'GET' ).toUpperCase();

		if ( match && method !== 'GET' && parseInt( match[ 1 ], 10 ) === guard.id ) {
			options.headers = Object.assign( {}, options.headers, { 'X-Brodfest-Swap-Count': String( guard.gen ) } );
		}

		return next( options );
	} );
}() );
