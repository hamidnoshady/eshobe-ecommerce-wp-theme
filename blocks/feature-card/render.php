<?php
/**
 * Render callback for wm/feature-card.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'icon'  => 'star',
		'title' => '',
		'text'  => '',
	)
);

if ( '' === trim( (string) $a['title'] ) ) {
	return;
}

$icon = wm_company_icon( sanitize_key( $a['icon'] ) );
?>
<div class="wm-company-feature-card">
	<?php if ( $icon ) : ?>
		<span class="wm-company-feature-card__icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG from wm_company_icon(). ?></span>
	<?php endif; ?>
	<h3 class="wm-company-feature-card__title"><?php echo esc_html( $a['title'] ); ?></h3>
	<?php if ( '' !== trim( (string) $a['text'] ) ) : ?>
		<p class="wm-company-feature-card__text"><?php echo esc_html( $a['text'] ); ?></p>
	<?php endif; ?>
</div>
