<?php
/**
 * Render callback for wm/cta-banner. Buttons reuse the .wm-home-button
 * design-system styles so CTAs match the homepage hero exactly.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'title'         => '',
		'text'          => '',
		'primaryText'   => '',
		'primaryUrl'    => '',
		'secondaryText' => '',
		'secondaryUrl'  => '',
	)
);

if ( '' === trim( (string) $a['title'] ) ) {
	return;
}

$primary_url = '' !== trim( (string) $a['primaryUrl'] )
	? $a['primaryUrl']
	: ( function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ) );
?>
<section class="wm-company-section wm-company-cta wm-section-decor wm-section-decor--hero wm-section-decor--ring">
	<div class="wm-company-cta__inner">
		<div class="wm-company-cta__copy">
			<h2 class="wm-company-cta__title"><?php echo esc_html( $a['title'] ); ?></h2>
			<?php if ( '' !== trim( (string) $a['text'] ) ) : ?>
				<p class="wm-company-cta__text"><?php echo esc_html( $a['text'] ); ?></p>
			<?php endif; ?>
		</div>
		<div class="wm-company-cta__actions">
			<?php if ( '' !== trim( (string) $a['primaryText'] ) ) : ?>
				<a class="wm-home-button wm-home-button--primary" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $a['primaryText'] ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $a['secondaryText'] ) && '' !== trim( (string) $a['secondaryUrl'] ) ) : ?>
				<a class="wm-home-button wm-home-button--outline" href="<?php echo esc_url( $a['secondaryUrl'] ); ?>"><?php echo esc_html( $a['secondaryText'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
