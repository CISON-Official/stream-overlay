( function ( $ ) {
	'use strict';

	$( function () {
		var $select = $( '#stream_overlay_source_type' );

		if ( ! $select.length ) {
			return;
		}

		function toggleFields() {
			var value = $select.val();
			$( '.stream-overlay-youtube-field' ).toggle( value === 'youtube' );
			$( '.stream-overlay-zoom-field' ).toggle( value === 'zoom' );
		}

		$select.on( 'change', toggleFields );
		toggleFields();
	} );
} )( jQuery );
