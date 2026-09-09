<?php
/**
 * Site-wide (admin) settings: storage, defaults, sanitization and runtime gates.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's classes/config.php, but backed by a single
 * `wp_options` row instead of one Moodle get_config() value per field.
 */
class Settings {

	const OPTION_NAME = 'wp_a11y_settings';

	/**
	 * Default values for every admin-configurable field, across all 5 sections.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			// General.
			'enabled'               => true,
			'showforguests'         => true,
			'excludedpages'         => '',
			'enabledfeatures'       => Options::all_ids(),
			// FAB.
			'fabposition'           => 'bottom-right',
			'fabicon'               => 'default',
			'fabshape'              => 'circle',
			// Panel.
			'panelformat'           => 'popover',
			'density'               => 'regular',
			'showprofiles'          => true,
			'footertext'            => '',
			// Colors.
			'accent'                => '#3b82f6',
			'highlighttitlescolor'  => '#eab308',
			'highlightlinkscolor'   => '#3b82f6',
			'highlightbuttonscolor' => '#f97316',
			'readingguidecolor'     => '#3b82f6',
			// Stats.
			'collectstats'          => false,
		);
	}

	/**
	 * Current settings, merged onto defaults (so newly added fields never
	 * come back missing for sites that saved before the field existed).
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Register the option, sections and fields with the Settings API.
	 */
	public static function register() {
		register_setting(
			'wp_a11y_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'wp_a11y_general', __( 'Geral', 'wp-a11y' ), '__return_false', 'wp-a11y' );
		add_settings_section( 'wp_a11y_fab', __( 'Botão Flutuante (FAB)', 'wp-a11y' ), '__return_false', 'wp-a11y' );
		add_settings_section( 'wp_a11y_panel', __( 'Painel', 'wp-a11y' ), '__return_false', 'wp-a11y' );
		add_settings_section( 'wp_a11y_colors', __( 'Cores', 'wp-a11y' ), '__return_false', 'wp-a11y' );
		add_settings_section( 'wp_a11y_stats', __( 'Estatísticas', 'wp-a11y' ), '__return_false', 'wp-a11y' );
	}

	/**
	 * Sanitize/validate a raw settings submission (Settings API callback).
	 *
	 * Mirrors local_a11y\manager::sanitize_settings()'s spirit: merge onto
	 * defaults, drop unknown keys, coerce types, clamp/validate enums.
	 *
	 * @param mixed $input Raw input from the settings form.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::defaults();
		$clean    = $defaults;

		$clean['enabled']       = ! empty( $input['enabled'] );
		$clean['showforguests'] = ! empty( $input['showforguests'] );

		if ( isset( $input['excludedpages'] ) ) {
			$lines                  = preg_split( '/\r\n|\r|\n/', (string) $input['excludedpages'] );
			$lines                  = array_map( 'trim', $lines );
			$lines                  = array_filter( $lines, static fn( $line ) => '' !== $line );
			$clean['excludedpages'] = implode( "\n", array_map( 'sanitize_text_field', $lines ) );
		}

		if ( isset( $input['enabledfeatures'] ) && is_array( $input['enabledfeatures'] ) ) {
			$known                    = Options::all_ids();
			$clean['enabledfeatures'] = array_values( array_intersect( $known, $input['enabledfeatures'] ) );
		} else {
			$clean['enabledfeatures'] = array();
		}

		$clean['fabposition'] = self::enum( $input, 'fabposition', array( 'bottom-right', 'bottom-left', 'middle-right', 'middle-left' ), $defaults['fabposition'] );
		$clean['fabicon']     = self::enum( $input, 'fabicon', array( 'default', 'un' ), $defaults['fabicon'] );
		$clean['fabshape']    = self::enum( $input, 'fabshape', array( 'circle', 'square' ), $defaults['fabshape'] );

		$clean['panelformat']  = self::enum( $input, 'panelformat', array( 'popover', 'drawer', 'modal' ), $defaults['panelformat'] );
		$clean['density']      = self::enum( $input, 'density', array( 'compact', 'regular', 'comfortable' ), $defaults['density'] );
		$clean['showprofiles'] = ! empty( $input['showprofiles'] );
		$clean['footertext']   = isset( $input['footertext'] ) ? wp_kses_post( $input['footertext'] ) : '';

		foreach ( array( 'accent', 'highlighttitlescolor', 'highlightlinkscolor', 'highlightbuttonscolor', 'readingguidecolor' ) as $color_field ) {
			$clean[ $color_field ] = self::hex_color( $input, $color_field, $defaults[ $color_field ] );
		}

		$clean['collectstats'] = ! empty( $input['collectstats'] );

		return $clean;
	}

	/**
	 * Validate an enum-style field, falling back to a default on any mismatch.
	 *
	 * @param array<string, mixed> $input   Raw input.
	 * @param string                $key     Field key.
	 * @param array<string>         $allowed Allowed values.
	 * @param string                $default Fallback value.
	 * @return string
	 */
	private static function enum( $input, $key, $allowed, $default ) {
		$value = isset( $input[ $key ] ) ? (string) $input[ $key ] : $default;

		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Validate a hex color field, falling back to a default on any mismatch.
	 *
	 * @param array<string, mixed> $input   Raw input.
	 * @param string                $key     Field key.
	 * @param string                $default Fallback value.
	 * @return string
	 */
	private static function hex_color( $input, $key, $default ) {
		$value = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : null;

		return $value ? $value : $default;
	}

	/**
	 * Whether the plugin is enabled site-wide.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return ! empty( self::get()['enabled'] );
	}

	/**
	 * Whether logged-out visitors see the widget.
	 *
	 * @return bool
	 */
	public static function show_for_guests() {
		return ! empty( self::get()['showforguests'] );
	}

	/**
	 * Configured URL wildcard exclusion patterns, one per line.
	 *
	 * @return array<string>
	 */
	public static function excluded_page_patterns() {
		$raw = self::get()['excludedpages'];

		if ( '' === trim( (string) $raw ) ) {
			return array();
		}

		return array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) );
	}

