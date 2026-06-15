<?php
/**
 * Home men quick filters.
 *
 * @package WM_Theme
 */

$items = wm_home_get_option( 'home_men_quick_filters', array() );
wm_render_home_quick_filters_section(
    $items,
    array(
        'class'    => 'wm-home-filters--men',
        'title'    => 'انتخاب‌های مردانه و پسرانه',
        'subtitle' => 'فیلترهای آماده برای مدل‌های کلاسیک، اسپرت و روزمره.',
    )
);

