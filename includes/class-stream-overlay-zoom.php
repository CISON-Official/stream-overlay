<?php
/**
 * Zoom parsing + embed logic (URL approach).
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stream_Overlay_Zoom
 */
final class Stream_Overlay_Zoom {

	/**
	 * DOM id for optional SDK mount.
	 *
	 * @var string
	 */
	const ROOT_ID = 'stream-overlay-zoom-root';

	/**
	 * Localized JS object for SDK.
	 *
	 * @var string
	 */
	const LOCALIZE_OBJECT = 'StreamOverlayZoom';

	/**
	 * Analyze a URL and return descriptor.
	 *
	 * @param string $url Raw URL.
	 * @return array<string,mixed>
	 */
	public static function analyze( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return array( 'type' => 'none' );
		}

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return array( 'type' => 'none' );
		}

		$host  = strtolower( $parts['host'] );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '';
		$query = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		if ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtu.be' ) ) {
			return self::analyze_youtube( $host, $path, $query, $url );
		}

		if ( false !== strpos( $host, 'zoom.us' ) && false !== strpos( $path, '/j/' ) ) {
			return self::analyze_zoom( $host, $path, $query, $url );
		}

		return array( 'type' => 'none' );
	}

	/**
	 * Parse YouTube.
	 *
	 * @param string               $host  Host.
	 * @param string               $path  Path.
	 * @param array<string,string> $query Query.
	 * @param string               $raw   Raw URL.
	 * @return array<string,mixed>
	 */
	private static function analyze_youtube( $host, $path, $query, $raw ) {
		$video_id = '';

		if ( ! empty( $query['v'] ) ) {
			$video_id = $query['v'];
		} elseif ( false !== strpos( $host, 'youtu.be' ) ) {
			$video_id = self::first_segment( $path );
		} elseif ( false !== strpos( $path, '/embed/' ) ) {
			$video_id = self::segment_after( $path, 'embed' );
		} elseif ( false !== strpos( $path, '/shorts/' ) ) {
			$video_id = self::segment_after( $path, 'shorts' );
		} elseif ( false !== strpos( $path, '/live/' ) ) {
			$video_id = self::segment_after( $path, 'live' );
		}

		if ( '' !== $video_id ) {
			return array(
				'type'     => 'youtube',
				'video_id' => sanitize_text_field( $video_id ),
			);
		}

		if ( false !== strpos( $path, '/channel/' ) ) {
			$channel_id = self::segment_after( $path, 'channel' );
			if ( '' !== $channel_id ) {
				return array(
					'type'       => 'youtube_channel',
					'channel_id' => sanitize_text_field( $channel_id ),
				);
			}
		}

		return array(
			'type' => 'youtube_fallback',
			'url'  => $raw,
		);
	}

	/**
	 * Parse Zoom.
	 *
	 * @param string               $host  Host.
	 * @param string               $path  Path.
	 * @param array<string,string> $query Query.
	 * @param string               $raw   Raw URL.
	 * @return array<string,mixed>
	 */
	private static function analyze_zoom( $host, $path, $query, $raw ) {
		$position = strpos( $path, '/j/' );
		if ( false === $position ) {
			return array( 'type' => 'none' );
		}
		$after   = substr( $path, $position + 3 );
		$after   = ( false !== strpos( $after, '/' ) ) ? substr( $after, 0, strpos( $after, '/' ) ) : $after;
		$meeting = preg_replace( '/[^0-9]/', '', $after );

		if ( '' === $meeting ) {
			return array( 'type' => 'none' );
		}

		return array(
			'type'       => 'zoom',
			'meeting_id' => $meeting,
			'pwd'        => isset( $query['pwd'] ) ? sanitize_text_field( $query['pwd'] ) : '',
			'host'       => $host,
			'join_url'   => esc_url_raw( $raw ),
		);
	}

	private static function first_segment( $path ) {
		$path = trim( (string) $path, '/' );
		if ( '' === $path ) {
			return '';
		}
		$parts = explode( '/', $path );
		return sanitize_text_field( array_shift( $parts ) );
	}

	private static function segment_after( $path, $key ) {
		$segments = array_values( array_filter( explode( '/', $path ) ) );
		$count    = count( $segments );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( $segments[ $i ] === $key && isset( $segments[ $i + 1 ] ) ) {
				return sanitize_text_field( $segments[ $i + 1 ] );
			}
		}
		return '';
	}

	/**
	 * Build Zoom inner markup.
	 *
	 * @param array<string,mixed> $source Source.
	 * @param Stream_Overlay_Plugin $plugin Plugin.
	 * @return string
	 */
	public static function build_zoom_inner( $source, $plugin ) {
		$meeting_id = isset( $source['meeting_id'] ) ? $source['meeting_id'] : '';
		$pwd        = isset( $source['pwd'] ) ? $source['pwd'] : '';
		$join_url   = isset( $source['join_url'] ) ? $source['join_url'] : '';

		$web_client = 'https://app.zoom.us/wc/' . rawurlencode( $meeting_id );
		if ( '' !== $pwd ) {
			$web_client = add_query_arg( 'pwd', $pwd, $web_client );
		}
		$web_client = apply_filters( 'stream_overlay_zoom_web_client_url', $web_client, $source );

		$sdk_key   = (string) apply_filters( 'stream_overlay_zoom_sdk_key', '', $source );
		$signature = (string) apply_filters( 'stream_overlay_zoom_signature', '', $source );

		if ( '' !== $sdk_key && '' !== $signature ) {
			return '<div id="' . esc_attr( self::ROOT_ID ) . '"></div>';
		}

		$join_url = $join_url ? $join_url : $web_client;

		return sprintf(
			'<div class="stream-overlay__zoom" data-stream-overlay-zoom>' .
				'<div class="stream-overlay__poster">' .
					'<span class="stream-overlay__poster-title">%1$s</span>' .
					'<span class="stream-overlay__poster-meta">%2$s</span>' .
					'<button type="button" class="stream-overlay__join" ' .
						'data-zoom-src="%3$s" data-zoom-title="%4$s" ' .
						'data-zoom-allow="%5$s">%6$s</button>' .
				'</div>' .
				'<div class="stream-overlay__zoom-bar">' .
					'<a class="stream-overlay__zoom-open" href="%7$s" target="_blank" rel="noopener noreferrer">%8$s</a>' .
				'</div>' .
			'</div>',
			esc_html__( 'Zoom meeting', 'stream-overlay' ),
			sprintf( esc_html__( 'Meeting %s', 'stream-overlay' ), esc_html( $meeting_id ) ),
			esc_url( $web_client ),
			esc_attr__( 'Zoom meeting', 'stream-overlay' ),
			esc_attr( 'camera; microphone; fullscreen; display-capture; autoplay; clipboard-write' ),
			esc_html__( 'Join meeting', 'stream-overlay' ),
			esc_url( $join_url ),
			esc_html__( 'Open in Zoom ↗', 'stream-overlay' )
		);
	}

	/**
	 * Enqueue SDK if credentials provided.
	 *
	 * @param array<string,mixed> $source Source.
	 * @param Stream_Overlay_Plugin $plugin Plugin.
	 * @return void
	 */
	public static function enqueue_sdk_if_needed( $source, $plugin ) {
		$sdk_key   = (string) apply_filters( 'stream_overlay_zoom_sdk_key', '', $source );
		$signature = (string) apply_filters( 'stream_overlay_zoom_signature', '', $source );

		if ( 'zoom' !== $source['type'] || '' === $sdk_key || '' === $signature ) {
			return;
		}

		$version = Stream_Overlay_Plugin::ZOOM_SDK_VERSION;
		$vendor  = 'https://source.zoom.us/' . $version . '/lib/vendor/';

		wp_enqueue_script( 'stream-overlay-react', $vendor . 'react.min.js', array(), $version, true );
		wp_enqueue_script( 'stream-overlay-react-dom', $vendor . 'react-dom.min.js', array( 'stream-overlay-react' ), $version, true );
		wp_enqueue_script( 'stream-overlay-redux', $vendor . 'redux.min.js', array(), $version, true );
		wp_enqueue_script( 'stream-overlay-redux-thunk', $vendor . 'redux-thunk.min.js', array( 'stream-overlay-redux' ), $version, true );
		wp_enqueue_script( 'stream-overlay-lodash', $vendor . 'lodash.min.js', array(), $version, true );

		$sdk_handle = 'stream-overlay-zoom-sdk';
		wp_enqueue_script(
			$sdk_handle,
			'https://source.zoom.us/' . $version . '/zoom-meeting-embedded-' . $version . '.min.js',
			array(
				'stream-overlay-react',
				'stream-overlay-react-dom',
				'stream-overlay-redux',
				'stream-overlay-redux-thunk',
				'stream-overlay-lodash',
			),
			$version,
			true
		);

		wp_localize_script(
			$sdk_handle,
			self::LOCALIZE_OBJECT,
			array(
				'sdkKey'        => $sdk_key,
				'signature'     => $signature,
				'meetingNumber' => isset( $source['meeting_id'] ) ? $source['meeting_id'] : '',
				'password'      => isset( $source['pwd'] ) ? $source['pwd'] : '',
				'userName'      => __( 'Guest', 'stream-overlay' ),
				'rootId'        => self::ROOT_ID,
				'assetPath'     => 'https://source.zoom.us/' . $version . '/lib',
			)
		);

		wp_add_inline_script( $sdk_handle, self::zoom_init_script(), 'after' );
	}

	private static function zoom_init_script() {
		return <<<'JS'
( function () {
	if ( typeof StreamOverlayZoom === 'undefined' || typeof ZoomMtgEmbedded === 'undefined' ) {
		return;
	}
	if ( ! StreamOverlayZoom.sdkKey || ! StreamOverlayZoom.signature ) {
		return;
	}

	function start() {
		var root = document.getElementById( StreamOverlayZoom.rootId );
		if ( ! root ) {
			return;
		}
		var client = ZoomMtgEmbedded.createClient();
		client.init( {
			zoomAppRoot: root,
			language: 'en-US',
			assetPath: StreamOverlayZoom.assetPath
		} );
		client.join( {
			sdkKey: StreamOverlayZoom.sdkKey,
			signature: StreamOverlayZoom.signature,
			meetingNumber: StreamOverlayZoom.meetingNumber,
			password: StreamOverlayZoom.password,
			userName: StreamOverlayZoom.userName
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
JS;
	}
}
