<?php
/**
 * YouTube embed helpers.
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stream_Overlay_YouTube
 */
final class Stream_Overlay_YouTube {

	/**
	 * Build inner markup for YouTube source.
	 *
	 * @param array<string,mixed> $source Source descriptor.
	 * @return string
	 */
	public static function build_inner( $source ) {
		switch ( $source['type'] ) {
			case 'youtube':
				$video_id = isset( $source['video_id'] ) ? $source['video_id'] : '';
				if ( '' === $video_id ) {
					return '';
				}
				$src = 'https://www.youtube.com/embed/' . rawurlencode( $video_id ) . '?autoplay=1&mute=1&playsinline=1&rel=0';
				return self::iframe( $src, __( 'YouTube video', 'stream-overlay' ), 'autoplay; encrypted-media; picture-in-picture' );

			case 'youtube_channel':
				$channel_id = isset( $source['channel_id'] ) ? $source['channel_id'] : '';
				if ( '' === $channel_id ) {
					return '';
				}
				$src = 'https://www.youtube.com/embed/live_stream?channel=' . rawurlencode( $channel_id ) . '&autoplay=1&mute=1&playsinline=1';
				return self::iframe( $src, __( 'YouTube live stream', 'stream-overlay' ), 'autoplay; encrypted-media; picture-in-picture' );

			case 'youtube_fallback':
				$url = isset( $source['url'] ) ? $source['url'] : '';
				if ( '' === $url ) {
					return '';
				}
				return self::iframe( $url, __( 'YouTube', 'stream-overlay' ), 'autoplay; encrypted-media; picture-in-picture' );
		}

		return '';
	}

	private static function iframe( $src, $title, $allow ) {
		return sprintf(
			'<iframe class="stream-overlay__inner" src="%1$s" title="%2$s" allow="%3$s" allowfullscreen frameborder="0"></iframe>',
			esc_url( $src ),
			esc_attr( $title ),
			esc_attr( $allow )
		);
	}
}
