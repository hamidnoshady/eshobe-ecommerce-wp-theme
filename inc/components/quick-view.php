<?php
/**
 * Quick-view modal shell (content is fetched via admin-ajax).
 *
 * @package WM_Theme
 */

function wm_render_quick_view_modal() {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}
	?>
	<div id="wm-quick-view-modal" class="wm-quick-view-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'نمایش سریع محصول', 'eshobe-ecommerce' ); ?>" hidden>
		<div class="wm-quick-view-modal__backdrop" data-wm-quick-view-close></div>
		<div class="wm-quick-view-modal__panel">
			<button type="button" class="wm-quick-view-modal__close" data-wm-quick-view-close aria-label="<?php echo esc_attr__( 'بستن', 'eshobe-ecommerce' ); ?>">&times;</button>
			<div class="wm-quick-view-modal__body" data-wm-quick-view-body></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'wm_render_quick_view_modal', 11 );
