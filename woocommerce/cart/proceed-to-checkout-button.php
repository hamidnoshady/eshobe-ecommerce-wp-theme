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

<?php
$wm_cta_lock_icon = '<svg class="wm-cart-summary__cta-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>';
?>

<?php if ( $wm_requires_otp_login ) : ?>
	<button type="button" class="checkout-button button alt wc-forward wm-cart-summary__cta" data-wm-otp-trigger data-wm-otp-redirect="checkout">
		<?php echo esc_html__( 'پرداخت و تکمیل سفارش', 'eshobe-ecommerce' ); ?>
		<?php echo $wm_cta_lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
<?php else : ?>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward wm-cart-summary__cta">
		<?php echo esc_html__( 'پرداخت و تکمیل سفارش', 'eshobe-ecommerce' ); ?>
		<?php echo $wm_cta_lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
<?php endif; ?>

<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wm-cart-summary__continue">
	<?php echo esc_html__( 'ادامه خرید', 'eshobe-ecommerce' ); ?>
</a>
