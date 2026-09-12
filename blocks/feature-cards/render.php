<?php
/**
 * Render callback for wm/feature-cards (InnerBlocks container for wm/feature-card).
 *
 * @package WM_Theme
 * @var array  $attributes
 * @var string $content Rendered inner blocks.
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
	return;
}

$a       = wp_parse_args( $attributes, array( 'title' => '', 'subtitle' => '', 'columns' => 3 ) );
$columns = min( 4, max( 2, absint( $a['columns'] ) ) );
?>
<section class="wm-company-section wm-company-features wm-section-decor wm-section-decor--brands wm-section-decor--dots">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
	<div class="wm-company-features__grid wm-company-features__grid--cols-<?php echo esc_attr( $columns ); ?>">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
	</div>
</section>
