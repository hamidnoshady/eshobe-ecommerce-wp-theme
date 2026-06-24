# Variation Swatch UI Rebuild Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace YITH's JS-hijacked `<select>` swatch rendering on the single-product page with a theme-native, design-token-styled swatch UI (color/image/label) that reads YITH's term-meta data read-only, plus fix the mobile sticky add-to-cart bar's flush-positioning, tap-target, and default-expanded behavior.

**Architecture:** A theme override of `woocommerce/single-product/add-to-cart/variable.php` renders custom swatch buttons alongside a visually-hidden native `<select>` per attribute. Swatch clicks set the hidden select's value and dispatch a native `change` event, so WooCommerce core's untouched `add-to-cart-variation.js` continues to own all matching/price/stock/AJAX-cart logic. YITH's own frontend swatch script is dequeued only on single-product pages; its admin screens and term-meta storage are untouched.

**Tech Stack:** PHP (WordPress/WooCommerce template overrides, no build step), vanilla JS, plain CSS using existing `--wm-*` design tokens. No test runner exists in this project — see Global Constraints.

## Global Constraints

- No automated test suite, linter, or build step exists in this repo (confirmed in `CLAUDE.md`). Every task's "test" step is a concrete manual verification command (`studio wp eval`, browser check via the `verify` skill) instead of an automated test — do not skip these steps.
- After every change: bump `ESHOBE_ECOMMERCE_VERSION` in `functions.php` AND `Version:` in `style.css` together (patch increment), per `CLAUDE.md`. Each task below specifies its exact version bump.
- CSS/JS must use only existing tokens from `assets/css/tokens.css` (`--wm-color-*`, `--wm-space-*`, `--wm-radius-*`, `--wm-transition`, `--wm-font-primary`) — no new hardcoded colors/spacing.
- Never modify YITH plugin files or its stored term meta — read-only consumption only (`wp-content/plugins/yith-woocommerce-color-label-variations-premium/`).
- Never modify WooCommerce core files (`wp-content/plugins/woocommerce/`) — only override its template via the theme's `woocommerce/` folder.
- RTL: all new markup must work in the theme's RTL/Persian layout (test with `dir="rtl"`, which is the site default).
- Per `CLAUDE.md`, after each commit: `git push origin main` (this repo's documented, required deploy step).

---

## File Structure

| File | Responsibility |
|---|---|
| `inc/yith-swatches.php` | Read-only helpers translating YITH term-meta into a generic swatch-data shape; safe fallback when YITH absent |
| `inc/woocommerce.php` | Add: dequeue YITH's frontend swatch script/style on `is_product()` |
| `woocommerce/single-product/add-to-cart/variable.php` | New theme override of the WC core template; renders hidden native selects + custom swatch buttons |
| `assets/css/components/variation-swatches.css` | All new swatch visual styles (color/image/label, selected, out-of-stock) |
| `assets/js/product-variations.js` | Swatch click → hidden-select sync; selected-name header sync; out-of-stock greying |
| `inc/product-components.php` | Edit: `wm_render_mobile_product_bottom_bar()` defaults to expanded for variable products |
| `assets/css/product-components.css` | Edit: mobile bar flush positioning + handle tap-target fix |
| `functions.php` | Edit: enqueue new CSS/JS on `is_product()`; version bumps |
| `style.css` | Edit: `Version:` bumps |

---

### Task 1: YITH swatch-data helpers

**Files:**
- Create: `inc/yith-swatches.php`
- Modify: `functions.php:222` (add `require` line, after the existing `inc/woocommerce.php` require)

**Interfaces:**
- Produces: `wm_get_attribute_swatch_type( string $taxonomy ): string` — returns `'colorpicker'|'image'|'label'|'none'`
- Produces: `wm_get_term_swatch_data( WP_Term $term, string $taxonomy ): array` — returns `['type' => 'color'|'image'|'label', 'value' => string, 'value2' => string, 'tooltip' => string]` (`value2` only present for dual-color; empty string otherwise)
- Consumes: WooCommerce core `wc_attribute_taxonomy_id_by_name()`, `wc_get_attribute_taxonomies()`; YITH's `ywccl_get_term_meta()` (guarded by `function_exists()`)

- [ ] **Step 1: Create the helpers file**

```php
<?php
/**
 * Read-only helpers for reading YITH Color, Image & Label Variation Swatches
 * term-meta, so the theme can render its own swatch UI without depending on
 * YITH's frontend JS. Falls back to plain label swatches if YITH is inactive
 * or an attribute isn't configured with a YITH swatch type. Never writes to
 * YITH's data — its admin screens keep working unchanged.
 *
 * @package WM_Theme
 */

if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

/**
 * Get the YITH swatch type configured for a product attribute taxonomy.
 *
 * @param string $taxonomy Attribute taxonomy name, e.g. 'pa_color' or 'color'.
 * @return string One of 'colorpicker', 'image', 'label', or 'none'.
 */
function wm_get_attribute_swatch_type( $taxonomy ) {
    if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
        return 'none';
    }

    $attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
    if ( ! $attribute_id ) {
        return 'none';
    }

    $taxonomies = wc_get_attribute_taxonomies();
    $tax_object = isset( $taxonomies[ 'id:' . $attribute_id ] ) ? $taxonomies[ 'id:' . $attribute_id ] : null;

    if ( ! $tax_object || empty( $tax_object->attribute_type ) ) {
        return 'none';
    }

    $supported = array( 'colorpicker', 'image', 'label' );

    return in_array( $tax_object->attribute_type, $supported, true ) ? $tax_object->attribute_type : 'none';
}

/**
 * Get normalized swatch-rendering data for a single attribute term, reading
 * YITH's term meta read-only. Falls back to a plain label using the term
 * name if YITH isn't active or the term has no swatch value configured.
 *
 * @param WP_Term $term     The attribute term.
 * @param string  $taxonomy Attribute taxonomy name (e.g. 'pa_color').
 * @return array{type: string, value: string, value2: string, tooltip: string}
 */
function wm_get_term_swatch_data( $term, $taxonomy ) {
    $fallback = array(
        'type'    => 'label',
        'value'   => $term->name,
        'value2'  => '',
        'tooltip' => '',
    );

    if ( ! function_exists( 'ywccl_get_term_meta' ) ) {
        return $fallback;
    }

    $swatch_type = wm_get_attribute_swatch_type( $taxonomy );
    if ( 'none' === $swatch_type ) {
        return $fallback;
    }

    $value   = ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy );
    $tooltip = ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $taxonomy );
    $tooltip = is_string( $tooltip ) ? $tooltip : '';

    if ( 'colorpicker' === $swatch_type ) {
        $swatch_subtype = ywccl_get_term_meta( $term->term_id, '_yith_wccl_swatch_type', true, $taxonomy );

        if ( 'image_color' === $swatch_subtype ) {
            $image_url = ywccl_get_term_meta( $term->term_id, '_yith_wccl_attribute_image', true, $taxonomy );
            return array(
                'type'    => 'image',
                'value'   => $image_url ? $image_url : '',
                'value2'  => '',
                'tooltip' => $tooltip,
            );
        }

        $colors = is_string( $value ) ? array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) ) : array();

        if ( empty( $colors ) ) {
            return $fallback;
        }

        return array(
            'type'    => 'color',
            'value'   => $colors[0],
            'value2'  => isset( $colors[1] ) ? $colors[1] : '',
            'tooltip' => $tooltip,
        );
    }

    if ( 'image' === $swatch_type ) {
        return array(
            'type'    => 'image',
            'value'   => $value ? $value : '',
            'value2'  => '',
            'tooltip' => $tooltip,
        );
    }

    // 'label' type.
    return array(
        'type'    => 'label',
        'value'   => $value ? $value : $term->name,
        'value2'  => '',
        'tooltip' => $tooltip,
    );
}
```

- [ ] **Step 2: Require the file from `functions.php`**

In `functions.php`, after the line `require get_template_directory() . '/inc/woocommerce.php';` (line 213), add:

```php
require get_template_directory() . '/inc/yith-swatches.php';
```

- [ ] **Step 3: Verify with a real product**

First, find a variable product with a color attribute (the "annie Lip Pencil Crayon" product from earlier, or any product using the `pa_color` taxonomy with YITH swatches configured):

```bash
studio wp post list --post_type=product --field=ID --post_status=publish | head -20
```

For a candidate ID, check its attributes:

```bash
studio wp eval '
$product = wc_get_product( PASTE_ID_HERE );
if ( $product && $product->is_type( "variable" ) ) {
    foreach ( $product->get_variation_attributes() as $taxonomy => $options ) {
        echo $taxonomy . ": " . implode( ",", $options ) . "\n";
    }
}
'
```

Pick a taxonomy from the output (e.g. `pa_color`), then verify the helpers:

```bash
studio wp eval '
$taxonomy = "pa_color";
echo "swatch type: " . wm_get_attribute_swatch_type( $taxonomy ) . "\n";
$terms = get_terms( array( "taxonomy" => $taxonomy, "hide_empty" => false ) );
foreach ( $terms as $term ) {
    $data = wm_get_term_swatch_data( $term, $taxonomy );
    echo $term->name . " => " . wp_json_encode( $data ) . "\n";
}
'
```

Expected: `swatch type: colorpicker` (or `image`/`label` depending on configuration), and each term printing a JSON object with a non-empty `value` matching the hex/URL/text configured in wp-admin → Products → Attributes → (the color attribute) → terms.

- [ ] **Step 4: Commit**

In `functions.php`, bump `ESHOBE_ECOMMERCE_VERSION` from `0.4.67` to `0.4.68`. In `style.css`, bump `Version:` from `0.4.67` to `0.4.68`.

```bash
git add inc/yith-swatches.php functions.php style.css
git commit -m "$(cat <<'EOF'
Add read-only YITH swatch-data helpers

wm_get_attribute_swatch_type() and wm_get_term_swatch_data() translate
YITH Color/Image/Label Variation Swatches term meta into a generic
shape the theme can render with, falling back to plain labels if YITH
is inactive. No YITH data is written.
EOF
)"
git push origin main
```

---

### Task 2: Dequeue YITH's frontend swatch script on single-product pages

**Files:**
- Modify: `inc/woocommerce.php` (append new block at end of file)

**Interfaces:**
- Consumes: nothing from Task 1
- Produces: nothing consumed by later tasks (purely removes a conflicting script)

- [ ] **Step 1: Confirm the registered handle name**

```bash
studio wp eval '
add_action( "wp_enqueue_scripts", function() {
    global $wp_scripts;
    foreach ( $wp_scripts->registered as $handle => $script ) {
        if ( false !== strpos( $handle, "wccl" ) ) {
            echo $handle . " => " . $script->src . "\n";
        }
    }
}, 9999 );
do_action( "wp" );
'
```

Expected output includes a line like `yith_wccl_frontend => .../assets/js/yith-wccl.js` — this confirms the handle name to dequeue. (If the handle differs from `yith_wccl_frontend`, use the actual printed handle in Step 2.)

- [ ] **Step 2: Add the dequeue hook**

Append to `inc/woocommerce.php`:

```php
/**
 * The theme renders its own variation swatch UI on single-product pages
 * (see woocommerce/single-product/add-to-cart/variable.php), reading YITH's
 * term meta directly. Dequeue YITH's own frontend swatch script/style there
 * to avoid it hijacking the native <select> a second time. Left fully
 * active everywhere else (shop loop, admin term-meta screens).
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( is_product() ) {
        wp_dequeue_script( 'yith_wccl_frontend' );
        wp_dequeue_style( 'yith_wccl_frontend' );
    }
}, 20 );
```

- [ ] **Step 3: Verify**

```bash
studio site start --skip-browser
```

Use the `verify` skill (or Chrome DevTools MCP) to open a variable product's single page and check the page source / Network tab: confirm no request to `yith-wccl.js` or `yith-wccl.css` loads. Then visit the shop archive page (`/shop/`) and confirm `yith-wccl.js`/`.css` DO still load there (loop swatches untouched).

- [ ] **Step 4: Commit**

Bump `ESHOBE_ECOMMERCE_VERSION` to `0.4.69` in `functions.php` and `Version:` to `0.4.69` in `style.css`.

```bash
git add inc/woocommerce.php functions.php style.css
git commit -m "$(cat <<'EOF'
Dequeue YITH frontend swatch script on single-product pages

The theme now renders its own swatch UI there using YITH's term meta
directly, so YITH's own DOM-hijacking script would otherwise double up.
Left untouched on the shop loop and in wp-admin.
EOF
)"
git push origin main
```

---

### Task 3: Theme override template with hidden-select-sync swatch markup

**Files:**
- Create: `woocommerce/single-product/add-to-cart/variable.php`

**Interfaces:**
- Consumes: `wm_get_attribute_swatch_type()`, `wm_get_term_swatch_data()` (Task 1)
- Produces: markup contract for Task 4 (CSS) and Task 5 (JS):
  - Wrapper: `<div class="wm-variation-attribute" data-attribute_name="attribute_{sanitized}">`
  - Hidden select: `<span class="wm-variation-select-native">` wrapping the unmodified `wc_dropdown_variation_attribute_options()` output (so the real `<select name="attribute_...">` stays in the DOM)
  - Live selected-name line: `<div class="wm-variation-attribute__selected" data-role="wm-selected-name"></div>`
  - Swatch row: `<div class="wm-variation-swatches" role="listbox">` containing one `<button type="button" class="wm-variation-swatch wm-variation-swatch--{color|image|label}" data-value="{term slug}" data-name="{term name}" role="option" aria-selected="false">` per term

- [ ] **Step 1: Create the override, copied from WC core 9.6.0 and modified**

```php
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
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) :
					$sanitized_name = sanitize_title( $attribute_name );
					$swatch_type    = taxonomy_exists( $attribute_name ) ? wm_get_attribute_swatch_type( $attribute_name ) : 'none';
					$terms_by_slug  = array();

					if ( 'none' !== $swatch_type && function_exists( 'wc_get_product_terms' ) ) {
						foreach ( wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ) as $term ) {
							if ( in_array( $term->slug, $options, true ) ) {
								$terms_by_slug[ $term->slug ] = $term;
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
							<?php echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : ''; ?>
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
```

- [ ] **Step 2: Verify the markup renders**

Use the `verify` skill to open the variable product page in a browser and inspect the DOM (Chrome DevTools MCP `take_snapshot` or view-source). Confirm:
- Each swatch-configured attribute row contains `.wm-variation-attribute` with a `<select>` inside `.wm-variation-select-native`, a `.wm-variation-attribute__selected` div, and one `.wm-variation-swatch` button per term.
- A non-swatch attribute (if the product has one without a YITH type configured) still renders the plain `<select>` exactly as before — no swatch UI, unchanged behavior.
- No PHP warnings/notices in `wp-content/debug.log` (enable with `studio site set --debug-log --debug-display` first if not already on).

- [ ] **Step 3: Commit**

Bump version to `0.4.70` in both files.

```bash
git add woocommerce/single-product/add-to-cart/variable.php functions.php style.css
git commit -m "$(cat <<'EOF'
Override variable.php to render theme-native swatch markup

Renders color/image/label swatch buttons per YITH-configured attribute
term, alongside the original (now visually-hidden) native <select>.
Non-swatch attributes keep rendering the plain dropdown unchanged.
EOF
)"
git push origin main
```

---

### Task 4: Swatch CSS (design-token based)

**Files:**
- Create: `assets/css/components/variation-swatches.css`

**Interfaces:**
- Consumes: markup classes from Task 3 (`.wm-variation-attribute`, `.wm-variation-select-native`, `.wm-variation-attribute__selected`, `.wm-variation-swatches`, `.wm-variation-swatch`, `.wm-variation-swatch--color|image|label`, `.wm-variation-swatch__fill`, `.wm-variation-swatch__fill--dual`, `.wm-variation-swatch__fill--image`, `.wm-variation-swatch__label`, `.wm-variation-swatch__check`)
- Produces: state classes Task 5's JS will toggle: `.wm-variation-swatch.is-selected`, `.wm-variation-swatch.is-unavailable`

- [ ] **Step 1: Write the stylesheet**

```css
/**
 * Theme-native variation swatch UI (color / image / label), styled
 * entirely from existing design tokens (assets/css/tokens.css). Reads
 * YITH term-meta server-side (see inc/yith-swatches.php) but renders
 * independently of YITH's own frontend script.
 */

.wm-variation-attribute {
  margin-bottom: var(--wm-space-3);
}

.wm-variation-select-native {
  display: none;
}

.wm-variation-attribute__selected {
  margin-bottom: var(--wm-space-2);
  color: var(--wm-color-primary);
  font-family: var(--wm-font-primary);
  font-size: 14px;
  font-weight: 800;
  min-height: 1.4em;
}

.wm-variation-swatches {
  display: flex;
  flex-wrap: wrap;
  gap: var(--wm-space-2);
}

.wm-variation-swatch {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
  transition: var(--wm-transition);
}

.wm-variation-swatch--color,
.wm-variation-swatch--image {
  width: 34px;
  height: 34px;
}

.wm-variation-swatch--label {
  height: 38px;
  min-width: 38px;
  padding: 0 var(--wm-space-3);
  border-radius: var(--wm-radius-pill);
}

.wm-variation-swatch__fill {
  display: block;
  width: 100%;
  height: 100%;
  border: 1px solid var(--wm-color-border);
  border-radius: 50%;
  background-color: var(--wm-swatch-color-1, var(--wm-color-border));
}

.wm-variation-swatch__fill--dual {
  background: linear-gradient(135deg, var(--wm-swatch-color-1) 50%, var(--wm-swatch-color-2) 50%);
}

.wm-variation-swatch__fill--image {
  border-radius: var(--wm-radius-sm);
  background-size: cover;
  background-position: center;
}

.wm-variation-swatch--image .wm-variation-swatch__fill {
  border-radius: var(--wm-radius-sm);
}

.wm-variation-swatch__label {
  color: var(--wm-color-text);
  font-family: var(--wm-font-primary);
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.wm-variation-swatch--label .wm-variation-swatch__fill {
  display: none;
}

.wm-variation-swatch--label {
  background: var(--wm-color-soft);
  border-color: var(--wm-color-border);
}

.wm-variation-swatch__check {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
}

.wm-variation-swatch.is-selected {
  border-color: var(--wm-color-accent);
}

.wm-variation-swatch--color.is-selected,
.wm-variation-swatch--image.is-selected {
  box-shadow: 0 0 0 2px var(--wm-color-surface), 0 0 0 4px var(--wm-color-accent);
}

.wm-variation-swatch--label.is-selected {
  background: var(--wm-color-primary);
  border-color: var(--wm-color-primary);
}

.wm-variation-swatch--label.is-selected .wm-variation-swatch__label {
  color: var(--wm-color-surface);
}

.wm-variation-swatch:focus-visible {
  outline: 2px solid var(--wm-color-accent);
  outline-offset: 2px;
}

.wm-variation-swatch.is-unavailable {
  opacity: 0.45;
}

.wm-variation-swatch.is-unavailable::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(
    to bottom left,
    transparent calc(50% - 1px),
    var(--wm-color-danger-text) calc(50% - 1px),
    var(--wm-color-danger-text) calc(50% + 1px),
    transparent calc(50% + 1px)
  );
  pointer-events: none;
}

@media (max-width: 767px) {
  .wm-variation-swatch--color,
  .wm-variation-swatch--image {
    width: 38px;
    height: 38px;
  }

  .wm-variation-swatches {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: var(--wm-space-1);
    -webkit-overflow-scrolling: touch;
  }

  .wm-variation-swatch {
    flex-shrink: 0;
  }
}
```

- [ ] **Step 2: Verify visually**

Not yet enqueued — defer visual verification to Task 6 (after enqueueing). For now, confirm the file has no CSS syntax errors:

```bash
studio wp eval 'echo "syntax check happens at enqueue time — see Task 6";'
```

(This step exists to keep file-creation and wiring separate; real verification happens once the stylesheet is actually loaded in Task 6.)

- [ ] **Step 3: Commit**

Bump version to `0.4.71`.

```bash
git add assets/css/components/variation-swatches.css functions.php style.css
git commit -m "$(cat <<'EOF'
Add variation swatch stylesheet using existing design tokens

Color/image/label swatch styling, selected and out-of-stock states.
Not yet enqueued — wired up in the next commit.
EOF
)"
git push origin main
```

---

### Task 5: Swatch interaction JS (select-sync + stock-greying)

**Files:**
- Create: `assets/js/product-variations.js`

**Interfaces:**
- Consumes: markup/classes from Task 3 and Task 4
- Produces: nothing consumed by later code tasks; this is the final interactive layer

- [ ] **Step 1: Write the script**

```js
(function () {
  function dispatchChange(select) {
    var event;
    if (typeof Event === 'function') {
      event = new Event('change', { bubbles: true });
    } else {
      event = document.createEvent('Event');
      event.initEvent('change', true, true);
    }
    select.dispatchEvent(event);
  }

  function getVariations(form) {
    var raw = form.getAttribute('data-product_variations');
    if (!raw) {
      return [];
    }
    try {
      return JSON.parse(raw) || [];
    } catch (e) {
      return [];
    }
  }

  function getCurrentSelections(form) {
    var selections = {};
    form.querySelectorAll('.wm-variation-select-native select').forEach(function (select) {
      if (select.value) {
        selections[select.getAttribute('data-attribute_name')] = select.value;
      }
    });
    return selections;
  }

  function isValueAvailable(variations, attrKey, value, currentSelections) {
    return variations.some(function (variation) {
      var attrs = variation.attributes || {};
      if (!attrs.hasOwnProperty(attrKey)) {
        return false;
      }
      var variationValue = attrs[attrKey];
      if (variationValue && variationValue !== value) {
        return false;
      }

      for (var key in currentSelections) {
        if (key === attrKey || !attrs.hasOwnProperty(key)) {
          continue;
        }
        var otherValue = attrs[key];
        if (otherValue && otherValue !== currentSelections[key]) {
          return false;
        }
      }

      return variation.is_in_stock !== false;
    });
  }

  function syncAttribute(wrapper, form, variations) {
    var select = wrapper.querySelector('.wm-variation-select-native select');
    var swatches = wrapper.querySelectorAll('.wm-variation-swatch');
    var selectedLabel = wrapper.querySelector('[data-role="wm-selected-name"]');
    var attrKey = wrapper.getAttribute('data-attribute_name');
    var currentSelections = getCurrentSelections(form);
    var selectedName = '';

    swatches.forEach(function (swatch) {
      var value = swatch.getAttribute('data-value');
      var selected = select.value === value;

      swatch.classList.toggle('is-selected', selected);
      swatch.setAttribute('aria-selected', selected ? 'true' : 'false');

      if (selected) {
        selectedName = swatch.getAttribute('data-name') || '';
      }

      var available = isValueAvailable(variations, attrKey, value, currentSelections);
      swatch.classList.toggle('is-unavailable', !available);
    });

    if (selectedLabel) {
      selectedLabel.textContent = selectedName;
    }
  }

  function syncAllAttributes(form) {
    var variations = getVariations(form);
    form.querySelectorAll('.wm-variation-attribute').forEach(function (wrapper) {
      syncAttribute(wrapper, form, variations);
    });
  }

  document.querySelectorAll('.variations_form').forEach(function (form) {
    var wrappers = form.querySelectorAll('.wm-variation-attribute');
    if (!wrappers.length) {
      return;
    }

    wrappers.forEach(function (wrapper) {
      var select = wrapper.querySelector('.wm-variation-select-native select');
      if (!select) {
        return;
      }

      wrapper.querySelectorAll('.wm-variation-swatch').forEach(function (swatch) {
        swatch.addEventListener('click', function () {
          select.value = swatch.getAttribute('data-value');
          dispatchChange(select);
        });

        swatch.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            swatch.click();
          }
        });
      });

      select.addEventListener('change', function () {
        syncAllAttributes(form);
      });
    });

    syncAllAttributes(form);

    jQueryReady(form);
  });

  // WooCommerce's variation events are fired via jQuery; re-sync swatches
  // whenever core resets or matches a variation so out-of-stock greying
  // stays accurate after every selection.
  function jQueryReady(form) {
    if (typeof window.jQuery === 'undefined') {
      return;
    }
    window.jQuery(form).on('reset_data woocommerce_update_variation_values found_variation', function () {
      syncAllAttributes(form);
    });
  }
})();
```

- [ ] **Step 2: Verify in the browser**

Use the `verify` skill: open the variable product page, then for each attribute:
1. Click a color/image/label swatch → confirm the matching swatch gets the selected ring/state, the live name line updates, and WooCommerce's price/stock/Add-to-Cart button update exactly as the old dropdown did.
2. Identify (or create via `studio wp eval`) a variation with `_stock_status` = `outofstock`; confirm its corresponding swatch shows the strike-through/opacity style but is still clickable, and clicking it shows WooCommerce's normal "out of stock" message with the Add to Cart button disabled (core behavior, untouched).
3. Tab through the swatches with keyboard only; confirm focus rings appear and Enter/Space selects.
4. Confirm clicking "Clear" (`.reset_variations`) resets all swatches to unselected and the selected-name lines clear.

- [ ] **Step 3: Commit**

Bump version to `0.4.72`.

```bash
git add assets/js/product-variations.js functions.php style.css
git commit -m "$(cat <<'EOF'
Add swatch interaction JS: select-sync and stock-greying

Swatch clicks set the hidden native select's value and dispatch a
change event, so WooCommerce core's own variation-matching JS keeps
doing all price/stock/cart logic. Also marks swatches whose value has
no in-stock match as unavailable without disabling them.
EOF
)"
git push origin main
```

---

### Task 6: Enqueue new swatch CSS/JS

**Files:**
- Modify: `functions.php:149-154` (the existing `is_product()` enqueue block)

**Interfaces:**
- Consumes: `assets/css/components/variation-swatches.css` (Task 4), `assets/js/product-variations.js` (Task 5)

- [ ] **Step 1: Add the enqueue calls**

In `functions.php`, inside the existing block:

```php
if ( function_exists( 'is_product' ) && is_product() ) {
    wp_enqueue_script( 'eshobe-ecommerce-product-gallery', wm_asset_uri( 'assets/js/product-gallery.js' ), array(), wm_asset_version( 'assets/js/product-gallery.js' ), true );
    wp_enqueue_script( 'eshobe-ecommerce-product-tabs', wm_asset_uri( 'assets/js/product-tabs.js' ), array(), wm_asset_version( 'assets/js/product-tabs.js' ), true );
    wp_enqueue_script( 'eshobe-ecommerce-related-products', wm_asset_uri( 'assets/js/related-products.js' ), array(), wm_asset_version( 'assets/js/related-products.js' ), true );
    wp_enqueue_script( 'eshobe-ecommerce-product-mobile', wm_asset_uri( 'assets/js/product-mobile.js' ), array(), wm_asset_version( 'assets/js/product-mobile.js' ), true );
    wp_enqueue_style( 'eshobe-ecommerce-variation-swatches', wm_asset_uri( 'assets/css/components/variation-swatches.css' ), array( 'eshobe-ecommerce-product-components' ), wm_asset_version( 'assets/css/components/variation-swatches.css' ) );
    wp_enqueue_script( 'eshobe-ecommerce-product-variations', wm_asset_uri( 'assets/js/product-variations.js' ), array( 'jquery', 'wc-add-to-cart-variation' ), wm_asset_version( 'assets/js/product-variations.js' ), true );
}
```

(Adding `'eshobe-ecommerce-product-components'` and `'wc-add-to-cart-variation'` as dependencies ensures our CSS loads after the base product styles it overrides, and our JS loads after WooCommerce's own variation script is registered.)

- [ ] **Step 2: Verify**

```bash
studio site start --skip-browser
```

Use the `verify` skill: open a variable product page, confirm via Network tab/page source that both `variation-swatches.css` and `product-variations.js` load with a numeric cache-busting query string (from `filemtime()`), and that the swatch UI now renders styled and interactive (combining Tasks 3–6 end to end). Re-run all checks from Task 5 Step 2 now that the files are actually live.

- [ ] **Step 3: Commit**

Bump version to `0.4.73`.

```bash
git add functions.php style.css
git commit -m "$(cat <<'EOF'
Enqueue variation swatch CSS/JS on single-product pages
EOF
)"
git push origin main
```

---

### Task 7: Mobile sticky bar — flush positioning + handle tap target

**Files:**
- Modify: `assets/css/product-components.css:1096-1097` (body padding), `:1414-1428` (bar position), `:1430-1441` (handle)

**Interfaces:**
- Consumes: nothing new
- Produces: nothing consumed by later tasks (pure CSS fix)

- [ ] **Step 1: Fix the bar's fixed positioning to sit flush**

Replace (around line 1414):

```css
  .wm-mobile-bottom-bar {
    position: fixed;
    right: 10px;
    bottom: 10px;
    left: 10px;
    z-index: 1000;
    display: block;
    padding: 7px 12px calc(12px + env(safe-area-inset-bottom, 0px));
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(229, 224, 216, 0.95);
    border-radius: 24px;
    box-shadow: 0 -10px 34px rgba(17, 24, 39, 0.13);
    backdrop-filter: blur(12px);
    direction: rtl;
  }
```

with:

```css
  .wm-mobile-bottom-bar {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1000;
    display: block;
    padding: 7px 16px calc(12px + env(safe-area-inset-bottom, 0px));
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(229, 224, 216, 0.95);
    border-bottom: 0;
    border-radius: var(--wm-radius-lg) var(--wm-radius-lg) 0 0;
    box-shadow: 0 -10px 34px rgba(17, 24, 39, 0.13);
    backdrop-filter: blur(12px);
    direction: rtl;
  }
```

- [ ] **Step 2: Enlarge the handle's tap target**

Replace (around line 1430):

```css
  .wm-mobile-bottom-bar__handle {
    width: 48px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 3px;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--wm-color-muted);
  }
```

with:

```css
  .wm-mobile-bottom-bar__handle {
    width: 100%;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--wm-color-muted);
  }
```

(The visual chevron inside, `.wm-mobile-bottom-bar__chevron`, keeps its existing 10x10px size — only the button's own clickable box grows.)

- [ ] **Step 3: Recalculate body bottom padding to match the now-flush bar**

The bar no longer has a 10px bottom margin, so the page's reserved space can shrink slightly. Around line 1096:

```css
  body.single-product {
    padding-bottom: calc(126px + env(safe-area-inset-bottom, 0px));
  }
```

becomes:

```css
  body.single-product {
    padding-bottom: calc(116px + env(safe-area-inset-bottom, 0px));
  }
```

- [ ] **Step 4: Verify**

Use the `verify` skill with a mobile viewport (e.g. 390×844, iPhone 12 size) via Chrome DevTools MCP `resize_page` + `take_screenshot`:
1. Confirm the bar's bottom/left/right edges touch the viewport edges exactly (no visible gap or rounded corners at the bottom).
2. Tap the handle in 10 different spots across its full width — confirm every tap toggles expand/collapse on the first try (no missed taps).
3. Confirm page content isn't clipped behind the bar (scroll to the bottom of the product tabs section and check nothing is hidden under the bar).
4. Repeat on at least 2 different mobile viewport widths (e.g. 360px and 414px) to confirm it holds across screen sizes.

- [ ] **Step 5: Commit**

Bump version to `0.4.74`.

```bash
git add assets/css/product-components.css functions.php style.css
git commit -m "$(cat <<'EOF'
Fix mobile sticky bar floating gap and tiny handle tap target

Bar now sits flush against the viewport edge instead of floating with
a 10px margin, and the expand handle's hit area grows to a full-width
44px strip (was 48x20px) so it registers on the first tap.
EOF
)"
git push origin main
```

---

### Task 8: Mobile bar defaults to expanded for variable products

**Files:**
- Modify: `inc/product-components.php:451-503` (`wm_render_mobile_product_bottom_bar()`)

**Interfaces:**
- Consumes: `$product->is_type( 'variable' )` (WooCommerce core API)
- Produces: nothing consumed by later tasks

- [ ] **Step 1: Make the initial state conditional on product type**

In `wm_render_mobile_product_bottom_bar()` (`inc/product-components.php:451`), replace:

```php
    $stock      = wm_get_product_stock_data( $product );
    $meta_items = wm_get_product_purchase_meta_items( $product );

    ob_start();
    ?>
    <section class="wm-mobile-bottom-bar wm-mobile-bottom-bar--collapsed" aria-label="<?php esc_attr_e( 'Mobile purchase bar', 'eshobe-ecommerce' ); ?>">
        <button class="wm-mobile-bottom-bar__handle" type="button" aria-expanded="false" aria-controls="wm-mobile-bottom-bar-content">
```

with:

```php
    $stock           = wm_get_product_stock_data( $product );
    $meta_items      = wm_get_product_purchase_meta_items( $product );
    $starts_expanded = $product->is_type( 'variable' );
    $state_class     = $starts_expanded ? 'wm-mobile-bottom-bar--expanded' : 'wm-mobile-bottom-bar--collapsed';

    ob_start();
    ?>
    <section class="wm-mobile-bottom-bar <?php echo esc_attr( $state_class ); ?>" aria-label="<?php esc_attr_e( 'Mobile purchase bar', 'eshobe-ecommerce' ); ?>">
        <button class="wm-mobile-bottom-bar__handle" type="button" aria-expanded="<?php echo $starts_expanded ? 'true' : 'false'; ?>" aria-controls="wm-mobile-bottom-bar-content">
```

And further down, replace:

```php
        <div class="wm-mobile-bottom-bar__content" id="wm-mobile-bottom-bar-content" hidden>
```

with:

```php
        <div class="wm-mobile-bottom-bar__content" id="wm-mobile-bottom-bar-content" <?php echo $starts_expanded ? '' : 'hidden'; ?>>
```

- [ ] **Step 2: Update the JS that reads initial `aria-hidden` state to match**

In `assets/js/product-mobile.js:22-40`, the bar's `content.setAttribute('aria-hidden', 'true')` on line 31 unconditionally hides content on load regardless of server-rendered state. Replace lines 22–39 with:

```js
  document.querySelectorAll('.wm-mobile-bottom-bar').forEach(function(bar) {
    var handle = bar.querySelector('.wm-mobile-bottom-bar__handle');
    var content = bar.querySelector('.wm-mobile-bottom-bar__content');

    if (!handle || !content) {
      return;
    }

    var startsExpanded = bar.classList.contains('wm-mobile-bottom-bar--expanded');
    content.hidden = false;
    content.setAttribute('aria-hidden', startsExpanded ? 'false' : 'true');

    handle.addEventListener('click', function() {
      var expanded = bar.classList.contains('wm-mobile-bottom-bar--expanded');
      bar.classList.toggle('wm-mobile-bottom-bar--expanded', !expanded);
      bar.classList.toggle('wm-mobile-bottom-bar--collapsed', expanded);
      handle.setAttribute('aria-expanded', !expanded ? 'true' : 'false');
      content.setAttribute('aria-hidden', !expanded ? 'false' : 'true');
    });
  });
```

- [ ] **Step 3: Verify**

Use the `verify` skill on a mobile viewport:
1. Open the variable "annie Lip Pencil Crayon" product (or any variable product) — confirm the mobile bar's variation swatches are visible immediately on page load, no tap needed.
2. Open a simple (non-variable) product — confirm the mobile bar still starts collapsed, matching today's behavior.
3. Tap the handle on the variable product to collapse it, then expand again — confirm toggling still works both ways.

- [ ] **Step 4: Commit**

Bump version to `0.4.75`.

```bash
git add inc/product-components.php assets/js/product-mobile.js functions.php style.css
git commit -m "$(cat <<'EOF'
Default mobile bar to expanded for variable products

Shoppers can now see and pick a variation on the mobile sticky bar
immediately, instead of having to find and tap the expand handle
first. Simple products keep the collapsed default.
EOF
)"
git push origin main
```

---

### Task 9: Full end-to-end verification pass

**Files:** none (verification only)

**Interfaces:** none

- [ ] **Step 1: Desktop pass**

Use the `verify` skill at a desktop viewport (≥1024px):
- Open a variable product with color swatches: confirm circular swatches render with the right colors, selecting one updates price/stock/Add to Cart and shows the live selected-name line.
- If a product with an image-type or label-type attribute exists, repeat for those (check via `studio wp eval` from Task 1 Step 3 which taxonomies are `image`/`label` type, or configure a test term temporarily in wp-admin → Products → Attributes if none exist).
- Confirm a known out-of-stock variation's swatch shows the strike-through and is still clickable.

- [ ] **Step 2: Mobile pass**

At 360px, 390px, and 414px viewport widths:
- Confirm the sticky bar sits flush with no gap.
- Confirm the handle is reliably tappable on the first try across all three widths.
- Confirm variable products open with the bar expanded by default; simple products open collapsed.
- Confirm the swatch row scrolls horizontally without clipping if there are more swatches than fit on screen.

- [ ] **Step 3: Confirm YITH admin is untouched**

```bash
studio wp eval 'echo class_exists( "YITH_WCCL_Admin" ) ? "YITH admin class loaded\n" : "MISSING\n";'
```

Then manually open wp-admin → Products → Attributes → (a color/image/label attribute) → Edit terms, and confirm the YITH swatch-configuration fields (color picker, image upload, label text, tooltip) still appear and save correctly — proving our read-only consumption didn't interfere with YITH's own admin UI.

- [ ] **Step 4: Confirm no PHP notices/warnings were introduced**

```bash
studio site set --debug-log --debug-display
```

Reload the variable product page once, then:

```bash
tail -n 50 wp-content/debug.log
```

Expected: no new `PHP Notice`/`PHP Warning`/`PHP Deprecated` lines referencing `yith-swatches.php` or `variable.php` from this session. If `WP_DEBUG_LOG` wasn't already on before this task, turn it back off afterward to match the site's prior state:

```bash
studio site set --debug-log=false --debug-display=false
```

(Skip this last command if debug logging was already enabled before you started.)

- [ ] **Step 5: Final commit**

If Steps 1–4 surfaced no fixes needed, there's nothing new to commit — this task is verification-only. If any issue was found and fixed during this pass, repeat the relevant earlier task's commit pattern (bump version, commit, push) for that fix.
