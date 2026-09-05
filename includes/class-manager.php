<?php
/**
 * Runtime settings manager: defaults, validation, and the page-active gate.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's classes/manager.php.
 *
 * Per-user preference is stored as a single JSON blob in one usermeta row
 * (mirrors Moodle's single `user_preferences` value) rather than one meta
 * row per option.
 */
class Manager {

	const USER_META_KEY = 'wp_a11y_settings';

	/**
	 * Memoized result of {@see self::is_active_on_current_page()}.
	 *
	 * @var bool|null
	 */
	private static $active_on_page = null;

	/**
	 * Default value for every option: false for toggles, 0 for steppers.
	 *
	 * @return array<string, bool|int>
	 */
	public static function get_default_settings() {
		$defaults = array();

		foreach ( Options::all() as $option ) {
			$defaults[ $option['id'] ] = ( 'stepper' === $option['kind'] ) ? 0 : false;
		}

		return $defaults;
	}

	/**
	 * Map of stepper option id => max level.
	 *
	 * @return array<string, int>
	 */
	public static function get_stepper_max() {
		$max = array();

		foreach ( Options::all() as $option ) {
			if ( 'stepper' === $option['kind'] ) {
				$max[ $option['id'] ] = (int) $option['max'];
			}
		}

		return $max;
	}

	/**
	 * Validate/clamp a raw settings payload against the known option list.
	 *
	 * Unknown keys are dropped; keys the admin has disabled are dropped;
	 * stepper values are clamped to [0, max]; booleans accept loose "true"/
	 * "1" string forms (client JSON may arrive already-typed, but this stays
	 * defensive the same way local_a11y\manager::sanitize_settings() is).
	 *
	 * @param mixed $raw Raw settings, usually decoded JSON.
	 * @return array<string, bool|int>
	 */
	public static function sanitize_settings( $raw ) {
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$defaults = self::get_default_settings();
		$stepper_max = self::get_stepper_max();
		$enabled  = array_flip( Settings::enabled_features() );
		$clean    = array();

		foreach ( $defaults as $id => $default_value ) {
			if ( ! isset( $enabled[ $id ] ) ) {
				$clean[ $id ] = $default_value;
				continue;
			}

			if ( ! array_key_exists( $id, $raw ) ) {
				$clean[ $id ] = $default_value;
				continue;
			}

			$value = $raw[ $id ];

			if ( isset( $stepper_max[ $id ] ) ) {
				$level        = self::to_int( $value );
				$clean[ $id ] = max( 0, min( $stepper_max[ $id ], $level ) );
			} else {
				$clean[ $id ] = self::to_bool( $value );
			}
		}

		return $clean;
	}

	/**
	 * Loosely coerce a value to bool (accepts real bools, "true"/"1" strings).
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( $value, array( true, 1, '1', 'true' ), true );
	}

	/**
	 * Loosely coerce a value to int.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	private static function to_int( $value ) {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Count how many keys differ from their default value.
	 *
	 * @param array<string, bool|int> $settings Settings to count.
	 * @return int
	 */
	public static function count_active( $settings ) {
		$defaults = self::get_default_settings();
		$count    = 0;

		foreach ( $defaults as $id => $default_value ) {
			if ( isset( $settings[ $id ] ) && $settings[ $id ] !== $default_value ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Whether the current viewer (guest or logged-in) is allowed to see the
	 * widget at all. There is no restrictive capability by design - this
	 * mirrors local/a11y:view being granted to every archetype including
	 * guests; the only real gate is the "show for guests" admin toggle.
	 *
	 * @return bool
	 */
	public static function allowed_for_current_user() {
		if ( is_user_logged_in() ) {
			return true;
		}

		return Settings::show_for_guests();
	}

	/**
	 * Main runtime gate: should the FAB/panel render on this request?
	 *
	 * Memoized per request. Never active inside wp-admin.
	 *
	 * @return bool
	 */
	public static function is_active_on_current_page() {
		if ( null !== self::$active_on_page ) {
			return self::$active_on_page;
		}

		if ( is_admin() ) {
			self::$active_on_page = false;
			return false;
		}

		self::$active_on_page = Settings::is_enabled()
			&& self::allowed_for_current_user()
			&& ! Settings::current_page_excluded();

		return self::$active_on_page;
	}

	/**
	 * Logged-in user's stored settings, or defaults for guests (guests are
	 * handled client-side via localStorage - see assets/js/storage.js).
	 *
	 * @param int|null $user_id User id; defaults to the current user.
	 * @return array<string, bool|int>
	 */
	public static function get_current_user_settings( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return self::get_default_settings();
		}

		$raw = get_user_meta( $user_id, self::USER_META_KEY, true );

		if ( ! is_string( $raw ) || '' === $raw ) {
			return self::get_default_settings();
		}

		$decoded = json_decode( $raw, true );

		return self::sanitize_settings( $decoded );
	}

	/**
	 * Persist a user's settings as a single sanitized JSON blob.
	 *
	 * @param int   $user_id  User id.
	 * @param mixed $settings Raw settings payload.
	 * @return array<string, bool|int> The sanitized settings that were saved.
	 */
	public static function save_user_settings( $user_id, $settings ) {
		$clean = self::sanitize_settings( $settings );

		update_user_meta( $user_id, self::USER_META_KEY, wp_json_encode( $clean ) );

		return $clean;
	}
}
