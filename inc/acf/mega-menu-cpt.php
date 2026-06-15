<?php
/**
 * Mega Menu custom post type.
 *
 * @package WM_Theme
 */

function wm_register_mega_menu_cpt() {
    register_post_type(
        'wm_mega_menu',
        array(
            'labels'             => array(
                'name'               => 'مگامنوها',
                'singular_name'      => 'مگامنو',
                'menu_name'          => 'مگامنو',
                'add_new'            => 'افزودن مگامنو',
                'add_new_item'       => 'افزودن مگامنوی جدید',
                'edit_item'          => 'ویرایش مگامنو',
                'new_item'           => 'مگامنوی جدید',
                'view_item'          => 'مشاهده مگامنو',
                'search_items'       => 'جستجوی مگامنو',
                'not_found'          => 'مگامنویی یافت نشد',
                'not_found_in_trash' => 'مگامنویی در زباله‌دان یافت نشد',
                'all_items'          => 'همه مگامنوها',
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_admin_bar'  => false,
            'show_in_rest'       => false,
            'menu_icon'          => 'dashicons-screenoptions',
            'menu_position'      => 59,
            'supports'           => array( 'title' ),
            'has_archive'        => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
        )
    );
}
add_action( 'init', 'wm_register_mega_menu_cpt' );
