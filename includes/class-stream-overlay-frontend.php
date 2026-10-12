<?php
/**
 * Front-end rendering.
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stream_Overlay_Frontend
 */
final class Stream_Overlay_Frontend {

	/**
	 * Main plugin instance.
	 *
	 * @var Stream_Overlay_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Stream_Overlay_Plugin $plugin Main plugin instance.
	 */
	public function __construct( Stream_Overlay_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( Stream_Overlay_Plugin::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->plugin->should_render() ) {
			return;
		}

		$url     = $this->plugin->get_url();
		$source  = Stream_Overlay_Zoom::analyze( $url );
		$floating = true;
		$inline   = ( 'shortcode' === get_option( 'stream_overlay_display_mode', 'floating' ) && $this->content_has_shortcode() );

		if ( ! $floating && ! $inline ) {
			return;
		}

		wp_enqueue_style(
			'stream-overlay',
			$this->plugin->url() . 'assets/css/stream-overlay.css',
			array(),
			STREAM_OVERLAY_VERSION
		);

		Stream_Overlay_Zoom::enqueue_sdk_if_needed( $source, $this->plugin );
		wp_add_inline_script(
			'stream-overlay',
			$this->overlay_script(),
			'after'
		);
	}

	/**
	 * Render floating overlay.
	 *
	 * @return void
	 */
	public function render_overlay() {
		// No floating overlay; use shortcode or embed as needed.
	}

	/**
	 * Shortcode render.
	 *
	 * @param array<string,string>|string $atts Attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Live stream', 'stream-overlay' ),
			),
			$atts,
			Stream_Overlay_Plugin::SHORTCODE
		);

		$url    = $this->plugin->get_url();
		$source = Stream_Overlay_Zoom::analyze( $url );
		$inner  = $this->build_inner( $source );

		if ( '' === $inner ) {
			return '';
		}

		wp_enqueue_style(
			'stream-overlay',
			$this->plugin->url() . 'assets/css/stream-overlay.css',
			array(),
			STREAM_OVERLAY_VERSION
		);
		Stream_Overlay_Zoom::enqueue_sdk_if_needed( $source, $this->plugin );
		wp_add_inline_script(
			'stream-overlay',
			$this->overlay_script(),
			'after'
		);

		return sprintf(
			'<div class="stream-overlay-embed" role="region" aria-label="%1$s">%2$s</div>',
			esc_attr( $atts['title'] ),
			$inner
		);
	}

	private function content_has_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		return ( $post instanceof WP_Post ) && has_shortcode( $post->post_content, Stream_Overlay_Plugin::SHORTCODE );
	}

	private function build_inner( $source ) {
		if ( 'youtube' === $source['type'] || 'youtube_channel' === $source['type'] || 'youtube_fallback' === $source['type'] ) {
			return Stream_Overlay_YouTube::build_inner( $source );
		}

		if ( 'zoom' === $source['type'] ) {
			return Stream_Overlay_Zoom::build_zoom_inner( $source, $this->plugin );
		}

		return '';
	}

	private function overlay_script() {
		return <<<'JS'
( function () {
	var overlay = document.getElementById( 'stream-overlay' );
	if ( ! overlay ) {
		return;
	}

	var joinButton = overlay.querySelector( '.stream-overlay__join' );
	if ( ! joinButton ) {
		return;
	}
	joinButton.addEventListener( 'click', function () {
		var container = joinButton.closest( '[data-stream-overlay-zoom]' ) || document.querySelector( '.stream-overlay-embed' ) || overlay;
		var poster = container.querySelector( '.stream-overlay__poster' );
		var iframe = document.createElement( 'iframe' );
		iframe.title = joinButton.getAttribute( 'data-zoom-title' ) || 'Zoom meeting';
		iframe.setAttribute( 'allow', joinButton.getAttribute( 'data-zoom-allow' ) || 'camera; microphone' );
		iframe.setAttribute( 'allowfullscreen', '' );
		iframe.setAttribute( 'referrerpolicy', 'no-referrer-when-downgrade' );
		if ( poster ) {
			poster.style.display = 'none';
		}
		var status = document.createElement( 'div' );
		status.className = 'stream-overlay__notice';
		status.textContent = 'Connecting to the meeting…';
		container.appendChild( status );
		var loaded = false;
		var timer = window.setTimeout( function () {
			if ( loaded ) return;
			status.textContent = 'This meeting could not be embedded. Use "Open in Zoom" below.';
		}, 15000 );
		iframe.addEventListener( 'load', function () {
			loaded = true;
			window.clearTimeout( timer );
			if ( status.parentNode ) status.parentNode.removeChild( status );
		} );
		container.insertBefore( iframe, container.firstChild );
		iframe.src = joinButton.getAttribute( 'data-zoom-src' );
	} );
} )();
JS;
	}
}
