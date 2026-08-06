<?php
get_header();
?>
<main id="primary" class="site-main wm-blog-archive">
    <div class="wm-container">
        <?php if ( is_home() && ! is_front_page() ) : ?>
            <header class="wm-blog-archive__header">
                <h1 class="wm-blog-archive__title"><?php single_post_title(); ?></h1>
            </header>
        <?php elseif ( is_archive() ) : ?>
            <header class="wm-blog-archive__header">
                <?php
                the_archive_title( '<h1 class="wm-blog-archive__title">', '</h1>' );
                the_archive_description( '<div class="wm-blog-archive__desc">', '</div>' );
                ?>
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

