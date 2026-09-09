<?php
/**
 * Accessibility panel template.
 *
 * Expects: $appearance, $categories, $profiles, $showprofiles, $footertext,
 * $activecount, $hasactive.
 *
 * @package WP_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	id="wp-a11y-panel"
	class="wp-a11y-panel wp-a11y-panel--<?php echo esc_attr( $appearance['panelformat'] ); ?> wp-a11y-panel--<?php echo esc_attr( $appearance['density'] ); ?>"
	style="--wp-a11y-accent: <?php echo esc_attr( $appearance['accent'] ); ?>;"
	role="dialog"
	aria-modal="true"
	aria-labelledby="wp-a11y-panel-title"
	hidden
>
	<div class="wp-a11y-panel__header">
		<div class="wp-a11y-panel__title-row">
			<span class="wp-a11y-panel__badge"><?php echo $badgeiconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
			<span class="wp-a11y-panel__titles">
				<h2 id="wp-a11y-panel-title"><?php esc_html_e( 'Acessibilidade', 'wp-a11y' ); ?></h2>
				<span id="wp-a11y-panel-status" class="wp-a11y-panel__status<?php echo $hasactive ? ' wp-a11y-panel__status--active' : ''; ?>" aria-live="polite">
					<?php if ( $hasactive ) : ?>
						<?php
						printf(
							/* translators: %d: number of active accessibility options. */
							esc_html( _n( '%d opção ativa', '%d opções ativas', $activecount, 'wp-a11y' ) ),
							(int) $activecount
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'Nenhuma opção ativa', 'wp-a11y' ); ?>
					<?php endif; ?>
				</span>
			</span>
			<button type="button" id="wp-a11y-panel-reset" class="wp-a11y-icon-button<?php echo $hasactive ? ' wp-a11y-icon-button--active' : ''; ?>" aria-label="<?php esc_attr_e( 'Redefinir', 'wp-a11y' ); ?>" title="<?php esc_attr_e( 'Redefinir', 'wp-a11y' ); ?>">
				<?php echo $refreshiconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?>
			</button>
			<button type="button" id="wp-a11y-panel-close" class="wp-a11y-icon-button" aria-label="<?php esc_attr_e( 'Fechar', 'wp-a11y' ); ?>">
				<?php echo $closeiconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?>
			</button>
		</div>
	</div>

	<div class="wp-a11y-panel__search">
		<span class="wp-a11y-panel__search-icon"><?php echo $searchiconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
		<label class="screen-reader-text" for="wp-a11y-search"><?php esc_html_e( 'Buscar opção', 'wp-a11y' ); ?></label>
		<input type="search" id="wp-a11y-search" placeholder="<?php esc_attr_e( 'Buscar…', 'wp-a11y' ); ?>" autocomplete="off" />
	</div>

	<div class="wp-a11y-panel__body">
		<?php if ( $showprofiles && ! empty( $profiles ) ) : ?>
			<section class="wp-a11y-profiles" data-a11y-section="profiles">
				<h3 class="wp-a11y-section-heading">
					<span class="wp-a11y-section-heading__icon"><?php echo $sparklesiconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
					<span class="wp-a11y-section-heading__text">
						<span class="wp-a11y-section-heading__label"><?php esc_html_e( 'Perfis', 'wp-a11y' ); ?></span>
						<span class="wp-a11y-section-heading__sublabel"><?php echo esc_html( $profilessublabel ); ?></span>
					</span>
				</h3>
				<div class="wp-a11y-profiles__grid">
					<?php foreach ( $profiles as $profile ) : ?>
						<?php \WP_A11y\Render::partial( 'profile-card', array( 'profile' => $profile ) ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php foreach ( $categories as $category ) : ?>
			<section class="wp-a11y-category" data-a11y-section="category" data-category="<?php echo esc_attr( $category['id'] ); ?>">
				<h3>
					<button
						type="button"
						class="wp-a11y-category__toggle"
						aria-expanded="<?php echo $category['isopen'] ? 'true' : 'false'; ?>"
						aria-controls="wp-a11y-cat-<?php echo esc_attr( $category['id'] ); ?>"
					>
						<span class="wp-a11y-category__icon"><?php echo $category['iconsvg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
						<span class="wp-a11y-category__label"><?php echo esc_html( $category['label'] ); ?></span>
						<?php if ( $category['activecount'] > 0 ) : ?>
							<span class="wp-a11y-category__count"><?php echo esc_html( (string) $category['activecount'] ); ?></span>
						<?php endif; ?>
						<span class="wp-a11y-category__chevron"><?php echo $chevroniconsvg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from Icons::svg(). ?></span>
					</button>
				</h3>
				<div id="wp-a11y-cat-<?php echo esc_attr( $category['id'] ); ?>" class="wp-a11y-category__options" <?php echo $category['isopen'] ? '' : 'hidden'; ?>>
					<?php foreach ( $category['options'] as $option ) : ?>
						<?php \WP_A11y\Render::partial( $option['isstepper'] ? 'option-stepper' : 'option-toggle', array( 'option' => $option ) ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>

	<div class="wp-a11y-panel__footer">
		<span class="wp-a11y-panel__footer-text">
			<span class="wp-a11y-panel__footer-title"><?php echo wp_kses_post( $footertext ); ?></span>
		</span>
		<kbd class="wp-a11y-panel__footer-kbd"><?php esc_html_e( 'Alt+A', 'wp-a11y' ); ?></kbd>
	</div>
</div>
