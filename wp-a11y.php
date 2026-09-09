<?php
/**
 * Plugin Name:       WP A11y
 * Plugin URI:        https://github.com/padilhars/wp-a11y
 * Description:       Plugin de acessibilidade para WordPress, port do local_a11y (Moodle).
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            padilhars
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-a11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_A11Y_VERSION', '1.0.2' );
define( 'WP_A11Y_FILE', __FILE__ );
define( 'WP_A11Y_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_A11Y_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'WP_A11y\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'WP_A11y\\' ) );
		$file     = WP_A11Y_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'WP_A11y\\Activation', 'activate' ) );

add_action( 'plugins_loaded', array( 'WP_A11y\\Plugin', 'init' ) );
