<?php
/**
 * Render callback for wm/home-section.
 *
 * Renders one of the theme's existing ACF-driven homepage sections unchanged;
 * this block only controls where it appears and in what order.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$section = ! empty( $attributes['section'] ) ? sanitize_key( $attributes['section'] ) : '';

if ( $section && function_exists( 'wm_render_home_section' ) ) {
    wm_render_home_section( $section );
}
