You are working on a WooCommerce product page for zaniziba.com (a Persian RTL e-commerce store).
The theme uses a custom BEM-style design system with CSS variables and class prefix `wm-`.

**Page:** Single product page (e.g., /shop/product-slug/)
**Relevant file(s):** The theme's PHP template for the mobile bottom bar, its CSS file(s), and the functions that render the mobile nav on product pages.

---

## DESIGN TOKENS (do not change these values — use them consistently):

```css
--wm-color-primary: #111827
--wm-color-accent:  #c1a488
--wm-color-muted:   #6B7280
--wm-color-border:  #c1b2a4
--wm-color-surface: #FFFFFF
--wm-space-2: 8px   --wm-space-3: 12px   --wm-space-4: 16px   --wm-space-5: 20px
--wm-radius-lg: 24px
--wm-font-primary: 'Vazirmatn', system-ui, sans-serif
```

---

## CURRENT STRUCTURE of the mobile bottom bar (`.wm-mobile-bottom-bar`):

```html
<section class="wm-mobile-bottom-bar wm-mobile-bottom-bar--collapsed"
         aria-label="Mobile purchase bar">

  <!-- hamburger trigger (opens shop sheet modal) -->
  <button class="wm-mobile-bottom-bar__menu-trigger" type="button"
          data-mobile-sheet-target="shop" aria-expanded="false" aria-label="باز کردن منو">
    <span class="wm-mobile-bottom-bar__menu-icon" aria-hidden="true"></span>
  </button>

  <!-- chevron handle (expands/collapses the bar) -->
  <button class="wm-mobile-bottom-bar__handle" type="button"
          aria-expanded="false" aria-controls="wm-mobile-bottom-bar-content">
    <span class="wm-mobile-bottom-bar__chevron" aria-hidden="true"></span>
  </button>

  <!-- collapsed summary row -->
  <div class="wm-mobile-bottom-bar__summary">
    <div class="wm-mobile-bottom-bar__price"> … price range … </div>
    <div class="wm-mobile-bottom-bar__stock wm-mobile-bottom-bar__stock--in_stock"> در انبار </div>
    <div class="wm-mobile-bottom-bar__cta">
      <!-- Full variations_form.cart here:
           TABLE.variations > TR > TH.label + TD.value > DIV.wm-variation-attribute (swatches + selected label)
           + DIV.wm-variation-stock-box (stock status + quantity stepper)
           + DIV.woocommerce-variation-add-to-cart > BUTTON.single_add_to_cart_button -->
    </div>
  </div>

  <!-- expanded content (SKU + trust slugs) -->
  <div id="wm-mobile-bottom-bar-content" class="wm-mobile-bottom-bar__content">
    <div class="wm-mobile-bottom-bar__meta">
      <div class="wm-mobile-bottom-bar__meta-item">
        <span class="wm-mobile-bottom-bar__meta-label">SKU</span>
        <span class="wm-mobile-bottom-bar__meta-value">zza05690</span>
      </div>
    </div>
    <div class="wm-mobile-bottom-bar__trust">
      <span>ضمانت اصالت کالا</span>
      <span>ارسال سریع</span>
      <span>پرداخت امن</span>
    </div>
  </div>

</section>
```

The `.wm-mobile-nav` (pill-shaped 4-tab bottom nav used on all other pages) is currently **not rendered** on product pages — it does not exist in the DOM. On other pages it looks like:

```html
<nav class="wm-mobile-nav wm-mobile-nav--comfortable wm-mobile-nav--labels"
     aria-label="نوار پایین موبایل">
  <div class="wm-mobile-nav__inner">
    <a class="wm-mobile-nav__item wm-mobile-nav__item--home wm-mobile-nav__item--active"
       href="https://zaniziba.com/" aria-current="page">
      <span class="wm-mobile-nav__icon"><svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4.5 10.8 12 4.5l7.5 6.3v8.1a1.6 1.6 0 0 1-1.6 1.6h-3.4v-5.6h-5v5.6H6.1a1.6 1.6 0 0 1-1.6-1.6v-8.1Z"/>
      </svg></span>
      <span class="wm-mobile-nav__label">خانه</span>
    </a>
    <button type="button" class="wm-mobile-nav__item wm-mobile-nav__item--shop"
            data-mobile-sheet-target="shop" aria-expanded="false">
      <span class="wm-mobile-nav__icon"><!-- shop bag SVG --></span>
      <span class="wm-mobile-nav__label">فروشگاه</span>
    </button>
    <button type="button" class="wm-mobile-nav__item wm-mobile-nav__item--cart"
            data-mobile-sheet-target="cart" aria-expanded="false">
      <span class="wm-mobile-nav__icon"><!-- cart SVG + badge --></span>
      <span class="wm-mobile-nav__label">سبد خرید</span>
    </button>
    <a class="wm-mobile-nav__item wm-mobile-nav__item--account"
       href="/my-account/">
      <span class="wm-mobile-nav__icon"><!-- user SVG --></span>
      <span class="wm-mobile-nav__label">حساب من</span>
    </a>
  </div>
</nav>
```

