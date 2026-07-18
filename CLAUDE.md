# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This is a WordPress theme (`eshobe-ecommerce-wp-theme`), an Underscores-inspired starter theme for a WooCommerce store ("Eshobe Ecommerce"), built for Persian/RTL with Vazirmatn (and optional Peyda) fonts. There is no JS/CSS build step or package manager for running the site — PHP/CSS/JS are loaded directly by WordPress. A PHPUnit test suite exists for pure-PHP logic (see below).

The theme runs inside a [WordPress Studio](https://developer.wordpress.com/studio/) site at:
`wp-content/themes/eshobe-ecommerce-wp-theme/`

WordPress Studio manages the site itself (PHP WASM, SQLite, local dev server). The working directory when editing the theme is inside the Studio site root — the rest of the WordPress install (core, mu-plugins, wp-config) lives above this folder. Start/stop the site and run WP-CLI via `studio site start/stop` and `studio wp …` — do **not** use a bare `wp` binary or Local WP. See `STUDIO.md` in the site root for full Studio workflow details.

## Development workflow

- Edit PHP/CSS/JS directly and reload the site (Studio dev environment) to see changes — no compile step is required to run the theme.
- **After every change**, bump `ESHOBE_ECOMMERCE_VERSION` in `functions.php` AND `Version:` in `style.css` together (patch increment). This is required, not optional.
- Version format: `MAJOR.MINOR.PATCH` (e.g. `0.5.8` → `0.5.9`). Bump patch for any fix/tweak, minor for new features.
- **Every change goes through a branch + pull request — never push directly to `main`.**
  ```
  git checkout -b fix/short-description
  git commit -am "..."
  git push -u origin fix/short-description
  gh pr create --fill
  ```
- CSS/JS for cart and checkout pages use `filemtime()` for versioning instead of `ESHOBE_ECOMMERCE_VERSION`, so those auto-bust on save.
- For UI changes, use the `run` or `verify` skills to launch/check the site in a browser (Playwright/Chrome DevTools MCP available).
- **Remote/cloud sessions (no Studio install available)**: when there's no running WordPress/WooCommerce/ACF stack to hit, verify pure CSS/JS UI changes (header, modals, animations, etc.) with a static Playwright harness instead of skipping verification:
  - Build a minimal standalone HTML file under `/tmp` that includes the real markup for the changed component plus the actual theme stylesheets/scripts via `file://` links to `assets/css/...` and `assets/js/...` (copy the relevant markup straight from the PHP template/component).
  - For features that call `admin-ajax.php` (e.g. OTP login, header search), stub `window.fetch` and any localized globals (`wmOtpData`, `wmSearchData`, etc.) in an inline `<script>` so the JS runs end-to-end without a backend.
  - Playwright's bundled browser download usually fails (no network); launch Chromium directly from the pre-installed binary instead: `chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' })` (check `/opt/pw-browsers` for the actual revision present).
  - Screenshot at both a desktop viewport (e.g. 1280px) and a mobile viewport (e.g. 390px) and drive interactions (clicks, form fills) to confirm animations/state transitions, not just static layout.

### PHP tests

A PHPUnit suite (with Brain Monkey for mocking WP functions) covers standalone PHP logic — currently AJAX search, product-components helpers, and the theme updater. It does **not** boot WordPress; tests stub only the specific WP functions each unit under test calls.

```
composer install          # first time only, installs vendor/ (gitignored)
vendor/bin/phpunit                                  # run the full suite
vendor/bin/phpunit --filter ThemeUpdaterTest         # a single test class
vendor/bin/phpunit --filter test_wm_get_guarantee    # a single test method
```

When adding logic to a new `inc/` module that's easy to unit test in isolation (no full WP boot required), add a matching test under `tests/` following the existing files' Brain Monkey pattern (`Functions\expect(...)->andReturn(...)`) rather than skipping coverage.

### Asset minification (optional, production only)

The site works with plain source CSS/JS at all times — `wm_asset_uri()`/`wm_asset_version()` in `functions.php` transparently serve the unminified file whenever `WP_DEBUG` is true or no `.min` counterpart exists, so minification is never required for local dev. `bin/build-assets.ps1` (PowerShell, requires Node.js) regenerates `.min.css`/`.min.js` for the asset list it hardcodes, via `npx clean-css-cli` / `npx terser`. Run it after editing a listed asset if you want the minified copy refreshed for production; if you add a new enqueued CSS/JS file that should ship minified, add it to the `$cssFiles`/`$jsFiles` arrays in that script too.

## Architecture

### Bootstrapping (`functions.php`)

`functions.php` is the entry point: theme setup, nav menus, sidebar registration, the `wm_asset_uri()`/`wm_asset_version()` asset-resolution helpers (min vs. source), and conditional asset enqueueing (home page, single product, cart, checkout, product archive, brand archive, logged-out OTP modal, etc. each load their own CSS/JS only when relevant). It then `require`s all the modules under `inc/`, in a specific load order (helpers → mega-menu ACF/CPT → components → product-components → woocommerce/yith → ajax → customizer/patterns/design-tokens/home-fields → blocks → compat/updater/seo) — respect this ordering when adding a new `require`, since later modules call functions defined by earlier ones.

### `inc/` module map

- `template-functions.php`, `template-tags.php` — general template helpers (Underscores defaults).
- `helpers/options.php` — `wm_get_option()`, the base ACF-options-with-fallback accessor other helper modules build on.
- `helpers/home-data.php` — `wm_home_*` helpers for reading ACF "option" fields (front page content blocks), image URL/HTML resolution, section ordering, and feature toggles.
- `helpers/marketing-data.php`, `helpers/technical-data.php` — same ACF-option-with-fallback pattern for marketing settings and "Technical Settings" (تنظیمات فنی) — e.g. theme update channel, OTP security settings.
- `acf/design-tokens.php` — central design token system. `wm_design_token_defaults()` defines all theme design variables (colors, fonts, spacing, decorative motifs); `wm_get_design_token()` reads from ACF options with fallback to defaults; tokens are emitted as inline CSS via `wm_get_design_tokens_css()`.
- `acf/home-fields.php` — registers ACF field groups for the front page/options.
- `acf/mega-menu-cpt.php`, `acf/mega-menu-fields.php`, `acf/mega-menu-nav-field.php`, `acf/mega-menu-migration.php` — the mega menu is a dedicated `wm_mega_menu` CPT (not just ACF fields on a nav item); a nav menu item links to a mega menu post via an ACF field, and `mega-menu-migration.php` handles migrating older data shapes.
- `customizer/design-settings.php` — WordPress Customizer controls (Persian-labeled) for font family, content width, etc. This is a secondary/legacy settings layer alongside the ACF design tokens.
- `components/site-header.php`, `components/mega-menu.php`, `components/mobile-nav.php`, `components/mini-cart.php`, `components/site-footer.php` — header/footer/navigation/cart-badge rendering.
- `components/product-card.php`, `components/product-carousel.php` — reusable product display components used on home page and archives.
- `components/product-archive.php` — shop/category archive configuration. `wm_product_archive_defaults()` defines all archive behavior (columns, filter sidebar, AJAX add-to-cart, pagination, etc.) via `wm_product_archive_get_option()`, mirroring the design-tokens pattern (ACF option with default fallback).
- `components/brand-archive.php` — brand taxonomy archive page config/rendering (paired with `page-templates/brand-archive.php`).
- `product-components.php` — single-product shortcodes/components: `[product_intro_block]`, `[product_specs_block]`, `[product_purchase_block]`. Includes helpers like `wm_get_current_product()`, `wm_get_product_brand_terms()`, `wm_get_guarantee()` (checks multiple possible meta keys including Persian "گارانتی").
- `woocommerce.php` — WooCommerce-specific hooks/overrides.
- `yith-swatches.php` — integration with the YITH WooCommerce Variation Swatches plugin.
- `ajax/search.php` — `wp_ajax(_nopriv)_wm_search_products`, the header live-search endpoint (deliberately tolerates a stale/missing nonce since the header can be served from full-page cache).
- `ajax/otp-auth.php` — phone-number OTP login/registration via Kavenegar SMS (`wm_otp_*` helpers), paired with `template-parts/auth/otp-modal.php` and enqueued only for logged-out visitors.
- `blocks/register-blocks.php`, `blocks/block-regions.php`, `blocks/block-regions-admin.php` — native Gutenberg blocks (see "Block-based homepage & regions" below).
- `compat/cache.php` — full-page cache (e.g. FlyingPress/LiteSpeed) compatibility: keeps cart badges accurate via WC cart fragments, and excludes cart/checkout/account/logged-in pages from caching.
- `patterns/register-patterns.php` — registers block patterns from `patterns/`.
- `theme-updater.php` — see "Theme auto-update" below.
- `seo.php` — SEO-related meta/output.

**Common pattern**: settings/config modules define a `*_defaults()` array and a `*_get_option()`/`wm_get_design_token()` accessor that checks ACF (`get_field`) first, falling back to the default. Follow this pattern when adding new configurable theme options.

### Block-based homepage & regions

`blocks/` holds native Gutenberg blocks (`wm/home-section`, `wm/product-carousel`, `wm/filter-section`, `wm/price-filter-card`), each a thin wrapper whose `render.php` calls the theme's *existing* PHP render functions (`wm_render_home_section()`, `wm_render_product_carousel()`, `wm_render_filter_card()`) — front-end markup/CSS/JS never changes just because this feature exists. `inc/blocks/block-regions.php` implements a parallel mechanism: block-editable "regions" (e.g. below the shop archive product grid, below single-product related products) backed by a hidden draft Page, rendered only if that page actually has block content — so an untouched region renders nothing and existing templates are unaffected. `inc/blocks/block-regions-admin.php` adds the admin screen (`مدیریت بلوک‌ها`) for opting into block-managed control of the homepage/regions. This is additive/optional infrastructure layered on top of the ACF-driven home page — it does not replace `template-parts/home/*.php`.

### Templates

- `front-page.php` assembles the home page from `template-parts/home/*.php` (hero slider, bestsellers, brand categories, quick filters, popular styles, recommended products, trust section, filter boxes) — each gated by `wm_home_enabled()`.
- `woocommerce/` overrides core WooCommerce templates: `single-product.php` (uses the product shortcodes/components directly), `archive-product.php`, cart (`cart/`), checkout (`checkout/`), my-account (`myaccount/`) templates.
- `taxonomy-product_cat.php` / `taxonomy-product_brand.php` — product taxonomy archives; `page-templates/brand-archive.php` is a selectable page template variant.

### Assets

- `assets/css/` — `tokens.css` (design tokens), `theme.css` (base styles), `components/*.css` (per-component styles), `pages/home.css`, `pages/brand-archive.css`.
- `assets/js/` — vanilla JS, one file per feature (navigation, header, header-search, mobile nav, mini-cart, product gallery/tabs/variations/wishlist, carousels, checkout, otp-auth, brand-archive, etc.), each conditionally enqueued in `functions.php` based on context (`is_front_page()`, `is_product()`, `is_cart()`, `wm_product_archive_is_context()`, etc.).
- `assets/fonts/vazirmatn/` — bundled Vazirmatn font files. Peyda fonts are expected at `assets/fonts/peyda/` but not bundled (see README).
- Any listed CSS/JS file can also have a hand- or script-generated `.min.` counterpart; see "Asset minification" above.

### RTL

`rtl.css` provides RTL-specific overrides; the theme is designed primarily for a Persian/RTL storefront (note Persian-language strings throughout `inc/customizer/`, `inc/helpers/technical-data.php`, ACF field labels, and meta key checks).

### Planning docs (`docs/superpowers/`)

Larger features get a written plan and (sometimes) a design spec under `docs/superpowers/plans/` and `docs/superpowers/specs/` before implementation, dated `YYYY-MM-DD-topic.md` (mega menu rebuild, AJAX search modal, variation swatches, block-theme work). `docs/Prompts/` holds standalone task prompts used for specific UX fix passes (cart, checkout, my-account, single-product). These are historical/reference material, not something every change needs to produce.

## Theme auto-update

- Source repo: `github.com/hamidnoshady/eshobe-ecommerce-wp-theme` (private).
- Dist repo: `github.com/hamidnoshady/eshobe-ecommerce-wp-theme-dist` (public, build artifacts only — never source code). WordPress reads only from here, unauthenticated.
- `inc/theme-updater.php` hooks into WordPress's native update system and reads `https://raw.githubusercontent.com/hamidnoshady/eshobe-ecommerce-wp-theme-dist/main/<channel>/update.json` (stable, cached 6h; beta, cached 1h). No auth, no rate-limit concerns (static file, not GitHub API).
- Channel is picked per-site on **Technical Settings ("تنظیمات فنی") → "به‌روزرسانی قالب"** in wp-admin (`wm_technical_theme_update_channel` ACF field, `stable`/`beta`), read via `wm_technical_theme_update_channel()` in `inc/helpers/technical-data.php`.
- To force WP to check for updates immediately: `studio wp eval "delete_transient('wm_theme_update_stable'); delete_transient('wm_theme_update_beta');"` then Dashboard → Updates → "Check Again".
- **GitHub Actions are not used in this repo — do not add, restore, or re-create any `.github/workflows/*` files.** The workflows that used to build and publish `stable`/`beta` releases to the dist repo on every push/merge were deliberately removed. `inc/theme-updater.php` and its comments still describe that old pipeline for context, but there is currently no automated process populating the dist repo — merging a PR to `main` does *not* by itself publish a new release. Publishing a release to the dist repo (zip + `update.json`) is a manual/out-of-band step; don't assume a merge alone ships it, and don't reintroduce CI/Actions to do it unless the user explicitly asks.
