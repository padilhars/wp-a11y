<?php
/**
 * Uninstall routine: removes the plugin's own option and stats table.
 *
 * Per-user preference (usermeta) is intentionally left in place - it is
 * ordinary user data, not plugin configuration, and WordPress core doesn't
 * purge usermeta on plugin uninstall for other plugins either.
 *
 * @package WP_A11y
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-options.php';
require_once __DIR__ . '/includes/class-stats.php';

delete_option( 'wp_a11y_settings' );

\WP_A11y\Stats::drop_table();