`.wm-mobile-nav__inner` is a pill-shaped card:
`min-height:66px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); align-items:center; gap:4px; padding:8px; border-radius:24px; background:rgba(255,255,255,0.96); box-shadow: 0 18px 44px rgba(17,24,39,0.14); backdrop-filter:blur(14px);`

---

## REQUIRED CHANGES — implement ALL of the following:

### 1. COMPACT VARIATION SWITCHER (inside `.wm-mobile-bottom-bar__cta`)

**Problem:** The variation title (`.wm-variation-attribute__selected`) displays large and below the swatches. Swatches are too big.

**Fix:**
- In the mobile bottom bar context only (`.wm-mobile-bottom-bar .wm-variation-attribute`), lay out the attribute row so the **label/title appears above the swatches in small text**, not next to or below them.
- Add these CSS rules scoped to `.wm-mobile-bottom-bar`:

```css
/* Variation title: small, above the swatch row */
.wm-mobile-bottom-bar .wm-variation-attribute__selected {
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 6px;
  min-height: unset;
  line-height: 1.2;
  color: var(--wm-color-muted);
}

/* Shrink the whole variation block */
.wm-mobile-bottom-bar .wm-variation-attribute {
  margin-bottom: 8px;
}

/* Smaller swatches in mobile bar */
.wm-mobile-bottom-bar .wm-variation-swatch--color,
.wm-mobile-bottom-bar .wm-variation-swatch--image {
  width: 26px;
  height: 26px;
}

.wm-mobile-bottom-bar .wm-variation-swatch--label {
  height: 28px;
  min-width: 28px;
  padding: 0 8px;
  font-size: 11px;
}

.wm-mobile-bottom-bar .wm-variation-swatches {
  gap: 6px;
}
```

---

### 2. COMPACT ADD-TO-CART BOX (`.wm-mobile-bottom-bar` — entire box)

**Problem:** The whole box is too tall, too padded, messy. Quantity stepper, variation swatches, add-to-cart button, price, and stock all need to be tighter.

**Fix — restructure the collapsed summary layout:**

Change the layout of `.wm-mobile-bottom-bar__summary` from a stacked multi-row layout to a **2-row compact layout**:

- **Row 1 (summary bar, always visible):** `[price] [stock badge] [▲ chevron]` — all on one line, tight.
- **Row 2 (expanded, shown when `wm-mobile-bottom-bar--expanded` is active):** variation swatches + quantity + add-to-cart button, styled compactly.

Apply these CSS rules:

```css
/* Tighter overall padding */
@media (max-width: 767px) {
  .wm-mobile-bottom-bar {
    padding: 6px 14px calc(10px + env(safe-area-inset-bottom, 0px));
  }

  /* Summary row: single line */
  .wm-mobile-bottom-bar__summary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0;
    min-height: 44px;
  }

  .wm-mobile-bottom-bar__price {
    flex: 1;
    min-height: unset;
    font-size: 14px;
  }

  .wm-mobile-bottom-bar__stock {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 20px;
    background: color-mix(in srgb, var(--wm-color-accent) 12%, transparent);
    color: var(--wm-color-primary);
    white-space: nowrap;
  }

  /* CTA area: more compact when expanded */
  .wm-mobile-bottom-bar__cta {
    padding-top: 8px;
  }

  /* Stock + quantity box: tighter */
  .wm-mobile-bottom-bar .wm-variation-stock-box {
    padding: 6px 10px;
    margin-bottom: 8px;
    gap: 8px;
  }

  .wm-mobile-bottom-bar .wm-variation-stock-box__qty {
    gap: 4px;
  }

  .wm-mobile-bottom-bar .wm-variation-stock-box__btn {
    width: 28px;
    height: 28px;
    min-width: 28px;
  }

  .wm-mobile-bottom-bar .input-text.qty {
    width: 36px;
    height: 28px;
    font-size: 13px;
  }

  /* Add to cart button: full-width, matches theme CTA style */
  .wm-mobile-bottom-bar__cta .button.single_add_to_cart_button {
    width: 100%;
    padding: 11px 16px;
    font-size: 14px;
    font-weight: 800;
    border-radius: var(--wm-radius-lg);
    letter-spacing: 0.01em;
  }

  /* Hide the reset variations link in mobile bar (clutter) */
  .wm-mobile-bottom-bar .reset_variations {
    display: none;
  }
}
```

