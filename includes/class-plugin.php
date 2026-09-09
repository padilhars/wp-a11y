<?php
/**
 * Main bootstrap: wires every piece together on `plugins_loaded`.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook registration entry point.
 */
class Plugin {

	/**
	 * Register all hooks. Called on `plugins_loaded`.
	 */
	public static function init() {
		load_plugin_textdomain( 'wp-a11y', false, dirname( plugin_basename( WP_A11Y_FILE ) ) . '/languages' );

		add_action( 'admin_init', array( Settings::class, 'register' ) );
		add_action( 'admin_menu', array( Admin_Page::class, 'register' ) );
		add_action( 'admin_menu', array( Stats_Page::class, 'register' ), 20 );
		add_action( 'rest_api_init', array( Rest::class, 'register_routes' ) );

		Privacy::register();
		Assets::register();
	}
}
