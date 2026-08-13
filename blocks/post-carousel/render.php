<?php
/**
 * Render callback for wm/post-carousel.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wm_render_post_carousel' ) ) {
    return;
}

$attributes = wp_parse_args(
    $attributes,
    array(
        'title'          => '',
        'subtitle'       => '',
        'category'       => '',
        'count'          => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'columnsDesktop' => 4,
        'columnsTablet'  => 3,
        'columnsMobile'  => 1,
    )
);

$count = max( 1, absint( $attributes['count'] ) );

$query_args = array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => $count,
    'ignore_sticky_posts' => true,
    'orderby'             => $attributes['orderby'],
    'order'               => $attributes['order'],
);

if ( ! empty( $attributes['category'] ) ) {
    $query_args['category_name'] = $attributes['category']; // Assumes category slug is provided
}

$posts = get_posts( $query_args );

if ( empty( $posts ) ) {
    return;
}

$view_all_url = '';
if ( ! empty( $attributes['category'] ) ) {
    $cat_obj = get_category_by_slug( $attributes['category'] );
    if ( $cat_obj ) {
        $view_all_url = get_category_link( $cat_obj->term_id );
    }
}
if ( empty( $view_all_url ) && get_option( 'page_for_posts' ) ) {
    $view_all_url = get_permalink( get_option( 'page_for_posts' ) );
} elseif ( empty( $view_all_url ) ) {
    $view_all_url = home_url( '/' );
}

echo wm_render_post_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $posts,
    array(
        'title'          => $attributes['title'],
        'subtitle'       => $attributes['subtitle'],
        'class'          => 'wm-home-section wm-home-posts',
        'view_all'       => $view_all_url,
        'columnsDesktop' => $attributes['columnsDesktop'],
        'columnsTablet'  => $attributes['columnsTablet'],
        'columnsMobile'  => $attributes['columnsMobile'],
    )
);
