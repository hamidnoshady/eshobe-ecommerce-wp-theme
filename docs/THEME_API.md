# Payload CMS — Eshobe Storefront Theme API

This document is the **single source of truth** for connecting the Eshobe
storefront theme to a Payload CMS backend. The theme in `payload-theme/`
consumes **only** the endpoints and helpers described here. Nothing else, and
no other assumption about the backend (no WordPress, no WooCommerce, no ACF)
is made by the theme at runtime.

The theme is **Persian-first**: the default locale is `fa`, the document is
RTL, and every public string ships in Persian unless the CMS provides a
localized alternative.

---

## 1. Conventions

### 1.1 Host header

Every request the theme makes carries a `Host` header corresponding to the
site being rendered. The canonical value is `acme.ir` and it is configurable
at boot time (see [`payload-theme/README.md`](../payload-theme/README.md)).

```http
GET /api/site
Host: acme.ir
Accept: application/json
```

The same `Host` value is sent on `GET /api/products` and `POST /api/checkout`.

### 1.2 Locales

`GET /api/site` returns `availableLocales` (an array of BCP-47-ish locale
codes). The theme uses:

- the site's declared `defaultLocale` for the initial render, falling back to
  the first entry in `availableLocales`, falling back to `fa`; and
- `dirFor(locale)` to decide the document/block direction (see §4.3).

### 1.3 Pricing integrity — never convert

Prices are **always** passed to `formatPrice(product.price, site.store.currency, locale)`.
The theme never multiplies, divides, rounds to a different currency, or
otherwise converts a price. `formatPrice` only formats the numeric value
already expressed in `site.store.currency`. If the backend stores prices in
minor units, the theme treats the value it returns from `/api/products` as the
value to format — it does not guess.

### 1.4 Cross-origin deployment (`api-base`)

`Host` is a forbidden header in browsers, so when the storefront is served on a
different origin from the Payload backend you tell the theme the API origin with
the `api-base` attribute on `<wm-site>`:

```html
<wm-site host="acme.ir" base="https://cms.example.com"></wm-site>
```

The theme then resolves every request (`/api/site`, `/api/products`,
`/api/products/:key`, `/api/checkout`) against that base and adds standard CORS
preflight handling. The `host` value is still passed to `core/theme.js` `api()`
and is sent as the `Host` header when running server-side/SSR (Node), where it
is not a forbidden header.

### 1.5 Locale switching

`availableLocales` drives a header switcher. Changing locale re-fetches
`/api/site` with the new `locale`, re-applies `dirFor(locale)` (toggling
`dir`/`lang` on `<html>`), re-injects `themeCss(site.theme)`, and re-renders the
home page. A Payload deployment should return localized strings for each
locale in `availableLocales`.

---

## 2. `GET /api/site`

Returns everything needed to boot the page: the available locales, the store
context, the active theme, and the ordered list of home-page blocks.

```
GET /api/site
Host: acme.ir
```

### 2.1 Response shape

