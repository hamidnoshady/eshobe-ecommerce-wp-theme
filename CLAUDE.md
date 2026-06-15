# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This is a WordPress theme (`watchmid-underscores-theme`), an Underscores-inspired starter theme for a WooCommerce store ("WatchMid"), built for Persian/RTL with Vazirmatn (and optional Peyda) fonts. There is no build step, package manager, or test suite — it's plain PHP/CSS/JS loaded directly by WordPress.

The theme runs inside a Local (by Flywheel) WordPress install at:
`app/public/wp-content/themes/watchmid-underscores-theme/`

Local WP manages the WordPress site itself (PHP, MySQL, web server). VS Code is opened directly on this theme directory, so the working directory here is just the theme — the rest of the WordPress install (core, other plugins, wp-config) lives outside this folder under `app/public/`. Start/stop the site and access its DB/PHP via the Local app, not from this directory.

## Development workflow

- No build/lint/test commands exist. Edit PHP/CSS/JS directly and reload the site (Local dev environment) to see changes.
- Bump `WATCHMID_VERSION` in `functions.php` (and the `Version:` header in `style.css`) when making notable changes — this constant is used as the cache-busting version for all enqueued assets.
- CSS/JS for cart and checkout pages use `filemtime()` for versioning instead of `WATCHMID_VERSION`, so those auto-bust on save.
- For UI changes, use the `run` or `verify` skills to launch/check the site in a browser (Playwright/Chrome DevTools MCP available).

## Architecture

### Bootstrapping (`functions.php`)

`functions.php` is the entry point: theme setup, nav menus, sidebar registration, and conditional asset enqueueing (e.g., home page, single product, cart, checkout, product archive each load their own CSS/JS only when relevant). It then `require`s all the modules under `inc/`.

### `inc/` module map

- `template-functions.php`, `template-tags.php` — general template helpers (Underscores defaults).
- `helpers/home-data.php` — `wm_home_*` helpers for reading ACF "option" fields (front page content blocks), image URL/HTML resolution, and feature toggles.
- `acf/design-tokens.php` — central design token system. `wm_design_token_defaults()` defines all theme design variables (colors, fonts, spacing, decorative motifs); `wm_get_design_token()` reads from ACF options with fallback to defaults; tokens are emitted as inline CSS (see `wm_get_design_tokens_css()` referenced in `functions.php`).
- `acf/home-fields.php` — registers ACF field groups for the front page/options.
- `customizer/design-settings.php` — WordPress Customizer controls (Persian-labeled) for font family, content width, etc. This is a secondary/legacy settings layer alongside the ACF design tokens.
- `components/site-header.php`, `components/mega-menu.php`, `components/mobile-nav.php`, `components/site-footer.php` — header/footer/navigation rendering.
- `components/product-card.php`, `components/product-carousel.php` — reusable product display components used on home page and archives.
- `components/product-archive.php` — shop/category archive configuration. `wm_product_archive_defaults()` defines all archive behavior (columns, filter sidebar, AJAX add-to-cart, pagination, etc.) via `wm_product_archive_get_option()`, mirroring the design-tokens pattern (ACF option with default fallback).
- `product-components.php` — single-product shortcodes/components: `[product_intro_block]`, `[product_specs_block]`, `[product_purchase_block]`. Includes helpers like `wm_get_current_product()`, `wm_get_product_brand_terms()`, `wm_get_guarantee()` (checks multiple possible meta keys including Persian "گارانتی").
- `patterns/register-patterns.php` — registers block patterns from `patterns/`.
- `woocommerce.php` — WooCommerce-specific hooks/overrides.

**Common pattern**: settings/config modules define a `*_defaults()` array and a `*_get_option()`/`wm_get_design_token()` accessor that checks ACF (`get_field`) first, falling back to the default. Follow this pattern when adding new configurable theme options.

### Templates

- `front-page.php` assembles the home page from `template-parts/home/*.php` (hero slider, bestsellers, brand categories, quick filters, popular styles, recommended products, trust section, filter boxes) — each gated by `wm_home_enabled()`.
- `woocommerce/` overrides core WooCommerce templates: `single-product.php` (uses the product shortcodes/components directly), `archive-product.php`, cart and checkout templates.
- `taxonomy-product_cat.php` / `taxonomy-product_brand.php` — product taxonomy archives.

### Assets

- `assets/css/` — `tokens.css` (design tokens), `theme.css` (base styles), `components/*.css` (per-component styles), `pages/home.css`.
- `assets/js/` — vanilla JS, one file per feature (navigation, header, mobile nav, product gallery/tabs, carousels, checkout, etc.), each conditionally enqueued in `functions.php` based on context (`is_front_page()`, `is_product()`, `is_cart()`, etc.).
- `assets/fonts/vazirmatn/` — bundled Vazirmatn font files. Peyda fonts are expected at `assets/fonts/peyda/` but not bundled (see README).

### RTL

`rtl.css` provides RTL-specific overrides; the theme is designed primarily for a Persian/RTL storefront (note Persian-language strings throughout `inc/customizer/` and meta key checks).