---

### 3. REMOVE HAMBURGER MENU BUTTON FROM BOTTOM BAR

**Problem:** `.wm-mobile-bottom-bar__menu-trigger` (the hamburger/≡ icon) is shown inside the bottom bar. This is redundant since the mobile nav (added in step 4) provides navigation access.

**Fix:**
```css
@media (max-width: 767px) {
  .wm-mobile-bottom-bar__menu-trigger {
    display: none !important;
  }
}
```

Also in PHP/HTML: if the hamburger button is rendered via PHP, wrap its output in a conditional or simply rely on the CSS to hide it. The `data-mobile-sheet-target="shop"` functionality will still be available via the nav bar in step 4.

---

### 4. ADD THE MOBILE NAV BAR TO PRODUCT PAGES (below the add-to-cart bar)

**Problem:** The `.wm-mobile-nav` pill-shaped nav bar (shown on homepage, shop, etc.) is **not rendered** on single product pages. Instead, a hamburger icon inside the bottom bar was used as a workaround.

**Fix — Render the mobile nav on product pages:**

In the PHP template/hook that renders the mobile bottom bar (likely `wm-mobile-bottom-bar.php` or hooked via `woocommerce_single_product_summary`), add the same `.wm-mobile-nav` markup that other pages output. The nav shell (`wm-mobile-nav-shell`) already exists in the DOM on product pages (it renders the sheet overlays), so only the **nav bar itself** needs to be injected.

Add the following HTML **after** the closing `</section>` of `.wm-mobile-bottom-bar`:

```html
<nav class="wm-mobile-nav wm-mobile-nav--comfortable wm-mobile-nav--labels wm-product-page-nav"
     aria-label="نوار پایین موبایل">
  <div class="wm-mobile-nav__inner">
    <a class="wm-mobile-nav__item wm-mobile-nav__item--home"
       href="<?php echo esc_url(home_url('/')); ?>">
      <span class="wm-mobile-nav__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4.5 10.8 12 4.5l7.5 6.3v8.1a1.6 1.6 0 0 1-1.6 1.6h-3.4v-5.6h-5v5.6H6.1a1.6 1.6 0 0 1-1.6-1.6v-8.1Z"/>
        </svg>
      </span>
      <span class="wm-mobile-nav__label">خانه</span>
    </a>
    <button type="button" class="wm-mobile-nav__item wm-mobile-nav__item--shop"
            data-mobile-sheet-target="shop" aria-expanded="false">
      <span class="wm-mobile-nav__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/>
        </svg>
      </span>
      <span class="wm-mobile-nav__label">فروشگاه</span>
    </button>
    <button type="button" class="wm-mobile-nav__item wm-mobile-nav__item--cart"
            data-mobile-sheet-target="cart" aria-expanded="false">
      <span class="wm-mobile-nav__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
      </span>
      <span class="wm-mobile-nav__label">سبد خرید</span>
    </button>
    <a class="wm-mobile-nav__item wm-mobile-nav__item--account"
       href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">
      <span class="wm-mobile-nav__icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </span>
      <span class="wm-mobile-nav__label">حساب من</span>
    </a>
  </div>
</nav>
```

**CSS for nav position relative to the bottom bar:**

The nav must render **visually below** the collapsed bottom bar (summary row), but since both are fixed-position, the nav must be positioned below the bar:

```css
@media (max-width: 767px) {
  /* Nav bar sits at the very bottom */
  .wm-product-page-nav.wm-mobile-nav {
    z-index: 999; /* Below the bottom bar (z-index: 1000) */
    padding-bottom: calc(6px + env(safe-area-inset-bottom, 0px));
    bottom: 0;
  }

  /* Bottom bar sits above the nav */
  .wm-mobile-bottom-bar {
    /* Push it up to sit on top of the nav bar */
    bottom: calc(66px + 12px + env(safe-area-inset-bottom, 0px));
    border-radius: var(--wm-radius-lg);
    /* Also give it a bottom border radius so it looks like a separate card */
    border-radius: var(--wm-radius-lg) var(--wm-radius-lg) var(--wm-radius-lg) var(--wm-radius-lg);
  }

  /* Body padding to account for both bars */
  body:has(.wm-mobile-bottom-bar):has(.wm-product-page-nav) {
    padding-bottom: calc(66px + 68px + env(safe-area-inset-bottom, 0px));
  }
}
```

