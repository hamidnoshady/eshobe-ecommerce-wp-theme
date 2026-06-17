<?php
/**
 * Global mobile bottom navigation.
 *
 * @package WM_Theme
 */

function wm_mobile_nav_get_option( $key, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $key, 'option' );
        if ( null !== $value && '' !== $value ) {
            return $value;
        }
    }

    return $default;
}

function wm_mobile_nav_is_enabled() {
    return (bool) wm_mobile_nav_get_option( 'wm_mobile_nav_enabled', true );
}

function wm_mobile_nav_should_render() {
    if ( ! wm_mobile_nav_is_enabled() ) {
        return false;
    }

    if ( function_exists( 'is_product' ) && is_product() && wm_mobile_nav_get_option( 'wm_product_mobile_bottom_bar_enabled', true ) ) {
        return false;
    }

    return true;
}

function wm_mobile_nav_shop_url() {
    if ( function_exists( 'wc_get_page_permalink' ) ) {
        return wc_get_page_permalink( 'shop' );
    }

    return home_url( '/' );
}

function wm_mobile_nav_account_url() {
    if ( function_exists( 'wc_get_page_permalink' ) ) {
        return wc_get_page_permalink( 'myaccount' );
    }

    return wp_login_url();
}

function wm_mobile_nav_cart_url() {
    if ( function_exists( 'wc_get_cart_url' ) ) {
        return wc_get_cart_url();
    }

    return home_url( '/' );
}

function wm_mobile_nav_cart_count() {
    if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
        return absint( WC()->cart->get_cart_contents_count() );
    }

    return 0;
}

function wm_mobile_nav_checkout_url() {
    if ( function_exists( 'wc_get_checkout_url' ) ) {
        return wc_get_checkout_url();
    }

    return wm_mobile_nav_cart_url();
}

function wm_mobile_nav_is_active( $item ) {
    if ( 'home' === $item && ( is_front_page() || is_home() ) ) {
        return true;
    }

    if ( 'shop' === $item && function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) ) {
        return true;
    }

    if ( 'account' === $item && function_exists( 'is_account_page' ) && is_account_page() ) {
        return true;
    }

    if ( 'cart' === $item && ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) ) {
        return true;
    }

    return false;
}

function wm_mobile_nav_icon( $icon ) {
    $icons = array(
        'home'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 10.8 12 4.5l7.5 6.3v8.1a1.6 1.6 0 0 1-1.6 1.6h-3.4v-5.6h-5v5.6H6.1a1.6 1.6 0 0 1-1.6-1.6v-8.1Z"/></svg>',
        'shop'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.2 9.5h13.6l-.9 10H6.1l-.9-10Z"/><path d="M8.2 9.5a3.8 3.8 0 0 1 7.6 0"/></svg>',
        'cart'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.3 7.7h13.1l-1.2 7.6H7.7L6.3 7.7Z"/><path d="M6.3 7.7 5.8 5H3.9"/><circle cx="9.1" cy="19" r="1.2"/><circle cx="17" cy="19" r="1.2"/></svg>',
        'account' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.4" r="3.4"/><path d="M5.8 19.5a6.2 6.2 0 0 1 12.4 0"/></svg>',
    );

    return isset( $icons[ $icon ] ) ? $icons[ $icon ] : '';
}

