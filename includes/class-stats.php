<?php
/**
 * Aggregate, anonymous usage-stats table.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's classes/stats.php + db/install.xml.
 *
 * A single table, one row per option id: `featureid`, `counter`,
 * `timemodified`. No user/session/course/IP identifier is ever stored, and
 * there is no per-event timestamp - only an aggregate counter per option,
 * same guarantee as the Moodle plugin (see README "Estatísticas de uso").
 */
class Stats {

	/**
	 * Full table name, with the site's table prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wp_a11y_stats';
	}

	/**
	 * Create (or upgrade) the stats table. Called on plugin activation.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			featureid VARCHAR(100) NOT NULL,
			counter BIGINT UNSIGNED NOT NULL DEFAULT 0,
			timemodified BIGINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY featureid (featureid)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Record one activation of an option, if stats collection is enabled.
	 *
	 * Never trusts a client-supplied "stats enabled" flag - re-checks the
	 * admin setting server-side, same as local_a11y\stats::record_activation().
	 *
	 * @param string $featureid A known option id.
	 * @return bool Whether a row was recorded.
	 */
	public static function record_activation( $featureid ) {
		if ( ! Settings::collect_stats_enabled() ) {
			return false;
		}

		if ( ! in_array( $featureid, Options::all_ids(), true ) ) {
			return false;
		}

		global $wpdb;

		$table_name = self::table_name();
		$now        = time();

		$inserted = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (featureid, counter, timemodified) VALUES (%s, 1, %d)
				 ON DUPLICATE KEY UPDATE counter = counter + 1, timemodified = VALUES(timemodified)',
				$table_name,
				$featureid,
				$now
			)
		);

		return false !== $inserted;
	}

	/**
	 * All stats rows, most-activated first.
	 *
	 * @return array<int, object>
	 */
	public static function get_all() {
		global $wpdb;

		$table_name = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT featureid, counter, timemodified FROM %i ORDER BY counter DESC, featureid ASC', $table_name )
		);
	}

	/**
	 * Drop the stats table entirely. Called on uninstall.
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = self::table_name();
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
	}
}
