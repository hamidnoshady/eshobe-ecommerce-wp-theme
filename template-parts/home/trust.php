<?php
/**
 * Home trust items.
 *
 * @package WM_Theme
 */

$items = wm_home_get_valid_items(
    'home_trust_items',
    function( $item ) {
        return ! empty( $item['trust_enabled'] ) && ! empty( $item['trust_title'] );
    }
);

if ( empty( $items ) ) {
    $items = array(
        array( 'trust_enabled' => true, 'trust_title' => 'ضمانت اصالت کالا', 'trust_text' => 'انتخاب مطمئن از محصولات معتبر' ),
        array( 'trust_enabled' => true, 'trust_title' => 'ارسال سریع', 'trust_text' => 'پردازش و ارسال منظم سفارش‌ها' ),
        array( 'trust_enabled' => true, 'trust_title' => 'پرداخت امن', 'trust_text' => 'خرید امن با تجربه ساده' ),
        array( 'trust_enabled' => true, 'trust_title' => 'پشتیبانی خرید', 'trust_text' => 'راهنمایی پیش از انتخاب نهایی' ),
    );
}
?>
<section class="wm-home-section wm-home-trust">
    <div class="wm-home-trust__grid">
        <?php foreach ( $items as $item ) : ?>
            <div class="wm-home-trust__item">
                <span class="wm-home-trust__icon">
                    <?php echo wm_home_get_image_html( ! empty( $item['trust_icon'] ) ? $item['trust_icon'] : '', 'thumbnail', array( 'alt' => $item['trust_title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
                <strong><?php echo esc_html( $item['trust_title'] ); ?></strong>
                <?php if ( ! empty( $item['trust_text'] ) ) : ?>
                    <small><?php echo esc_html( $item['trust_text'] ); ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

