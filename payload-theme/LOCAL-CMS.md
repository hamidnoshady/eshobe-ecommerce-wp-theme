# Running the theme against a local Payload CMS

This is the working setup for developing `payload-theme/` against a real
[`eshobe-cms`](https://github.com/hamidnoshady/eshobe-cms) instead of the mock
API in `server/index.mjs`. Two processes, two ports, one adapter between them.

```
┌──────────────────────────┐        ┌───────────────────────────────┐
│ payload-theme            │  fetch │ eshobe-cms (Payload 3 + Next)  │
│ node server/index.mjs    │───────▶│ pnpm dev                      │
│ http://localhost:4173    │  CORS  │ http://shop.localhost:3000    │
└──────────────────────────┘        └───────────────┬───────────────┘
        core/payload-adapter.js                     │
        translates the CMS's REST shape      Postgres (docker or local)
```

---

## 1. The CMS

```bash
cd eshobe-cms
docker compose up -d db          # or a local Postgres on 5432
cp .env.example .env             # then edit, see below
pnpm install
pnpm payload migrate
pnpm seed                        # creates acme/shop/studio.localhost with content
pnpm dev                         # http://localhost:3000
```

`.env` needs only four values for this, plus **one that is specific to attaching
a theme on another origin**:

```ini
DATABASE_URL=postgres://eshobe:eshobe@127.0.0.1:5432/eshobe
PAYLOAD_SECRET=<32+ chars>
NEXT_PUBLIC_SERVER_URL=http://localhost:3000
PREVIEW_SECRET=<anything>

# The theme is served from its own origin, and Payload's CORS list is explicit
# (`cors: true` is deliberately not used — see src/payload.config.ts).
API_CORS_ORIGINS=http://localhost:4173,http://127.0.0.1:4173
```

### Hosts entries

**The tenant is the `Host` header** — that is the platform's whole routing
contract. `localhost:3000` belongs to no site and answers `404 {"error":
"unknown-host"}`; the seeded store lives on `shop.localhost`.

- Linux/macOS: `echo '127.0.0.1 shop.localhost acme.localhost studio.localhost' | sudo tee -a /etc/hosts`
- Windows: `scripts/dev-hosts.ps1` in the CMS repo (Windows does not resolve
  `*.localhost` on its own).

Check it before starting the theme:

```bash
curl -s http://shop.localhost:3000/api/site | head -c 200
# {"availableLocales":["fa"],"blocks":["content",…],"contractVersion":1,…}
```

## 2. The theme

```bash
cd eshobe-ecommerce-wp-theme/payload-theme
node server/index.mjs            # http://localhost:4173
```

Then open the storefront **pointed at the CMS**:

```
http://localhost:4173/?base=http://shop.localhost:3000
```

`?base=` sets the `api-base` attribute on `<wm-site>`; the tenant is the
hostname of that base (override with `&host=`, pick a locale with `&locale=`).
With no `?base=` the same page runs on the bundled mock exactly as before.

## 3. What happens on that first request

`GET /api/site` answers a **descriptor**, not a page: locales, design tokens,
currency, and the *names* of the blocks the site may use. That is a different
document from the one `docs/THEME_API.md` in this repo describes, so
`core/payload-adapter.js` engages automatically (the tell is `contractVersion`
plus a `blocks` array of strings) and from then on:

| The theme asks for      | The CMS is asked                                              |
| ----------------------- | ------------------------------------------------------------- |
| `GET /api/site`         | + `GET /api/pages?where[slug][equals]=home&depth=2` for blocks |
| (the nav)               | `GET /api/pages?limit=50` — the CMS has no menu collection     |
| `GET /api/products`     | `GET /api/products?depth=2&sort=-createdAt` → `{docs}`         |
| `?search=…`             | `where[or][0][title][like]=…`, `[1][summary][like]=…`          |
| `?ids=a,b`              | `where[or][i][id][equals]=…` (a `populateBy: selection` block) |
| `GET /api/products/:key`| `where[slug][equals]` (or `where[id][equals]` for a UUID)      |
| `POST /api/checkout`    | unchanged — the bodies already match                           |

Blocks are mapped `content → richText`, `productGrid → productGrid`,
`features → trust`, and the page's `hero` field becomes the hero block.
Anything else the CMS saved is dropped and listed once in the console:

```
[payload-adapter] 3 block(s) the theme has no renderer for: testimonials, team, pricing
```

Nav links route to `#/p/<slug>`, product links to `#/products/<slug>`.

## 4. Verifying the pair

```bash
node --test tests/*.test.mjs     # adapter translation, no network
```

A quick end-to-end pass through the browser, all against real CMS rows:

1. `/?base=http://shop.localhost:3000` — Persian RTL home page, the site's own
   green/amber tokens from `theme` (not the theme's defaults).
2. `#/p/products` — the catalogue. Two products, **not** the seeded draft: the
   CMS's public read is `_status: published` only, and that is the leak test.
3. Prices read `۱۹۸٬۰۰۰ تومان` — Persian digits, unit from `store.currency`
   (`IRT`), the integer never converted.
4. Add to cart → «ثبت سفارش» → name + phone → an `orders` row exists in
   Postgres and the browser lands on the CMS's signed receipt page
   (`/checkout/<id>?r=…`).
5. A second identical order within 15 minutes is refused with the CMS's own
   Persian duplicate message — the guard is server-side, so the theme only
   displays it.

## 5. Known gaps

- **A `bank` site has no PSP redirect.** The CMS answers `redirectUrl: null`
  plus `confirmationUrl`; the adapter promotes the latter, otherwise the buy
  form sits on «در حال انتقال…» forever.
- **No storefront search endpoint.** `like` over title/summary is what Payload's
  query language offers; it is not a relevance ranking.
- **`testimonials`, `team`, `pricing`, `faq`, `cta`, `gallery`, `logos`,
  `mediaBlock`, `contact`, `formBlock`, `archive`** have no renderer in this
  theme yet — a store site can save them and they will not draw.
- **The mobile header reaches neither the menu nor the cart.** The copied
  WooCommerce `header.css` hides `.wm-site-header__cart` under
  `display: none !important` at mobile widths (WooCommerce put the cart in a
  bottom bar this theme does not render), and the burger
  (`.wm-site-header__tablet-toggle`, emitted by `buildHeader` in
  `components/wm-site.js`) has no click handler. Unrelated to the CMS, but it is
  what you will notice first at 390px.
