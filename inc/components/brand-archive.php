<?php
/**
 * Brand directory (alphabetical brand archive) helpers.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * The fallback index bucket for brands with no English (Latin) characters.
 */
define( 'WM_BRAND_ARCHIVE_FALLBACK_INDEX', '#' );

/**
 * Whether the current request is rendering the brand directory template.
 */
function wm_brand_archive_is_context() {
    return is_page_template( 'page-templates/brand-archive.php' );
}

/**
 * Determine the alphabetical index "bucket" for a brand term name.
 *
 * The index is always based on the English (Latin) part of the brand
 * name (e.g. "اپل واچ | APPLEWATCH" sorts under "A") since the
 * alphabet is used purely for ordering, regardless of the site's
 * Persian/RTL content. Names with no Latin letters fall under "#".
 */
function wm_brand_archive_index_letter( $name ) {
    $name = trim( wp_strip_all_tags( (string) $name ) );

    if ( preg_match( '/[A-Za-z]/', $name, $matches ) ) {
        return strtoupper( $matches[0] );
    }

    return WM_BRAND_ARCHIVE_FALLBACK_INDEX;
}

/**
 * Build the ordered list of index buckets: A-Z, then "#".
 */
function wm_brand_archive_letter_order() {
    return array_merge( range( 'A', 'Z' ), array( WM_BRAND_ARCHIVE_FALLBACK_INDEX ) );
}

/**
 * Fetch all product brand terms grouped alphabetically.
 *
 * @return array {
 *     @type WP_Term[][] $groups Letter => terms map, ordered per wm_brand_archive_letter_order().
 *     @type int         $total  Total number of brand terms.
 * }
 */
function wm_brand_archive_get_groups() {
    $groups = array();
    $total  = 0;

    if ( ! taxonomy_exists( 'product_brand' ) ) {
        return array( 'groups' => $groups, 'total' => $total );
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'product_brand',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'update_term_meta_cache' => true,
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array( 'groups' => $groups, 'total' => $total );
    }

    $thumbnail_ids = array();
    foreach ( $terms as $term ) {
        // Top-level terms are alphabet/category buckets, not real brands.
        // Only their children are actual brands shown as cards.
        if ( 0 === (int) $term->parent ) {
            continue;
        }

        $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
        if ( $thumbnail_id ) {
            $thumbnail_ids[] = absint( $thumbnail_id );
        }

        $letter             = wm_brand_archive_index_letter( $term->name );
        $groups[ $letter ][] = $term;
        $total++;
    }

    // Pre-fetch attachment post objects to prevent N+1 queries during card rendering.
    if ( ! empty( $thumbnail_ids ) && function_exists( '_prime_post_caches' ) ) {
        _prime_post_caches( array_unique( $thumbnail_ids ), false, true );
    }

    $order   = wm_brand_archive_letter_order();
    $ordered = array();
    foreach ( $order as $letter ) {
        if ( ! empty( $groups[ $letter ] ) ) {
            $ordered[ $letter ] = $groups[ $letter ];
        }
    }

    return array( 'groups' => $ordered, 'total' => $total );
}

/**
 * Render a single brand card.
 */
function wm_brand_archive_render_card( WP_Term $term ) {
    $link = get_term_link( $term );
    if ( is_wp_error( $link ) ) {
        return;
    }

    $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
    $image_html   = '';
    if ( $thumbnail_id ) {
        $image_html = wp_get_attachment_image( absint( $thumbnail_id ), 'medium', false, array( 'class' => 'wm-brand-card__image', 'alt' => $term->name ) );
    }
    ?>
    <a class="wm-brand-card" href="<?php echo esc_url( $link ); ?>">
        <span class="wm-brand-card__media">
            <?php if ( $image_html ) : ?>
                <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <span class="wm-brand-card__monogram" aria-hidden="true"><?php echo esc_html( mb_substr( $term->name, 0, 1, 'UTF-8' ) ); ?></span>
            <?php endif; ?>
        </span>
        <span class="wm-brand-card__body">
            <strong><?php echo esc_html( $term->name ); ?></strong>
            <span class="wm-brand-card__count"><?php echo esc_html( number_format_i18n( (int) $term->count ) . ' محصول' ); ?></span>
        </span>
    </a>
    <?php
}
