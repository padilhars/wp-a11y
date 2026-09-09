<?php
/**
 * A single stepper (multi-level) option row.
 *
 * Expects: $option (array: id, iconsvg, label, desc, value, max, active,
 * effect, levellabels, currentlabel).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-a11y-option<?php echo $option['active'] ? ' wp-a11y-option--active' : ''; ?>" data-option-id="<?php echo esc_attr( $option['id'] ); ?>" data-kind="stepper" data-max="<?php echo esc_attr( $option['max'] ); ?>" data-effect="<?php echo esc_attr( $option['effect'] ); ?>">
	<span class="wp-a11y-option__icon"><?php echo $option['iconsvg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(), not user input. ?></span>
	<span class="wp-a11y-option__text">
		<span class="wp-a11y-option__label"><?php echo esc_html( $option['label'] ); ?></span>
		<?php if ( $option['desc'] ) : ?>
			<span class="wp-a11y-option__desc"><?php echo esc_html( $option['desc'] ); ?></span>
		<?php endif; ?>
	</span>
	<button
		type="button"
		class="wp-a11y-stepper<?php echo $option['active'] ? ' is-active' : ''; ?>"
		data-option-id="<?php echo esc_attr( $option['id'] ); ?>"
		data-max="<?php echo esc_attr( $option['max'] ); ?>"
		data-value="<?php echo esc_attr( $option['value'] ); ?>"
		data-levels="<?php echo esc_attr( wp_json_encode( $option['levellabels'] ) ); ?>"
		aria-label="<?php echo esc_attr( $option['label'] ); ?>"
	>
		<span class="wp-a11y-stepper__dots" aria-hidden="true">
			<?php for ( $i = 1; $i <= $option['max']; $i++ ) : ?>
				<span class="wp-a11y-stepper__dot<?php echo $i <= $option['value'] ? ' is-filled' : ''; ?>"></span>
			<?php endfor; ?>
		</span>
		<span class="wp-a11y-stepper__value">
			<?php echo esc_html( $option['currentlabel'] ); ?>
		</span>
	</button>
</div>
