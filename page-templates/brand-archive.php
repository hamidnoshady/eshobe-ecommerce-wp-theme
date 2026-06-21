<?php
/**
 * Template Name: آرشیو برندها
 * Template Post Type: page
 *
 * Alphabetical directory of all product brands.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$wm_brand_data   = wm_brand_archive_get_groups();
$wm_brand_groups = $wm_brand_data['groups'];
$wm_brand_total  = $wm_brand_data['total'];
?>
<main id="primary" class="site-main wm-brand-archive">
    <div class="wm-brand-archive__container wm-section-decor wm-section-decor--brands wm-section-decor--dots">
        <header class="wm-brand-archive__header">
            <?php woocommerce_breadcrumb(); ?>
            <div class="wm-brand-archive__header-row">
                <div>
                    <h1 class="wm-brand-archive__title"><?php echo esc_html( get_the_title() ?: __( 'برندها', 'eshobe-ecommerce' ) ); ?></h1>
                    <p class="wm-brand-archive__subtitle">
                        <?php
                        if ( get_the_content() ) {
                            the_content();
                        } else {
                            echo esc_html__( 'مرور برندهای موجود در فروشگاه بر اساس حروف الفبا', 'eshobe-ecommerce' );
                        }
                        ?>
                    </p>
                </div>
                <?php if ( $wm_brand_total > 0 ) : ?>
                    <span class="wm-brand-archive__count"><?php echo esc_html( sprintf( _n( '%s برند', '%s برند', $wm_brand_total, 'eshobe-ecommerce' ), number_format_i18n( $wm_brand_total ) ) ); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <?php if ( empty( $wm_brand_groups ) ) : ?>
            <p class="wm-brand-archive__empty"><?php echo esc_html__( 'در حال حاضر برندی برای نمایش وجود ندارد.', 'eshobe-ecommerce' ); ?></p>
        <?php else : ?>
            <nav class="wm-brand-index" dir="ltr" aria-label="<?php echo esc_attr__( 'فهرست الفبایی برندها', 'eshobe-ecommerce' ); ?>" data-brand-index>
                <?php foreach ( $wm_brand_groups as $letter => $terms ) : ?>
                    <a class="wm-brand-index__link" href="#wm-brand-group-<?php echo esc_attr( rawurlencode( $letter ) ); ?>"><?php echo esc_html( $letter ); ?></a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ( $wm_brand_groups as $letter => $terms ) : ?>
                <section class="wm-brand-archive__group" id="wm-brand-group-<?php echo esc_attr( rawurlencode( $letter ) ); ?>" data-brand-group="<?php echo esc_attr( $letter ); ?>">
                    <h2 class="wm-brand-archive__group-title"><?php echo esc_html( $letter ); ?></h2>
                    <div class="wm-brand-archive__grid">
                        <?php foreach ( $terms as $term ) : ?>
                            <?php wm_brand_archive_render_card( $term ); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
