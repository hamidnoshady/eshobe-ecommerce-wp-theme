<?php
/**
 * Mini-cart (cart drawer) component.
 *
 * @package WM_Theme
 */

/**
 * Render the inner contents of the cart drawer (items list or empty state).
 *
 * Used both for the initial page render and as an AJAX fragment so the
 * drawer updates live after add-to-cart / remove actions.
 */
function wm_get_mini_cart_body_html() {
    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return '';
    }

    ob_start();

    if ( WC()->cart->is_empty() ) {
        $empty_cats = array();
        if ( taxonomy_exists( 'product_cat' ) ) {
            $terms = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'number'     => 4,
                )
            );
            if ( ! is_wp_error( $terms ) ) {
                $empty_cats = $terms;
            }
        }
        ?>
        <div class="wm-cart-drawer__empty">
            <span class="wm-cart-drawer__empty-icon" aria-hidden="true">🛒</span>
            <p class="wm-cart-drawer__empty-text"><?php echo esc_html__( 'سبد خرید شما خالی است.', 'eshobe-ecommerce' ); ?></p>
            <?php if ( ! empty( $empty_cats ) ) : ?>
                <div class="wm-cart-drawer__empty-chips">
                    <?php foreach ( $empty_cats as $term ) : ?>
                        <?php $term_link = get_term_link( $term ); ?>
                        <?php if ( ! is_wp_error( $term_link ) ) : ?>
                            <a class="wm-cart-drawer__empty-chip" href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a class="wm-cart-drawer__btn wm-cart-drawer__btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo esc_html__( 'مشاهده محصولات', 'eshobe-ecommerce' ); ?></a>
        </div>
        <?php
    } else {
        ?>
        <ul class="wm-cart-drawer__items">
            <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                $product = $cart_item['data'];

                if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
                    continue;
                }

                $product_permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
                $thumbnail         = $product->get_image( 'thumbnail' );
                $price_html        = WC()->cart->get_product_price( $product );
                $remove_url        = wc_get_cart_remove_url( $cart_item_key );
                ?>
                <li class="wm-cart-drawer__item">
                    <?php if ( $product_permalink ) : ?>
                        <a class="wm-cart-drawer__item-image" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <?php else : ?>
                        <span class="wm-cart-drawer__item-image"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <?php endif; ?>

                    <div class="wm-cart-drawer__item-body">
                        <?php if ( $product_permalink ) : ?>
                            <a class="wm-cart-drawer__item-name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
                        <?php else : ?>
                            <span class="wm-cart-drawer__item-name"><?php echo esc_html( $product->get_name() ); ?></span>
                        <?php endif; ?>

                        <div class="wm-cart-drawer__item-meta">
                            <span class="wm-cart-drawer__item-qty"><?php echo esc_html( $cart_item['quantity'] ); ?> ×</span>
                            <span class="wm-cart-drawer__item-price"><?php echo wp_kses_post( $price_html ); ?></span>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( $remove_url ); ?>" class="wm-cart-drawer__item-remove remove remove_from_cart_button" aria-label="<?php echo esc_attr__( 'حذف از سبد خرید', 'eshobe-ecommerce' ); ?>" data-product_id="<?php echo esc_attr( $cart_item['product_id'] ); ?>" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>">&times;</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="wm-cart-drawer__footer">
            <div class="wm-cart-drawer__subtotal">
                <span><?php echo esc_html__( 'جمع کل', 'eshobe-ecommerce' ); ?></span>
                <strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
            </div>
            <div class="wm-cart-drawer__actions">
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wm-cart-drawer__btn wm-cart-drawer__btn--secondary"><?php echo esc_html__( 'مشاهده سبد خرید', 'eshobe-ecommerce' ); ?></a>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wm-cart-drawer__btn wm-cart-drawer__btn--primary"><?php echo esc_html__( 'تسویه حساب', 'eshobe-ecommerce' ); ?></a>
            </div>
        </div>
        <?php
    }

    return ob_get_clean();
}

/**
 * Render the cart drawer markup (hidden by default, toggled via JS).
 */
function wm_render_mini_cart_drawer() {
    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }
    ?>
    <div id="wm-cart-drawer" class="wm-cart-drawer" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'سبد خرید', 'eshobe-ecommerce' ); ?>">
        <div class="wm-cart-drawer__backdrop" data-wm-cart-close></div>
        <div class="wm-cart-drawer__panel">
            <div class="wm-cart-drawer__header">
                <h2 class="wm-cart-drawer__title"><?php echo esc_html__( 'سبد خرید', 'eshobe-ecommerce' ); ?></h2>
                <button type="button" class="wm-cart-drawer__close" data-wm-cart-close aria-label="<?php echo esc_attr__( 'بستن', 'eshobe-ecommerce' ); ?>">&times;</button>
            </div>
            <div class="wm-cart-drawer__body" data-wm-cart-drawer-body>
                <?php echo wm_get_mini_cart_body_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'wm_render_mini_cart_drawer', 9 );

/**
 * Refresh the cart drawer body via WooCommerce's cart fragments mechanism so
 * it stays in sync after add-to-cart / quantity / remove actions.
 */
function wm_mini_cart_fragments( $fragments ) {
    $fragments['div.wm-cart-drawer__body'] = '<div class="wm-cart-drawer__body" data-wm-cart-drawer-body>' . wm_get_mini_cart_body_html() . '</div>';

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'wm_mini_cart_fragments' );
