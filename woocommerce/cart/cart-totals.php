<?php
/**
 * Custom cart totals.
 *
 * @package WM_Theme
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wm_cart_totals_row' ) ) {
	/**
	 * Render one summary row.
	 *
	 * @param string $class Row class.
	 * @param string $label Row label.
	 * @param string $value Row value HTML.
	 */
	function wm_cart_totals_row( $class, $label, $value ) {
		?>
		<div class="wm-cart-summary__row <?php echo esc_attr( $class ); ?>">
			<div class="wm-cart-summary__label"><?php echo wp_kses_post( $label ); ?></div>
			<div class="wm-cart-summary__value"><?php echo wp_kses_post( $value ); ?></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wm_cart_totals_capture' ) ) {
	/**
	 * Capture echoed WooCommerce total fragments.
	 *
	 * @param callable $callback Echoing callback.
	 * @return string
	 */
	function wm_cart_totals_capture( $callback ) {
		ob_start();
		call_user_func( $callback );
		return ob_get_clean();
	}
}

if ( ! function_exists( 'wm_cart_totals_shipping_fragment' ) ) {
	/**
	 * Convert the default WooCommerce shipping row into a non-table fragment.
	 *
	 * @return string
	 */
	function wm_cart_totals_shipping_fragment() {
		$shipping_html = wm_cart_totals_capture( 'wc_cart_totals_shipping_html' );
		$shipping_html = preg_replace( '#</?(tr|th|td)[^>]*>#i', '', $shipping_html );
		$shipping_html = preg_replace( '#>\s*(Shipment|Shipping)\s*<#i', '><', $shipping_html );
		$shipping_html = preg_replace( '#^\s*(Shipment|Shipping)\s*#i', '', $shipping_html );

		return $shipping_html;
	}
}
?>

<div class="cart_totals wm-cart-summary <?php echo esc_attr( WC()->customer->has_calculated_shipping() ? 'calculated_shipping' : '' ); ?>">
	<header class="wm-cart-summary__head">
		<div>
			<span class="wm-cart-summary__kicker"><?php echo esc_html__( 'بازبینی نهایی', 'eshobe-ecommerce' ); ?></span>
			<h2><?php echo esc_html__( 'خلاصه سفارش', 'eshobe-ecommerce' ); ?></h2>
		</div>
		<span class="wm-cart-summary__count"><?php echo esc_html( sprintf( _n( '%d کالا', '%d کالا', WC()->cart->get_cart_contents_count(), 'eshobe-ecommerce' ), WC()->cart->get_cart_contents_count() ) ); ?></span>
	</header>

	<div class="wm-cart-summary__rows">
		<?php
		wm_cart_totals_row(
			'cart-subtotal',
			esc_html__( 'جمع جزء', 'eshobe-ecommerce' ),
			wm_cart_totals_capture( 'wc_cart_totals_subtotal_html' )
		);

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
			wm_cart_totals_row(
				'cart-discount coupon-' . sanitize_title( $code ),
				wc_cart_totals_coupon_label( $coupon, false ),
				wm_cart_totals_capture(
					function() use ( $coupon ) {
						wc_cart_totals_coupon_html( $coupon );
					}
				)
			);
		}

		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
			?>
			<div class="wm-cart-summary__row shipping">
				<div class="wm-cart-summary__label"><?php echo esc_html__( 'ارسال', 'eshobe-ecommerce' ); ?></div>
				<div class="wm-cart-summary__value wm-cart-summary__shipping">
					<?php echo wm_cart_totals_shipping_fragment(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
			<?php
		} elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
			?>
			<div class="wm-cart-summary__row shipping">
				<div class="wm-cart-summary__label"><?php echo esc_html__( 'ارسال', 'eshobe-ecommerce' ); ?></div>
				<div class="wm-cart-summary__value wm-cart-summary__shipping"><?php woocommerce_shipping_calculator(); ?></div>
			</div>
			<?php
		}

		foreach ( WC()->cart->get_fees() as $fee ) {
			wm_cart_totals_row(
				'fee',
				esc_html( $fee->name ),
				wm_cart_totals_capture(
					function() use ( $fee ) {
						wc_cart_totals_fee_html( $fee );
					}
				)
			);
		}

		if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = '';

			if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
				$estimated_text = sprintf( ' <small>' . esc_html__( 'برآورد شده برای %s', 'eshobe-ecommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
			}

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
					wm_cart_totals_row(
						'tax-rate tax-rate-' . sanitize_title( $code ),
						esc_html( $tax->label ) . $estimated_text,
						wp_kses_post( $tax->formatted_amount )
					);
				}
			} else {
				wm_cart_totals_row(
					'tax-total',
					esc_html( WC()->countries->tax_or_vat() ) . $estimated_text,
					wm_cart_totals_capture( 'wc_cart_totals_taxes_total_html' )
				);
			}
		}

		?>
	</div>

	<div class="wm-cart-summary__total">
		<?php
		wm_cart_totals_row(
			'order-total',
			esc_html__( 'جمع کل', 'eshobe-ecommerce' ),
			wm_cart_totals_capture( 'wc_cart_totals_order_total_html' )
		);
		?>
	</div>

	<div class="wm-cart-summary__checkout wc-proceed-to-checkout">
		<?php wc_get_template( 'cart/proceed-to-checkout-button.php' ); ?>
	</div>
</div>