**The nav must be visible in both collapsed AND expanded states of the add-to-cart bar.** Since the add-to-cart bar is positioned above the nav (bottom offset), this is automatic — no JS changes needed.

---

### 5. MOVE SKU AND TRUST SLUGS OUT OF THE BOTTOM BAR

**Problem:** SKU (`#wm-mobile-bottom-bar-content > .wm-mobile-bottom-bar__meta`) and trust badges (`.wm-mobile-bottom-bar__trust`) are inside the expandable bottom bar, adding visual noise.

**Fix:**

**A) Remove from bottom bar:** In the PHP template, remove (or conditionally hide) the `wm-mobile-bottom-bar__meta` (SKU) div and `wm-mobile-bottom-bar__trust` div from inside `#wm-mobile-bottom-bar-content`.

```css
/* CSS-only fallback if PHP change is not possible */
@media (max-width: 767px) {
  .wm-mobile-bottom-bar__meta,
  .wm-mobile-bottom-bar__trust {
    display: none;
  }
}
```

**B) Add SKU to the product info region:** In the single product summary template (`.wm-product-specs` or equivalent), make sure SKU already renders there. If not, add it via `woocommerce_product_meta_start/end` hooks or in the product meta template.

**C) Add trust badges to the product page body:** Add trust badges as a styled row **inside** the product purchase region (`.wm-product-purchase`) or right below the add-to-cart form. Create a `wm-trust-bar` component:

```html
<!-- Add inside .wm-product-purchase, after the add-to-cart button, desktop+mobile -->
<div class="wm-trust-bar">
  <span class="wm-trust-bar__item">
    <svg><!-- shield/checkmark icon --></svg>
    ضمانت اصالت کالا
  </span>
  <span class="wm-trust-bar__item">
    <svg><!-- rocket/fast icon --></svg>
    ارسال سریع
  </span>
  <span class="wm-trust-bar__item">
    <svg><!-- lock icon --></svg>
    پرداخت امن
  </span>
</div>
```

```css
.wm-trust-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--wm-space-3);
  flex-wrap: wrap;
  padding: var(--wm-space-3) 0 0;
  border-top: 1px solid color-mix(in srgb, var(--wm-color-border) 40%, transparent);
  margin-top: var(--wm-space-3);
}

.wm-trust-bar__item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 700;
  color: var(--wm-color-muted);
  font-family: var(--wm-font-primary);
}

.wm-trust-bar__item svg {
  width: 14px;
  height: 14px;
  stroke: var(--wm-color-accent);
  fill: none;
  stroke-width: 2;
  flex-shrink: 0;
}
```

---

### 6. THEME-ALIGNED OVERALL STYLING OF THE BOTTOM BAR

Ensure the bottom bar matches the existing card/purchase-box design language:

```css
@media (max-width: 767px) {
  .wm-mobile-bottom-bar {
    /* Match the .wm-product-purchase card style */
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(229, 224, 216, 0.95);
    box-shadow: 0 -8px 28px rgba(17, 24, 39, 0.11);
    backdrop-filter: blur(14px);
  }

  /* Chevron handle: make it a proper drag indicator */
  .wm-mobile-bottom-bar__handle {
    min-height: 28px;
    margin-bottom: 2px;
  }

  .wm-mobile-bottom-bar__chevron {
    display: block;
    width: 32px;
    height: 3px;
    border-radius: 2px;
    background: var(--wm-color-border);
    margin: auto;
    transition: transform 0.2s;
  }

  .wm-mobile-bottom-bar--expanded .wm-mobile-bottom-bar__chevron {
    transform: rotate(180deg);
  }
}
```

---

## SUMMARY OF ALL CHANGES:

| # | What | Where |
|---|------|--------|
| 1 | Variation title: small text above swatches, smaller swatch buttons | CSS scoped to `.wm-mobile-bottom-bar` |
| 2 | Entire add-to-cart box more compact, proper 2-row layout | CSS for `.wm-mobile-bottom-bar` |
| 3 | Remove hamburger button from bottom bar | CSS `display:none` + optionally PHP |
| 4 | Add `.wm-mobile-nav` pill nav below the bottom bar on product pages | PHP template + CSS positioning |
| 5 | Move SKU + trust slugs out of bottom bar → into product page body | PHP template + CSS for new `.wm-trust-bar` |
| 6 | Match card/surface design language of rest of theme | CSS on `.wm-mobile-bottom-bar` |

All changes must be **mobile-only** (inside `@media (max-width: 767px)`) unless they target elements that are already mobile-only.
All new elements must use RTL-compatible layout (the page uses `direction: rtl`).
Do not change desktop layout.