<?php
/**
 * Render callback for wm/faq-item. Uses native <details>/<summary> so the
 * accordion works with zero JS and stays keyboard-accessible; company.js only
 * adds the smooth height animation on top.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'question' => '',
		'answer'   => '',
		'open'     => false,
	)
);

if ( '' === trim( (string) $a['question'] ) ) {
	return;
}
?>
<details class="wm-company-faq__item"<?php echo ! empty( $a['open'] ) ? ' open' : ''; ?>>
	<summary class="wm-company-faq__question">
		<span><?php echo esc_html( $a['question'] ); ?></span>
		<span class="wm-company-faq__chevron" aria-hidden="true"></span>
	</summary>
	<div class="wm-company-faq__answer">
		<?php echo wp_kses_post( wpautop( (string) $a['answer'] ) ); ?>
	</div>
</details>
