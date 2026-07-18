<?php
/**
 * Render callback for wm/filter-section.
 *
 * Wraps child wm/price-filter-card blocks in the exact same section/grid
 * markup as the theme's existing ACF-driven filter-boxes section.
 *
 * @package WM_Theme
 * @var array  $attributes
 * @var string $content Rendered inner blocks (wm/price-filter-card items).
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
    return;
}

$title    = ! empty( $attributes['title'] ) ? $attributes['title'] : '';
$subtitle = ! empty( $attributes['subtitle'] ) ? $attributes['subtitle'] : '';
?>
<section class="wm-home-section wm-home-filters wm-home-filters--cards_3 wm-section-decor wm-section-decor--filters wm-section-decor--ring">
    <?php if ( $title || $subtitle ) : ?>
        <div class="wm-home-section__header">
            <div>
                <?php if ( $title ) : ?>
                    <h2 class="wm-home-section__title"><?php echo esc_html( $title ); ?></h2>
                <?php endif; ?>
                <?php if ( $subtitle ) : ?>
                    <p class="wm-home-section__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="wm-home-filters__grid">
        <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>
<?php