```json
{
  "availableLocales": ["fa", "en"],
  "defaultLocale": "fa",
  "store": {
    "currency": "IRR",
    "currencyLocale": "fa-IR",
    "name": "فروشگاه آنلاین",
    "description": "انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا.",
    "logo": {"src": "https://cdn.example.com/logo.svg", "alt": "فروشگاه آنلاین"},
    "nav": [
      {"label": "خانه", "href": "/"},
      {"label": "فروشگاه", "href": "/shop"},
      {"label": "تماس با ما", "href": "/contact-us"}
    ],
    "topbar": ["ضمانت اصالت کالا", "ارسال سریع", "پرداخت امن", "پشتیبانی قبل از خرید"],
    "footer": {
      "title": "فروشگاه آنلاین",
      "description": "انتخابی مطمئن برای خرید آنلاین.",
      "links": [
        {"label": "درباره ما", "href": "/about-us"},
        {"label": "تماس با ما", "href": "/contact-us"},
        {"label": "راهنمای خرید", "href": "/buying-guide"},
        {"label": "پیگیری سفارش", "href": "/order-tracking"}
      ],
      "guarantee": "ضمانت بازگشت کالا تا ۷ روز پس از تحویل سفارش",
      "copyright": "© ۲۰۲۶ فروشگاه آنلاین. کلیه حقوق محفوظ است.",
      "badges": []
    }
  },
  "theme": {
    "colors": {
      "primary": "#111827",
      "secondary": "#6B7280",
      "text": "#1F2937",
      "accent": "#C89B3C",
      "accentDark": "#9F7425",
      "background": "#F6F5F2",
      "surface": "#FFFFFF",
      "border": "#E5E0D8",
      "muted": "#6B7280",
      "cta": "#111827"
    },
    "font": {
      "family": "Vazirmatn",
      "sizeBase": "15px",
      "sizeH1": "32px",
      "sizeH2": "26px",
      "sizeH3": "20px",
      "sizeSmall": "13px",
      "lineHeightBody": 1.9,
      "weightHeading": 800,
      "weightBody": 400
    },
    "layout": {
      "contentWidth": 1200,
      "globalRadius": 24
    }
  },
  "blocks": [
    {"id": "hero", "type": "hero", "slides": [ /* see §5.1 */ ]},
    {"id": "brands", "type": "brandGrid", "title": "برندها", "subtitle": "بر اساس برندهای پربازدید", "items": [ /* §5.2 */ ]},
    {"id": "recommended", "type": "productGrid", "title": "پیشنهاد ما", "subtitle": "محصولاتی که ارزش دیدن دارند.", "limit": 10, "collection": "recommended"},
    {"id": "filters", "type": "filterBoxes", "columns": 3, "items": [ /* §5.4 */ ]},
    {"id": "bestsellers", "type": "productGrid", "title": "پرفروش‌ها", "subtitle": "محصولاتی که بیشتر انتخاب شده‌اند.", "limit": 10, "collection": "bestsellers"},
    {"id": "styles", "type": "styleGrid", "title": "سبک‌های محبوب", "subtitle": "بر اساس زبان طراحی و موقعیت استفاده انتخاب کنید.", "items": [ /* §5.5 */ ]},
    {"id": "trust", "type": "trust", "items": [ /* §5.6 */ ]}
  ]
}
```

### 2.2 `store` fields

| Field              | Type             | Used by                                  |
| ------------------ | ---------------- | ---------------------------------------- |
| `currency`         | `string`         | `formatPrice(product.price, currency, locale)` |
| `currencyLocale`   | `string`         | fallback locale when formatting currency |
| `name`             | `string`         | header / footer / brand                   |
| `description`      | `string`         | footer                                   |
| `logo`             | `{src, alt}`     | header                                   |
| `nav`              | `{label, href}[]`| header nav                               |
| `topbar`           | `string[]`       | header top bar items                     |
| `footer`           | `object`         | footer                                   |

### 2.3 `theme` fields

The theme object is forwarded to `themeCss(site.theme)` which emits a `<style>`
block overriding the design tokens (colors, font, radius, content width). Any
field omitted falls back to the theme's built-in defaults (§4.2). This is what
lets a Payload admin re-skin the storefront without touching theme code.

### 2.4 Blocks

`blocks` is an ordered array. Each entry has at least `id` and `type`. The
theme renders them top-to-bottom via the block registry (§5). Unknown block
types render nothing (they never crash the page).

---

## 3. `GET /api/products`

A `productGrid` block issues this request and renders the returned products.

```
GET /api/products
Host: acme.ir
Accept: application/json
```

Optional query-string filters the theme may send (they are safe to ignore):

- `collection` — e.g. `recommended`, `bestsellers`, `new` (echoes the block's
  `collection` config, when provided)
