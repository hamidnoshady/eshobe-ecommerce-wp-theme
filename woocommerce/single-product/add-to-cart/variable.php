<?php
/**
 * Variable product add to cart — theme override.
 *
 * Copied from WooCommerce core 9.6.0
 * (wp-content/plugins/woocommerce/templates/single-product/add-to-cart/variable.php)
 * and modified to render theme-native swatch buttons (color/image/label)
 * alongside the original, visually-hidden <select> per attribute. Swatch
 * clicks set the hidden select's value and dispatch a native `change`
 * event (see assets/js/product-variations.js), so WooCommerce core's own
 * add-to-cart-variation.js continues to own all matching/price/stock/
 * AJAX-cart logic untouched. All original action hooks are preserved so
 * other plugins hooking this template keep working.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<?php
			// Pre-fetch all swatch terms to avoid N+1 queries.
			$swatch_taxonomies = array();
			foreach ( $attributes as $attribute_name => $options ) {
				if ( taxonomy_exists( $attribute_name ) && 'none' !== wm_get_attribute_swatch_type( $attribute_name ) ) {
					$swatch_taxonomies[] = $attribute_name;
				}
			}

			$all_swatch_terms = array();
			if ( ! empty( $swatch_taxonomies ) ) {
				// Use wp_get_object_terms to fetch terms for multiple taxonomies at once,
				// because wc_get_product_terms() expects a string taxonomy name.
				$fetched_terms = wp_get_object_terms( $product->get_id(), $swatch_taxonomies, array( 'fields' => 'all' ) );
				if ( ! is_wp_error( $fetched_terms ) && is_array( $fetched_terms ) ) {
					foreach ( $fetched_terms as $term ) {
						if ( isset( $term->taxonomy ) ) {
							$all_swatch_terms[ $term->taxonomy ][] = $term;
						}
					}
				}
			}
		?>
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) :
					$sanitized_name = sanitize_title( $attribute_name );
					$swatch_type    = taxonomy_exists( $attribute_name ) ? wm_get_attribute_swatch_type( $attribute_name ) : 'none';
					$terms_by_slug  = array();

					if ( 'none' !== $swatch_type ) {
						if ( isset( $all_swatch_terms[ $attribute_name ] ) ) {
							foreach ( $all_swatch_terms[ $attribute_name ] as $term ) {
								if ( in_array( $term->slug, $options, true ) ) {
									$terms_by_slug[ $term->slug ] = $term;
								}
							}
						} elseif ( function_exists( 'wc_get_product_terms' ) ) {
							foreach ( wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ) as $term ) {
								if ( in_array( $term->slug, $options, true ) ) {
									$terms_by_slug[ $term->slug ] = $term;
								}
							}
						}
					}
					?>
					<tr>
						<th class="label"><label for="<?php echo esc_attr( $sanitized_name ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></th>
						<td class="value">
							<?php if ( 'none' !== $swatch_type && ! empty( $terms_by_slug ) ) : ?>
								<div class="wm-variation-attribute" data-attribute_name="attribute_<?php echo esc_attr( $sanitized_name ); ?>">
									<span class="wm-variation-select-native">
										<?php
											wc_dropdown_variation_attribute_options(
												array(
													'options'   => $options,
													'attribute' => $attribute_name,
													'product'   => $product,
												)
											);
										?>
									</span>
									<div class="wm-variation-attribute__selected" data-role="wm-selected-name" aria-live="polite"></div>
									<div class="wm-variation-swatches" role="listbox" aria-label="<?php echo esc_attr( wc_attribute_label( $attribute_name ) ); ?>">
										<?php foreach ( $options as $option_slug ) :
											if ( ! isset( $terms_by_slug[ $option_slug ] ) ) {
												continue;
											}
											$term   = $terms_by_slug[ $option_slug ];
											$swatch = wm_get_term_swatch_data( $term, $attribute_name );
											?>
											<button
												type="button"
												class="wm-variation-swatch wm-variation-swatch--<?php echo esc_attr( $swatch['type'] ); ?>"
												data-value="<?php echo esc_attr( $option_slug ); ?>"
												data-name="<?php echo esc_attr( $term->name ); ?>"
												role="option"
												aria-selected="false"
												aria-label="<?php echo esc_attr( $term->name ); ?>"
												title="<?php echo esc_attr( $swatch['tooltip'] ? $swatch['tooltip'] : $term->name ); ?>"
											>
												<?php if ( 'color' === $swatch['type'] ) : ?>
													<span
														class="wm-variation-swatch__fill<?php echo $swatch['value2'] ? ' wm-variation-swatch__fill--dual' : ''; ?>"
														style="--wm-swatch-color-1: <?php echo esc_attr( $swatch['value'] ); ?>; --wm-swatch-color-2: <?php echo esc_attr( $swatch['value2'] ? $swatch['value2'] : $swatch['value'] ); ?>;"
													></span>
												<?php elseif ( 'image' === $swatch['type'] && $swatch['value'] ) : ?>
													<span class="wm-variation-swatch__fill wm-variation-swatch__fill--image" style="background-image: url('<?php echo esc_url( $swatch['value'] ); ?>');"></span>
												<?php else : ?>
													<span class="wm-variation-swatch__label"><?php echo esc_html( $swatch['value'] ); ?></span>
												<?php endif; ?>
												<span class="wm-variation-swatch__check" aria-hidden="true"></span>
											</button>
										<?php endforeach; ?>
									</div>
								</div>
							<?php else : ?>
								<?php
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'product'   => $product,
										)
									);
								?>
							<?php endif; ?>
							<?php
								// Hardcoded (not run through __()) because WooCommerce's Persian
								// language pack mistranslates "Clear" as "صاف" ("plain/smooth").
								echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="حذف انتخاب‌ها"><span aria-hidden="true">✕</span> پاک کردن انتخاب</a>' ) ) : '';
							?>

						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
				do_action( 'woocommerce_before_single_variation' );
				do_action( 'woocommerce_single_variation' );
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
