# Block Theme Rebuild — Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a new WordPress block theme (`eshobe-block-theme`) with design tokens, header/footer, product archive, and single product templates built from native blocks — leaving the current classic theme (`eshobe-ecommerce-wp-theme`) completely untouched and still active.

**Architecture:** Pure block theme (`theme.json` + `templates/` + `parts/` + `patterns/`, no PHP template files). A new companion plugin (`eshobe-theme-support`) holds the one piece of non-template logic Phase 1 needs (YITH swatch data hooks), so it works regardless of which theme is active.

**Tech Stack:** WordPress block theme APIs (`theme.json` v3, block templates/parts/patterns), core WooCommerce blocks (Product Collection, Filter by Price, Filter by Attribute, Active Filters, WooCommerce single-product blocks). No build step, no package manager — matches this project's existing convention of plain PHP/CSS/JSON loaded directly by WordPress.

## Global Constraints

- Never activate the new theme on the live site during Phase 1 — verify via WordPress's built-in **Live Preview** (Appearance → Themes → hover new theme → "Live Preview"), which renders block themes without switching the active theme.
- Never edit files under `wp-content/themes/eshobe-ecommerce-wp-theme/` (the current live theme) as part of this plan — it is a separate, already-working codebase.
- Design tokens (colors, type sizes, weights, line-height, site width, radius) must match the exact values in `wp-content/themes/eshobe-ecommerce-wp-theme/inc/acf/design-tokens.php::wm_design_token_defaults()` — copied verbatim below in Task 2.
- All `studio wp` commands (never bare `wp`) — see project `STUDIO.md`.
- Every WP-CLI/browser verification step must be actually run and its output checked before checking off a step — no assuming success.
- New theme directory: `wp-content/themes/eshobe-block-theme/`. New plugin directory: `wp-content/plugins/eshobe-theme-support/`.

---

### Task 1: Scaffold the new theme

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/style.css`
- Create: `wp-content/themes/eshobe-block-theme/functions.php`
- Create: `wp-content/themes/eshobe-block-theme/templates/index.html`
- Create: `wp-content/themes/eshobe-block-theme/theme.json` (minimal skeleton — full tokens land in Task 2)

**Interfaces:**
- Produces: theme slug `eshobe-block-theme`, recognized by WordPress, appears in `studio wp theme list`. Later tasks add `templates/`, `parts/`, `patterns/` files inside this same directory.

- [ ] **Step 1: Create `style.css` with the theme header block**

```css
/*
Theme Name: Eshobe Block Theme
Theme URI: https://eshobe.com
Author: Eshobe
Author URI: https://eshobe.com
Description: Block-theme rebuild of the Eshobe WooCommerce storefront. Phase 1: foundation + product catalog.
Version: 0.1.0
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: LICENSE
Text Domain: eshobe-block-theme
Tags: block-theme, custom-colors, custom-menu, ecommerce, rtl-language-support
*/
```

- [ ] **Step 2: Create minimal `functions.php`**

```php
<?php
/**
 * Eshobe Block Theme bootstrap.
 *
 * @package Eshobe_Block_Theme
 */

if ( ! defined( 'ESHOBE_BLOCK_THEME_VERSION' ) ) {
	define( 'ESHOBE_BLOCK_THEME_VERSION', '0.1.0' );
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);
```

- [ ] **Step 3: Create `theme.json` skeleton (v3, empty settings — populated in Task 2)**

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"settings": {},
	"styles": {}
}
```

- [ ] **Step 4: Create `templates/index.html` fallback template (required for a valid block theme)**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 5: Verify the theme is recognized**

