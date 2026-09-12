<?php
/**
 * Render callback for wm/contact-card.
 *
 * Supports tel:/mailto: links (typed manually in linkUrl) so phone/email
 * cards are tappable on mobile.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'icon'     => 'phone',
		'title'    => '',
		'lines'    => '',
		'linkUrl'  => '',
		'linkText' => '',
	)
);

if ( '' === trim( (string) $a['title'] ) ) {
	return;
}

$icon  = wm_company_icon( sanitize_key( $a['icon'] ) );
$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $a['lines'] ) ) );
$url   = trim( (string) $a['linkUrl'] );
// esc_url() strips tel:/sms: unless allowed explicitly.
$allowed_protocols = array_merge( wp_allowed_protocols(), array( 'tel', 'sms' ) );
?>
<div class="wm-company-contact-card">
	<?php if ( $icon ) : ?>
		<span class="wm-company-contact-card__icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG from wm_company_icon(). ?></span>
	<?php endif; ?>
	<h3 class="wm-company-contact-card__title"><?php echo esc_html( $a['title'] ); ?></h3>
	<?php if ( $lines ) : ?>
		<div class="wm-company-contact-card__lines">
			<?php foreach ( $lines as $line ) : ?>
				<span class="wm-company-contact-card__line"><?php echo esc_html( $line ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( $url ) : ?>
		<a class="wm-company-contact-card__link" href="<?php echo esc_url( $url, $allowed_protocols ); ?>">
			<?php echo esc_html( '' !== trim( (string) $a['linkText'] ) ? $a['linkText'] : __( 'مشاهده', 'eshobe-ecommerce' ) ); ?>
		</a>
	<?php endif; ?>
</div>
