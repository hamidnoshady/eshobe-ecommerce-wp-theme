<?php
/**
 * Stepped checkout.
 *
 * @package WM_Theme
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

$wm_checkout_locked = ! is_user_logged_in();

$customer      = WC()->customer;
$billing_city  = $customer ? $customer->get_billing_city() : '';
$billing_state = $customer ? $customer->get_billing_state() : '';
$billing_addr  = $customer ? trim( $customer->get_billing_address_1() . ' ' . $customer->get_billing_address_2() ) : '';
$has_address   = ! $wm_checkout_locked && ( $billing_city || $billing_state || $billing_addr );
$item_count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>

<section class="wm-checkout-page<?php echo $wm_checkout_locked ? ' wm-checkout-page--locked' : ''; ?>" dir="rtl">
	<?php if ( $wm_checkout_locked ) : ?>
		<button type="button" class="screen-reader-text" data-wm-otp-trigger data-wm-otp-redirect="checkout" data-wm-otp-force data-wm-otp-autotrigger>
			<?php echo esc_html__( 'ورود / ثبت‌نام', 'eshobe-ecommerce' ); ?>
		</button>
	<?php endif; ?>
	<div class="wm-checkout-page__container" <?php echo $wm_checkout_locked ? 'aria-hidden="true" inert' : ''; ?>>
		<header class="wm-checkout-page__header">
			<div>
				<span class="wm-checkout-page__eyebrow"><?php echo esc_html__( 'تکمیل سفارش', 'eshobe-ecommerce' ); ?></span>
				<h1><?php echo esc_html__( 'تسویه حساب', 'eshobe-ecommerce' ); ?></h1>
			</div>
			<div class="wm-checkout-page__meta"><?php echo esc_html( sprintf( _n( '%d کالا در سفارش شما', '%d کالا در سفارش شما', $item_count, 'eshobe-ecommerce' ), $item_count ) ); ?></div>
		</header>

		<form name="checkout" method="post" class="checkout woocommerce-checkout wm-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'تسویه حساب', 'eshobe-ecommerce' ); ?>">
			<nav class="wm-checkout-steps" aria-label="<?php echo esc_attr__( 'مراحل تسویه حساب', 'eshobe-ecommerce' ); ?>">
				<button class="wm-checkout-steps__item is-active" type="button" data-checkout-step-target="address">
					<span>۱</span>
					<strong><?php echo esc_html__( 'آدرس', 'eshobe-ecommerce' ); ?></strong>
				</button>
				<button class="wm-checkout-steps__item" type="button" data-checkout-step-target="shipping">
					<span>۲</span>
					<strong><?php echo esc_html__( 'ارسال', 'eshobe-ecommerce' ); ?></strong>
				</button>
				<button class="wm-checkout-steps__item" type="button" data-checkout-step-target="payment">
					<span>۳</span>
					<strong><?php echo esc_html__( 'پرداخت', 'eshobe-ecommerce' ); ?></strong>
				</button>
			</nav>

			<div class="wm-checkout-layout">
				<div class="wm-checkout-main">
					<section class="wm-checkout-panel is-active" data-checkout-step="address">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله اول', 'eshobe-ecommerce' ); ?></span>
								<h2><?php echo esc_html__( 'آدرس دریافت سفارش', 'eshobe-ecommerce' ); ?></h2>
							</div>
						</header>

						<?php if ( $has_address ) : ?>
							<div class="wm-checkout-saved-address">
								<div>
									<strong><?php echo esc_html__( 'آدرس ذخیره‌شده شما', 'eshobe-ecommerce' ); ?></strong>
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
								<span><?php echo esc_html__( 'قابل ویرایش', 'eshobe-ecommerce' ); ?></span>
							</div>
						<?php else : ?>
							<div class="wm-checkout-new-address-note">
								<?php echo esc_html__( 'برای ادامه، آدرس جدید خود را وارد کنید.', 'eshobe-ecommerce' ); ?>
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
							<button class="wm-checkout-next" type="button" data-checkout-next="shipping"><?php echo esc_html__( 'ادامه به روش ارسال', 'eshobe-ecommerce' ); ?></button>
						</footer>
					</section>

					<section class="wm-checkout-panel" data-checkout-step="shipping">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله دوم', 'eshobe-ecommerce' ); ?></span>
								<h2><?php echo esc_html__( 'روش ارسال و هزینه حمل', 'eshobe-ecommerce' ); ?></h2>
							</div>
							<button class="wm-checkout-back" type="button" data-checkout-prev="address"><?php echo esc_html__( 'بازگشت به آدرس', 'eshobe-ecommerce' ); ?></button>
						</header>

						<div class="wm-checkout-shipping-note">
							<?php echo esc_html__( 'پس از تکمیل یا تغییر آدرس، روش‌های ارسال و هزینه حمل به‌روزرسانی می‌شوند.', 'eshobe-ecommerce' ); ?>
						</div>

						<div class="wm-checkout-review-box">
							<?php wc_get_template( 'checkout/review-order.php', array( 'checkout' => $checkout ) ); ?>
						</div>

						<footer class="wm-checkout-panel__actions">
							<button class="wm-checkout-next" type="button" data-checkout-next="payment"><?php echo esc_html__( 'ادامه به پرداخت', 'eshobe-ecommerce' ); ?></button>
						</footer>
					</section>

					<section class="wm-checkout-panel" data-checkout-step="payment">
						<header class="wm-checkout-panel__head">
							<div>
								<span class="wm-checkout-panel__kicker"><?php echo esc_html__( 'مرحله سوم', 'eshobe-ecommerce' ); ?></span>
								<h2><?php echo esc_html__( 'پرداخت و ثبت سفارش', 'eshobe-ecommerce' ); ?></h2>
							</div>
							<button class="wm-checkout-back" type="button" data-checkout-prev="shipping"><?php echo esc_html__( 'بازگشت به ارسال', 'eshobe-ecommerce' ); ?></button>
						</header>

						<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

						<div id="order_review" class="woocommerce-checkout-review-order wm-checkout-payment-box">
							<?php woocommerce_checkout_payment(); ?>
						</div>

						<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
					</section>
				</div>

				<aside class="wm-checkout-sidebar" aria-label="<?php echo esc_attr__( 'خلاصه سفارش', 'eshobe-ecommerce' ); ?>">
					<div class="wm-checkout-sidebar__card">
						<header>
							<span><?php echo esc_html__( 'سفارش شما', 'eshobe-ecommerce' ); ?></span>
							<strong><?php echo esc_html( sprintf( _n( '%d کالا', '%d کالا', $item_count, 'eshobe-ecommerce' ), $item_count ) ); ?></strong>
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
										<small><?php echo esc_html( sprintf( __( 'تعداد: %d', 'eshobe-ecommerce' ), $cart_item['quantity'] ) ); ?></small>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="wm-checkout-sidebar__total">
							<span><?php echo esc_html__( 'مبلغ قابل پرداخت', 'eshobe-ecommerce' ); ?></span>
							<strong><?php wc_cart_totals_order_total_html(); ?></strong>
						</div>
					</div>
				</aside>
			</div>
		</form>
	</div>
</section>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
