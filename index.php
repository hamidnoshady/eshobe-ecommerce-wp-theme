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