Run: `studio wp theme list --format=csv`
Expected: output includes a row `eshobe-block-theme,...` (status will be `inactive` — that's correct, do not activate).

If the theme doesn't appear, run `studio wp theme list --debug 2>&1 | tail -30` and fix the reported error (usually a missing `style.css` header field) before continuing.

- [ ] **Step 6: Commit**

```bash
git checkout -b feature/block-theme-rebuild-phase1
git add wp-content/themes/eshobe-block-theme/
git commit -m "Scaffold eshobe-block-theme (empty block theme, not activated)"
```

Note: run this from the site root (`C:\Users\hamid\Studio\eshobe-ecommerce-theme`), not inside the `eshobe-ecommerce-wp-theme` git repo — the new theme lives outside that repo's working tree. If the site root has no `.git`, run `git init` there first (confirm with the user before doing so, since it changes repo structure for the whole site).

---

### Task 2: Design tokens in `theme.json`

**Files:**
- Modify: `wp-content/themes/eshobe-block-theme/theme.json`
- Create: `wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/Vazirmatn-Regular.woff2` (copied)
- Create: `wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/Vazirmatn-Medium.woff2` (copied)
- Create: `wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/Vazirmatn-SemiBold.woff2` (copied)
- Create: `wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/Vazirmatn-Bold.woff2` (copied)
- Create: `wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/Vazirmatn-ExtraBold.woff2` (copied)

**Interfaces:**
- Consumes: token values from `wp-content/themes/eshobe-ecommerce-wp-theme/inc/acf/design-tokens.php::wm_design_token_defaults()` (read-only reference, not modified).
- Produces: `theme.json` color palette slugs (`primary`, `secondary`, `text`, `accent`, `accent-dark`, `background`, `surface`, `border`, `muted`, `cta`), font size slugs (`small`, `base`, `h3`, `h2`, `h1`), font family slug `vazirmatn`, `settings.layout.contentSize` = `1200px`, `settings.custom.radius.lg` = `24px` — later tasks (templates, patterns) reference these exact slugs via `var:preset|color|<slug>` etc.

- [ ] **Step 1: Copy font files from the old theme**

```bash
mkdir -p wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn
cp wp-content/themes/eshobe-ecommerce-wp-theme/assets/fonts/vazirmatn/*.woff2 \
   wp-content/themes/eshobe-block-theme/assets/fonts/vazirmatn/
```

- [ ] **Step 2: Write the full `theme.json`**

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"settings": {
		"color": {
			"custom": false,
			"customGradient": false,
			"palette": [
				{ "slug": "primary", "name": "Primary", "color": "#111827" },
				{ "slug": "secondary", "name": "Secondary", "color": "#6B7280" },
				{ "slug": "text", "name": "Text", "color": "#1F2937" },
				{ "slug": "accent", "name": "Accent", "color": "#C89B3C" },
				{ "slug": "accent-dark", "name": "Accent Dark", "color": "#9F7425" },
				{ "slug": "background", "name": "Background", "color": "#F6F5F2" },
				{ "slug": "surface", "name": "Surface", "color": "#FFFFFF" },
				{ "slug": "border", "name": "Border", "color": "#E5E0D8" },
				{ "slug": "muted", "name": "Muted", "color": "#6B7280" },
				{ "slug": "cta", "name": "CTA", "color": "#111827" }
			]
		},
		"typography": {
			"customFontSize": false,
			"fontFamilies": [
				{
					"slug": "vazirmatn",
					"name": "Vazirmatn",
					"fontFamily": "'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
					"fontFace": [
						{ "fontFamily": "Vazirmatn", "fontWeight": "400", "fontStyle": "normal", "src": [ "file:./assets/fonts/vazirmatn/Vazirmatn-Regular.woff2" ] },
						{ "fontFamily": "Vazirmatn", "fontWeight": "500", "fontStyle": "normal", "src": [ "file:./assets/fonts/vazirmatn/Vazirmatn-Medium.woff2" ] },
						{ "fontFamily": "Vazirmatn", "fontWeight": "600", "fontStyle": "normal", "src": [ "file:./assets/fonts/vazirmatn/Vazirmatn-SemiBold.woff2" ] },
						{ "fontFamily": "Vazirmatn", "fontWeight": "700", "fontStyle": "normal", "src": [ "file:./assets/fonts/vazirmatn/Vazirmatn-Bold.woff2" ] },
						{ "fontFamily": "Vazirmatn", "fontWeight": "800", "fontStyle": "normal", "src": [ "file:./assets/fonts/vazirmatn/Vazirmatn-ExtraBold.woff2" ] }
					]
				}
			],
			"fontSizes": [
				{ "slug": "small", "name": "Small", "size": "13px" },
				{ "slug": "base", "name": "Base", "size": "15px" },
				{ "slug": "h3", "name": "H3", "size": "20px" },
				{ "slug": "h2", "name": "H2", "size": "26px" },
				{ "slug": "h1", "name": "H1", "size": "32px" }
			]
		},
		"layout": {
			"contentSize": "1200px",
			"wideSize": "1400px"
		},
		"custom": {
			"radius": { "lg": "24px" },
			"lineHeight": { "body": "1.9" }
		}
	},
	"styles": {
		"color": {
			"background": "var(--wp--preset--color--background)",
			"text": "var(--wp--preset--color--text)"
		},
		"typography": {
			"fontFamily": "var(--wp--preset--font-family--vazirmatn)",
			"fontSize": "var(--wp--preset--font-size--base)",
			"fontWeight": "400",
			"lineHeight": "var(--wp--custom--line-height--body)"
		},
		"elements": {
			"h1": { "typography": { "fontSize": "var(--wp--preset--font-size--h1)", "fontWeight": "800" } },
			"h2": { "typography": { "fontSize": "var(--wp--preset--font-size--h2)", "fontWeight": "800" } },
			"h3": { "typography": { "fontSize": "var(--wp--preset--font-size--h3)", "fontWeight": "800" } },
			"link": { "color": { "text": "var(--wp--preset--color--accent)" } }
		}
	}
}
```

- [ ] **Step 3: Verify the tokens load without errors**

Run: `studio wp eval "echo json_last_error() === JSON_ERROR_NONE ? 'ok' : 'bad json';" 2>&1` — actually validate the file directly since `wp eval` doesn't read theme.json on its own:

Run: `studio wp eval "\$path = get_theme_root() . '/eshobe-block-theme/theme.json'; \$data = json_decode( file_get_contents( \$path ) ); echo json_last_error() === JSON_ERROR_NONE ? 'valid json' : 'INVALID: ' . json_last_error_msg();"`
Expected: `valid json`

- [ ] **Step 4: Visually verify via Live Preview**

Open the site admin in the browser (`studio site status` for the URL), go to **Appearance → Themes**, hover "Eshobe Block Theme", click **Live Preview**, open **Styles → Typography → Text** and **Styles → Colors** in the Site Editor panel that opens. Confirm the accent gold (`#C89B3C`), background (`#F6F5F2`), and Vazirmatn font family are present as options. Close the preview without saving/activating.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/eshobe-block-theme/theme.json wp-content/themes/eshobe-block-theme/assets/
git commit -m "Add design tokens and Vazirmatn font faces to theme.json"
```

---

### Task 3: Header and footer template parts

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/parts/header.html`
- Create: `wp-content/themes/eshobe-block-theme/parts/footer.html`
- Modify: `wp-content/themes/eshobe-block-theme/theme.json` (register the two template parts)

