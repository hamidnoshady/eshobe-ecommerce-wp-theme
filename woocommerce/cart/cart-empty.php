<?php
/**
 * Custom empty cart template.
 *
 * @package WM_Theme
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<section class="wm-cart-page wm-cart-page--empty" dir="rtl">
	<div class="wm-cart-page__container">
		<div class="wm-cart-empty">
			<div class="wm-cart-empty__mark" aria-hidden="true"></div>
			<span class="wm-cart-empty__eyebrow"><?php echo esc_html__( 'سبد خرید', 'eshobe-ecommerce' ); ?></span>
			<h1><?php echo esc_html__( 'سبد خرید شما خالی است', 'eshobe-ecommerce' ); ?></h1>
			<p><?php echo esc_html__( 'برای انتخاب محصول بعدی، به فروشگاه برگردید و محصولات را مرور کنید.', 'eshobe-ecommerce' ); ?></p>
			<a class="button wc-backward wm-cart-empty__button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php echo esc_html__( 'مشاهده محصولات', 'eshobe-ecommerce' ); ?>
			</a>
		</div>
	</div>
</section>

<?php do_action( 'woocommerce_after_cart' ); ?>
