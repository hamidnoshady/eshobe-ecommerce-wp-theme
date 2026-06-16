<?php
/**
 * Custom proceed to checkout button.
 *
 * @package WM_Theme
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

$wm_requires_otp_login = function_exists( 'wm_technical_otp_enabled' ) && wm_technical_otp_enabled() && ! is_user_logged_in();
?>

<?php if ( $wm_requires_otp_login ) : ?>
	<button type="button" class="checkout-button button alt wc-forward wm-cart-summary__cta" data-wm-otp-trigger data-wm-otp-redirect="checkout">
		<?php echo esc_html__( 'ادامه فرایند خرید', 'watchmid' ); ?>
	</button>
<?php else : ?>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward wm-cart-summary__cta">
		<?php echo esc_html__( 'ادامه فرایند خرید', 'watchmid' ); ?>
	</a>
<?php endif; ?>
