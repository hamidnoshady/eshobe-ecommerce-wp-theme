<?php
/**
 * Shared post card component for blog archives.
 *
 * @package WM_Theme
 */

function wm_render_post_card( $post_id = null, $args = array() ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'class' => '',
        )
    );

    $permalink = get_permalink( $post->ID );
    $title     = get_the_title( $post->ID );
    $classes   = trim( 'wm-post-card ' . $args['class'] );
    
    // Get thumbnail
    $thumbnail_id = get_post_thumbnail_id( $post->ID );
    $has_thumb    = ! empty( $thumbnail_id );

    // Get categories
    $categories = get_the_category( $post->ID );
    $primary_cat = ! empty( $categories ) ? $categories[0] : null;

    ob_start();
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <a class="wm-post-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
            <?php if ( $has_thumb ) : ?>
                <?php echo wp_get_attachment_image( $thumbnail_id, 'medium_large', false, array( 'class' => 'wm-post-card__image', 'alt' => $title, 'loading' => 'lazy' ) ); ?>
            <?php else : ?>
                <div class="wm-post-card__placeholder">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            <?php endif; ?>
        </a>
        
        <div class="wm-post-card__content">
            <?php if ( $primary_cat ) : ?>
                <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>" class="wm-post-card__category">
                    <?php echo esc_html( $primary_cat->name ); ?>
                </a>
            <?php endif; ?>
            
            <h3 class="wm-post-card__title">
                <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
            </h3>
            
            <div class="wm-post-card__excerpt">
                <?php echo wp_trim_words( get_the_excerpt( $post ), 15, '...' ); ?>
            </div>
            
            <div class="wm-post-card__meta">
                <span class="wm-post-card__date"><?php echo get_the_date( '', $post ); ?></span>
                <a href="<?php echo esc_url( $permalink ); ?>" class="wm-post-card__read-more" aria-label="<?php esc_attr_e( 'ادامه مطلب', 'eshobe-ecommerce' ); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
                </a>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
