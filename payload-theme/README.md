# Eshobe — Payload CMS Storefront Theme

A **Persian-first (RTL) ecommerce storefront** that reads everything from a
[Payload CMS](https://payloadcms.com) backend. It is a drop-in re-skin of the
Eshobe WooCommerce theme: it ships **the exact same design-system CSS**
(copied from the original theme) and renders identical markup, so nobody can
tell the storefront no longer runs on WordPress/WooCommerce.

This folder is self-contained. Point it at any Payload deployment that
implements the contract in [`docs/THEME_API.md`](../docs/THEME_API.md).

---

## The one-line summary

The theme does exactly four things, driven entirely by the API:

1. **`GET /api/site`** (with `Host: acme.ir`) → `availableLocales`, `blocks`,
   `store.currency`, `theme`.
2. Every price goes through **`formatPrice(product.price, site.store.currency, locale)`** — it **never converts** the amount.
3. A **`productGrid` block** fetches **`GET /api/products`** and renders the
   storefront grid.
4. **Buy** posts **`POST /api/checkout`** with `{ product: uuid, quantity, name, phone }`
   plus a hidden **`company` honeypot**, then redirects to the returned `redirectUrl`.

The theme styles itself with **`themeCss(site.theme)`** injected as a `<style>`,
formats dates via **`formatDate`**, and chooses direction via **`dirFor(locale)`**.

---

## Run locally

No build step and no dependencies — plain ES modules + a tiny Node server.

```bash
cd payload-theme
node server/index.mjs
# http://localhost:4173/
```

The bundled `server/index.mjs` is a **mock Payload backend** that implements
`/api/site`, `/api/products` and `/api/checkout` so you can preview the theme
without a live CMS. Replace it (or point the theme) at a real Payload
deployment that follows `docs/THEME_API.md`.

### Pointing at a real Payload CMS (`eshobe-cms`)

The bundled mock implements `docs/THEME_API.md` literally. A real
[`eshobe-cms`](https://github.com/hamidnoshady/eshobe-cms) does **not**: its
`GET /api/site` is a bootstrap descriptor (locales, design tokens, currency, the
*names* of the allowed blocks), the page content lives in `/api/pages`, and
everything else is Payload's generic REST API with `{docs:[…]}` and its own
query language.

`core/payload-adapter.js` bridges the two. It engages by itself — a descriptor
carrying `contractVersion` and a `blocks` array of strings is a live CMS — and
translates `/api/products*` and the checkout answer behind `api()`, so no
component knows which backend it is on. Run the pair locally with
[`LOCAL-CMS.md`](./LOCAL-CMS.md):

```bash
node server/index.mjs
# then: http://localhost:4173/?base=http://shop.localhost:3000
```

`?base=` (and optional `&host=` / `&locale=`) set the `<wm-site>` attributes, so
the same page serves both backends without an edit. Adapter tests:

```bash
node --test tests/*.test.mjs
```

### Pointing at a real backend

The `host` attribute on `<wm-site>` is the value sent as the `Host` header
(and is what the backend uses to pick the site).

`Host` is a **forbidden header in browsers**, so when the storefront lives on a
different origin from the Payload backend, give the theme the API origin via
`base` (the `api-base` attribute). The theme then resolves every `/api/…` call
against it and handles CORS preflight.

```html
<wm-site host="acme.ir" base="https://cms.example.com"></wm-site>
```

Node/SSR consumers send the `Host` header directly; browsers send the host of
the `base`/page origin.

---

## Architecture

```
payload-theme/
├── index.html                 # boot page (fa + RTL), loads design system + components
├── core/
│   ├── theme.js               # getSite, api, formatPrice, formatDate, dirFor, themeCss
│   ├── blocks.js              # block registry + renderers (hero, productGrid, brandGrid, …)
│   └── payload-adapter.js     # live eshobe-cms translation (descriptor, pages, REST)
├── components/                # reusable, framework-free custom elements
│   ├── wm-site.js             # orchestrator: site -> themeCss -> dir -> header/main/footer + routing
│   ├── wm-product-card.js     # single product card (emits wm:add-to-cart / wm:quick-view)
│   ├── wm-product-grid.js     # carousel that fetches GET /api/products
│   ├── wm-product-detail.js   # single-product view (#/products/:key) + CTA banner
│   ├── wm-search.js           # header live-search modal (GET /api/products?search=)
│   ├── wm-cart.js             # mini-cart drawer (client-side basket, localStorage)
│   └── wm-buy-form.js         # checkout modal with company honeypot
├── styles/                    # copied verbatim from the Eshobe WP theme
│   ├── tokens.css theme.css fonts.css pages-home.css
│   └── components/{header,footer,decorative-motifs,product-components,checkout,quick-view,buy-form}.css
├── fonts/vazirmatn/           # bundled Vazirmatn woff2
├── tests/                     # node --test, adapter translation only
└── server/index.mjs           # mock Payload API + static server
```

### Design-token contract

`themeCss(site.theme)` emits a `:root { … }` block that overrides the built-in
design tokens (colors, font, content width, radius). Omitted tokens fall back
to `styles/tokens.css` defaults, so any Payload `theme` object re-skins the site
without touching theme code. The design system is therefore:

- **base** → the copied `styles/*.css` (identical look to the WP theme);
- **overrides** → `themeCss(site.theme)` at runtime.

---

## Reusable components

The `components/` directory is **not** coupled to Payload or to `<wm-site>`.
Each custom element is usable standalone in any theme:

| Component            | Reusable API                                                                      |
| -------------------- | --------------------------------------------------------------------------------- |
| `<wm-product-card>`  | `.product = {...}`; emits `wm:add-to-cart` / `wm:quick-view`                       |
| `<wm-product-grid>`  | attributes `host,locale,collection,limit,title,subtitle,url,class` (plus `base`)   |
| `<wm-product-detail>`| `.product = {...}`, `host`, `base`, `locale`, `currency`; emits `wm:add-to-cart` + `wm:open-buy`; includes a CTA banner |
| `<wm-search>`        | `.open()`, `.close()`, `.isOpen`; `GET /api/products?search=…`; emits `wm:select-product` |
| `<wm-cart>`          | `.add(product)`, `.remove(key)`, `.items`, `.count`, `.open()`, `.close()`; localStorage-backed; emits `wm:open-buy` on checkout |
| `<wm-buy-form>`      | `.product = {...}`, `.open()`, `.close()`, POSTs to `/api/checkout` + honeypot     |
| `<wm-site>`          | `host`, `base`, `locale` — boots storefront + locale switcher + client routing     |

Example — drop a product carousel into any page, no `<wm-site>` required:

```html
<wm-product-grid
  host="acme.ir" locale="fa" collection="bestsellers" limit="8"
  title="پرفروش‌ها" subtitle="محصولاتی که بیشتر انتخاب شده‌اند."
></wm-product-grid>
```

Example — a standalone buy modal:

```html
<wm-buy-form host="acme.ir" locale="fa"></wm-buy-form>
<script>
  const form = document.querySelector('wm-buy-form');
  form.product = { id: 'prod-01', title: 'کیف چرم', price: 2450000, currency: 'IRR' };
  form.open();
</script>
```

---

## API contract

Full contract: [`docs/THEME_API.md`](../docs/THEME_API.md). The theme never
touches WordPress, WooCommerce, or ACF at runtime.

- `GET /api/site` — `availableLocales`, `defaultLocale`, `store`, `theme`, `blocks`.
- `GET /api/products` — `{ products: [...] }`; a `productGrid` block issues this.
  Accepts optional `?search=…` (title/brand/category match, used by the header
  live-search modal) plus `?collection`, `?limit`, `?locale`.
- `GET /api/products/:key` — single product (`id` or `slug`) for the detail view.
- `POST /api/checkout` — JSON body `{product, quantity, name, phone, company}`;
  `company` is the honeypot (empty = human, non-empty = bot → `400`); success is
  a `302` to the order confirmation page (`redirectUrl`).
- Prices: `formatPrice(product.price, site.store.currency, locale)` — formatting
  only, **never** a currency conversion.

The storefront is a single page with hash routing:
`#/` = home (`site.blocks`), `#/products/:key` = product detail. The header has
a **locale switcher** (from `availableLocales`) that re-fetches the site,
re-applies `dirFor(locale)` and `themeCss(site.theme)`.

---

## Design parity

The design system files are verbatim copies of the Eshobe WooCommerce theme:
`assets/css/{tokens,theme,fonts}.css`, `assets/css/pages/home.css`, and the
matching `assets/css/components/*.css`. RTL is native (`body{direction:rtl}` +
`dirFor(locale)`), Vazirmatn is bundled, and `formatPrice` renders Persian
digits (۰۱۲۳…) while keeping the numeric run LTR via `<bdi>` exactly like the
WooCommerce theme. Nobody can tell the CMS changed.