function wm_mobile_nav_primary_links() {
    $links = array();

    if ( has_nav_menu( 'primary' ) || has_nav_menu( 'menu-1' ) ) {
        $locations = get_nav_menu_locations();
        $menu_id   = ! empty( $locations['primary'] ) ? $locations['primary'] : ( ! empty( $locations['menu-1'] ) ? $locations['menu-1'] : 0 );
        $items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();

        foreach ( (array) $items as $item ) {
            if ( ! empty( $item->menu_item_parent ) ) {
                continue;
            }

            $links[] = array(
                'label' => $item->title,
                'url'   => $item->url,
            );
        }
    }

    if ( empty( $links ) ) {
        $links = array(
            array( 'label' => __( 'خانه', 'watchmid' ), 'url' => home_url( '/' ) ),
            array( 'label' => __( 'فروشگاه', 'watchmid' ), 'url' => wm_mobile_nav_shop_url() ),
            array( 'label' => __( 'برندها', 'watchmid' ), 'url' => home_url( '/product-brand/' ) ),
            array( 'label' => __( 'پرفروش‌ها', 'watchmid' ), 'url' => add_query_arg( 'orderby', 'popularity', wm_mobile_nav_shop_url() ) ),
            array( 'label' => __( 'پیشنهادها', 'watchmid' ), 'url' => add_query_arg( 'on_sale', '1', wm_mobile_nav_shop_url() ) ),
            array( 'label' => __( 'حساب کاربری', 'watchmid' ), 'url' => wm_mobile_nav_account_url() ),
        );
    }

    return array_slice( $links, 0, 8 );
}

function wm_mobile_nav_items() {
    return array(
        array( 'key' => 'home', 'label' => __( 'خانه', 'watchmid' ), 'url' => home_url( '/' ), 'icon' => 'home', 'type' => 'link' ),
        array( 'key' => 'shop', 'label' => __( 'فروشگاه', 'watchmid' ), 'url' => '#wm-mobile-sheet-shop', 'icon' => 'shop', 'type' => 'sheet' ),
        array( 'key' => 'cart', 'label' => __( 'سبد خرید', 'watchmid' ), 'url' => '#wm-mobile-sheet-cart', 'icon' => 'cart', 'type' => 'sheet', 'badge' => wm_mobile_nav_cart_count() ),
        array( 'key' => 'account', 'label' => __( 'حساب من', 'watchmid' ), 'url' => '#wm-mobile-sheet-account', 'icon' => 'account', 'type' => 'sheet' ),
    );
}

function wm_mobile_nav_cart_items() {
    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return array();
    }

    return array_slice( WC()->cart->get_cart(), 0, 3, true );
}

function wm_mobile_nav_cart_subtotal() {
    if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
        return WC()->cart->get_cart_subtotal();
    }

    return '';
}

