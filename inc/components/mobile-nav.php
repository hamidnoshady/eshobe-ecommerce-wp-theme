<?php
/**
 * Global mobile bottom navigation.
 *
 * @package WM_Theme
 */

function wm_mobile_nav_get_option( $key, $default = '' ) {
	return wm_get_option( $key, $default );
}

function wm_mobile_nav_is_enabled() {
    return (bool) wm_mobile_nav_get_option( 'wm_mobile_nav_enabled', true );
}

function wm_mobile_nav_should_render() {
    return wm_mobile_nav_is_enabled();
}

function wm_mobile_nav_is_product_page() {
    return function_exists( 'is_product' ) && is_product();
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
    $tree = wm_mobile_nav_primary_tree();

    $flat = array();
    foreach ( $tree as $node ) {
        if ( ! empty( $node['children'] ) ) {
            continue;
        }
        $flat[] = array(
            'label' => $node['label'],
            'url'   => $node['url'],
        );
    }

    return array_slice( $flat, 0, 8 );
}

/**
 * Retrieve subcategories for a given product_cat term.
 *
 * @param int $term_id Term ID of the product_cat.
 * @return array List of tree node items.
 */
function wm_mobile_nav_get_category_children( $term_id ) {
    if ( ! taxonomy_exists( 'product_cat' ) ) {
        return array();
    }

    $children = array();

    $term_children = get_terms( array(
        'taxonomy'   => 'product_cat',
        'parent'     => $term_id,
        'hide_empty' => false,
    ) );

    if ( ! is_wp_error( $term_children ) && ! empty( $term_children ) ) {
        foreach ( $term_children as $term ) {
            $children[] = array(
                'id'       => 'cat_' . $term->term_id,
                'label'    => $term->name,
                'url'      => get_term_link( $term ),
                'children' => array(),
            );
        }
    }

    return $children;
}

/**
 * Returns a hierarchical tree of the primary WordPress menu (top-level items
 * with their nested children). The shop sheet renders each non-leaf node as a
 * button that opens a drill-down sub-view in the same modal.
 *
 * @return array<int, array{id:int|string,label:string,url:string,children:array}>
 */
function wm_mobile_nav_primary_tree() {
    $items_raw = array();

    if ( has_nav_menu( 'mobile' ) || has_nav_menu( 'primary' ) || has_nav_menu( 'menu-1' ) ) {
        $locations = get_nav_menu_locations();
        $menu_id   = ! empty( $locations['mobile'] ) ? $locations['mobile'] : ( ! empty( $locations['primary'] ) ? $locations['primary'] : ( ! empty( $locations['menu-1'] ) ? $locations['menu-1'] : 0 ) );
        $items_raw = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
    }

    if ( empty( $items_raw ) ) {
        $tree = array();

        if ( taxonomy_exists( 'product_cat' ) ) {
            $top_terms = get_terms( array(
                'taxonomy'   => 'product_cat',
                'parent'     => 0,
                'hide_empty' => false,
            ) );

            if ( ! is_wp_error( $top_terms ) && ! empty( $top_terms ) ) {
                foreach ( $top_terms as $term ) {
                    if ( 'uncategorized' === $term->slug ) {
                        continue;
                    }
                    $tree[] = array(
                        'id'       => 'cat_' . $term->term_id,
                        'label'    => $term->name,
                        'url'      => get_term_link( $term ),
                        'children' => wm_mobile_nav_get_category_children( $term->term_id ),
                    );
                }
            }
        }

        if ( empty( $tree ) ) {
            $fallback = array(
                array( 'label' => __( 'خانه', 'eshobe-ecommerce' ), 'url' => home_url( '/' ) ),
                array( 'label' => __( 'فروشگاه', 'eshobe-ecommerce' ), 'url' => wm_mobile_nav_shop_url() ),
                array( 'label' => __( 'برندها', 'eshobe-ecommerce' ), 'url' => home_url( '/product-brand/' ) ),
                array( 'label' => __( 'پرفروش‌ها', 'eshobe-ecommerce' ), 'url' => add_query_arg( 'orderby', 'popularity', wm_mobile_nav_shop_url() ) ),
                array( 'label' => __( 'پیشنهادها', 'eshobe-ecommerce' ), 'url' => add_query_arg( 'on_sale', '1', wm_mobile_nav_shop_url() ) ),
                array( 'label' => __( 'حساب کاربری', 'eshobe-ecommerce' ), 'url' => wm_mobile_nav_account_url() ),
            );

            foreach ( $fallback as $link ) {
                $tree[] = array(
                    'id'       => 0,
                    'label'    => $link['label'],
                    'url'      => $link['url'],
                    'children' => array(),
                );
            }
        }

        return array_slice( $tree, 0, 8 );
    }

    $by_id = array();
    foreach ( (array) $items_raw as $item ) {
        $by_id[ $item->ID ] = array(
            'id'        => (int) $item->ID,
            'label'     => (string) $item->title,
            'url'       => (string) $item->url,
            'object'    => isset( $item->object ) ? (string) $item->object : '',
            'object_id' => isset( $item->object_id ) ? (int) $item->object_id : 0,
            'children'  => array(),
        );
    }

    foreach ( (array) $items_raw as $item ) {
        $parent_id = (int) $item->menu_item_parent;
        if ( $parent_id && isset( $by_id[ $parent_id ] ) ) {
            $by_id[ $parent_id ]['children'][] = $by_id[ $item->ID ];
        }
    }

    $root = array();
    foreach ( (array) $items_raw as $item ) {
        $parent_id = (int) $item->menu_item_parent;
        if ( ! $parent_id || ! isset( $by_id[ $parent_id ] ) ) {
            $root[] = $by_id[ $item->ID ];
        }
    }

    return array_slice( $root, 0, 8 );
}

