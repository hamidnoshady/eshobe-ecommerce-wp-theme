<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="wm-container">
        <?php
        /*
         * Cart and checkout templates render their own titled headers (with
         * the checkout stepper), so skip the generic breadcrumb + title
         * banner there to avoid two stacked title boxes on those pages.
         */
        $wm_hide_page_header = ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() );

        if ( ! $wm_hide_page_header ) :
        ?>
        <header class="wm-page-header">
            <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
            <div class="wm-page-header__row">
                <div>
                    <?php the_title( '<h1 class="wm-page-title">', '</h1>' ); ?>
                </div>
            </div>
        </header>
        <?php endif; ?>

        <?php if ( has_post_thumbnail() ) : ?>
            <div class="wm-page-featured-image">
                <?php the_post_thumbnail( 'full' ); ?>
            </div>
        <?php endif; ?>

        <div class="entry-content">
            <?php
            the_content();
            
            wp_link_pages(
                array(
                    'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'eshobe-ecommerce' ),
                    'after'  => '</div>',
                )
            );
            ?>
        </div>
    </div>
</article>
