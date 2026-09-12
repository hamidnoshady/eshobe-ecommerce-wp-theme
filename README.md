# Eshobe Ecommerce WP Theme

A lightweight Underscores-inspired WordPress/WooCommerce starter theme prepared for Gutenberg and Persian RTL stores.

## Font placement
Put Peyda font files here:

`wp-content/themes/eshobe-ecommerce-wp-theme/assets/fonts/peyda/`

Expected files:

- PeydaWeb-Regular.woff2
- PeydaWeb-Medium.woff2
- PeydaWeb-SemiBold.woff2
- PeydaWeb-Bold.woff2
- PeydaWeb-ExtraBold.woff2

## Company pages (About Us / Contact Us)

The theme ships a Gutenberg block family for building "درباره ما" and
"تماس با ما" pages entirely inside the block editor, fully styled by the
theme design system (tokens, gold-underline section headers, soft-shadow
cards, decorative motifs, RTL, Vazirmatn):

| Block | Purpose |
| --- | --- |
| `wm/page-hero` | Page header with eyebrow badge, H1, subtitle, optional image + breadcrumb |
| `wm/about-story` | Two-column brand story (text + image, side switchable, badge overlay) |
| `wm/stats-row` + `wm/stat-item` | Key numbers with animated counters (Persian-locale formatting) |
| `wm/feature-cards` + `wm/feature-card` | Value/benefit cards with a built-in SVG icon set (2–4 columns) |
| `wm/team-grid` + `wm/team-member` | Team members with photo, role, bio and social links |
| `wm/contact-cards` + `wm/contact-card` | Contact channel cards (`tel:` / `mailto:` links supported) |
| `wm/contact-form` | Secure AJAX contact form (nonce + honeypot + per-IP rate limit); messages are emailed **and** archived under «پیام‌های تماس» in wp-admin; no-JS fallback via admin-post.php |
| `wm/map-embed` | Google/Neshan/Balad map iframe in a themed frame (https-only, sandboxed) |
| `wm/faq` + `wm/faq-item` | Accessible `<details>` accordion FAQ |
| `wm/cta-banner` | Dark closing CTA banner reusing the `.wm-home-button` design-system buttons |

Quick start:

1. Create a page and assign the **«صفحه شرکتی (درباره ما / تماس با ما)»**
   template (or just use the default template — assets load automatically on
   any page containing these blocks).
2. In the editor, insert the ready-made pattern **«صفحه درباره ما (کامل)»**
   or **«صفحه تماس با ما (کامل)»** from the «قالب» pattern category, then
   edit each section via its block sidebar controls.

All blocks are dynamic (server-rendered via `blocks/*/render.php`) with
ServerSideRender previews in the editor, matching the architecture of the
existing homepage blocks. Front-end CSS/JS
(`assets/css/pages/company.css`, `assets/js/company.js`) only enqueue on
pages that actually contain a company block.

## Product shortcodes

- `[product_intro_block]`
- `[product_specs_block]`
- `[product_purchase_block]`

The theme also includes a WooCommerce `single-product.php` template using these components directly.
