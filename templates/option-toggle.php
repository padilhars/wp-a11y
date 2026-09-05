<?php
/**
 * A single boolean option row.
 *
 * Expects: $option (array: id, label, active, effect).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-a11y-option" data-option-id="<?php echo esc_attr( $option['id'] ); ?>" data-kind="toggle" data-effect="<?php echo esc_attr( $option['effect'] ); ?>">
	<span class="wp-a11y-option__label"><?php echo esc_html( $option['label'] ); ?></span>
	<button
		type="button"
		class="wp-a11y-toggle"
		role="switch"
		aria-checked="<?php echo $option['active'] ? 'true' : 'false'; ?>"
		data-option-id="<?php echo esc_attr( $option['id'] ); ?>"
	>
		<span class="wp-a11y-toggle__track"><span class="wp-a11y-toggle__thumb"></span></span>
	</button>
</div>
