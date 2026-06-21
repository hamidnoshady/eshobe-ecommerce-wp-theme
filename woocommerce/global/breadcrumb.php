<?php
/**
 * Themed breadcrumb trail (overrides woocommerce/templates/global/breadcrumb.php).
 *
 * @package WM_Theme
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $breadcrumb ) ) {
	return;
}

$wm_breadcrumb_total = count( $breadcrumb );
?>
<nav class="wm-breadcrumb" aria-label="<?php esc_attr_e( 'مسیر صفحه', 'eshobe-ecommerce' ); ?>">
	<ol class="wm-breadcrumb__list">
		<?php foreach ( $breadcrumb as $wm_breadcrumb_key => $wm_breadcrumb_crumb ) :
			$wm_breadcrumb_is_last = ( $wm_breadcrumb_total === $wm_breadcrumb_key + 1 );
			?>
			<li class="wm-breadcrumb__item<?php echo $wm_breadcrumb_is_last ? ' is-current' : ''; ?>">
				<?php if ( 0 === $wm_breadcrumb_key ) : ?>
					<svg class="wm-breadcrumb__home-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3.5 11.5 12 4l8.5 7.5" />
						<path d="M5.5 10v9.5h13V10" />
						<path d="M9.75 19.5v-6h4.5v6" />
					</svg>
				<?php endif; ?>
				<?php if ( ! empty( $wm_breadcrumb_crumb[1] ) && ! $wm_breadcrumb_is_last ) : ?>
					<a href="<?php echo esc_url( $wm_breadcrumb_crumb[1] ); ?>"><?php echo esc_html( $wm_breadcrumb_crumb[0] ); ?></a>
				<?php else : ?>
					<span <?php echo $wm_breadcrumb_is_last ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $wm_breadcrumb_crumb[0] ); ?></span>
				<?php endif; ?>
			</li>
			<?php if ( ! $wm_breadcrumb_is_last ) : ?>
				<li class="wm-breadcrumb__sep" aria-hidden="true">‹</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
