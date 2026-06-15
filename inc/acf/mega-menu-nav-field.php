<?php
/**
 * ACF field for assigning a Mega Menu post to a nav menu item.
 *
 * @package WM_Theme
 */

function wm_register_mega_menu_nav_field() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_wm_mega_menu_nav_field',
            'title'    => 'مگامنوی آیتم منو',
            'fields'   => array(
                array(
                    'key'           => 'field_wm_mega_menu_post',
                    'label'         => 'مگامنو',
                    'name'          => 'wm_mega_menu_post',
                    'type'          => 'post_object',
                    'post_type'     => array( 'wm_mega_menu' ),
                    'return_format' => 'id',
                    'allow_null'    => 1,
                    'ui'            => 1,
                    'instructions'  => 'در صورت انتخاب، با هاور/فوکوس روی این آیتم منو، مگامنوی انتخاب‌شده باز می‌شود.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'nav_menu_item',
                        'operator' => '==',
                        'value'    => 'all',
                    ),
                ),
            ),
        )
    );
}
add_action( 'acf/init', 'wm_register_mega_menu_nav_field' );
