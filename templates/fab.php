<?php
/**
 * FAB (floating action button) template.
 *
 * Expects: $appearance (array), $activecount (int), $hasactive (bool).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<button
	type="button"
	id="wp-a11y-fab"
	class="wp-a11y-fab wp-a11y-fab--<?php echo esc_attr( $appearance['fabshape'] ); ?> wp-a11y-fab--<?php echo esc_attr( $appearance['fabposition'] ); ?>"
	style="--wp-a11y-accent: <?php echo esc_attr( $appearance['accent'] ); ?>;"
	aria-haspopup="dialog"
	aria-expanded="false"
	aria-controls="wp-a11y-panel"
>
	<span class="dashicons dashicons-universal-access" aria-hidden="true"></span>
	<span id="wp-a11y-fab-badge" class="wp-a11y-fab__badge" aria-live="polite" <?php echo $hasactive ? '' : 'hidden'; ?>>
		<?php echo esc_html( (string) $activecount ); ?>
	</span>
	<span class="screen-reader-text"><?php esc_html_e( 'Abrir opções de acessibilidade', 'wp-a11y' ); ?></span>
</button>
