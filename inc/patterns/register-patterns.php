<?php
/**
 * Register theme block pattern infrastructure.
 *
 * @package WM_Theme
 */

function eshobe_ecommerce_register_pattern_categories() {
    if ( ! function_exists( 'register_block_pattern_category' ) ) {
        return;
    }

    register_block_pattern_category(
        'eshobe-ecommerce',
        array(
            'label' => __( 'قالب', 'eshobe-ecommerce' ),
        )
    );
}
add_action( 'init', 'eshobe_ecommerce_register_pattern_categories' );
