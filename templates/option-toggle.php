<?php
/**
 * A single boolean option row.
 *
 * Expects: $option (array: id, iconsvg, label, desc, active, effect,
 * hashelp, helplabel, helptext).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-a11y-option<?php echo $option['active'] ? ' wp-a11y-option--active' : ''; ?><?php echo $option['hashelp'] ? ' wp-a11y-option--has-help' : ''; ?>" data-option-id="<?php echo esc_attr( $option['id'] ); ?>" data-kind="toggle" data-effect="<?php echo esc_attr( $option['effect'] ); ?>">
	<span class="wp-a11y-option__icon"><?php echo $option['iconsvg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(), not user input. ?></span>
	<span class="wp-a11y-option__text">
		<span class="wp-a11y-option__label"><?php echo esc_html( $option['label'] ); ?></span>
		<?php if ( $option['desc'] ) : ?>
			<span class="wp-a11y-option__desc"><?php echo esc_html( $option['desc'] ); ?></span>
		<?php endif; ?>
	</span>
	<?php if ( $option['hashelp'] ) : ?>
		<button type="button" class="wp-a11y-help-btn" data-help-for="<?php echo esc_attr( $option['id'] ); ?>" aria-expanded="false" aria-label="<?php echo esc_attr( $option['helplabel'] ); ?>">?</button>
	<?php endif; ?>
	<button
		type="button"
		class="wp-a11y-toggle"
		role="switch"
		aria-checked="<?php echo $option['active'] ? 'true' : 'false'; ?>"
		data-option-id="<?php echo esc_attr( $option['id'] ); ?>"
	>
		<span class="wp-a11y-toggle__track"><span class="wp-a11y-toggle__thumb"></span></span>
	</button>
	<?php if ( $option['hashelp'] ) : ?>
		<div class="wp-a11y-help-block" data-help-block="<?php echo esc_attr( $option['id'] ); ?>" hidden>
			<?php echo esc_html( $option['helptext'] ); ?>
		</div>
	<?php endif; ?>
</div>
