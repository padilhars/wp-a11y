<?php
/**
 * Read-only aggregate usage-stats report page.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the "Estatísticas de uso" submenu page under the plugin's menu.
 */
class Stats_Page {

	const SLUG = 'wp-a11y-stats';

	/**
	 * Register the submenu page.
	 */
	public static function register() {
		add_submenu_page(
			Admin_Page::SLUG,
			__( 'Estatísticas de uso', 'wp-a11y' ),
			__( 'Estatísticas de uso', 'wp-a11y' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render the report table.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rows = Stats::get_all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Estatísticas de uso', 'wp-a11y' ); ?></h1>
			<p><?php esc_html_e( 'Contadores agregados e anônimos de ativação por opção. Nenhum identificador de usuário, sessão ou IP é armazenado.', 'wp-a11y' ); ?></p>

			<?php if ( ! Settings::collect_stats_enabled() ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'A coleta de estatísticas está desligada em Geral > Estatísticas.', 'wp-a11y' ); ?></p></div>
			<?php endif; ?>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Opção', 'wp-a11y' ); ?></th>
						<th><?php esc_html_e( 'Ativações', 'wp-a11y' ); ?></th>
						<th><?php esc_html_e( 'Última atualização', 'wp-a11y' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'Nenhum dado ainda.', 'wp-a11y' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->featureid ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $row->counter ) ); ?></td>
								<td><?php echo esc_html( $row->timemodified ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row->timemodified ) : '-' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
