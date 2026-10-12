<?php
/**
 * Admin settings screen.
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stream_Overlay_Settings
 */
final class Stream_Overlay_Settings {

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

		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'maybe_admin_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STREAM_OVERLAY_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Register the admin settings page.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Stream Overlay', 'stream-overlay' ),
			__( 'Stream Overlay', 'stream-overlay' ),
			Stream_Overlay_Plugin::CAPABILITY,
			Stream_Overlay_Plugin::MENU_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-video-alt3',
			81
		);
	}

	/**
	 * Add Settings link to plugin row.
	 *
	 * @param array<int,string> $links Existing links.
	 * @return array<int,string>
	 */
	public function plugin_action_links( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . Stream_Overlay_Plugin::MENU_SLUG ) ),
			esc_html__( 'Settings', 'stream-overlay' )
		);
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Register setting and field.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			Stream_Overlay_Plugin::OPTION_GROUP,
			Stream_Overlay_Plugin::OPTION_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_url' ),
				'default'           => '',
			)
		);

		add_settings_section(
			'stream_overlay_section',
			__( 'Stream Source', 'stream-overlay' ),
			array( $this, 'render_section_intro' ),
			Stream_Overlay_Plugin::MENU_SLUG
		);

		add_settings_field(
			'stream_url',
			__( 'Stream or Meeting URL', 'stream-overlay' ),
			array( $this, 'render_url_field' ),
			Stream_Overlay_Plugin::MENU_SLUG,
			'stream_overlay_section'
		);
	}

	/**
	 * Section intro.
	 *
	 * @return void
	 */
	public function render_section_intro() {
		echo '<p>' . esc_html__( 'Paste a YouTube or Zoom link. The platform is detected automatically.', 'stream-overlay' ) . '</p>';
	}

	/**
	 * URL field.
	 *
	 * @return void
	 */
	public function render_url_field() {
		$value = $this->plugin->get_url();
		?>
		<input type="url"
			id="stream_overlay_url"
			name="<?php echo esc_attr( Stream_Overlay_Plugin::OPTION_KEY ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text code"
			placeholder="https://www.youtube.com/watch?v=... or https://zoom.us/j/..."
			autocomplete="off" />
		<p class="description"><?php esc_html_e( 'Examples: YouTube watch/live/youtu.be/channel or a Zoom meeting link.', 'stream-overlay' ); ?></p>
		<?php
	}

	/**
	 * Sanitize URL.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_url( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return esc_url_raw( trim( $value ) );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( Stream_Overlay_Plugin::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stream-overlay' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( Stream_Overlay_Plugin::OPTION_GROUP );
				do_settings_sections( Stream_Overlay_Plugin::MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Admin notice when URL missing/unrecognized.
	 *
	 * @return void
	 */
	public function maybe_admin_notice() {
		if ( ! current_user_can( Stream_Overlay_Plugin::CAPABILITY ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$allowed = array(
			'toplevel_page_' . Stream_Overlay_Plugin::MENU_SLUG,
			'plugins',
		);

		if ( ! in_array( $screen->id, $allowed, true ) ) {
			return;
		}

		$url = $this->plugin->get_url();
		if ( '' === $url ) {
			$message = __( 'Stream Overlay: no stream URL is configured yet.', 'stream-overlay' );
		} else {
			$source = Stream_Overlay_Zoom::analyze( $url );
			if ( 'none' === $source['type'] ) {
				$message = __( 'Stream Overlay: the configured URL was not recognised as a YouTube or Zoom link.', 'stream-overlay' );
			} else {
				return;
			}
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
