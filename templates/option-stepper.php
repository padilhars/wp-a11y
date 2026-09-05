<?php
/**
 * A single stepper (multi-level) option row.
 *
 * Expects: $option (array: id, label, value, max, active, effect).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-a11y-option" data-option-id="<?php echo esc_attr( $option['id'] ); ?>" data-kind="stepper" data-max="<?php echo esc_attr( $option['max'] ); ?>" data-effect="<?php echo esc_attr( $option['effect'] ); ?>">
	<span class="wp-a11y-option__label"><?php echo esc_html( $option['label'] ); ?></span>
	<button
		type="button"
		class="wp-a11y-stepper<?php echo $option['active'] ? ' is-active' : ''; ?>"
		data-option-id="<?php echo esc_attr( $option['id'] ); ?>"
		data-max="<?php echo esc_attr( $option['max'] ); ?>"
		data-value="<?php echo esc_attr( $option['value'] ); ?>"
		aria-label="<?php echo esc_attr( $option['label'] ); ?>"
	>
		<span class="wp-a11y-stepper__dots" aria-hidden="true">
			<?php for ( $i = 1; $i <= $option['max']; $i++ ) : ?>
				<span class="wp-a11y-stepper__dot<?php echo $i <= $option['value'] ? ' is-filled' : ''; ?>"></span>
			<?php endfor; ?>
		</span>
		<span class="wp-a11y-stepper__value">
			<?php echo 0 === $option['value'] ? esc_html__( 'Padrão', 'wp-a11y' ) : esc_html( sprintf( '%d/%d', $option['value'], $option['max'] ) ); ?>
		</span>
	</button>
</div>
