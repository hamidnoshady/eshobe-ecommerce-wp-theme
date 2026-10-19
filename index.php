<?php
get_header();
?>
<main id="primary" class="site-main wm-blog-archive">
    <div class="wm-container">
        <?php if ( is_home() && ! is_front_page() ) : ?>
            <header class="wm-page-header wm-page-header--archive">
                <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
                <div class="wm-page-header__row">
                    <div>
                        <h1 class="wm-page-title"><?php single_post_title(); ?></h1>
                    </div>
                </div>
            </header>
        <?php elseif ( is_archive() ) : ?>
            <header class="wm-page-header wm-page-header--archive">
                <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
                <div class="wm-page-header__row">
                    <div>
                        <?php
                        the_archive_title( '<h1 class="wm-page-title">', '</h1>' );
                        the_archive_description( '<div class="wm-page-subtitle">', '</div>' );
                        ?>
                    </div>
                </div>
            </header>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>
            <?php
            // ⚡ Bolt: Pre-fetch attachment post objects to prevent N+1 queries during post card rendering in the main loop. Expected impact: Reduces database queries by the number of posts displayed per page.
            global $wp_query;
            if ( ! empty( $wp_query->posts ) && function_exists( '_prime_post_caches' ) ) {
                $thumbnail_ids = array();
                foreach ( $wp_query->posts as $loop_post ) {
                    $thumbnail_id = get_post_thumbnail_id( $loop_post->ID );
                    if ( $thumbnail_id ) {
                        $thumbnail_ids[] = absint( $thumbnail_id );
                    }
                }
                if ( ! empty( $thumbnail_ids ) ) {
                    _prime_post_caches( array_unique( $thumbnail_ids ), false, true );
                }
            }
            ?>
            <ul class="wm-blog-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <li>
                    <?php
                    if ( function_exists( 'wm_render_post_card' ) ) {
                        echo wm_render_post_card( get_the_ID() );
                    } else {
                        get_template_part( 'template-parts/content', get_post_type() );
                    }
                    ?>
                </li>
            <?php endwhile; ?>
            </ul>
            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <?php get_template_part( 'template-parts/content', 'none' ); ?>
        <?php endif; ?>
    </div>
</main>
<?php get_footer();

