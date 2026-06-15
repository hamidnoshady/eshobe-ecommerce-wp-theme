<?php
get_header();
?>
<main id="primary" class="site-main">
    <?php if ( have_posts() ) : ?>
        <div class="entry-content">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
        <?php endwhile; the_posts_navigation(); ?>
        </div>
    <?php else : ?>
        <?php get_template_part( 'template-parts/content', 'none' ); ?>
    <?php endif; ?>
</main>
<?php get_footer();