**Interfaces:**
- Consumes: `settings.layout.contentSize` (`1200px`) from Task 2, color palette slugs `surface`/`border`/`text` from Task 2.
- Produces: template part slugs `header` and `footer`, already referenced by `templates/index.html` (Task 1) via `<!-- wp:template-part {"slug":"header"} -->` / `{"slug":"footer"}`. Later tasks' page templates (archive-product, single-product) also reference these two slugs.

Scope note: layout only — logo, a native Navigation block (empty menu for now), cart icon placeholder, and footer columns. No mega menu, no mobile off-canvas nav — those are out of scope for Phase 1 per the design spec.

- [ ] **Step 1: Register the template parts in `theme.json`**

Add to the existing `theme.json` (Task 2) under `settings`:

```json
		"templateParts": [
			{ "name": "header", "title": "Header", "area": "header" },
			{ "name": "footer", "title": "Footer", "area": "footer" }
		]
```

This is a top-level `templateParts` key inside `settings` — insert it as a sibling of `"color"`, `"typography"`, etc.

- [ ] **Step 2: Create `parts/header.html`**

```html
<!-- wp:group {"style":{"color":{"background":"var:preset|color|surface"},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:var(--wp--preset--color--surface);border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
	<div class="wp-block-group">
		<!-- wp:site-title /-->
		<!-- wp:navigation {"layout":{"type":"flex"}} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `parts/footer.html`**

```html
<!-- wp:group {"style":{"color":{"background":"var:preset|color|primary","text":"var:preset|color|surface"}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:var(--wp--preset--color--primary);color:var(--wp--preset--color--surface)">
	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center">&copy; Eshobe</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: Verify in Live Preview**

Appearance → Themes → Live Preview on "Eshobe Block Theme". Confirm the header shows the site title and an (empty) nav row with a surface-white background and bottom border, and the footer shows a dark band with centered white "© Eshobe" text.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/eshobe-block-theme/parts/ wp-content/themes/eshobe-block-theme/theme.json
git commit -m "Add header and footer template parts"
```

---

### Task 4: Extract YITH swatch hooks into `eshobe-theme-support` plugin

**Files:**
- Create: `wp-content/plugins/eshobe-theme-support/eshobe-theme-support.php`
- Create: `wp-content/plugins/eshobe-theme-support/inc/yith-swatches.php` (copied verbatim from the old theme)

**Interfaces:**
- Produces: functions `wm_translate_attribute_label( $label )`, `wm_get_attribute_swatch_type( $taxonomy )`, `wm_get_term_swatch_data( $term, $taxonomy )` — globally available once the plugin is active, independent of active theme. Task 6 (product-card pattern) calls `wm_get_term_swatch_data()` and `wm_get_attribute_swatch_type()`.

The old theme (`eshobe-ecommerce-wp-theme/inc/yith-swatches.php`) keeps its own copy untouched — do not modify or remove it, it still needs to work while that theme is active.

- [ ] **Step 1: Copy the file verbatim**

```bash
mkdir -p wp-content/plugins/eshobe-theme-support/inc
cp wp-content/themes/eshobe-ecommerce-wp-theme/inc/yith-swatches.php \
   wp-content/plugins/eshobe-theme-support/inc/yith-swatches.php
```

- [ ] **Step 2: Create the plugin bootstrap file**

```php
<?php
/**
 * Plugin Name: Eshobe Theme Support
 * Description: Non-template logic shared across Eshobe themes (swatch data, etc.), kept independent of which theme is active.
 * Version: 0.1.0
 * Author: Eshobe
 * Text Domain: eshobe-theme-support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/yith-swatches.php';
```

- [ ] **Step 3: Activate and verify**

Run: `studio wp plugin activate eshobe-theme-support`
Expected: `Plugin 'eshobe-theme-support' activated.`

Run: `studio wp eval "echo function_exists( 'wm_get_term_swatch_data' ) ? 'yes' : 'no';"`
Expected: `yes`

- [ ] **Step 4: Confirm no fatal from duplicate function names**

The old theme is still active and still loads its own copy of these functions from `inc/yith-swatches.php`. Since both are plain functions (not namespaced), loading both would fatal on redeclaration.

Run: `studio wp eval "echo 'ok';" 2>&1`
Expected: `ok` with no `Fatal error: Cannot redeclare` message. If a redeclaration fatal appears, the old theme's `inc/yith-swatches.php` must stop being loaded while `eshobe-theme-support` is active — resolve by wrapping the `require` in the old theme's `functions.php` with `if ( ! function_exists( 'wm_translate_attribute_label' ) )`, or by deactivating the plugin until Phase 1 cutover. Flag this to the user rather than silently picking one.

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/eshobe-theme-support/
git commit -m "Extract YITH swatch hooks into eshobe-theme-support plugin"
```

---

### Task 5: Product archive template (Product Collection + Filter blocks)

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/templates/archive-product.html`
- Modify: `wp-content/themes/eshobe-block-theme/theme.json` (register the template)

**Interfaces:**
- Consumes: `parts/header.html`, `parts/footer.html` (Task 3); `settings.layout.contentSize` (Task 2).
- Produces: template slug `archive-product`, used by WooCommerce for `/shop/` and category archives once the theme is active (not yet, per Global Constraints).

- [ ] **Step 1: Confirm WooCommerce Blocks version supports the Filter blocks**

Run: `studio wp plugin get woocommerce --field=version`
Expected: `9.0` or higher (Filter by Price/Attribute/Active Filters blocks require WooCommerce 8.3+; if lower, stop and flag to the user before continuing — Phase 1's filter-block approach depends on this).

- [ ] **Step 2: Create `templates/archive-product.html`**

No `theme.json` edit needed for this step — WordPress auto-discovers any `.html` file under `templates/` by its filename, unlike template parts (which need the `templateParts` registration from Task 3).

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained","contentSize":"1200px"}} -->
<main class="wp-block-group">
	<!-- wp:query-title {"type":"archive"} /-->

	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter"} -->
			<div class="wp-block-woocommerce-filter-wrapper">
				<!-- wp:woocommerce/product-filter-price /-->
			</div>
			<!-- /wp:woocommerce/filter-wrapper -->

			<!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter"} -->
			<div class="wp-block-woocommerce-filter-wrapper">
				<!-- wp:woocommerce/product-filter-attribute /-->
			</div>
			<!-- /wp:woocommerce/filter-wrapper -->

			<!-- wp:woocommerce/product-filter-active {"lock":{"remove":false}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"75%"} -->
		<div class="wp-block-column" style="flex-basis:75%">
			<!-- wp:woocommerce/product-collection {"query":{"inherit":true,"perPage":12},"displayLayout":{"type":"flex","columns":3}} -->
			<div class="wp-block-woocommerce-product-collection">
				<!-- wp:pattern {"slug":"eshobe-block-theme/product-card"} /-->
			</div>
			<!-- /wp:woocommerce/product-collection -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

Note: the `wp:pattern` reference to `eshobe-block-theme/product-card` is created in Task 6 — this template will show a WordPress "pattern not found" notice in Live Preview until Task 6 lands. That's expected; don't treat it as a Task 5 failure.

- [ ] **Step 3: Verify the file exists and is well-formed**

`get_block_templates()` only reads from the *active* theme, and this theme stays inactive per the Global Constraints — so file presence is the right check here, not a WP-CLI template query. Full rendering is verified visually in Task 8's Live Preview pass.

Run: `test -f wp-content/themes/eshobe-block-theme/templates/archive-product.html && echo exists`
Expected: `exists`

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/eshobe-block-theme/templates/archive-product.html
git commit -m "Add product archive template with native Filter blocks"
```

---

### Task 6: Product card pattern (badges + swatches)

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/patterns/product-card.php`

**Interfaces:**
- Consumes: `wm_get_attribute_swatch_type()`, `wm_get_term_swatch_data()` from `eshobe-theme-support` plugin (Task 4); color palette slugs `accent`, `surface`, `border` (Task 2).
- Produces: registered pattern slug `eshobe-block-theme/product-card`, referenced by `templates/archive-product.html` (Task 5).

- [ ] **Step 1: Create the pattern file**

```php
<?php
/**
 * Title: Product Card
 * Slug: eshobe-block-theme/product-card
 * Categories: woocommerce
 * Block Types: woocommerce/product-collection
 */
?>
<!-- wp:group {"style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"var:custom|radius|lg"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:var(--wp--custom--radius--lg)">
	<!-- wp:woocommerce/product-sale-badge /-->
	<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail"} /-->
	<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"}}} /-->
	<!-- wp:woocommerce/product-price /-->
	<!-- wp:woocommerce/product-button /-->
</div>
<!-- /wp:group -->
```

This covers the sale badge and price/button via core WooCommerce blocks. Variation swatches on archive cards are a display enhancement on top of this — Phase 1 ships the card without inline swatches (they require a custom block since no core block renders them), and that gap is called out in Task 7's verification as a known scope reduction versus the old theme, for the user to confirm is acceptable or to schedule as a small follow-up custom block.

- [ ] **Step 2: Verify the pattern file has no PHP syntax errors**

Run: `studio wp eval-file wp-content/themes/eshobe-block-theme/patterns/product-card.php`
Expected: no fatal error output (the file will just echo its HTML comment block — that's fine, this step is only checking for a parse error).

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/eshobe-block-theme/patterns/product-card.php
git commit -m "Add product-card pattern (sale badge, image, title, price, add-to-cart)"
```

---

### Task 7: Single product template

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/templates/single-product.html`

**Interfaces:**
- Consumes: `parts/header.html`, `parts/footer.html` (Task 3).
- Produces: template slug `single-product`.

- [ ] **Step 1: Create `templates/single-product.html`**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained","contentSize":"1200px"}} -->
<main class="wp-block-group">
	<!-- wp:woocommerce/product-image-gallery /-->

	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:post-title {"level":1} /-->
		<!-- wp:woocommerce/product-price /-->
		<!-- wp:woocommerce/product-rating /-->
		<!-- wp:woocommerce/add-to-cart-form /-->
		<!-- wp:woocommerce/product-meta /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-details /-->
	<!-- wp:woocommerce/related-products /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 2: Verify file exists and is well-formed**

Run: `test -f wp-content/themes/eshobe-block-theme/templates/single-product.html && echo exists`
Expected: `exists`

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/eshobe-block-theme/templates/single-product.html
git commit -m "Add single product template with core WooCommerce product blocks"
```

---

### Task 8: End-to-end Live Preview verification

**Files:** none created/modified — verification only.

- [ ] **Step 1: Confirm test products exist**

Run: `studio wp post list --post_type=product --posts_per_page=3 --field=ID`
Expected: at least one product ID printed. If none, run `studio wp wc product create --user=admin --name="Test Product" --regular_price=100 --sku=TEST-001` (or note to the user that sample product data needs importing) before continuing — the remaining checks need real product data.

- [ ] **Step 2: Live Preview the shop/archive page**

Appearance → Themes → Live Preview on "Eshobe Block Theme". Navigate to the shop page. Confirm:
- Header renders (site title, empty nav bar)
- Product cards render in a 3-column grid with image, title, price, "Add to cart" button
- Price filter and attribute filter blocks render in the left sidebar (may show "no filter data" if the store has no variable products with attributes — that's expected, not a bug)
- Footer renders

- [ ] **Step 3: Live Preview a single product page**

From the shop grid preview, click into one product. Confirm:
- Gallery, title, price, rating, add-to-cart form, and related products render without PHP errors on the page

- [ ] **Step 4: Check for PHP errors during preview**

Run: `studio config set --debug-log --debug-display` (if not already enabled), reload both preview pages from Steps 2–3, then:

Run: `tail -40 wp-content/debug.log`
Expected: no new fatal/warning entries timestamped during the preview session. If there are, fix them before considering Phase 1 done — do not defer known errors to a later phase.

- [ ] **Step 5: Record known gaps**

Update the Phase 1 design spec (`docs/superpowers/specs/2026-07-11-block-theme-rebuild-phase1-design.md`) with a short "Phase 1 outcome" section listing: inline variation swatches on archive cards deferred (Task 6 note), and anything else discovered during verification that diverges from the spec.

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "Phase 1 verification complete: record known gaps in design spec"
```

Do not open a PR or merge to `main` yet — confirm with the user first, since the old theme repo's `CLAUDE.md` workflow (branch → PR → auto-deploy) applies once this work moves into that repo's actual remote, and the new theme currently has no remote/CI configured (out of scope for Phase 1).
