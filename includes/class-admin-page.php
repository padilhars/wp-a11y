<?php
/**
 * wp-admin settings page (5 sections, mirrors local_a11y's settings.php).
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the "Acessibilidade (A11y)" settings page.
 */
class Admin_Page {

	const SLUG = 'wp-a11y';

	/**
	 * Register the top-level admin menu page.
	 */
	public static function register() {
		add_menu_page(
			__( 'Acessibilidade (A11y)', 'wp-a11y' ),
			__( 'Acessibilidade (A11y)', 'wp-a11y' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-universal-access',
			80
		);
	}

	/**
	 * Render the settings form.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Acessibilidade (A11y)', 'wp-a11y' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'wp_a11y_settings_group' ); ?>

				<h2><?php esc_html_e( 'Geral', 'wp-a11y' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Ativar plugin', 'wp-a11y' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
								<?php esc_html_e( 'Mostrar o botão de acessibilidade no site.', 'wp-a11y' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mostrar para visitantes', 'wp-a11y' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[showforguests]" value="1" <?php checked( $settings['showforguests'] ); ?> />
								<?php esc_html_e( 'Também exibir para usuários não autenticados.', 'wp-a11y' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp-a11y-excludedpages"><?php esc_html_e( 'Páginas excluídas', 'wp-a11y' ); ?></label></th>
						<td>
							<textarea id="wp-a11y-excludedpages" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[excludedpages]" rows="4" class="large-text" placeholder="/wp-admin/*"><?php echo esc_textarea( $settings['excludedpages'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Um padrão de URL por linha; use * como curinga.', 'wp-a11y' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Opções disponíveis', 'wp-a11y' ); ?></th>
						<td>
							<?php foreach ( Options::grouped_by_category() as $cat => $options ) : ?>
								<p><strong><?php echo esc_html( ucfirst( $cat ) ); ?></strong></p>
								<?php foreach ( $options as $option ) : ?>
									<label style="display:inline-block;min-width:220px;">
										<input type="checkbox"
											name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[enabledfeatures][]"
											value="<?php echo esc_attr( $option['id'] ); ?>"
											<?php checked( in_array( $option['id'], $settings['enabledfeatures'], true ) ); ?> />
										<?php echo esc_html( $option['id'] ); ?>
									</label>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Botão Flutuante (FAB)', 'wp-a11y' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wp-a11y-fabposition"><?php esc_html_e( 'Posição', 'wp-a11y' ); ?></label></th>
						<td>
							<select id="wp-a11y-fabposition" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[fabposition]">
								<?php foreach ( array( 'bottom-right', 'bottom-left', 'middle-right', 'middle-left' ) as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['fabposition'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp-a11y-fabicon"><?php esc_html_e( 'Ícone', 'wp-a11y' ); ?></label></th>
						<td>
							<select id="wp-a11y-fabicon" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[fabicon]">
								<?php foreach ( array( 'default', 'un' ) as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['fabicon'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp-a11y-fabshape"><?php esc_html_e( 'Formato', 'wp-a11y' ); ?></label></th>
						<td>
							<select id="wp-a11y-fabshape" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[fabshape]">
								<?php foreach ( array( 'circle', 'square' ) as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['fabshape'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Painel', 'wp-a11y' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wp-a11y-panelformat"><?php esc_html_e( 'Formato (telas maiores)', 'wp-a11y' ); ?></label></th>
						<td>
							<select id="wp-a11y-panelformat" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[panelformat]">
								<?php foreach ( array( 'popover', 'drawer', 'modal' ) as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['panelformat'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp-a11y-density"><?php esc_html_e( 'Densidade', 'wp-a11y' ); ?></label></th>
						<td>
							<select id="wp-a11y-density" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[density]">
								<?php foreach ( array( 'compact', 'regular', 'comfortable' ) as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['density'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Perfis', 'wp-a11y' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[showprofiles]" value="1" <?php checked( $settings['showprofiles'] ); ?> />
								<?php esc_html_e( 'Mostrar a seção de perfis prontos.', 'wp-a11y' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp-a11y-footertext"><?php esc_html_e( 'Texto do rodapé', 'wp-a11y' ); ?></label></th>
						<td>
							<textarea id="wp-a11y-footertext" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[footertext]" rows="3" class="large-text"><?php echo esc_textarea( $settings['footertext'] ); ?></textarea>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Cores', 'wp-a11y' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$color_fields = array(
						'accent'                => __( 'Acento do botão/painel', 'wp-a11y' ),
						'highlighttitlescolor'  => __( 'Destacar Títulos', 'wp-a11y' ),
						'highlightlinkscolor'   => __( 'Destacar Links', 'wp-a11y' ),
						'highlightbuttonscolor' => __( 'Destacar Botões', 'wp-a11y' ),
						'readingguidecolor'     => __( 'Guia de Leitura', 'wp-a11y' ),
					);
					foreach ( $color_fields as $field => $label ) :
						?>
						<tr>
							<th scope="row"><label for="wp-a11y-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input type="color" id="wp-a11y-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $settings[ $field ] ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'Estatísticas', 'wp-a11y' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Coletar estatísticas de uso', 'wp-a11y' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[collectstats]" value="1" <?php checked( $settings['collectstats'] ); ?> />
								<?php esc_html_e( 'Contadores agregados e anônimos de ativação por opção, desligado por padrão.', 'wp-a11y' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