- `limit` — max number of items (echoes the block's `limit`)
- `locale` — the active locale (`fa`, `en`)

### 3.1 Response shape

```json
{
  "products": [
    {
      "id": "9f8c1e2a-3b4d-4c5e-8f6a-7b8c9d0e1f2a",
      "title": "کیف چرم دست‌دوز",
      "slug": "leather-handbag",
      "price": 2500000,
      "currency": "IRR",
      "image": {"src": "https://cdn.example.com/leather-handbag.jpg", "alt": "کیف چرم دست‌دوز"},
      "gallery": [
        {"src": "https://cdn.example.com/leather-handbag-2.jpg", "alt": "کیف چرم دست‌دوز"}
      ],
      "brand": "برند نمونه",
      "url": "/products/leather-handbag",
      "badge": "تخفیف ویژه",
      "inStock": true
    }
  ],
  "total": 42
}
```

### 3.2 Product fields

| Field         | Type       | Notes                                                |
| ------------- | ---------- | ---------------------------------------------------- |
| `id`          | `uuid`     | used as `product` in `/api/checkout`                 |
| `title`       | `string`   | product name                                         |
| `price`       | `number`   | numeric amount in `site.store.currency`              |
| `currency`    | `string`   | ignored for formatting; `site.store.currency` is used |
| `image`       | `{src,alt}`| primary image                                        |
| `gallery`     | `[]`       | `gallery[0]` = hover image; used by the detail view  |
| `url`         | `string`   | product landing URL                                  |
| `badge`       | `string`   | optional ribbon text                                 |
| `inStock`     | `boolean`  | controls the buy CTA state                            |
| `description` | `string`   | short description (detail view)                       |
| `specs`       | `[]`       | `{label, value}[]` (detail view)                      |
| `sku`         | `string`   | optional (detail view meta)                           |
| `guarantee`   | `string`   | optional (detail view meta)                           |
| `category`    | `string`   | optional (detail view meta)                           |
| `categoryUrl` | `string`   | optional (detail view meta link)                      |
| `brand`       | `string`   | brand name (card + detail)                            |
| `brandUrl`    | `string`   | optional (detail view meta link)                      |
| `publishedAt` | `string`   | ISO date (formatted via `formatDate` in the detail view) |

The theme renders each product with `<wm-product-card>`. The card's buy button
opens the `<wm-buy-form>` (see §6) which POSTs to `/api/checkout`.

### 3.3 `GET /api/products/:key`

The product-detail view (`#/products/:key`) fetches a **single** product with
the fuller field set above (description, specs, sku, guarantee, gallery,
publishedAt). `:key` may be the product `id` (uuid) **or** its `slug`.

```
GET /api/products/9f8c1e2a-3b4d-4c5e-8f6a-7b8c9d0e1f2a
GET /api/products/leather-handbag
Host: acme.ir
```

Response:

```json
{ "product": { "id": "…", "title": "…", "price": 2450000, "specs": […], "description": "…" } }
```

Returns `404 {"error":"not_found"}` when no product matches.

---

## 4. Theme helpers

These are the pure helpers every theme (and any other theme reusing these
components) depends on. They are exposed from `payload-theme/core/theme.js`.

### 4.1 `formatPrice(price, currency, locale)`

Formats a price **without converting** it. `currency` is a currency code
(e.g. `IRR`); `locale` is the display locale (e.g. `fa`, `fa-IR`). It applies
`Intl.NumberFormat(locale, { style: 'currency', currency })` and, for Persian
locales, renders Latin digits as Eastern Arabic numerals (۰۱۲۳…) to match the
rest of the UI. Returns an HTML-safe formatted string.

```js
formatPrice(2500000, 'IRR', 'fa-IR'); // "۲٬۵۰۰٬۰۰۰ تومان" (or your currency symbol)
```

The theme **never** converts the amount; the value passed in is the value
formatted.

### 4.2 `themeCss(theme)`

Builds a CSS string from a `site.theme` object and returns a `<style>` element
(or, with `asString: true`, the raw CSS). The emitted rules override the theme's
`:root` design tokens. This is how the payload admin re-skins the site.

```js
themeCss(site.theme); // <style> :root { --wm-color-primary: #111827; ... } </style>
themeCss(site.theme, { asString: true }); // ":root{...}"
```

Defaults (used for any omitted token):

| CSS var                       | Default  |
| ----------------------------- | -------- |
| `--wm-color-primary`          | `#111827`|
| `--wm-color-secondary`        | `#6B7280`|
| `--wm-color-text`             | `#1F2937`|
| `--wm-color-accent`           | `#C89B3C`|
| `--wm-color-accent-dark`      | `#9F7425`|
| `--wm-color-background`/`--bg`| `#F6F5F2`|
| `--wm-color-surface`          | `#FFFFFF`|
| `--wm-color-border`           | `#E5E0D8`|
| `--wm-color-muted`            | `#6B7280`|
| `--wm-color-cta`              | `#111827`|
| `--wm-font-primary`           | `'Vazirmatn', system-ui, …`|
| `--wm-content-width`          | `1200px`|
| `--wm-radius-lg`              | `24px`   |

### 4.3 `dirFor(locale)`

Returns `'rtl'` for right-to-left locales (`fa`, `ar`, `he`, `ur`, …) and `'ltr'`
otherwise. The theme sets `document.documentElement.dir` from the first
rendered locale and re-applies it if the visitor switches locale.

### 4.4 `formatDate(date, locale)`

Formats a date in the given locale (e.g. `fa-IR` for the Persian/Eastern-Arabic
calendar, with Persian digits). Accepts a `Date`, timestamp, or ISO string.

---

## 5. Block registry

Block renderers live in `payload-theme/core/blocks.js`. `renderBlocks(blocks, ctx)`
walks `site.blocks` in order and hands each block to its renderer. `ctx`
carries `{ site, locale, dir, helpers }`.

### 5.1 `hero`

```json
{"type":"hero","slides":[
  {"type":"content","eyebrow":"مجموعه ویژه","title":"محصولات منتخب برای امروز","subtitle":"مجموعه‌ای منتخب با تجربه خرید تمیز و سریع.","primaryText":"مشاهده فروشگاه","primaryUrl":"/shop","secondaryText":"پرفروش‌ها","secondaryUrl":"#bestsellers","image":{"src":…}}
]}
```

### 5.2 `brandGrid`

```json
{"type":"brandGrid","title":"برندها","subtitle":"…","items":[
  {"title":"برند الف","href":"/brand/الف","image":{"src":…},"count":24}
]}
```

### 5.3 `productGrid`

```json
{"type":"productGrid","title":"پیشنهاد ما","subtitle":"…","limit":10,"collection":"recommended"}
```

The renderer fetches `GET /api/products` (Host: `acme.ir`) and renders the
returned products as `<wm-product-card>` items inside a scrollable carousel
(`.wm-product-carousel`). If the request fails it renders nothing.

### 5.4 `filterBoxes`

```json
{"type":"filterBoxes","title":"…","subtitle":"…","columns":3,"items":[
  {"title":"سبک کلاسیک","href":"/shop?style=classic","image":{"src":…},"variant":"light"}
]}
```

### 5.5 `styleGrid`

```json
{"type":"styleGrid","title":"سبک‌های محبوب","subtitle":"…","items":[
  {"title":"کلاسیک","subtitle":"ساده، ماندگار و همیشه قابل استفاده","href":"/shop?style=classic","image":{"src":…}}
]}
```

### 5.6 `trust`

```json
{"type":"trust","items":[
  {"title":"ضمانت اصالت کالا","text":"انتخاب مطمئن از محصولات معتبر","icon":{"src":…}}
]}
```

---

## 6. `POST /api/checkout`

The buy flow renders `<wm-buy-form>` for the selected product. When submitted it
POSTs JSON to `/api/checkout` with `Host: acme.ir`:

```http
POST /api/checkout
Host: acme.ir
Content-Type: application/json

{ "product": "9f8c1e2a-3b4d-4c5e-8f6a-7b8c9d0e1f2a", "quantity": 1, "name": "نام خریدار", "phone": "09120000000", "company": "" }
```

### 6.1 Honeypot

`company` is a **honeypot** field. It must be rendered in the form as a real,
visually-hidden input so bots fill it. The client sends whatever is in it; a
correctly-behaved human leaves it empty.

- If `company` is **non-empty**, the server treats it as a bot and returns
  `400` and the theme refuses to navigate.
- If `company` is **empty**, the server validates the order and responds with a
  redirect target.

### 6.2 Response

On success the server returns a **302 redirect** to the order confirmation page:

```http
HTTP/1.1 302 Found
Location: /checkout/success?order=abc123
```

The client follows the `Location` header. (The theme also supports a JSON
response of the form `{ "redirectUrl": "/checkout/success?order=abc123" }`.)

On validation failure the server returns `400` with:

```json
{ "error": "phone_required", "message": "شماره تماس الزامی است." }
```

The theme surfaces `message` (or a localized fallback) inline.

---

## 7. RTL / fonts

- `dirFor('fa') === 'rtl'`; the document opens with `dir="rtl"` and `lang="fa"`.
- Vazirmatn (`assets/fonts/vazirmatn/`) is bundled; `theme.font.family` can
  override the stack via `themeCss`. Fonts are loaded from
  `payload-theme/styles/fonts.css`.
- `.woocommerce-Price-amount` base styling is intentionally kept (see
  `theme.css`) so any reused markup keeps left-to-right numeric prices inside
  an RTL document.

---

## 8. Client routing

The storefront is a single page. `<wm-site>` routes on the URL hash:

| Hash                    | View                                            |
| ----------------------- | ----------------------------------------------- |
| `#/`                    | home — renders `site.blocks` in order           |
| `#/products/:key`       | product detail — `GET /api/products/:key`       |

Internal product links are intercepted and converted to `#/products/:key`.
The product detail is rendered by the reusable `<wm-product-detail>` element
(which mirrors the Eshobe `single-product` markup: `<wm-product-layout>`,
`<wm-product-gallery>`, `<wm-product-intro>`, `<wm-product-specs>`,
`<wm-product-purchase>`). Its buy button emits `wm:open-buy`, which the host
answers with a `<wm-buy-form>`.
