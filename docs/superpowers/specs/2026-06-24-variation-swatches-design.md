# Variation Add-to-Cart UX Redesign — Design Spec

Date: 2026-06-24
Theme: `eshobe-ecommerce-wp-theme`

## Problem

The variable-product add-to-cart box has several UX problems:

1. Variation selection relies entirely on the YITH Color, Image & Label Variation Swatches plugin's own frontend JS hijacking the native `<select>`. The visual result is cramped: the selected variation's name overlaps/crowds the swatch row instead of having its own clear label.
2. Out-of-stock variations aren't visually distinguished — a shopper can't tell which swatch leads to an unavailable combination until after selecting it.
3. On mobile, the sticky add-to-cart bar:
   - Floats with a 10px margin on all sides instead of sitting flush against the screen edge (`right:10px; bottom:10px; left:10px` in `assets/css/product-components.css`).
   - Has a 48×20px expand handle — well under the ~44px minimum comfortable touch target, causing the reported "need 3-4 taps" issue.
   - Starts collapsed even for variable products, forcing the shopper to find and tap the tiny handle just to choose a variation before they can buy.

## Goals

- Theme renders its own variation swatch UI (color circle / image thumbnail / label pill), matching the site's existing design tokens (`assets/css/tokens.css`), instead of relying on YITH's frontend rendering.
- Continue reading swatch configuration (colors, images, labels, tooltips) from the YITH plugin's term meta, so merchants keep using YITH's existing wp-admin screens unchanged. If YITH is ever deactivated, swatches degrade gracefully to plain label pills — nothing fatals.
- Out-of-stock variation values are shown (not hidden) but visually marked unavailable; they remain clickable per WooCommerce core's existing behavior.
- Mobile sticky bar sits flush to the viewport edge, has a properly sized tap target for its expand handle, and defaults to expanded for variable products.
- All new UI (colors, radii, spacing, type) draws from the existing `--wm-*` design tokens — no new ad hoc colors/spacing introduced.

## Non-goals

- No changes to YITH's admin UI, term meta schema, or stored data — read-only consumption only.
- No change to WooCommerce's variation-matching, pricing, stock, or AJAX-add-to-cart logic — we only change what's rendered, not how a variation is found/validated.
- No redesign of the simple-product (non-variable) mobile bar behavior beyond the flush-positioning and handle tap-target fixes, which apply to both.

## Architecture

### Data flow (read-only from YITH)

Per attribute on the product:

```php
$attr_tax  = wc_get_attribute_taxonomy_by_name( $raw_attribute_name ); // e.g. 'color'
$attr_type = $attr_tax ? $attr_tax->attribute_type : '';               // 'colorpicker' | 'image' | 'label' | 'select'
```

Per term of that attribute (only if YITH is active — guard with `function_exists( 'ywccl_get_term_meta' )`):

```php
$value         = ywccl_get_term_meta( $term_id, '_yith_wccl_value', true, $taxonomy );        // hex (or 'hex1,hex2' for dual), or image id
$swatch_subtype = ywccl_get_term_meta( $term_id, '_yith_wccl_swatch_type', true, $taxonomy );  // single_color | dual_color | image_color
$tooltip       = ywccl_get_term_meta( $term_id, '_yith_wccl_tooltip', true, $taxonomy );
```

If `function_exists( 'ywccl_get_term_meta' )` is false, or `$attr_type` isn't one of YITH's custom types, fall back to a plain label-pill swatch using the term's name — the theme never assumes YITH is present.

These helpers live in a new file `inc/yith-swatches.php`:
- `wm_get_attribute_swatch_type( string $taxonomy ): string` — returns `colorpicker|image|label|none`
- `wm_get_term_swatch_data( WP_Term $term, string $taxonomy ): array` — returns `['type' => ..., 'value' => ..., 'subtype' => ..., 'tooltip' => ...]` with safe defaults

### Rendering: theme template override

New file `woocommerce/single-product/add-to-cart/variable.php`, copied from WooCommerce core 9.6.0 and modified:

- Keep the existing `<form class="variations_form cart" data-product_variations="...">` wrapper and all core hooks (`woocommerce_before_add_to_cart_form`, `woocommerce_before_variations_table`, `woocommerce_single_variation`, etc.) untouched, so any other plugin/filter relying on them still works.
- For each attribute, render:
  1. A visually-hidden-but-focusable native `<select>` exactly as `wc_dropdown_variation_attribute_options()` produces (still inside the form, still named/keyed the same way) — this is what WooCommerce core's `add-to-cart-variation.js` reads to find matching variations, compute price/stock/availability, and enable/disable the Add to Cart button. We never touch that JS.
  2. A custom swatch row built from `wm_get_term_swatch_data()` for each `$options` term, with an attribute-name header showing the live-selected value (e.g. `رنگ: قرمز گلبهی`).
