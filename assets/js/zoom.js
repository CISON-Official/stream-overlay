( function () {
	'use strict';

	if ( typeof StreamOverlayZoom === 'undefined' || typeof ZoomMtgEmbedded === 'undefined' ) {
		return;
	}

	var config = StreamOverlayZoom;

	function hideLoading() {
		var loading = document.getElementById( config.loadingId );
		if ( loading ) {
			loading.style.display = 'none';
		}
	}

	function failLoading() {
		var loading = document.getElementById( config.loadingId );
		if ( loading ) {
			loading.textContent = 'Unable to join the meeting.';
		}
	}

	function start() {
		var root = document.getElementById( config.rootId );
		if ( ! root ) {
			return;
		}

		var client = ZoomMtgEmbedded.createClient();

		client.init( {
			zoomAppRoot: root,
			language: 'en-US',
			assetPath: config.assetPath,
			customize: {
				video: {
					defaultViewType: 'non-fullscreen',
					isResizable: false,
					popper: { disableDraggable: true }
				}
			}
		} );

		client.join( {
			sdkKey: config.sdkKey,
			signature: config.signature,
			meetingNumber: config.meetingNumber,
			password: config.password,
			userName: config.userName
		} ).then( hideLoading ).catch( function ( error ) {
			failLoading();
			if ( window.console && window.console.error ) {
				window.console.error( 'Stream Overlay Zoom error:', error );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
