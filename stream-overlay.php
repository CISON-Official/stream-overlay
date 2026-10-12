<?php
/**
 * Plugin Name:       Stream Overlay
 * Plugin URI:        https://example.com/stream-overlay
 * Description:       Paste a single YouTube or Zoom URL to show a floating overlay (bottom-right) on every page.
 * Version:           1.2.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            CISON
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       stream-overlay
 * Domain Path:       /languages
 *
 * @package StreamOverlay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STREAM_OVERLAY_FILE', __FILE__ );
define( 'STREAM_OVERLAY_VERSION', '1.2.0' );

require_once __DIR__ . '/includes/class-stream-overlay-plugin.php';

register_activation_hook( __FILE__, array( 'Stream_Overlay_Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Stream_Overlay_Plugin', 'uninstall' ) );

function stream_overlay() {
	return Stream_Overlay_Plugin::instance( STREAM_OVERLAY_FILE );
}

stream_overlay();