- New `assets/js/product-variations.js`: on swatch click/keydown, sets the corresponding hidden `<select>`'s value and dispatches a native `change` event (`select.dispatchEvent(new Event('change', { bubbles: true }))`), so core JS does the rest. Also listens for WooCommerce's `found_variation`/`reset_data`/`woocommerce_update_variation_values` events to keep the swatch-row header and selected/disabled visual states in sync.
- Out-of-stock marking: on load and on every attribute change, for each term button, check whether any variation in `data-product_variations` containing that term value (matching whatever's currently selected for other attributes) has `is_in_stock !== false`; if none does, add a `is-unavailable` class (strike-through + reduced opacity) — never set `disabled`/`aria-disabled` so it stays selectable.

### YITH frontend script

Dequeue YITH's own frontend swatch script/style only on single-product pages (where our custom UI replaces it), leaving it fully active everywhere else (shop loop, admin):

```php
add_action( 'wp_enqueue_scripts', function () {
    if ( is_product() ) {
        wp_dequeue_script( 'yith_wccl_frontend' );
        wp_dequeue_style( 'yith_wccl_frontend' );
    }
}, 20 );
```

This avoids the double-rendering/DOM-hijack conflict while leaving YITH's admin term-meta screens (where merchants configure swatches) completely untouched.

### Mobile sticky bar

In `assets/css/product-components.css`, within the `@media (max-width: 767px)` block:

- `.wm-mobile-bottom-bar`: change `right/left: 10px; bottom: 10px` → `right: 0; left: 0; bottom: 0`; change `border-radius: 24px` → round only the top two corners (`border-radius: var(--wm-radius-lg) var(--wm-radius-lg) 0 0`), keep existing `box-shadow`/`backdrop-filter`.
- `.wm-mobile-bottom-bar__handle`: keep the visual chevron small, but give the button itself `min-height: 44px` and `width: 100%` (or at least 88px wide) so the actual tap target meets touch-target guidelines; chevron stays visually centered via flex.
- `body.single-product` bottom padding recalculated to match the new bar height now that there's no outer 10px gap.

In `inc/product-components.php`, `wm_render_mobile_product_bottom_bar()`: when `$product->is_type( 'variable' )`, render the bar with the `wm-mobile-bottom-bar--expanded` class (and `aria-expanded="true"` / `aria-hidden="false"` on its content) instead of `--collapsed`, so the swatch-selection UI is visible by default. Non-variable products keep today's collapsed default.

### Styling — design system compliance

All new CSS in `assets/css/components/variation-swatches.css` uses only existing tokens — no new hex colors or hardcoded spacing:

- Swatch size: 34px desktop / 38px mobile, gap `var(--wm-space-2)` (8px)
- Selected ring: `var(--wm-color-accent)` border, 2px
- Selected checkmark: `var(--wm-color-surface)` icon over `var(--wm-color-primary)` or accent fill
- Image-type swatch radius: `var(--wm-radius-sm)` (10px); color-type stays a circle (`border-radius: 50%`)
- Label-type pill: `var(--wm-radius-pill)`, `var(--wm-color-border)` border, `var(--wm-color-soft)` background, switches to `var(--wm-color-primary)` background / `var(--wm-color-surface)` text when selected
- Out-of-stock overlay: `var(--wm-color-danger-text)` strike line at ~45% opacity
- Attribute header text: existing `--wm-font-primary`, `--wm-color-text` for the label, `--wm-color-primary` + bold for the live value name
- Transitions use the existing `var(--wm-transition)`

## Files touched

| File | Change |
|---|---|
| `woocommerce/single-product/add-to-cart/variable.php` | New — theme override of WC core template |
| `inc/yith-swatches.php` | New — read-only YITH data helpers with non-YITH fallback |
| `assets/css/components/variation-swatches.css` | New — swatch UI styles, design-token based |
| `assets/js/product-variations.js` | New — swatch interaction, hidden-select sync, stock-greying |
| `inc/product-components.php` | Edit — `wm_render_mobile_product_bottom_bar()` default-expanded for variable products |
| `inc/woocommerce.php` | Edit — dequeue YITH frontend script/style on `is_product()` |
| `assets/css/product-components.css` | Edit — mobile bar flush positioning + handle tap-target fix |
| `functions.php` | Edit — enqueue new CSS/JS on `is_product()`; bump `ESHOBE_ECOMMERCE_VERSION` |
| `style.css` | Edit — bump `Version:` to match |

## Testing / verification plan

- Use the `run`/`verify` skill against the local Studio site.
- Test product: the "annie Lip Pencil Crayon" variable product already configured with YITH color swatches (from the screenshots).
- Verify: swatch rendering (color/image/label types if more than one attribute type exists among real products — check via `studio wp wc product_attribute list` or admin), selection updates price/stock/Add to Cart button (core JS untouched), out-of-stock value shows struck-through but is selectable, mobile bar flush to edge with no gap, handle tappable on first try, variable-product mobile bar opens expanded by default, simple-product mobile bar still collapsed by default.
- Confirm YITH's own admin attribute/term swatch-configuration screens still work normally (read-only consumption shouldn't be able to break this, but verify).
- Test both desktop (≥1024px) and mobile (≤767px) viewports.

## Risks

- WooCommerce may update `single-product/add-to-cart/variable.php` in future core releases; since we're overriding it, we won't get those changes automatically. Mitigated by keeping the override as close to core structure/hooks as possible and noting the WC version (9.6.0) it was copied from in a comment.
- If a product attribute uses a YITH swatch type we don't fully handle (e.g., dual_color), fall back to single-color rendering using the first hex value rather than failing.
