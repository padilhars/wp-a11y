<?php
/**
 * Builds the FAB/panel render context and includes the PHP templates.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's classes/output/{fab,panel,renderer}.php, minus
 * the Mustache indirection - PHP partials in templates/ read straight from
 * the context arrays built here.
 */
class Render {

	/**
	 * Human-readable labels for every option id (source of truth for the UI
	 * text - same terminology the Moodle plugin's README already uses).
	 *
	 * @return array<string, string>
	 */
	public static function option_labels() {
		return array(
			'readableFont'     => __( 'Fonte Legível', 'wp-a11y' ),
			'dyslexicFont'     => __( 'Fonte para Dislexia', 'wp-a11y' ),
			'highlightTitles'  => __( 'Destacar Títulos', 'wp-a11y' ),
			'highlightLinks'   => __( 'Destacar Links', 'wp-a11y' ),
			'highlightButtons' => __( 'Destacar Botões', 'wp-a11y' ),
			'textSize'         => __( 'Tamanho do Texto', 'wp-a11y' ),
			'lineHeight'       => __( 'Altura da Linha', 'wp-a11y' ),
			'textSpacing'      => __( 'Espaçamento do Texto', 'wp-a11y' ),
			'wordSpacing'      => __( 'Espaçamento entre Palavras', 'wp-a11y' ),
			'textAlign'        => __( 'Alinhamento do Texto', 'wp-a11y' ),
			'bionicReading'    => __( 'Leitura Biônica', 'wp-a11y' ),
			'contrast'         => __( 'Contraste', 'wp-a11y' ),
			'invertColors'     => __( 'Inverter Cores', 'wp-a11y' ),
			'colorChange'      => __( 'Mudar Cores', 'wp-a11y' ),
			'saturation'       => __( 'Saturação', 'wp-a11y' ),
			'blueLightFilter'  => __( 'Filtro de Luz Azul', 'wp-a11y' ),
			'hideImages'       => __( 'Ocultar Imagens', 'wp-a11y' ),
			'pauseAnimations'  => __( 'Pausar Animações', 'wp-a11y' ),
			'silenceMedia'     => __( 'Silenciar Mídia', 'wp-a11y' ),
			'tooltips'         => __( 'Dicas de Ferramentas', 'wp-a11y' ),
			'readingGuide'     => __( 'Guia de Leitura', 'wp-a11y' ),
			'readingMask'      => __( 'Máscara de Leitura', 'wp-a11y' ),
			'magnifier'        => __( 'Lupa', 'wp-a11y' ),
			'cursor'           => __( 'Cursor', 'wp-a11y' ),
			'focusMode'        => __( 'Modo Foco', 'wp-a11y' ),
			'screenReader'     => __( 'Leitor de Tela', 'wp-a11y' ),
			'virtualKeyboard'  => __( 'Teclado Virtual', 'wp-a11y' ),
			'voiceCommands'    => __( 'Comandos por Voz', 'wp-a11y' ),
			'faceNavigation'   => __( 'Navegação por Face', 'wp-a11y' ),
		);
	}

	/**
	 * Human-readable labels for every category id.
	 *
	 * @return array<string, string>
	 */
	public static function category_labels() {
		return array(
			'typography' => __( 'Texto e Tipografia', 'wp-a11y' ),
			'color'      => __( 'Cores e Contraste', 'wp-a11y' ),
			'media'      => __( 'Mídia e Animação', 'wp-a11y' ),
			'navigation' => __( 'Foco e Navegação', 'wp-a11y' ),
			'advanced'   => __( 'Recursos Avançados', 'wp-a11y' ),
		);
	}

