<?php
/**
 * Render callback for wm/contact-cards (InnerBlocks container for wm/contact-card).
 *
 * @package WM_Theme
 * @var array  $attributes
 * @var string $content Rendered inner blocks.
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
	return;
}

$a = wp_parse_args( $attributes, array( 'title' => '', 'subtitle' => '' ) );
?>
<section class="wm-company-section wm-company-contact-cards wm-section-decor wm-section-decor--brands wm-section-decor--dots">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
	<div class="wm-company-contact-cards__grid">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
	</div>
</section>
