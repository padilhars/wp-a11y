<?php
/**
 * Privacy API: exporter/eraser for the one piece of personal data this
 * plugin stores, plus suggested privacy policy content.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Equivalent of local_a11y's classes/privacy/provider.php.
 *
 * The plugin stores exactly one piece of personal data: the
 * `wp_a11y_settings` usermeta (a JSON blob of the user's chosen
 * accessibility options) - see Manager::USER_META_KEY. Guests' settings
 * live in the browser's localStorage only, which WordPress's privacy
 * subsystem has no visibility into (nothing to export/erase server-side
 * for).
 *
 * `wp_wp_a11y_stats` (the optional, off-by-default usage-stats table - see
 * Settings::collect_stats_enabled()) is DELIBERATELY not exposed here: one
 * row per accessibility option (a global counter and a last-modified
 * timestamp), no user id/session/IP/per-event timestamp column at all -
 * nothing in it to export or erase for any individual user, and no code
 * path in this plugin ever queries it by user. See class-stats.php.
 */
class Privacy {

	/**
	 * Register hooks. Called from Plugin::init().
	 */
	public static function register() {
		add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Suggests privacy policy language covering both the stored setting and
	 * the two options whose *browser* talks to a third party on the site's
	 * behalf when turned on - Voice Commands (microphone audio) and Face
	 * Navigation (camera image, processed locally, but two support files
	 * fetched live from third-party CDNs). Mirrors local_a11y's D62 finding:
	 * this plugin's own code never sends, receives or stores that audio,
	 * image, or the third-party files' content - the browser/CDN exchange
	 * happens entirely outside this plugin, but transparency about it
	 * belongs in the site's privacy policy regardless.
	 */
	public static function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . __( 'Este site usa o plugin WP A11y para oferecer opções de acessibilidade.', 'wp-a11y' ) . '</p>'
			. '<p>' . __( 'Se você estiver logado, suas preferências de acessibilidade escolhidas (por exemplo, tamanho de texto, contraste, filtros de cor) são salvas associadas à sua conta, para que sejam lembradas em qualquer dispositivo. Se você não estiver logado, essas preferências ficam salvas apenas no seu próprio navegador (localStorage) e nunca chegam a este site.', 'wp-a11y' ) . '</p>'
			. '<p>' . __( 'Duas opções, quando ativadas voluntariamente por você, fazem seu próprio navegador conversar diretamente com um serviço de terceiros - este site não envia, recebe ou armazena esse conteúdo:', 'wp-a11y' ) . '</p>'
			. '<ul>'
			. '<li>' . __( '"Comandos por Voz": o áudio captado pelo seu microfone é enviado pelo próprio navegador a um serviço remoto de reconhecimento de fala operado pelo fabricante do navegador (ex.: Google, no Chrome), não por este site.', 'wp-a11y' ) . '</li>'
			. '<li>' . __( '"Navegação por Face": a imagem da câmera é processada inteiramente no seu navegador e nunca é enviada a lugar nenhum; para isso, o navegador baixa dois arquivos de um provedor de terceiros (MediaPipe), verificados antes do uso.', 'wp-a11y' ) . '</li>'
			. '</ul>'
			. '<p>' . __( 'Opcionalmente, o administrador deste site pode ativar uma contagem agregada e anônima de quais opções de acessibilidade são mais usadas - esse contador nunca identifica quem o ativou.', 'wp-a11y' ) . '</p>';

		wp_add_privacy_policy_content( 'WP A11y', wp_kses_post( $content ) );
	}

	/**
	 * Registers this plugin's exporter with WordPress's personal data
	 * export tool (Tools > Export Personal Data).
	 *
	 * @param array $exporters Existing registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['wp-a11y-settings'] = array(
			'exporter_friendly_name' => __( 'WP A11y - Preferências de Acessibilidade', 'wp-a11y' ),
			'callback'               => array( __CLASS__, 'export_settings' ),
		);

		return $exporters;
	}

	/**
	 * Exports the requested user's accessibility settings, if any are
	 * stored for them.
	 *
	 * @param string $email_address The requester's email address.
	 * @param int    $page          Pagination (unused - always one row).
	 * @return array{data: array, done: bool}
	 */
	public static function export_settings( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$raw = get_user_meta( $user->ID, Manager::USER_META_KEY, true );

		if ( ! is_string( $raw ) || '' === $raw ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$settings = Manager::sanitize_settings( json_decode( $raw, true ) );

		$item_data = array();
		foreach ( $settings as $id => $value ) {
			$item_data[] = array(
				'name'  => $id,
				'value' => is_bool( $value ) ? ( $value ? __( 'ativado', 'wp-a11y' ) : __( 'desativado', 'wp-a11y' ) ) : (string) $value,
			);
		}

		return array(
			'data' => array(
				array(
					'group_id'    => 'wp-a11y',
					'group_label' => __( 'Preferências de Acessibilidade (WP A11y)', 'wp-a11y' ),
					'item_id'     => 'wp-a11y-settings',
					'data'        => $item_data,
				),
			),
			'done' => true,
		);
	}

	/**
	 * Registers this plugin's eraser with WordPress's personal data erasure
	 * tool (Tools > Erase Personal Data).
	 *
	 * @param array $erasers Existing registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['wp-a11y-settings'] = array(
			'eraser_friendly_name' => __( 'WP A11y - Preferências de Acessibilidade', 'wp-a11y' ),
			'callback'             => array( __CLASS__, 'erase_settings' ),
		);

		return $erasers;
	}

	/**
	 * Erases the requested user's accessibility settings usermeta, if any.
	 *
	 * @param string $email_address The requester's email address.
	 * @param int    $page          Pagination (unused - always one row).
	 * @return array{items_removed: bool, items_retained: bool, messages: array, done: bool}
	 */
	public static function erase_settings( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$had_data = '' !== (string) get_user_meta( $user->ID, Manager::USER_META_KEY, true );

		if ( $had_data ) {
			delete_user_meta( $user->ID, Manager::USER_META_KEY );
		}

		return array(
			'items_removed'  => $had_data,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