function wm_mobile_nav_account_links() {
    $account_url = wm_mobile_nav_account_url();

    if ( ! is_user_logged_in() ) {
        $login_link = array(
            'label'       => __( 'ورود / ثبت‌نام', 'watchmid' ),
            'url'         => $account_url,
            'primary'     => true,
            'otp_trigger' => true,
        );

        return array( $login_link );
    }

    return array(
        array( 'label' => __( 'کیف پول', 'watchmid' ), 'url' => $account_url ),
        array( 'label' => __( 'سفارش‌ها', 'watchmid' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url ),
        array( 'label' => __( 'آدرس‌ها', 'watchmid' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-address' ) : $account_url ),
        array( 'label' => __( 'جزئیات حساب', 'watchmid' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-account' ) : $account_url ),
        array( 'label' => __( 'خروج از حساب کاربری', 'watchmid' ), 'url' => wp_logout_url( home_url( '/' ) ) ),
    );
}

function wm_render_mobile_sheet_header( $key, $title, $view_all_url = '' ) {
    ?>
    <div class="wm-mobile-sheet__header">
        <strong class="wm-mobile-sheet__title"><?php echo esc_html( $title ); ?></strong>
        <?php if ( $view_all_url ) : ?>
            <a class="wm-mobile-sheet__view-all" href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html__( 'مشاهده همه', 'watchmid' ); ?></a>
        <?php endif; ?>
    </div>
    <?php
}

function wm_render_mobile_shop_sheet() {
    ?>
    <section class="wm-mobile-sheet wm-mobile-sheet--shop" id="wm-mobile-sheet-shop" data-mobile-sheet="shop" aria-hidden="true">
        <div class="wm-mobile-sheet__panel">
            <?php wm_render_mobile_sheet_header( 'shop', __( 'فروشگاه', 'watchmid' ), wm_mobile_nav_shop_url() ); ?>
            <div class="wm-mobile-sheet__body">
                <nav class="wm-mobile-sheet__links" aria-label="<?php echo esc_attr__( 'لینک‌های فروشگاه', 'watchmid' ); ?>">
                    <?php foreach ( wm_mobile_nav_primary_links() as $link ) : ?>
                        <a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </section>
    <?php
}

function wm_render_mobile_cart_sheet() {
    $cart_count = wm_mobile_nav_cart_count();
    $cart_items = wm_mobile_nav_cart_items();
    ?>
    <section class="wm-mobile-sheet wm-mobile-sheet--cart" id="wm-mobile-sheet-cart" data-mobile-sheet="cart" aria-hidden="true">
        <div class="wm-mobile-sheet__panel">
            <?php wm_render_mobile_sheet_header( 'cart', __( 'سبد خرید', 'watchmid' ) ); ?>
            <div class="wm-mobile-sheet__body">
                <?php if ( $cart_count && ! empty( $cart_items ) ) : ?>
                    <div class="wm-mobile-cart-sheet__summary">
                        <span class="wm-mobile-cart-sheet__summary-label"><?php echo esc_html__( 'جمع کل', 'watchmid' ); ?></span>
                        <?php if ( wm_mobile_nav_cart_subtotal() ) : ?>
                            <strong class="wm-mobile-cart-sheet__summary-value"><?php echo wp_kses_post( wm_mobile_nav_cart_subtotal() ); ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="wm-mobile-cart-sheet__items">
                        <?php foreach ( $cart_items as $cart_item ) : ?>
                            <?php
                            $product = ! empty( $cart_item['data'] ) ? $cart_item['data'] : null;
                            if ( ! $product || ! is_object( $product ) ) {
                                continue;
                            }
                            ?>
                            <div class="wm-mobile-cart-sheet__item">
                                <span class="wm-mobile-cart-sheet__item-title"><?php echo esc_html( $product->get_name() ); ?></span>
                                <small class="wm-mobile-cart-sheet__item-meta"><?php echo esc_html( absint( $cart_item['quantity'] ) . ' ×' ); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="wm-mobile-sheet__empty"><?php echo esc_html__( 'سبد خرید شما خالی است.', 'watchmid' ); ?></p>
                <?php endif; ?>
                <div class="wm-mobile-cart-sheet__actions">
                    <a class="wm-mobile-sheet__button wm-mobile-sheet__button--primary wm-mobile-cart-sheet__checkout" href="<?php echo esc_url( wm_mobile_nav_checkout_url() ); ?>"><?php echo esc_html__( 'رفتن به تسویه حساب', 'watchmid' ); ?></a>
                    <a class="wm-mobile-sheet__button wm-mobile-sheet__button--secondary wm-mobile-cart-sheet__view-cart" href="<?php echo esc_url( $cart_count ? wm_mobile_nav_cart_url() : wm_mobile_nav_shop_url() ); ?>"><?php echo esc_html( $cart_count ? __( 'مشاهده سبد خرید', 'watchmid' ) : __( 'مشاهده محصولات', 'watchmid' ) ); ?></a>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function wm_render_mobile_account_sheet() {
    ?>
    <section class="wm-mobile-sheet wm-mobile-sheet--account" id="wm-mobile-sheet-account" data-mobile-sheet="account" aria-hidden="true">
        <div class="wm-mobile-sheet__panel">
            <?php wm_render_mobile_sheet_header( 'account', is_user_logged_in() ? __( 'حساب من', 'watchmid' ) : __( 'ورود به حساب', 'watchmid' ) ); ?>
            <div class="wm-mobile-sheet__body">
                <?php if ( is_user_logged_in() ) : ?>
                    <div class="wm-mobile-account-sheet__user">
                        <strong><?php echo esc_html__( 'حساب من', 'watchmid' ); ?></strong>
                        <span><?php echo esc_html__( 'مدیریت سفارش‌ها و اطلاعات حساب', 'watchmid' ); ?></span>
                    </div>
                <?php else : ?>
                    <p class="wm-mobile-sheet__empty"><?php echo esc_html__( 'برای مشاهده حساب کاربری وارد شوید.', 'watchmid' ); ?></p>
                <?php endif; ?>
                <nav class="wm-mobile-account-sheet__links" aria-label="<?php echo esc_attr__( 'لینک‌های حساب کاربری', 'watchmid' ); ?>">
                    <?php foreach ( wm_mobile_nav_account_links() as $link ) : ?>
                        <a
                            class="wm-mobile-account-sheet__link <?php echo ! empty( $link['primary'] ) ? 'wm-mobile-sheet__button wm-mobile-sheet__button--primary' : ''; ?>"
                            href="<?php echo esc_url( $link['url'] ); ?>"
                            <?php if ( ! empty( $link['otp_trigger'] ) ) : ?>
                                data-wm-otp-trigger data-wm-otp-redirect="account"
                            <?php endif; ?>
                        ><?php echo esc_html( $link['label'] ); ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </section>
    <?php
}

function wm_render_mobile_nav() {
    if ( ! wm_mobile_nav_should_render() ) {
        return;
    }

    $show_labels = (bool) wm_mobile_nav_get_option( 'wm_mobile_nav_show_labels', true );
    $density     = wm_mobile_nav_get_option( 'wm_mobile_nav_density', 'comfortable' );
    $density     = in_array( $density, array( 'compact', 'comfortable' ), true ) ? $density : 'comfortable';
    $class       = 'wm-mobile-nav wm-mobile-nav--' . sanitize_html_class( $density );
    $class      .= $show_labels ? ' wm-mobile-nav--labels' : ' wm-mobile-nav--icons-only';
    ?>
    <div class="wm-mobile-nav-shell" data-mobile-nav-root>
        <div class="wm-mobile-sheet__backdrop" data-mobile-sheet-close hidden></div>
        <?php
        wm_render_mobile_shop_sheet();
        wm_render_mobile_cart_sheet();
        wm_render_mobile_account_sheet();
        ?>

        <nav class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr__( 'نوار پایین موبایل', 'watchmid' ); ?>">
            <div class="wm-mobile-nav__inner">
                <?php foreach ( wm_mobile_nav_items() as $item ) : ?>
                    <?php
                    $is_active  = wm_mobile_nav_is_active( $item['key'] );
                    $item_class = 'wm-mobile-nav__item wm-mobile-nav__item--' . sanitize_html_class( $item['key'] );
                    $item_class .= $is_active ? ' wm-mobile-nav__item--active' : '';
                    $attrs      = 'class="' . esc_attr( $item_class ) . '"';
                    ?>
                    <?php if ( 'sheet' === $item['type'] ) : ?>
                        <button type="button" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-mobile-sheet-target="<?php echo esc_attr( $item['key'] ); ?>" aria-expanded="false">
                            <span class="wm-mobile-nav__icon"><?php echo wm_mobile_nav_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <?php if ( isset( $item['badge'] ) ) : ?>
                                <span class="wm-mobile-nav__badge<?php echo absint( $item['badge'] ) > 0 ? '' : ' wm-mobile-nav__badge--hidden'; ?>"><?php echo esc_html( number_format_i18n( absint( $item['badge'] ) ) ); ?></span>
                            <?php endif; ?>
                            <span class="wm-mobile-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
                        </button>
                    <?php else : ?>
                        <a <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                            <span class="wm-mobile-nav__icon"><?php echo wm_mobile_nav_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <?php if ( isset( $item['badge'] ) ) : ?>
                                <span class="wm-mobile-nav__badge<?php echo absint( $item['badge'] ) > 0 ? '' : ' wm-mobile-nav__badge--hidden'; ?>"><?php echo esc_html( number_format_i18n( absint( $item['badge'] ) ) ); ?></span>
                            <?php endif; ?>
                            <span class="wm-mobile-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
    <?php
}
add_action( 'wp_footer', 'wm_render_mobile_nav', 8 );
