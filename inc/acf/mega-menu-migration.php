<?php
/**
 * One-time migration of legacy ACF mega menu repeater to wm_mega_menu CPT.
 *
 * @package WM_Theme
 */

function wm_migrate_legacy_mega_menus() {
    if ( get_option( 'wm_mega_menu_migrated' ) ) {
        return;
    }

    if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
        return;
    }

    $rows = get_field( 'wm_header_mega_menus', 'option' );

    if ( empty( $rows ) || ! is_array( $rows ) ) {
        update_option( 'wm_mega_menu_migrated', 1 );
        return;
    }

    $trigger_map = array();

    foreach ( (array) $rows as $row ) {
        $row = (array) $row;

        if ( empty( $row['mega_enabled'] ) ) {
            continue;
        }

        $title = $row['mega_title'] ?? '';

        if ( '' === $title ) {
            $title = $row['mega_trigger_key'] ?? '';
        }

        if ( '' === $title ) {
            $title = 'مگامنو';
        }

        $post_id = wp_insert_post(
            array(
                'post_type'   => 'wm_mega_menu',
                'post_status' => 'publish',
                'post_title'  => $title,
            ),
            true
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        update_field( 'mega_subtitle', $row['mega_subtitle'] ?? '', $post_id );
        update_field( 'mega_columns', $row['mega_columns'] ?? array(), $post_id );
        update_field( 'mega_feature_card_enabled', $row['mega_feature_card_enabled'] ?? 0, $post_id );
        update_field( 'mega_feature_title', $row['mega_feature_title'] ?? '', $post_id );
        update_field( 'mega_feature_text', $row['mega_feature_text'] ?? '', $post_id );
        update_field( 'mega_feature_image', $row['mega_feature_image'] ?? '', $post_id );
        update_field( 'mega_feature_url', $row['mega_feature_url'] ?? '', $post_id );
        update_field( 'mega_feature_button_text', $row['mega_feature_button_text'] ?? '', $post_id );

        $trigger_key = strtolower( trim( (string) ( $row['mega_trigger_key'] ?? '' ) ) );

        if ( '' !== $trigger_key ) {
            $trigger_map[ $trigger_key ] = $post_id;
        }
    }

    if ( ! empty( $trigger_map ) ) {
        $menus = wp_get_nav_menus();

        foreach ( (array) $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );

            if ( empty( $items ) || ! is_array( $items ) ) {
                continue;
            }

            foreach ( $items as $item ) {
                $classes = isset( $item->classes ) ? (array) $item->classes : array();

                foreach ( $classes as $class ) {
                    $class = strtolower( trim( (string) $class ) );

                    if ( 0 !== strpos( $class, 'wm-mega-trigger-' ) ) {
                        continue;
                    }

                    $key = substr( $class, strlen( 'wm-mega-trigger-' ) );

                    if ( isset( $trigger_map[ $key ] ) ) {
                        update_field( 'wm_mega_menu_post', $trigger_map[ $key ], $item->ID );
                    }
                }
            }
        }
    }

    update_option( 'wm_mega_menu_migrated', 1 );
}
add_action( 'admin_init', 'wm_migrate_legacy_mega_menus' );
