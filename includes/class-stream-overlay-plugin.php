<?php
/**
 * Main plugin orchestrator.
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stream_Overlay_Plugin
 */
final class Stream_Overlay_Plugin {

	/**
	 * Option name used to persist settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'stream_overlay_url';

	/**
	 * Admin menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'stream-overlay';

	/**
	 * Capability required to manage the plugin.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Settings group used by the Settings API.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'stream_overlay_group';

	/**
	 * Zoom Meeting Web SDK version to load from source.zoom.us.
	 *
	 * @var string
	 */
	const ZOOM_SDK_VERSION = '1.9.9';

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const SHORTCODE = 'stream_overlay';

	/**
	 * Singleton instance.
	 *
	 * @var Stream_Overlay_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Public URL to the plugin directory (trailing slash).
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @param string|null $file Main plugin file path (required on first call).
	 * @return Stream_Overlay_Plugin
	 */
	public static function instance( $file = null ) {
		if ( null === self::$instance ) {
			self::$instance = new self( (string) $file );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $file Main plugin file path.
	 */
	private function __construct( $file ) {
		$this->file = $file;
		$this->url  = plugin_dir_url( $file );

		$this->load_dependencies();
		$this->init_components();
	}

	/**
	 * Load required class files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once __DIR__ . '/class-stream-overlay-settings.php';
		require_once __DIR__ . '/class-stream-overlay-frontend.php';
		require_once __DIR__ . '/class-stream-overlay-youtube.php';
		require_once __DIR__ . '/class-stream-overlay-zoom.php';
	}

	/**
	 * Instantiate components.
	 *
	 * @return void
	 */
	private function init_components() {
		new Stream_Overlay_Settings( $this );
		new Stream_Overlay_Frontend( $this );
	}

	/**
	 * Public URL to the plugin directory.
	 *
	 * @return string
	 */
	public function url() {
		return $this->url;
	}

	/**
	 * Absolute filesystem path to the plugin directory (no trailing slash).
	 *
	 * @return string
	 */
	public function path() {
		return dirname( $this->file );
	}

	/**
	 * Fetch the saved URL.
	 *
	 * @return string
	 */
	public function get_url() {
		$stored = get_option( self::OPTION_KEY, '' );
		if ( ! is_string( $stored ) ) {
			$stored = '';
		}
		return trim( $stored );
	}

	/**
	 * Whether the current request should render the overlay.
	 *
	 * @return bool
	 */
	public function should_render() {
		if ( is_admin() || is_feed() || is_embed() || is_robots() || is_trackback() ) {
			return false;
		}
		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return false;
		}
		return true;
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		add_option( self::OPTION_KEY, '' );
	}

	/**
	 * Plugin uninstall callback.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( self::OPTION_KEY );
	}
}
