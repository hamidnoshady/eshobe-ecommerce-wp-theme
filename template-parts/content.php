<article id="post-<?php the_ID(); ?>" <?php post_class( 'wm-single-post' ); ?>>
    <div class="wm-container">
        <header class="wm-page-header">
            <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
            <div class="wm-page-header__row">
                <div>
                    <?php
                    if ( is_singular() ) :
                        the_title( '<h1 class="wm-page-title">', '</h1>' );
                    else :
                        the_title( '<h2 class="wm-page-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
                    endif;
                    ?>
                    
                    <?php if ( 'post' === get_post_type() ) : ?>
                        <div class="wm-page-meta">
                            <span class="wm-page-meta__item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <?php echo get_the_date(); ?>
                            </span>
                            <span class="wm-page-meta__item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <?php the_author(); ?>
                            </span>
                            <?php
                            $categories_list = get_the_category_list( esc_html__( ', ', 'eshobe-ecommerce' ) );
                            if ( $categories_list ) :
                                ?>
                                <span class="wm-page-meta__item">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    <?php echo $categories_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <?php if ( has_post_thumbnail() && ! post_password_required() && ! is_attachment() ) : ?>
            <div class="wm-page-featured-image">
                <?php the_post_thumbnail( 'full' ); ?>
            </div>
        <?php endif; ?>

        <div class="entry-content">
            <?php
            the_content(
                sprintf(
                    wp_kses(
                        /* translators: %s: Name of current post. Only visible to screen readers */
                        __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'eshobe-ecommerce' ),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post( get_the_title() )
                )
            );

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
