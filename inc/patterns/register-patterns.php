<?php
/**
 * Register theme block pattern infrastructure.
 *
 * @package WM_Theme
 */

function watchmid_register_pattern_categories() {
    if ( ! function_exists( 'register_block_pattern_category' ) ) {
        return;
    }

    register_block_pattern_category(
        'watchmid',
        array(
            'label' => __( 'قالب', 'watchmid' ),
        )
    );
}
add_action( 'init', 'watchmid_register_pattern_categories' );