	/**
	 * Human-readable labels for every profile id.
	 *
	 * @return array<string, string>
	 */
	public static function profile_labels() {
		return array(
			'lowVision' => __( 'Baixa Visão', 'wp-a11y' ),
			'colorBlind' => __( 'Daltonismo', 'wp-a11y' ),
			'dyslexia'  => __( 'Dislexia', 'wp-a11y' ),
			'adhd'      => __( 'TDAH/Foco', 'wp-a11y' ),
			'senior'    => __( 'Idoso/Sênior', 'wp-a11y' ),
			'epilepsy'  => __( 'Epilepsia', 'wp-a11y' ),
			'motor'     => __( 'Deficiência Motora', 'wp-a11y' ),
			'cognitive' => __( 'Cognitivo', 'wp-a11y' ),
			'night'     => __( 'Modo Noturno', 'wp-a11y' ),
		);
	}

	/**
	 * Build the panel's category/option context tree.
	 *
	 * @param array<string, bool|int> $settings Current settings.
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_categories( $settings ) {
		$labels        = self::option_labels();
		$cat_labels    = self::category_labels();
		$default_open  = Options::category_default_open();
		$categories    = array();

		foreach ( Options::grouped_by_category() as $cat_id => $options ) {
			$rows = array();

			foreach ( $options as $option ) {
				$id       = $option['id'];
				$is_step  = ( 'stepper' === $option['kind'] );
				$value    = isset( $settings[ $id ] ) ? $settings[ $id ] : ( $is_step ? 0 : false );

				$rows[] = array(
					'id'       => $id,
					'kind'     => $option['kind'],
					'isstepper' => $is_step,
					'istoggle'  => ! $is_step,
					'label'    => isset( $labels[ $id ] ) ? $labels[ $id ] : $id,
					'cat'      => $cat_id,
					'effect'   => $option['effect'],
					'max'      => $is_step ? (int) $option['max'] : 0,
					'value'    => $is_step ? (int) $value : 0,
					'active'   => $is_step ? ( (int) $value > 0 ) : (bool) $value,
				);
			}

			$categories[] = array(
				'id'          => $cat_id,
				'label'       => isset( $cat_labels[ $cat_id ] ) ? $cat_labels[ $cat_id ] : $cat_id,
				'isopen'      => in_array( $cat_id, $default_open, true ),
				'options'     => $rows,
				'activecount' => count(
					array_filter(
						$rows,
						static function ( $row ) {
							return $row['active'];
						}
					)
				),
			);
		}

		return $categories;
	}

	/**
	 * Build the profiles context.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_profiles() {
		$labels = self::profile_labels();
		$tones  = Profiles::tone_colors();
		$rows   = array();

		foreach ( Profiles::all() as $profile ) {
			$tone = isset( $tones[ $profile['tone'] ] ) ? $tones[ $profile['tone'] ] : array(
				'bg'     => '#f1f5f9',
				'text'   => '#334155',
				'icon'   => '#64748b',
				'border' => '#f1f5f9',
			);

			$rows[] = array(
				'id'    => $profile['id'],
				'label' => isset( $labels[ $profile['id'] ] ) ? $labels[ $profile['id'] ] : $profile['id'],
				'apply' => wp_json_encode( $profile['apply'] ),
				'bg'    => $tone['bg'],
				'text'  => $tone['text'],
				'icon'  => $tone['icon'],
				'border' => $tone['border'],
			);
		}

		return $rows;
	}

	/**
	 * Full panel + FAB render context.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_context() {
		$settings    = Manager::get_current_user_settings();
		$appearance  = Settings::get_appearance();
		$activecount = Manager::count_active( $settings );

		return array(
			'appearance'   => $appearance,
			'effectcolors' => Settings::get_effect_colors(),
			'activecount'  => $activecount,
			'hasactive'    => $activecount > 0,
			'showprofiles' => $appearance['showprofiles'],
			'profiles'     => self::build_profiles(),
			'categories'   => self::build_categories( $settings ),
			'footertext'   => Settings::footer_text(),
		);
	}

	/**
	 * Public entry point for templates to render a sub-partial (e.g. panel.php
	 * rendering one option-toggle/option-stepper/profile-card row per item).
	 *
	 * @param string               $template Template file name (no extension).
	 * @param array<string, mixed> $context  Variables to extract into scope.
	 */
	public static function partial( $template, $context ) {
		self::render_template( $template, $context );
	}

