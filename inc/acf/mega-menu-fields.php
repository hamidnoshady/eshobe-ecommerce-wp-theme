<?php
/**
 * ACF fields for the Mega Menu custom post type.
 *
 * @package WM_Theme
 */

function wm_register_mega_menu_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_wm_mega_menu_fields',
            'title'    => 'تنظیمات مگامنو',
            'fields'   => array(
                array(
                    'key'   => 'field_wm_mega_subtitle',
                    'label' => 'توضیح کوتاه مگامنو',
                    'name'  => 'mega_subtitle',
                    'type'  => 'textarea',
                    'rows'  => 2,
                    'instructions' => 'عنوان مگامنو از عنوان این پست (بالای صفحه) خوانده می‌شود.',
                ),
                array(
                    'key'          => 'field_wm_mega_columns',
                    'label'        => 'ستون‌های مگامنو',
                    'name'         => 'mega_columns',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'button_label' => 'افزودن ستون',
                    'collapsed'    => 'field_wm_mega_column_title',
                    'instructions' => 'هر ستون شامل یک عنوان و چند لینک است. ستون‌های غیرفعال یا بدون لینک نمایش داده نمی‌شوند.',
                    'sub_fields'   => array(
                        array( 'key' => 'field_wm_mega_column_enabled', 'label' => 'فعال', 'name' => 'column_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                        array( 'key' => 'field_wm_mega_column_title', 'label' => 'عنوان ستون', 'name' => 'column_title', 'type' => 'text' ),
                        array(
                            'key'          => 'field_wm_mega_column_links',
                            'label'        => 'لینک‌های ستون',
                            'name'         => 'column_links',
                            'type'         => 'repeater',
                            'layout'       => 'block',
                            'button_label' => 'افزودن لینک',
                            'collapsed'    => 'field_wm_mega_link_label',
                            'instructions' => 'لینک‌های غیرفعال یا بدون عنوان/URL نمایش داده نمی‌شوند. badge، توضیح و آیکن اختیاری هستند.',
                            'sub_fields'   => array(
                                array( 'key' => 'field_wm_mega_link_enabled', 'label' => 'فعال', 'name' => 'link_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                                array( 'key' => 'field_wm_mega_link_label', 'label' => 'عنوان لینک', 'name' => 'link_label', 'type' => 'text' ),
                                array( 'key' => 'field_wm_mega_link_url', 'label' => 'آدرس لینک', 'name' => 'link_url', 'type' => 'url' ),
                                array( 'key' => 'field_wm_mega_link_badge', 'label' => 'Badge اختیاری', 'name' => 'link_badge', 'type' => 'text', 'instructions' => 'مثال: محبوب، جدید، اقتصادی.' ),
                                array( 'key' => 'field_wm_mega_link_description', 'label' => 'توضیح کوتاه لینک', 'name' => 'link_description', 'type' => 'text' ),
                                array( 'key' => 'field_wm_mega_link_icon', 'label' => 'آیکن اختیاری', 'name' => 'link_icon', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail' ),
                            ),
                        ),
                    ),
                ),
                array( 'key' => 'field_wm_mega_feature_card_enabled', 'label' => 'نمایش کارت ویژه', 'name' => 'mega_feature_card_enabled', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1 ),
                array( 'key' => 'field_wm_mega_feature_title', 'label' => 'عنوان کارت ویژه', 'name' => 'mega_feature_title', 'type' => 'text' ),
                array( 'key' => 'field_wm_mega_feature_text', 'label' => 'متن کارت ویژه', 'name' => 'mega_feature_text', 'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'field_wm_mega_feature_image', 'label' => 'تصویر کارت ویژه', 'name' => 'mega_feature_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_wm_mega_feature_url', 'label' => 'لینک کارت ویژه', 'name' => 'mega_feature_url', 'type' => 'url' ),
                array( 'key' => 'field_wm_mega_feature_button_text', 'label' => 'متن دکمه کارت ویژه', 'name' => 'mega_feature_button_text', 'type' => 'text', 'default_value' => 'مشاهده' ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'wm_mega_menu',
                    ),
                ),
            ),
        )
    );
}
add_action( 'acf/init', 'wm_register_mega_menu_acf_fields' );
