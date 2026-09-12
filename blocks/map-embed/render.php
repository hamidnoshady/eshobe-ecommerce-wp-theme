<?php
/**
 * Render callback for wm/map-embed.
 *
 * Only https iframe sources are accepted; anything else renders the address
 * fallback card so a typo can never inject markup.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'title'       => '',
		'subtitle'    => '',
		'embedUrl'    => '',
		'height'      => 380,
		'addressText' => '',
	)
);

$url    = trim( (string) $a['embedUrl'] );
$height = min( 640, max( 220, absint( $a['height'] ) ) );
$valid  = $url && 0 === strpos( $url, 'https://' ) && wp_http_validate_url( $url );

if ( ! $valid && '' === trim( (string) $a['addressText'] ) && '' === trim( (string) $a['title'] ) ) {
	return;
}
?>
<section class="wm-company-section wm-company-map wm-section-decor wm-section-decor--filters wm-section-decor--ring">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
	<div class="wm-company-map__frame" style="--wm-map-height: <?php echo esc_attr( $height ); ?>px;">
		<?php if ( $valid ) : ?>
			<iframe
				src="<?php echo esc_url( $url ); ?>"
				title="<?php echo esc_attr( '' !== trim( (string) $a['title'] ) ? $a['title'] : __( 'نقشه فروشگاه', 'eshobe-ecommerce' ) ); ?>"
				loading="lazy"
				allowfullscreen
				referrerpolicy="no-referrer-when-downgrade"
				sandbox="allow-scripts allow-same-origin allow-popups"
			></iframe>
		<?php else : ?>
			<div class="wm-company-map__placeholder">
				<?php echo wm_company_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
				<span><?php esc_html_e( 'برای نمایش نقشه، آدرس iframe نقشه را در تنظیمات بلوک وارد کنید.', 'eshobe-ecommerce' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( '' !== trim( (string) $a['addressText'] ) ) : ?>
		<p class="wm-company-map__address">
			<?php echo wm_company_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
			<span><?php echo esc_html( $a['addressText'] ); ?></span>
		</p>
	<?php endif; ?>
</section>
