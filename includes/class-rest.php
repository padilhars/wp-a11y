<?php
/**
 * REST API: per-user settings sync + anonymous activation counter.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's core_user/repository JS calls (settings sync)
 * and the `local_a11y_record_activation` web service (activation counter).
 */
class Rest {

	const NAMESPACE_NAME = 'wp-a11y/v1';

	/**
	 * Register routes. Called on `rest_api_init`.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_NAME,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_settings' ),
					'permission_callback' => array( __CLASS__, 'require_login' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save_settings' ),
					'permission_callback' => array( __CLASS__, 'require_login' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_NAME,
			'/activate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'activate' ),
				'permission_callback' => array( __CLASS__, 'allowed_for_current_user' ),
				'args'                => array(
					'featureid' => array(
						'required'          => true,
						'type'              => 'string',
						// NOT sanitize_key(): it lowercases, which would turn
						// e.g. "pauseAnimations" into "pauseanimations" and
						// never match Options::all_ids() again. The allowlist
						// check in Stats::record_activation() is the real
						// validation; this just strips stray characters.
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Permission callback: must be logged in (settings sync is per-user;
	 * guests use localStorage client-side, never this route).
	 *
	 * @return bool
	 */
	public static function require_login() {
		return is_user_logged_in();
	}

	/**
	 * Permission callback: same view gate as the widget itself - open to
	 * guests too when the admin allows it, exactly like Moodle's
	 * `local/a11y:view` capability grants guests access to the stats
	 * web service.
	 *
	 * @return bool
	 */
	public static function allowed_for_current_user() {
		return Manager::allowed_for_current_user();
	}

	/**
	 * GET /settings - the current user's stored settings.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_settings() {
		return rest_ensure_response( Manager::get_current_user_settings() );
	}

	/**
	 * POST /settings - validate and persist the current user's settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function save_settings( \WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$clean   = Manager::save_user_settings( get_current_user_id(), $payload );

		return rest_ensure_response( $clean );
	}

	/**
	 * POST /activate - record one activation of a known option id, if
	 * stats collection is enabled (re-checked server-side, never trusts
	 * the client).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function activate( \WP_REST_Request $request ) {
		$recorded = Stats::record_activation( $request->get_param( 'featureid' ) );

		return rest_ensure_response( array( 'recorded' => $recorded ) );
	}
}
