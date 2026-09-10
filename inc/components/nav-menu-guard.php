<?php
/**
 * Nav menu wishlist guard.
 *
 * The "علاقه مندی ها" (Wishlist) entry must never appear as a top-level item
 * in any navigation menu — it only belongs inside the logged-in account
 * dropdown / mobile account sheet, which render it themselves.
 *
 * Historically this item was removed by hand (3 times) and kept coming back.
 * The known resurrection paths are WordPress itself:
 *
 *  - "Automatically add new top-level pages" (wp_nav_menu_item_auto_add)
 *    re-attaching stray top-level items when a menu is saved, and
 *  - plugins/importers programmatically re-inserting a nav_menu_item post
 *    pointing at the wishlist page/URL.
 *
 * This guard therefore works at three levels so the item cannot survive in a
 * top-level position:
 *
 *  1. Render-time filter (`wp_nav_menu_objects`): any top-level item whose
 *     title/object/URL identifies the wishlist is stripped before markup is
 *     generated, whatever put it back in the DB.
 *  2. One-time DB purge on admin requests: stray top-level wishlist
 *     nav_menu_item posts are deleted outright (children are re-parented to
 *     the menu root so nothing is orphaned).
 *  3. Purge re-arm after every admin "save menu" request, so the very next
 *     admin load cleans up anything WP's auto-add resurrected during a save.
 *
 * Wishlist links nested under another item (e.g. inside the account menu
 * branch) are deliberately left untouched.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Does a nav-menu item represent the wishlist entry?
 *
 * Matches the Persian label used on this site plus the usual English slugs,
 * so the guard also catches duplicates created with a different title but
 * the same target.
 *
 * @param object|array $item Nav menu item (WP_Post-like object or assoc array).
 * @return bool
 */
function wm_nav_item_is_wishlist( $item ) {
    $get = static function ( $key ) use ( $item ) {
        if ( is_array( $item ) ) {
            return isset( $item[ $key ] ) ? (string) $item[ $key ] : '';
        }
        return isset( $item->{$key} ) ? (string) $item->{$key} : '';
    };

    $title       = trim( $get( 'title' ) );
    $title_attr  = trim( $get( 'attr_title' ) );
    $object      = $get( 'object' );
    $object_id   = absint( $get( 'object_id' ) );
    $url         = strtolower( $get( 'url' ) );

    // Persian label (any variant: علاقه مندی ها / علاقه‌مندی‌ها / لیست علاقه مندی ها …)
    // or an English wishlist/favorites title.
    if ( '' !== $title && 1 === preg_match( '/علاقه|\bwishlist\b|favorite/iu', $title ) ) {
        return true;
    }

    // A wishlist page object assigned as the link target.
    if ( 'page' === $object && $object_id ) {
        $slug = get_post_field( 'post_name', $object_id );

        if ( is_string( $slug ) && 1 === preg_match( '/wishlist|favorites|favourites/i', $slug ) ) {
            return true;
        }
    }

    // Wishlist URL targets (plain or query-arg based).
    if ( '' !== $url && 1 === preg_match( '/wishlist|favorites|favourites/i', $url ) ) {
        return true;
    }

    if ( '' !== $title_attr && 1 === preg_match( '/wishlist|favorites|favourites/i', $title_attr ) ) {
        return true;
    }

    return false;
}

/**
 * Is the item top-level (directly under the menu root)?
 *
 * @param object|array $item Nav menu item.
 * @return bool
 */
function wm_nav_item_is_top_level( $item ) {
    $parent = is_array( $item )
        ? ( isset( $item['menu_item_parent'] ) ? (int) $item['menu_item_parent'] : 0 )
        : ( isset( $item->menu_item_parent ) ? (int) $item->menu_item_parent : 0 );

    return 0 === $parent;
}

/**
 * Render-time guard: drop top-level wishlist items from every wp_nav_menu()
 * call (header, mobile drawer, footer, widgets) before markup is built.
 *
 * @param array $items Sorted list of WP_Post nav menu items.
 * @return array
 */
function wm_nav_guard_filter_menu_objects( $items ) {
    if ( empty( $items ) || ! is_array( $items ) ) {
        return $items;
    }

    $wishlist_ids = array();

    foreach ( $items as $item ) {
        if ( wm_nav_item_is_top_level( $item ) && wm_nav_item_is_wishlist( $item ) ) {
            $wishlist_ids[] = (int) ( is_object( $item ) ? $item->ID : $item['ID'] );
        }
    }

    if ( empty( $wishlist_ids ) ) {
        return $items;
    }

    return array_values(
        array_filter(
            $items,
            static function ( $item ) use ( $wishlist_ids ) {
                $id = (int) ( is_object( $item ) ? $item->ID : $item['ID'] );
                return ! in_array( $id, $wishlist_ids, true );
            }
        )
    );
}
add_filter( 'wp_nav_menu_objects', 'wm_nav_guard_filter_menu_objects', 999 );

/**
 * Purge stray top-level wishlist nav_menu_item posts from every nav menu.
 *
 * Children of a purged item are re-parented to the menu root (top level) so
 * nested entries are never orphaned. Runs only on admin requests so the
 * front-end never pays the extra query.
 *
 * @return void
 */
function wm_nav_guard_purge_wishlist_items() {
    if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }

    $menus = wp_get_nav_menus();

    if ( empty( $menus ) || ! is_array( $menus ) ) {
        return;
    }

    foreach ( $menus as $menu ) {
        $menu_id = is_object( $menu ) ? (int) $menu->term_id : (int) $menu;

        if ( ! $menu_id ) {
            continue;
        }

        $items = wp_get_nav_menu_items( $menu_id );

        if ( empty( $items ) || ! is_array( $items ) ) {
            continue;
        }

        foreach ( $items as $item ) {
            if ( ! wm_nav_item_is_top_level( $item ) || ! wm_nav_item_is_wishlist( $item ) ) {
                continue;
            }

            // Re-parent any children so they are not orphaned.
            foreach ( $items as $child ) {
                if ( isset( $child->menu_item_parent ) && (int) $child->menu_item_parent === (int) $item->ID ) {
                    wp_update_nav_menu_item(
                        $menu_id,
                        (int) $child->ID,
                        array( 'menu-item-parent-id' => 0 )
                    );
                }
            }

            wp_delete_post( (int) $item->ID, true );
        }
    }
}

/**
 * Schedule/execute the purge at the right moments:
 *  - `admin_menu` runs on every wp-admin load including the Menus screen;
 *  - `admin_footer` re-runs it after a Menus screen save, because WP's
 *    "automatically add top-level pages" re-attaches resurrected items
 *    during the save that the pre-save purge cannot see.
 */
add_action( 'admin_menu', 'wm_nav_guard_purge_wishlist_items', 999 );
add_action( 'admin_footer', 'wm_nav_guard_purge_wishlist_items', 999 );
