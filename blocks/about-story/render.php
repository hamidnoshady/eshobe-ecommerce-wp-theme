<?php
/**
 * Render callback for wm/about-story.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'title'     => '',
		'subtitle'  => '',
		'content'   => '',
		'imageId'   => 0,
		'imageSide' => 'start',
		'badgeText' => '',
	)
);

if ( '' === trim( (string) $a['title'] ) && '' === trim( (string) $a['content'] ) ) {
	return;
}

$image      = wm_company_image_html( $a['imageId'], 'large', array( 'class' => 'wm-company-story__img' ) );
$side_class = 'end' === $a['imageSide'] ? ' wm-company-story--image-end' : '';
?>
<section class="wm-company-section wm-company-story wm-section-decor wm-section-decor--styles wm-section-decor--soft-grid<?php echo esc_attr( $side_class ); ?>">
	<?php if ( $image ) : ?>
		<div class="wm-company-story__media">
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
			<?php if ( '' !== trim( (string) $a['badgeText'] ) ) : ?>
				<span class="wm-company-story__badge"><?php echo esc_html( $a['badgeText'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="wm-company-story__body">
		<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
		<?php if ( '' !== trim( (string) $a['content'] ) ) : ?>
			<div class="wm-company-story__text">
				<?php echo wp_kses_post( wpautop( $a['content'] ) ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
