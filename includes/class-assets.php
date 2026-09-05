<?php
/**
 * Front-end asset registration: CSS, JS script modules, and the widget hooks.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything needed to get the FAB/panel on the page, gated by
 * {@see Manager::is_active_on_current_page()}.
 */
class Assets {

	/**
	 * Register hooks. Called from Plugin::init().
	 */
	public static function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'print_head_extras' ) );
		add_action( 'wp_footer', array( __CLASS__, 'print_widget' ), 9999 );
	}

	/**
	 * Whether the widget should load on this request.
	 *
	 * @return bool
	 */
	private static function should_load() {
		return Manager::is_active_on_current_page();
	}

	/**
	 * Enqueue the effects/panel CSS and the JS script modules.
	 */
	public static function enqueue() {
		if ( ! self::should_load() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'wp-a11y-effects',
			WP_A11Y_URL . 'assets/css/a11y-effects.css',
			array(),
			WP_A11Y_VERSION
		);

		wp_enqueue_style(
			'wp-a11y-panel',
			WP_A11Y_URL . 'assets/css/panel.css',
			array( 'dashicons' ),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/storage',
			WP_A11Y_URL . 'assets/js/storage.js',
			array(),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/effects',
			WP_A11Y_URL . 'assets/js/effects.js',
			array(),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/profiles',
			WP_A11Y_URL . 'assets/js/profiles.js',
			array(),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/stats',
			WP_A11Y_URL . 'assets/js/stats.js',
			array(),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/panel',
			WP_A11Y_URL . 'assets/js/panel.js',
			array( '@wp-a11y/storage', '@wp-a11y/effects', '@wp-a11y/profiles', '@wp-a11y/stats' ),
			WP_A11Y_VERSION
		);

		wp_register_script_module(
			'@wp-a11y/main',
			WP_A11Y_URL . 'assets/js/main.js',
			array( '@wp-a11y/panel' ),
			WP_A11Y_VERSION
		);

		wp_enqueue_script_module( '@wp-a11y/main' );
	}

	/**
	 * Open the #wp-a11y-content-wrap wrapper (right after <body>), then
	 * print the colorblind SVG filters + effect-color CSS vars + no-FOUC
	 * script.
	 *
	 * The wrapper exists so that `filter`-based effects (invert, color-blind
	 * simulation, saturation, blue-light) can target it instead of <body>
	 * itself - CSS `filter` on an ancestor forces a new containing block for
	 * `position: fixed` descendants, which would break the FAB/panel's fixed
	 * positioning if they were inside the filtered subtree. Closed in
	 * {@see self::print_widget()}, which runs after everything else in the
	 * footer, so the FAB/panel are printed as siblings *after* the wrapper,
	 * never inside it. (local_a11y solves the same problem by relocating the
	 * FAB/panel DOM nodes with JS at runtime - this achieves the same result
	 * server-side, since WordPress has no equivalent of Moodle's fixed
	 * `.moodle-shell` content wrapper to target directly.)
	 */
	public static function print_head_extras() {
		if ( ! self::should_load() ) {
			return;
		}

		echo '<div id="wp-a11y-content-wrap">';
		Render::render_head_extras();
		Render::render_nofouc_script();
	}

	/**
	 * Close the #wp-a11y-content-wrap wrapper, then print the FAB + panel
	 * HTML as late as possible in the footer so it ends up after the
	 * theme's own content and outside the filtered wrapper.
	 */
	public static function print_widget() {
		if ( ! self::should_load() ) {
			return;
		}

		echo '</div><!-- #wp-a11y-content-wrap -->';
		Render::render_config_script();
		Render::render_widget();
	}
}
