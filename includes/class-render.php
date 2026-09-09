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
	 * Short description text for the few options whose label alone isn't
	 * self-explanatory (mirrors local_a11y's desckey - only options with a
	 * non-null desckey there get one here too).
	 *
	 * @return array<string, string>
	 */
	public static function option_desc_labels() {
		return array(
			'colorChange'    => __( 'Filtros para daltonismo', 'wp-a11y' ),
			'focusMode'      => __( 'Simplifica a página', 'wp-a11y' ),
			'screenReader'   => __( 'Texto para fala', 'wp-a11y' ),
			'faceNavigation' => __( 'Controle o cursor com movimentos da cabeça', 'wp-a11y' ),
		);
	}

	/**
	 * Help ("?") block content for every option with `hashelp = true` in
	 * Options::all() - all of them are toggle-kind options (never a
	 * stepper - see build_categories(), same restriction local_a11y's D76
	 * documents: a stepper row is itself the sole focusable control, so a
	 * nested help button would violate axe's nested-interactive rule).
	 *
	 * @return array<string, string> Option id => plain-text help sentence.
	 */
	public static function option_help_content() {
		return array(
			'bionicReading'   => __( 'Deixa em negrito a primeira metade de cada palavra, criando um ponto de ancoragem visual mais rápido para os olhos.', 'wp-a11y' ),
			'hideImages'      => __( 'Oculta imagens, vídeos e iframes da página, mantendo o restante do conteúdo.', 'wp-a11y' ),
			'pauseAnimations' => __( 'Pausa animações CSS na página.', 'wp-a11y' ),
			'silenceMedia'    => __( 'Muta todo áudio e vídeo da página, incluindo mídia que começa a tocar depois de ativado.', 'wp-a11y' ),
			'readingGuide'    => __( 'Uma faixa horizontal destacada acompanha o mouse, ajudando a manter a linha de leitura.', 'wp-a11y' ),
			'readingMask'     => __( 'Escurece a área acima e abaixo do cursor, isolando a linha de texto sendo lida.', 'wp-a11y' ),
			'magnifier'       => __( 'Uma lente circular amplia o conteúdo sob o cursor ao mover o mouse.', 'wp-a11y' ),
			'screenReader'    => __( 'Passe o mouse sobre um texto para destacá-lo; clique para ouvi-lo em voz alta.', 'wp-a11y' ),
			'virtualKeyboard' => __( 'Um teclado na tela aparece automaticamente ao focar um campo de texto da página.', 'wp-a11y' ),
			'voiceCommands'   => __( 'Controle o painel e a página por comandos de voz - requer autorização do microfone.', 'wp-a11y' ),
			'faceNavigation'  => __( 'Controle o cursor com movimentos da cabeça e clique abrindo a boca ou piscando - requer autorização da câmera.', 'wp-a11y' ),
		);
	}

	/**
	 * Per-level labels for every stepper option, index 0 (default) through
	 * `max`. Without this, a stepper's displayed value has nothing more
	 * meaningful to show than a bare "N/max" fraction, which says nothing
	 * about what level N actually does.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function option_level_labels() {
		return array(
			'textSize'        => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Pequeno', 'wp-a11y' ),
				__( 'Médio', 'wp-a11y' ),
				__( 'Grande', 'wp-a11y' ),
				__( 'Máximo', 'wp-a11y' ),
			),
			'lineHeight'      => array(
				__( 'Padrão', 'wp-a11y' ),
				__( '1.5×', 'wp-a11y' ),
				__( '1.8×', 'wp-a11y' ),
				__( '2.2×', 'wp-a11y' ),
			),
			'textSpacing'     => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Leve', 'wp-a11y' ),
				__( 'Médio', 'wp-a11y' ),
				__( 'Amplo', 'wp-a11y' ),
			),
			'wordSpacing'     => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Leve', 'wp-a11y' ),
				__( 'Médio', 'wp-a11y' ),
				__( 'Amplo', 'wp-a11y' ),
			),
			'textAlign'       => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Esquerda', 'wp-a11y' ),
				__( 'Centralizado', 'wp-a11y' ),
				__( 'Direita', 'wp-a11y' ),
				__( 'Justificado', 'wp-a11y' ),
			),
			'dyslexicFont'    => array(
				__( 'Desligado', 'wp-a11y' ),
				__( 'Lexend', 'wp-a11y' ),
				__( 'OpenDyslexic', 'wp-a11y' ),
			),
			'contrast'        => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Escuro', 'wp-a11y' ),
				__( 'Claro', 'wp-a11y' ),
				__( 'Alto', 'wp-a11y' ),
			),
			'colorChange'     => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Protanopia', 'wp-a11y' ),
				__( 'Deuteranopia', 'wp-a11y' ),
				__( 'Tritanopia', 'wp-a11y' ),
			),
			'saturation'      => array(
				__( 'Normal', 'wp-a11y' ),
				__( 'Alta', 'wp-a11y' ),
				__( 'Baixa', 'wp-a11y' ),
				__( 'Mono', 'wp-a11y' ),
			),
			'blueLightFilter' => array(
				__( 'Desligado', 'wp-a11y' ),
				__( 'Sutil', 'wp-a11y' ),
				__( 'Médio', 'wp-a11y' ),
				__( 'Forte', 'wp-a11y' ),
			),
			'cursor'          => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Grande preto', 'wp-a11y' ),
				__( 'Grande branco', 'wp-a11y' ),
			),
			'focusMode'       => array(
				__( 'Padrão', 'wp-a11y' ),
				__( 'Sem distrações', 'wp-a11y' ),
				__( 'Leitura confortável', 'wp-a11y' ),
				__( 'Somente texto', 'wp-a11y' ),
			),
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
			'lowVision'  => __( 'Baixa Visão', 'wp-a11y' ),
			'colorBlind' => __( 'Daltonismo', 'wp-a11y' ),
			'dyslexia'   => __( 'Dislexia', 'wp-a11y' ),
			'adhd'       => __( 'TDAH/Foco', 'wp-a11y' ),
			'senior'     => __( 'Idoso/Sênior', 'wp-a11y' ),
			'epilepsy'   => __( 'Epilepsia', 'wp-a11y' ),
			'motor'      => __( 'Deficiência Motora', 'wp-a11y' ),
			'cognitive'  => __( 'Cognitivo', 'wp-a11y' ),
			'night'      => __( 'Modo Noturno', 'wp-a11y' ),
		);
	}

	/**
	 * Build the panel's category/option context tree.
	 *
	 * @param array<string, bool|int> $settings Current settings.
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_categories( $settings ) {
		$labels       = self::option_labels();
		$descs        = self::option_desc_labels();
		$help         = self::option_help_content();
		$level_labels = self::option_level_labels();
		$cat_labels   = self::category_labels();
		$cat_icons    = Icons::category_icons();
		$default_open = Options::category_default_open();
		$categories   = array();

		foreach ( Options::grouped_by_category() as $cat_id => $options ) {
			$rows = array();

			foreach ( $options as $option ) {
				$id      = $option['id'];
				$is_step = ( 'stepper' === $option['kind'] );
				$value   = isset( $settings[ $id ] ) ? $settings[ $id ] : ( $is_step ? 0 : false );
				$hashelp = ! $is_step && ! empty( $option['hashelp'] ) && isset( $help[ $id ] );
				$levels  = $is_step && isset( $level_labels[ $id ] ) ? $level_labels[ $id ] : array();

				$rows[] = array(
					'id'           => $id,
					'kind'         => $option['kind'],
					'isstepper'    => $is_step,
					'istoggle'     => ! $is_step,
					'iconsvg'      => Icons::svg( $option['icon'], 18 ),
					'label'        => isset( $labels[ $id ] ) ? $labels[ $id ] : $id,
					'desc'         => isset( $descs[ $id ] ) ? $descs[ $id ] : null,
					'cat'          => $cat_id,
					'effect'       => $option['effect'],
					'max'          => $is_step ? (int) $option['max'] : 0,
					'value'        => $is_step ? (int) $value : 0,
					'active'       => $is_step ? ( (int) $value > 0 ) : (bool) $value,
					'levellabels'  => $levels,
					'currentlabel' => $is_step
						? ( isset( $levels[ (int) $value ] ) ? $levels[ (int) $value ] : sprintf( '%d/%d', (int) $value, (int) $option['max'] ) )
						: null,
					'hashelp'      => $hashelp,
					'helplabel'    => $hashelp ? __( 'Ajuda', 'wp-a11y' ) : null,
					'helptext'     => $hashelp ? $help[ $id ] : null,
				);
			}

			$categories[] = array(
				'id'          => $cat_id,
				'iconsvg'     => Icons::svg( isset( $cat_icons[ $cat_id ] ) ? $cat_icons[ $cat_id ] : '', 15 ),
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
				'id'           => $profile['id'],
				'iconsvg'      => Icons::svg( $profile['icon'], 16 ),
				'checkiconsvg' => Icons::svg( 'check', 9 ),
				'label'        => isset( $labels[ $profile['id'] ] ) ? $labels[ $profile['id'] ] : $profile['id'],
				'apply'        => wp_json_encode( $profile['apply'] ),
				'bg'           => $tone['bg'],
				'text'         => $tone['text'],
				'icon'         => $tone['icon'],
				'border'       => $tone['border'],
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
			'appearance'       => $appearance,
			'effectcolors'     => Settings::get_effect_colors(),
			'activecount'      => $activecount,
			'hasactive'        => $activecount > 0,
			'showprofiles'     => $appearance['showprofiles'],
			'profiles'         => self::build_profiles(),
			'categories'       => self::build_categories( $settings ),
			'footertext'       => Settings::footer_text(),
			'badgeiconsvg'     => Icons::svg( 'accessibility', 20 ),
			'refreshiconsvg'   => Icons::svg( 'refresh', 16 ),
			'closeiconsvg'     => Icons::svg( 'close', 16 ),
			'searchiconsvg'    => Icons::svg( 'search', 16 ),
			'sparklesiconsvg'  => Icons::svg( 'sparkles', 15 ),
			'checkiconsvg'     => Icons::svg( 'check', 10 ),
			'chevroniconsvg'   => Icons::svg( 'chevronDown', 14 ),
			'profilessublabel' => __( 'Ative configurações otimizadas com um clique', 'wp-a11y' ),
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
	 * Echo the synchronous no-FOUC script: applies known body classes
	 * before first paint (mirrors local_a11y's render_nofouc_script()).
	 *
	 * Logged-in users: the server already knows their real settings
	 * (usermeta, synced via REST since Phase 2) - embed them directly, no
	 * localStorage read needed. Guests: settings only ever live in
	 * localStorage, read synchronously here same as Phase 1.
	 */
	public static function render_nofouc_script() {
		$is_logged_in    = is_user_logged_in();
		$server_settings = $is_logged_in ? Manager::get_current_user_settings() : null;
		?>
		<script id="wp-a11y-nofouc">
		(function () {
			try {
				var serverSettings = <?php echo null === $server_settings ? 'null' : wp_json_encode( $server_settings ); ?>;
				var settings = serverSettings;
				if ( ! settings ) {
					var raw = window.localStorage.getItem( 'wp_a11y_settings' );
					if ( ! raw ) {
						return;
					}
					settings = JSON.parse( raw );
				}
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
	 * Echo the JSON config the JS script modules read at runtime: REST base
	 * URL + nonce, login state, and whether stats collection is enabled
	 * (so the client can skip pointless activation calls when it's off,
	 * mirroring local_a11y's `collectStatsEnabled` flag passed to main.js).
	 */
	public static function render_config_script() {
		$config = array(
			'restUrl'             => esc_url_raw( rest_url( 'wp-a11y/v1' ) ),
			'nonce'               => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn'          => is_user_logged_in(),
			'collectStatsEnabled' => Settings::collect_stats_enabled(),
			// Full URL (not a bare specifier) so panel.js can dynamic-
			// import() Phase 3 feature modules without needing an import
			// map entry for each one - see class-assets.php.
			'featuresUrl'         => esc_url_raw( WP_A11Y_URL . 'assets/js/features/' ),
		);
		?>
		<script type="application/json" id="wp-a11y-config"><?php echo wp_json_encode( $config ); ?></script>
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
