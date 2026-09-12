<?php
/**
 * Render callback for wm/page-hero.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'eyebrow'        => '',
		'title'          => '',
		'subtitle'       => '',
		'imageId'        => 0,
		'showBreadcrumb' => true,
	)
);

$title = '' !== trim( (string) $a['title'] ) ? $a['title'] : get_the_title();
$image = wm_company_image_html( $a['imageId'], 'large', array( 'class' => 'wm-company-hero__img', 'loading' => 'eager' ) );
?>
<section class="wm-company-section wm-company-hero wm-section-decor wm-section-decor--hero wm-section-decor--dots wm-section-decor--ring<?php echo $image ? ' wm-company-hero--has-image' : ''; ?>">
	<div class="wm-company-hero__content">
		<?php if ( ! empty( $a['showBreadcrumb'] ) && function_exists( 'woocommerce_breadcrumb' ) ) : ?>
			<?php woocommerce_breadcrumb(); ?>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $a['eyebrow'] ) ) : ?>
			<span class="wm-company-hero__eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></span>
		<?php endif; ?>
		<h1 class="wm-company-hero__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== trim( (string) $a['subtitle'] ) ) : ?>
			<p class="wm-company-hero__subtitle"><?php echo esc_html( $a['subtitle'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $image ) : ?>
		<div class="wm-company-hero__media">
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
		</div>
	<?php endif; ?>
</section>