function wm_mobile_nav_items() {
    $account_item = is_user_logged_in()
        ? array( 'key' => 'account', 'label' => __( 'حساب من', 'eshobe-ecommerce' ), 'url' => '#wm-mobile-sheet-account', 'icon' => 'account', 'type' => 'sheet' )
        : array( 'key' => 'account', 'label' => __( 'ورود', 'eshobe-ecommerce' ), 'url' => wm_mobile_nav_account_url(), 'icon' => 'account', 'type' => 'otp-trigger' );

    return array(
        array( 'key' => 'home', 'label' => __( 'خانه', 'eshobe-ecommerce' ), 'url' => home_url( '/' ), 'icon' => 'home', 'type' => 'link' ),
        array( 'key' => 'shop', 'label' => __( 'فروشگاه', 'eshobe-ecommerce' ), 'url' => '#wm-mobile-sheet-shop', 'icon' => 'shop', 'type' => 'sheet' ),
        array( 'key' => 'cart', 'label' => __( 'سبد خرید', 'eshobe-ecommerce' ), 'url' => '#wm-mobile-sheet-cart', 'icon' => 'cart', 'type' => 'sheet', 'badge' => wm_mobile_nav_cart_count() ),
        $account_item,
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
            'label'       => __( 'ورود / ثبت‌نام', 'eshobe-ecommerce' ),
            'url'         => $account_url,
            'primary'     => true,
            'otp_trigger' => true,
        );

        return array( $login_link );
    }

    return array(
        array( 'label' => __( 'کیف پول', 'eshobe-ecommerce' ), 'url' => $account_url ),
        array( 'label' => __( 'سفارش‌ها', 'eshobe-ecommerce' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url ),
        array( 'label' => __( 'آدرس‌ها', 'eshobe-ecommerce' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-address' ) : $account_url ),
        array( 'label' => __( 'جزئیات حساب', 'eshobe-ecommerce' ), 'url' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-account' ) : $account_url ),
        array( 'label' => __( 'خروج از حساب کاربری', 'eshobe-ecommerce' ), 'url' => wp_logout_url( home_url( '/' ) ) ),
    );
}

function wm_render_mobile_sheet_header( $key, $title, $view_all_url = '' ) {
    ?>
    <div class="wm-mobile-sheet__header">
        <strong class="wm-mobile-sheet__title"><?php echo esc_html( $title ); ?></strong>
        <?php if ( $view_all_url ) : ?>
            <a class="wm-mobile-sheet__view-all" href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html__( 'مشاهده همه', 'eshobe-ecommerce' ); ?></a>
        <?php endif; ?>
    </div>
    <?php
}

function wm_render_mobile_shop_sheet_arrow_icon() {
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>';
}

/**
 * Render the <nav> of <a>/<button> chips for one drill-down level. Items
 * with children render as buttons with a left-arrow indicator that open
 * their own pane (rendered separately by wm_render_mobile_shop_sheet_panes()).
 *
 * @param array $items Tree nodes for this level.
 */
function wm_render_mobile_shop_sheet_nav( $items ) {
    ?>
    <nav class="wm-mobile-sheet__links" aria-label="<?php echo esc_attr__( 'منوی فروشگاه', 'eshobe-ecommerce' ); ?>">
        <?php foreach ( $items as $item ) :
            $has_children = ! empty( $item['children'] );
        ?>
            <?php if ( $has_children ) : ?>
                <button type="button" class="wm-mobile-shop-sheet__parent" data-mobile-shop-open="<?php echo esc_attr( $item['id'] ); ?>" aria-controls="wm-mobile-shop-sheet-view-<?php echo esc_attr( $item['id'] ); ?>" aria-expanded="false">
                    <span class="wm-mobile-shop-sheet__label"><?php echo esc_html( $item['label'] ); ?></span>
                    <span class="wm-mobile-shop-sheet__arrow" aria-hidden="true"><?php echo wm_render_mobile_shop_sheet_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                </button>
            <?php else : ?>
                <a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}

/**
 * Render every drill-down pane (the root list plus one sub-view per parent
 * item, at every depth) as flat siblings directly under the stage container.
 *
 * Panes used to be rendered nested inside their parent pane's markup, which
 * meant hiding the root pane (display:none) also hid every sub-view nested
 * inside it, even after JS cleared that sub-view's own `hidden` attribute —
 * so drilling in showed an empty modal. Rendering them as a flat list (a
 * queue instead of recursive nesting) keeps each pane independently
 * show/hide-able.
 *
 * @param array $tree Root tree from wm_mobile_nav_primary_tree().
 */
function wm_render_mobile_shop_sheet_panes( $tree ) {
    $queue = array(
        array(
            'items'      => $tree,
            'view_key'   => 'root',
            'parent_key' => '',
            'label'      => __( 'فروشگاه', 'eshobe-ecommerce' ),
            'url'        => wm_mobile_nav_shop_url(),
        ),
    );

    while ( $queue ) {
        $pane = array_shift( $queue );

        if ( 'root' === $pane['view_key'] ) {
            ?>
            <div class="wm-mobile-shop-sheet__pane" data-mobile-shop-view="root" data-mobile-shop-parent="" data-mobile-shop-label="<?php echo esc_attr( $pane['label'] ); ?>" data-mobile-shop-url="<?php echo esc_url( $pane['url'] ); ?>">
                <?php wm_render_mobile_shop_sheet_nav( $pane['items'] ); ?>
            </div>
            <?php
        } else {
            ?>
            <section class="wm-mobile-shop-sheet__subview" id="wm-mobile-shop-sheet-view-<?php echo esc_attr( $pane['view_key'] ); ?>" data-mobile-shop-view="<?php echo esc_attr( $pane['view_key'] ); ?>" data-mobile-shop-parent="<?php echo esc_attr( $pane['parent_key'] ); ?>" data-mobile-shop-label="<?php echo esc_attr( $pane['label'] ); ?>" data-mobile-shop-url="<?php echo esc_url( $pane['url'] ); ?>" hidden>
                <?php wm_render_mobile_shop_sheet_nav( $pane['items'] ); ?>
            </section>
            <?php
        }

        foreach ( $pane['items'] as $item ) {
            if ( empty( $item['children'] ) ) {
                continue;
            }
            $queue[] = array(
                'items'      => $item['children'],
                'view_key'   => (string) $item['id'],
                'parent_key' => $pane['view_key'],
                'label'      => $item['label'],
                'url'        => $item['url'],
            );
        }
    }
}

function wm_render_mobile_shop_sheet() {
    $tree = wm_mobile_nav_primary_tree();
    ?>
    <section class="wm-mobile-sheet wm-mobile-sheet--shop" id="wm-mobile-sheet-shop" data-mobile-sheet="shop" aria-hidden="true">
        <div class="wm-mobile-sheet__panel">
            <div class="wm-mobile-sheet__header" data-mobile-shop-header>
                <div class="wm-mobile-sheet__title-wrap">
                    <button type="button" class="wm-mobile-sheet__back" data-mobile-shop-back aria-label="<?php echo esc_attr__( 'بازگشت به دسته‌بندی‌ها', 'eshobe-ecommerce' ); ?>" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                    <strong class="wm-mobile-sheet__title" data-mobile-shop-title><?php echo esc_html__( 'فروشگاه', 'eshobe-ecommerce' ); ?></strong>
                </div>
                <a class="wm-mobile-sheet__view-all" href="<?php echo esc_url( wm_mobile_nav_shop_url() ); ?>" data-mobile-shop-view-all><?php echo esc_html__( 'مشاهده همه', 'eshobe-ecommerce' ); ?></a>
            </div>
            <div class="wm-mobile-sheet__body wm-mobile-sheet__body--shop" data-mobile-shop-stage="root">
                <?php wm_render_mobile_shop_sheet_panes( $tree ); ?>
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
            <?php wm_render_mobile_sheet_header( 'cart', __( 'سبد خرید', 'eshobe-ecommerce' ) ); ?>
            <div class="wm-mobile-sheet__body">
                <?php if ( $cart_count && ! empty( $cart_items ) ) : ?>
                    <div class="wm-mobile-cart-sheet__summary">
                        <span class="wm-mobile-cart-sheet__summary-label"><?php echo esc_html__( 'جمع کل', 'eshobe-ecommerce' ); ?></span>
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
                    <p class="wm-mobile-sheet__empty"><?php echo esc_html__( 'سبد خرید شما خالی است.', 'eshobe-ecommerce' ); ?></p>
                <?php endif; ?>
                <div class="wm-mobile-cart-sheet__actions">
                    <a class="wm-mobile-sheet__button wm-mobile-sheet__button--primary wm-mobile-cart-sheet__checkout" href="<?php echo esc_url( wm_mobile_nav_checkout_url() ); ?>"><?php echo esc_html__( 'رفتن به تسویه حساب', 'eshobe-ecommerce' ); ?></a>
                    <a class="wm-mobile-sheet__button wm-mobile-sheet__button--secondary wm-mobile-cart-sheet__view-cart" href="<?php echo esc_url( $cart_count ? wm_mobile_nav_cart_url() : wm_mobile_nav_shop_url() ); ?>"><?php echo esc_html( $cart_count ? __( 'مشاهده سبد خرید', 'eshobe-ecommerce' ) : __( 'مشاهده محصولات', 'eshobe-ecommerce' ) ); ?></a>
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
            <?php wm_render_mobile_sheet_header( 'account', is_user_logged_in() ? __( 'حساب من', 'eshobe-ecommerce' ) : __( 'ورود به حساب', 'eshobe-ecommerce' ) ); ?>
            <div class="wm-mobile-sheet__body">
                <?php if ( is_user_logged_in() ) : ?>
                    <div class="wm-mobile-account-sheet__user">
                        <strong><?php echo esc_html__( 'حساب من', 'eshobe-ecommerce' ); ?></strong>
                        <span><?php echo esc_html__( 'مدیریت سفارش‌ها و اطلاعات حساب', 'eshobe-ecommerce' ); ?></span>
                    </div>
                    <div class="wm-mobile-account-sheet__notifications">
                        <strong class="wm-account-dropdown__title"><?php echo esc_html__( 'اعلان‌ها', 'eshobe-ecommerce' ); ?></strong>
                        <div class="wm-notification-list" data-wm-notification-list></div>
                    </div>
                <?php else : ?>
                    <p class="wm-mobile-sheet__empty"><?php echo esc_html__( 'برای مشاهده حساب کاربری وارد شوید.', 'eshobe-ecommerce' ); ?></p>
                <?php endif; ?>
                <nav class="wm-mobile-account-sheet__links" aria-label="<?php echo esc_attr__( 'لینک‌های حساب کاربری', 'eshobe-ecommerce' ); ?>">
                    <?php foreach ( wm_mobile_nav_account_links() as $link ) : ?>
                        <a
                            class="wm-mobile-account-sheet__link <?php echo ! empty( $link['primary'] ) ? 'wm-mobile-sheet__button wm-mobile-sheet__button--primary' : ''; ?>"
                            href="<?php echo esc_url( $link['url'] ); ?>"
                            <?php if ( ! empty( $link['otp_trigger'] ) ) : ?>
                                data-wm-otp-trigger
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
    if ( ! wm_mobile_nav_is_enabled() ) {
        return;
    }

    $show_bar = wm_mobile_nav_should_render();

    $show_labels = (bool) wm_mobile_nav_get_option( 'wm_mobile_nav_show_labels', true );
    $density     = wm_mobile_nav_get_option( 'wm_mobile_nav_density', 'comfortable' );
    $density     = in_array( $density, array( 'compact', 'comfortable' ), true ) ? $density : 'comfortable';
    $class       = 'wm-mobile-nav wm-mobile-nav--' . sanitize_html_class( $density );
    $class      .= $show_labels ? ' wm-mobile-nav--labels' : ' wm-mobile-nav--icons-only';
    // On product pages the nav renders alongside the product's own purchase
    // bar (wm_render_mobile_product_bottom_bar), which sits above it.
    $class      .= wm_mobile_nav_is_product_page() ? ' wm-product-page-nav' : '';
    ?>
    <div class="wm-mobile-nav-shell" data-mobile-nav-root>
        <div class="wm-mobile-sheet__backdrop" data-mobile-sheet-close hidden></div>
        <?php
        wm_render_mobile_shop_sheet();
        wm_render_mobile_cart_sheet();
        wm_render_mobile_account_sheet();
        ?>

        <?php if ( $show_bar ) : ?>
        <nav class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr__( 'نوار پایین موبایل', 'eshobe-ecommerce' ); ?>">
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
                    <?php elseif ( 'otp-trigger' === $item['type'] ) : ?>
                        <a <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( $item['url'] ); ?>" data-wm-otp-trigger>
                            <span class="wm-mobile-nav__icon"><?php echo wm_mobile_nav_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="wm-mobile-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
                        </a>
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
        <?php endif; ?>
    </div>
    <?php
}
add_action( 'wp_footer', 'wm_render_mobile_nav', 8 );