	/**
	 * Render a template file with a context array in scope.
	 *
	 * @param string               $template Template file name (no extension).
	 * @param array<string, mixed> $context  Variables to extract into scope.
	 */
	private static function render_template( $template, $context ) {
		$file = WP_A11Y_DIR . 'templates/' . $template . '.php';

		if ( ! file_exists( $file ) ) {
			return;
		}

		extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $file;
	}

	/**
	 * Echo the colorblind-simulation SVG filter defs (protanopia,
	 * deuteranopia, tritanopia) and the effect-color CSS custom properties.
	 * Placed right after <body> opens, alongside the no-FOUC script.
	 */
	public static function render_head_extras() {
		$colors = Settings::get_effect_colors();
		?>
		<svg aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden;" xmlns="http://www.w3.org/2000/svg">
			<filter id="wp-a11y-protanopia">
				<feColorMatrix type="matrix" values="0.567 0.433 0 0 0  0.558 0.442 0 0 0  0 0.242 0.758 0 0  0 0 0 1 0" />
			</filter>
			<filter id="wp-a11y-deuteranopia">
				<feColorMatrix type="matrix" values="0.625 0.375 0 0 0  0.7 0.3 0 0 0  0 0.3 0.7 0 0  0 0 0 1 0" />
			</filter>
			<filter id="wp-a11y-tritanopia">
				<feColorMatrix type="matrix" values="0.95 0.05 0 0 0  0 0.433 0.567 0 0  0 0.475 0.525 0 0  0 0 0 1 0" />
			</filter>
		</svg>
		<style id="wp-a11y-colors">
			:root {
				--wp-a11y-highlighttitles: <?php echo esc_html( $colors['highlighttitles'] ); ?>;
				--wp-a11y-highlightlinks: <?php echo esc_html( $colors['highlightlinks'] ); ?>;
				--wp-a11y-highlightbuttons: <?php echo esc_html( $colors['highlightbuttons'] ); ?>;
				--wp-a11y-readingguide: <?php echo esc_html( $colors['readingguide'] ); ?>;
			}
		</style>
		<?php
	}

	/**
	 * Echo the synchronous no-FOUC script: applies known body classes from
	 * localStorage before first paint (mirrors local_a11y's
	 * render_nofouc_script(), but always reads localStorage for now since
	 * server-side sync lands in Phase 2).
	 */
	public static function render_nofouc_script() {
		?>
		<script id="wp-a11y-nofouc">
		(function () {
			try {
				var raw = window.localStorage.getItem( 'wp_a11y_settings' );
				if ( ! raw ) {
					return;
				}
				var settings = JSON.parse( raw );
				var classes = [];
				var steppers = <?php echo wp_json_encode( Manager::get_stepper_max() ); ?>;
				Object.keys( settings ).forEach( function ( id ) {
					var kebab = id.replace( /([A-Z])/g, '-$1' ).toLowerCase();
					if ( Object.prototype.hasOwnProperty.call( steppers, id ) ) {
						var level = parseInt( settings[ id ], 10 ) || 0;
						if ( level > 0 ) {
							classes.push( 'a11y-' + kebab + '-' + level );
						}
					} else if ( settings[ id ] === true ) {
						classes.push( 'a11y-' + kebab );
					}
				} );
				if ( settings.focusMode >= 3 && classes.indexOf( 'a11y-hide-images' ) === -1 ) {
					classes.push( 'a11y-hide-images' );
				}
				if ( classes.length ) {
					document.body.className += ' ' + classes.join( ' ' );
				}
			} catch ( e ) {
				/* corrupted localStorage value: treat as "no preference yet". */
			}
		})();
		</script>
		<?php
	}

	/**
	 * Echo the FAB + panel HTML.
	 */
	public static function render_widget() {
		$context = self::build_context();

		self::render_template( 'fab', $context );
		self::render_template( 'panel', $context );
	}
}
