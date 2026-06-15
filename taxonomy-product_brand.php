<?php
/**
 * Product brand archive placeholder.
 *
 * @package WM_Theme
 */

if ( function_exists( 'wc_get_template' ) ) {
    wc_get_template( 'archive-product.php' );
} else {
    get_template_part( 'index' );
}
