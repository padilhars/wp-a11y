<?php
/**
 * A single accessibility-profile card.
 *
 * Expects: $profile (array: id, iconsvg, label, apply (JSON string), bg, text, icon, border).
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<button
	type="button"
	class="wp-a11y-profile"
	data-profile-id="<?php echo esc_attr( $profile['id'] ); ?>"
	data-apply="<?php echo esc_attr( $profile['apply'] ); ?>"
	style="--tone-bg: <?php echo esc_attr( $profile['bg'] ); ?>; --tone-text: <?php echo esc_attr( $profile['text'] ); ?>; --tone-icon: <?php echo esc_attr( $profile['icon'] ); ?>; --tone-border: <?php echo esc_attr( $profile['border'] ); ?>;"
>
	<span class="wp-a11y-profile__check" hidden><?php echo $profile['checkiconsvg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
	<span class="wp-a11y-profile__icon"><?php echo $profile['iconsvg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
	<span class="wp-a11y-profile__label"><?php echo esc_html( $profile['label'] ); ?></span>
</button>
