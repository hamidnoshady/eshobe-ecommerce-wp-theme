<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="wm-page-header">
        <div class="wm-container">
            <?php the_title( '<h1 class="wm-page-title">', '</h1>' ); ?>
        </div>
    </header>

    <div class="wm-container">
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
