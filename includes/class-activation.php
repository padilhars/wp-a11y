<?php
/**
 * Plugin activation routine.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once when the plugin is activated.
 */
class Activation {

	/**
	 * Create the stats table. Settings themselves need no explicit
	 * provisioning - {@see Settings::get()} always merges onto defaults.
	 */
	public static function activate() {
		Stats::create_table();
	}
}
