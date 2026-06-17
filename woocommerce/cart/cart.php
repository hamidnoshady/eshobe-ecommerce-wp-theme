<?php
/**
 * Custom cart page.
 *
 * @package WM_Theme
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<section class="wm-cart-page" dir="rtl">
	<div class="wm-cart-page__container">
		<header class="wm-cart-page__header">
			<div>
				<h1 class="wm-cart-page__title"><?php echo esc_html__( 'سبد خرید', 'watchmid' ); ?></h1>
			</div>
		</header>

		<div class="wm-cart-layout">
			<form id="wm-cart-form" class="woocommerce-cart-form wm-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<section class="wm-cart-items" aria-label="<?php echo esc_attr__( 'محصولات داخل سبد خرید', 'watchmid' ); ?>">
					<div class="wm-cart-items__head">
						<div>
							<h2><?php echo esc_html__( 'محصولات شما', 'watchmid' ); ?></h2>
							<span><?php echo esc_html( sprintf( _n( '%d کالا', '%d کالا', WC()->cart->get_cart_contents_count(), 'watchmid' ), WC()->cart->get_cart_contents_count() ) ); ?></span>
						</div>
						<button type="submit" class="button wm-cart-items__update" name="update_cart" value="<?php echo esc_attr__( 'به‌روزرسانی سبد خرید', 'watchmid' ); ?>"><?php echo esc_html__( 'به‌روزرسانی سبد', 'watchmid' ); ?></button>
					</div>

					<div class="wm-cart-items__table-head" aria-hidden="true">
						<span></span>
						<span><?php echo esc_html__( 'مجموع', 'watchmid' ); ?></span>
						<span><?php echo esc_html__( 'تعداد', 'watchmid' ); ?></span>
						<span><?php echo esc_html__( 'محصول', 'watchmid' ); ?></span>
						<span><?php echo esc_html__( 'تصویر', 'watchmid' ); ?></span>
					</div>

					<div class="wm-cart-items__list">
						<?php
						foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
							$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
							$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

							if ( ! $_product || ! $_product->exists() || 0 >= $cart_item['quantity'] || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
								continue;
							}

							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
							?>
							<article class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'wm-cart-item cart_item', $cart_item, $cart_item_key ) ); ?>">
								<div class="wm-cart-item__product">
									<div class="wm-cart-item__media">
										<?php
										$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );

										if ( $product_permalink ) {
											printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
											echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
									</div>

									<div class="wm-cart-item__info">
										<h3 class="wm-cart-item__title">
											<?php
											if ( $product_permalink ) {
												printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $product_name ) );
											} else {
												echo wp_kses_post( $product_name );
											}
											?>
										</h3>

										<div class="wm-cart-item__meta">
											<?php
											echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

											if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
												echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'موجودی این محصول در حالت پیش‌خرید است.', 'watchmid' ) . '</p>', $product_id ) );
											}
											?>
										</div>

									</div>
								</div>

								<div class="wm-cart-item__unit">
									<span><?php echo esc_html__( 'قیمت واحد', 'watchmid' ); ?></span>
									<strong><?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
								</div>

								<div class="wm-cart-item__quantity">
									<label for="<?php echo esc_attr( 'quantity_' . $cart_item_key ); ?>"><?php echo esc_html__( 'تعداد', 'watchmid' ); ?></label>
									<?php
									if ( $_product->is_sold_individually() ) {
										$min_quantity = 1;
										$max_quantity = 1;
									} else {
										$min_quantity = 0;
										$max_quantity = $_product->get_max_purchase_quantity();
									}

									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_quantity',
										woocommerce_quantity_input(
											array(
												'input_id'     => 'quantity_' . $cart_item_key,
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max_quantity,
												'min_value'    => $min_quantity,
												'product_name' => $product_name,
											),
											$_product,
											false
										),
										$cart_item_key,
										$cart_item
									);
									?>
								</div>

								<div class="wm-cart-item__subtotal">
									<span><?php echo esc_html__( 'جمع', 'watchmid' ); ?></span>
									<strong><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
								</div>

								<div class="wm-cart-item__remove-cell">
									<?php
									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a role="button" href="%s" class="wm-cart-item__remove remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span aria-hidden="true">&times;</span></a>',
											esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
											esc_attr( sprintf( __( 'حذف %s از سبد خرید', 'watchmid' ), wp_strip_all_tags( $product_name ) ) ),
											esc_attr( $product_id ),
											esc_attr( $_product->get_sku() )
										),
										$cart_item_key
									);
									?>
									</div>
							</article>
							<?php
						}
						?>
					</div>
				</section>

				<?php if ( wc_coupons_enabled() ) : ?>
					<section class="wm-cart-coupon coupon" aria-label="<?php echo esc_attr__( 'کد تخفیف', 'watchmid' ); ?>">
						<label for="coupon_code"><?php echo esc_html__( 'کد تخفیف', 'watchmid' ); ?></label>
						<div class="wm-cart-coupon__row">
							<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php echo esc_attr__( 'کد تخفیف را وارد کنید', 'watchmid' ); ?>">
							<button type="submit" class="button wm-cart-coupon__button" name="apply_coupon" value="<?php echo esc_attr__( 'اعمال کد', 'watchmid' ); ?>"><?php echo esc_html__( 'اعمال', 'watchmid' ); ?></button>
						</div>
					</section>
				<?php endif; ?>

				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</form>

			<aside class="wm-cart-layout__summary" aria-label="<?php echo esc_attr__( 'خلاصه سفارش', 'watchmid' ); ?>">
				<?php wc_get_template( 'cart/cart-totals.php' ); ?>
			</aside>
		</div>

		<?php do_action( 'woocommerce_after_cart' ); ?>
	</div>
</section>
