/**
 * Upcoming Calendar Events — Front-end AJAX loader
 *
 * Finds every .uce-events-wrapper on the page, reads its data attributes,
 * fires an AJAX request, and replaces the loading placeholder with the
 * rendered HTML returned from the server.
 */
( function ( $ ) {
	'use strict';

	/**
	 * Load events for a single wrapper element.
	 *
	 * @param {jQuery} $wrapper
	 */
	function loadEvents( $wrapper ) {
		var calendarId      = $wrapper.data( 'calendar-id' );
		var count           = $wrapper.data( 'count' );
		var daysAhead       = $wrapper.data( 'days-ahead' );
		var showDescription = $wrapper.data( 'show-description' );

		$.ajax( {
			url:    uceData.ajaxUrl,
			method: 'POST',
			data:   {
				action:           'uce_fetch_events',
				nonce:            uceData.nonce,
				calendar_id:      calendarId,
				count:            count,
				days_ahead:       daysAhead,
				show_description: showDescription,
			},
			success: function ( response ) {
				if ( response.success && response.data && response.data.html ) {
					$wrapper.html( response.data.html );
				} else {
					var msg = ( response.data && response.data.message )
						? response.data.message
						: 'An unknown error occurred.';
					$wrapper.html( renderError( msg ) );
				}
			},
			error: function ( xhr ) {
				var msg = 'Failed to load events (HTTP ' + xhr.status + ').';
				$wrapper.html( renderError( msg ) );
			},
		} );
	}

	/**
	 * Build an accessible error paragraph.
	 *
	 * @param  {string} message
	 * @return {string} HTML string
	 */
	function renderError( message ) {
		return '<p class="uce-error" role="alert">' + escapeHtml( message ) + '</p>';
	}

	/**
	 * Minimal HTML escaping for untrusted strings inserted into the DOM.
	 *
	 * @param  {string} str
	 * @return {string}
	 */
	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	// Initialise all wrappers once the DOM is ready.
	$( function () {
		$( '.uce-events-wrapper' ).each( function () {
			loadEvents( $( this ) );
		} );
	} );

} )( jQuery );
