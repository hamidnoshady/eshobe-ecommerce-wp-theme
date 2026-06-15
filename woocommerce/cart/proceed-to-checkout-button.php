<?php
/**
 * Custom proceed to checkout button.
 *
 * @package WM_Theme
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;
?>

<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward wm-cart-summary__cta">
	<?php echo esc_html__( 'ادامه فرایند خرید', 'watchmid' ); ?>
</a>