	/**
	 * Whether the current request URL matches an exclusion pattern.
	 *
	 * @return bool
	 */
	public static function current_page_excluded() {
		$patterns = self::excluded_page_patterns();

		if ( empty( $patterns ) ) {
			return false;
		}

		$current = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		foreach ( $patterns as $pattern ) {
			$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';

			if ( preg_match( $regex, $current ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * FAB/panel appearance settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_appearance() {
		$settings = self::get();

		return array(
			'fabposition'  => $settings['fabposition'],
			'fabicon'      => $settings['fabicon'],
			'fabshape'     => $settings['fabshape'],
			'panelformat'  => $settings['panelformat'],
			'density'      => $settings['density'],
			'accent'       => $settings['accent'],
			'showprofiles' => (bool) $settings['showprofiles'],
		);
	}

	/**
	 * The 4 configurable highlight/reading-guide colors.
	 *
	 * @return array<string, string>
	 */
	public static function get_effect_colors() {
		$settings = self::get();

		return array(
			'highlighttitles'  => $settings['highlighttitlescolor'],
			'highlightlinks'   => $settings['highlightlinkscolor'],
			'highlightbuttons' => $settings['highlightbuttonscolor'],
			'readingguide'     => $settings['readingguidecolor'],
		);
	}

	/**
	 * Ids of options the admin has left enabled.
	 *
	 * @return array<string>
	 */
	public static function enabled_features() {
		$features = self::get()['enabledfeatures'];

		return empty( $features ) ? Options::all_ids() : $features;
	}

	/**
	 * Sanitized panel footer text (already wp_kses_post'd on save).
	 *
	 * @return string
	 */
	public static function footer_text() {
		$text = self::get()['footertext'];

		return '' !== $text ? $text : __( 'Desenvolvido com ❤️ pela <strong>UFPel</strong> para você.', 'wp-a11y' );
	}

	/**
	 * Whether aggregate usage stats collection is turned on.
	 *
	 * @return bool
	 */
	public static function collect_stats_enabled() {
		return ! empty( self::get()['collectstats'] );
	}
}
