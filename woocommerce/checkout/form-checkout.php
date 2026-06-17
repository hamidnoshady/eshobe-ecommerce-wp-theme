<?php
/**
 * Stepped checkout.
 *
 * @package WM_Theme
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! is_user_logged_in() ) {
	?>
	<section class="wm-checkout-page wm-checkout-page--locked" dir="rtl">
		<div class="wm-checkout-page__container">
			<div class="wm-checkout-login-gate">
				<h1><?php echo esc_html__( 'برای ادامه خرید وارد حساب کاربری شوید', 'watchmid' ); ?></h1>
				<p><?php echo esc_html__( 'برای ثبت سفارش، ابتدا با شماره موبایل خود وارد یا ثبت‌نام کنید.', 'watchmid' ); ?></p>
				<button type="button" class="wm-checkout-login-gate__button" data-wm-otp-trigger data-wm-otp-redirect="checkout">
					<?php echo esc_html__( 'ورود / ثبت‌نام', 'watchmid' ); ?>
				</button>
			</div>
		</div>
	</section>
	<?php
	return;
}

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'برای ثبت سفارش باید وارد حساب کاربری شوید.', 'watchmid' ) ) );
	return;
}

$customer      = WC()->customer;
$billing_city  = $customer ? $customer->get_billing_city() : '';
$billing_state = $customer ? $customer->get_billing_state() : '';
$billing_addr  = $customer ? trim( $customer->get_billing_address_1() . ' ' . $customer->get_billing_address_2() ) : '';
$has_address   = is_user_logged_in() && ( $billing_city || $billing_state || $billing_addr );
$item_count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>

<section class="wm-checkout-page" dir="rtl">
	<div class="wm-checkout-page__container">
		<header class="wm-checkout-page__header">
			<div>
				<span class="wm-checkout-page__eyebrow"><?php echo esc_html__( 'تکمیل سفارش', 'watchmid' ); ?></span>
				<h1><?php echo esc_html__( 'تسویه حساب', 'watchmid' ); ?></h1>
			</div>
			<div class="wm-checkout-page__meta"><?php echo esc_html( sprintf( _n( '%d کالا در سفارش شما', '%d کالا در سفارش شما', $item_count, 'watchmid' ), $item_count ) ); ?></div>
		</header>

		<form name="checkout" method="post" class="checkout woocommerce-checkout wm-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'تسویه حساب', 'watchmid' ); ?>">
			<nav class="wm-checkout-steps" aria-label="<?php echo esc_attr__( 'مراحل تسویه حساب', 'watchmid' ); ?>">
				<button class="wm-checkout-steps__item is-active" type="button" data-checkout-step-target="address">
					<span>۱</span>
					<strong><?php echo esc_html__( 'آدرس', 'watchmid' ); ?></strong>
				</button>
				<button class="wm-checkout-steps__item" type="button" data-checkout-step-target="shipping">
					<span>۲</span>
					<strong><?php echo esc_html__( 'ارسال', 'watchmid' ); ?></strong>
				</button>
				<button class="wm-checkout-steps__item" type="button" data-checkout-step-target="payment">
					<span>۳</span>
					<strong><?php echo esc_html__( 'پرداخت', 'watchmid' ); ?></strong>
				</button>
			</nav>

			<div class="wm-checkout-layout">
				<div class="wm-checkout-main">
					<section class="wm-checkout-panel is-active" data-checkout-step="address">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله اول', 'watchmid' ); ?></span>
								<h2><?php echo esc_html__( 'آدرس دریافت سفارش', 'watchmid' ); ?></h2>
							</div>
						</header>

						<?php if ( $has_address ) : ?>
							<div class="wm-checkout-saved-address">
								<div>
									<strong><?php echo esc_html__( 'آدرس ذخیره‌شده شما', 'watchmid' ); ?></strong>
									<p>
										<?php
										echo esc_html(
											implode(
												'، ',
												array_filter(
													array(
														$billing_state,
														$billing_city,
														$billing_addr,
													)
												)
											)
										);
										?>
									</p>
								</div>
								<span><?php echo esc_html__( 'قابل ویرایش', 'watchmid' ); ?></span>
							</div>
						<?php else : ?>
							<div class="wm-checkout-new-address-note">
								<?php echo esc_html__( 'برای ادامه، آدرس جدید خود را وارد کنید.', 'watchmid' ); ?>
							</div>
						<?php endif; ?>

						<div id="customer_details" class="wm-checkout-fields">
							<div class="wm-checkout-fields__group">
								<?php do_action( 'woocommerce_checkout_billing' ); ?>
							</div>
							<div class="wm-checkout-fields__group">
								<?php do_action( 'woocommerce_checkout_shipping' ); ?>
							</div>
						</div>

						<footer class="wm-checkout-panel__actions">
							<button class="wm-checkout-next" type="button" data-checkout-next="shipping"><?php echo esc_html__( 'ادامه به روش ارسال', 'watchmid' ); ?></button>
						</footer>
					</section>

					<section class="wm-checkout-panel" data-checkout-step="shipping">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله دوم', 'watchmid' ); ?></span>
								<h2><?php echo esc_html__( 'روش ارسال و هزینه حمل', 'watchmid' ); ?></h2>
							</div>
							<button class="wm-checkout-back" type="button" data-checkout-prev="address"><?php echo esc_html__( 'بازگشت به آدرس', 'watchmid' ); ?></button>
						</header>

						<div class="wm-checkout-shipping-note">
							<?php echo esc_html__( 'پس از تکمیل یا تغییر آدرس، روش‌های ارسال و هزینه حمل به‌روزرسانی می‌شوند.', 'watchmid' ); ?>
						</div>

						<div class="wm-checkout-review-box">
							<?php wc_get_template( 'checkout/review-order.php', array( 'checkout' => $checkout ) ); ?>
						</div>

						<footer class="wm-checkout-panel__actions">
							<button class="wm-checkout-next" type="button" data-checkout-next="payment"><?php echo esc_html__( 'ادامه به پرداخت', 'watchmid' ); ?></button>
						</footer>
					</section>

					<section class="wm-checkout-panel" data-checkout-step="payment">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله سوم', 'watchmid' ); ?></span>
								<h2><?php echo esc_html__( 'پرداخت و ثبت سفارش', 'watchmid' ); ?></h2>
							</div>
							<button class="wm-checkout-back" type="button" data-checkout-prev="shipping"><?php echo esc_html__( 'بازگشت به ارسال', 'watchmid' ); ?></button>
						</header>

						<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

						<div id="order_review" class="woocommerce-checkout-review-order wm-checkout-payment-box">
							<?php woocommerce_checkout_payment(); ?>
						</div>

						<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
					</section>
				</div>

				<aside class="wm-checkout-sidebar" aria-label="<?php echo esc_attr__( 'خلاصه سفارش', 'watchmid' ); ?>">
					<div class="wm-checkout-sidebar__card">
						<header>
							<span><?php echo esc_html__( 'سفارش شما', 'watchmid' ); ?></span>
							<strong><?php echo esc_html( sprintf( _n( '%d کالا', '%d کالا', $item_count, 'watchmid' ), $item_count ) ); ?></strong>
						</header>
						<div class="wm-checkout-sidebar__items">
							<?php foreach ( WC()->cart->get_cart() as $cart_item ) : ?>
								<?php
								$product = $cart_item['data'];
								if ( ! $product || ! $product->exists() ) {
									continue;
								}
								?>
								<div class="wm-checkout-sidebar__item">
									<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
									<div>
										<span><?php echo esc_html( $product->get_name() ); ?></span>
										<small><?php echo esc_html( sprintf( __( 'تعداد: %d', 'watchmid' ), $cart_item['quantity'] ) ); ?></small>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="wm-checkout-sidebar__total">
							<span><?php echo esc_html__( 'مبلغ قابل پرداخت', 'watchmid' ); ?></span>
							<strong><?php wc_cart_totals_order_total_html(); ?></strong>
						</div>
					</div>
				</aside>
			</div>
		</form>
	</div>
</section>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
