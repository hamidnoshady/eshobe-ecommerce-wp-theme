/**
 * Cart page behaviour: reveal the update-cart button on quantity change,
 * show a spinner while WooCommerce recalculates totals after a shipping
 * method change, and surface the coupon notice next to the coupon field.
 * Hooks into WooCommerce's own AJAX (wc-cart.js) events rather than
 * reimplementing cart/shipping requests.
 */
( function( $ ) {
	'use strict';

	if ( ! $ ) {
		return;
	}

	$( function() {
		var $form = $( '.wm-cart-form' );
		var $updateButton = $form.find( '.wm-cart-items__update' );

		// Add 44px +/- stepper buttons around each native qty input (touch target requirement).
		$form.find( '.quantity' ).each( function() {
			var $quantity = $( this );
			var $input = $quantity.find( 'input.qty' );
			if ( ! $input.length || $quantity.find( '.wm-qty-step' ).length ) {
				return;
			}

			$quantity.addClass( 'wm-quantity-stepper' );
			$input.before( '<button type="button" class="wm-qty-step wm-qty-step--minus" aria-label="کاهش تعداد">−</button>' );
			$input.after( '<button type="button" class="wm-qty-step wm-qty-step--plus" aria-label="افزایش تعداد">+</button>' );
		} );

		$form.on( 'click', '.wm-qty-step', function() {
			var $button = $( this );
			var $input = $button.closest( '.quantity' ).find( 'input.qty' );
			var step = parseFloat( $input.attr( 'step' ) ) || 1;
			var min = parseFloat( $input.attr( 'min' ) );
			var max = parseFloat( $input.attr( 'max' ) );
			var value = parseFloat( $input.val() ) || 0;

			value = $button.hasClass( 'wm-qty-step--plus' ) ? value + step : value - step;

			if ( ! isNaN( min ) ) {
				value = Math.max( value, min );
			}
			if ( ! isNaN( max ) ) {
				value = Math.min( value, max );
			}

			$input.val( value ).trigger( 'change' );
		} );

		$form.on( 'input change', 'input.qty', function() {
			$updateButton.addClass( 'is-visible' );
		} );

		$( document.body ).on( 'updated_cart_totals', function() {
			$updateButton.removeClass( 'is-visible' );
		} );

		var $summary = $( '.wm-cart-summary' );

		$( document.body ).on( 'change', '.woocommerce-shipping-methods input[type="radio"]', function() {
			$summary.addClass( 'is-recalculating' );
		} );

		$( document.body ).on( 'updated_cart_totals updated_shipping_method', function() {
			$summary.removeClass( 'is-recalculating' );
		} );

		var $couponButton = $( '[data-wm-coupon-submit]' );

		$couponButton.on( 'click', function() {
			$couponButton.addClass( 'is-loading' );
		} );

		var $couponFeedback = $( '[data-wm-coupon-feedback]' );
		var $notice = $( '.woocommerce-notices-wrapper .woocommerce-message, .woocommerce-notices-wrapper .woocommerce-error' ).last();

		if ( $notice.length ) {
			$couponFeedback
				.text( $notice.text().trim() )
				.toggleClass( 'is-success', $notice.hasClass( 'woocommerce-message' ) )
				.toggleClass( 'is-error', $notice.hasClass( 'woocommerce-error' ) )
				.prop( 'hidden', false );
		}
	} );
} )( window.jQuery );
