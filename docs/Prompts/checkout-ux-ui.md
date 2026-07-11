You are an expert WordPress/WooCommerce frontend developer. I need you to redesign the checkout page of my WooCommerce store to fix serious UX/UI problems. The site uses a custom theme called "eshobe-ecommerce-wp-theme" with RTL (Persian/Farsi) layout and the Vazirmatn font.

---

## SITE DESIGN SYSTEM (DO NOT DEVIATE FROM THESE TOKENS)

```css
/* Theme CSS Variables — use these everywhere */
--wm-color-accent: #c1a488;
--wm-color-accent-dark: #c18853;
--wm-color-background: #F6F5F2;
--wm-color-bg: #F6F5F2;
--wm-color-border: #c1b2a4;
--wm-color-cta: #111827;
--wm-color-muted: #6B7280;
--wm-color-primary: #111827;
--wm-color-secondary: #6B7280;
--wm-color-soft: #F9F8F5;
--wm-color-success-bg: #ECFDF3;
--wm-color-success-text: #027A48;
--wm-color-danger-bg: #FEF3F2;
--wm-color-danger-text: #B42318;
--wm-color-surface: #FFFFFF;
--wm-color-text: #1F2937;
--wm-radius-lg: 24px;
--wm-radius-md: 18px;
--wm-radius-pill: 999px;
--wm-radius-sm: 10px;
--wm-radius-xs: 8px;
--wm-shadow-md: 0 18px 45px rgba(17, 24, 39, 0.10);
--wm-shadow-sm: 0 8px 24px rgba(17, 24, 39, 0.06);
--wm-transition: all 0.22s ease;
--wm-font-primary: 'Vazirmatn', system-ui, sans-serif;
--wm-font-size-base: 15px;
--wm-font-weight-heading: 800;
--wm-space-2: 8px; --wm-space-3: 12px; --wm-space-4: 16px;
--wm-space-5: 20px; --wm-space-6: 24px; --wm-space-8: 32px;
```

Existing input style for reference:
- border: 1px solid rgb(193, 178, 164)
- border-radius: 14px
- CTA button: background #111827, color #fff, border-radius: 14px
- Font: Vazirmatn

---

## HTML/CSS CLASS STRUCTURE (existing — don't change these class names, only style them)

```html
<!-- Coupon toggle (currently outside layout, at top right — MUST BE MOVED) -->
<div class="woocommerce-form-coupon-toggle">
  <div class="woocommerce-info">
    کوپن تخفیف دارید؟ <a href="#" class="showcoupon">برای نوشتن کد اینجا کلیک کنید</a>
  </div>
</div>
<form class="checkout_coupon woocommerce-form-coupon"> ... </form>

<!-- Step navigation -->
<nav class="wm-checkout-steps">
  <button class="wm-checkout-steps__item is-active" data-checkout-step-target="address">
    <span>۱</span><strong>آدرس</strong>
  </button>
  <button class="wm-checkout-steps__item" data-checkout-step-target="shipping">
    <span>۲</span><strong>ارسال</strong>
  </button>
  <button class="wm-checkout-steps__item" data-checkout-step-target="payment">
    <span>۳</span><strong>پرداخت</strong>
  </button>
</nav>

<!-- Shipping methods (inside .wm-checkout-panel for shipping step) -->
<ul class="woocommerce-shipping-methods">
  <li>
    <input type="radio" name="shipping_method" class="shipping_method" />
    <label>نام روش ارسال — قیمت</label>
  </li>
</ul>

<!-- Payment methods -->
<ul class="wc_payment_methods payment_methods methods">
  <li class="wc_payment_method payment_method_pa_wallet pa-wc-disable-gateway">
    <input type="radio" name="payment_method" id="payment_method_pa_wallet" />
    <label for="payment_method_pa_wallet">
      <img src="..." /> کیف پول (موجودی: ۰ تومان)
    </label>
    <div class="payment_box payment_method_pa_wallet"> ... </div>
  </li>
  <li class="wc_payment_method payment_method_pa_zarinpal">
    <input type="radio" name="payment_method" id="payment_method_pa_zarinpal" />
    <label for="payment_method_pa_zarinpal">
      <img src="..." /> درگاه زرین پال
    </label>
  </li>
  <!-- more payment methods: pa_snappypay, pa_trbpay -->
</ul>

<!-- Select fields -->
<select name="billing_country" class="country_to_state country_select select2-hidden-accessible"> ... </select>
<select name="billing_city" class="select2-hidden-accessible"> ... </select>
<select name="billing_state" class="state_select select2-hidden-accessible"> ... </select>

<!-- Terms checkbox -->
<div class="woocommerce-terms-and-conditions-wrapper">
  <div class="woocommerce-privacy-policy-text"> ... privacy text ... </div>
  <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
    <input type="checkbox" name="terms" id="terms" />
    <span>شرایط وب‌سایت و مقررات را مطالعه کرده‌ام و می‌پذیرم *</span>
  </label>
</div>

<!-- Order summary sidebar -->
<div class="wm-checkout-review-box"> ... </div>
```

---

## PROBLEMS TO FIX — implement ALL of these:

---

### FIX 1 — COUPON CODE: Relocate & Redesign

**Problem:** The coupon is a tiny text link floating outside the layout at the top-right of the page. It is invisible on mobile and has no brand styling.

**Solution:** 
1. Use CSS to `display: none` the original `.woocommerce-form-coupon-toggle` in its current position.
2. Create a styled coupon card that is injected via JavaScript INSIDE the order summary sidebar (`.wm-checkout-review-box`), BELOW the order total row.
3. Style it as:

```css
/* Coupon block — inject inside order summary */
.wm-coupon-block {
  margin-top: var(--wm-space-4);
  border-top: 1px solid var(--wm-color-border);
  padding-top: var(--wm-space-4);
}

.wm-coupon-toggle-btn {
  background: none;
  border: none;
  color: var(--wm-color-accent-dark);
  font-family: var(--wm-font-primary);
  font-size: 13px;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  gap: 6px;
  direction: rtl;
}

.wm-coupon-toggle-btn::before {
  content: "🏷️";
}

.wm-coupon-input-row {
  display: flex;
  gap: 8px;
  margin-top: var(--wm-space-3);
  direction: rtl;
}

.wm-coupon-input-row input[type="text"] {
  flex: 1;
  border: 1px solid var(--wm-color-border);
  border-radius: var(--wm-radius-sm);
  padding: 10px 14px;
  font-family: var(--wm-font-primary);
  font-size: 13px;
  background: var(--wm-color-surface);
  color: var(--wm-color-text);
  direction: rtl;
  text-align: right;
}

.wm-coupon-input-row input[type="text"]:focus {
  outline: none;
  border-color: var(--wm-color-accent);
  box-shadow: 0 0 0 3px rgba(193, 164, 136, 0.15);
}

.wm-coupon-input-row button {
  background: var(--wm-color-cta);
  color: #fff;
  border: none;
  border-radius: var(--wm-radius-sm);
  padding: 10px 18px;
  font-family: var(--wm-font-primary);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: var(--wm-transition);
}

.wm-coupon-input-row button:hover {
  background: #374151;
}

/* Success state */
.wm-coupon-success {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--wm-color-success-bg);
  border: 1px solid #A6F4C5;
  border-radius: var(--wm-radius-sm);
  padding: 10px 14px;
  color: var(--wm-color-success-text);
  font-size: 13px;
  margin-top: var(--wm-space-3);
  direction: rtl;
}
```

**JavaScript** — move the coupon form and wire it up:
```javascript
// On DOMContentLoaded
// Move .checkout_coupon form inside .wm-checkout-review-box
// Create a toggle button that shows/hides the input row
// On coupon apply success (WooCommerce updated_checkout event), show success badge
```

---

### FIX 2 — SELECT FIELDS (City / Province / Country): Custom Styled Dropdown

**Problem:** The site already loads Select2 (`select2.css` from WooCommerce) but the selects don't match the brand. The `.select2-hidden-accessible` class shows Select2 is already initialized — just override the Select2 CSS to match the theme.

**Solution:** Override Select2 styles completely:

```css
/* === Select2 Override — match brand === */
.select2-container--default .select2-selection--single {
  height: 52px !important;
  border: 1px solid var(--wm-color-border) !important;
  border-radius: 14px !important;
  background: var(--wm-color-surface) !important;
  display: flex !important;
  align-items: center !important;
  padding: 0 16px !important;
  transition: var(--wm-transition) !important;
}

.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
  border-color: var(--wm-color-accent) !important;
  box-shadow: 0 0 0 3px rgba(193, 164, 136, 0.18) !important;
  outline: none !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
  color: var(--wm-color-text) !important;
  font-family: var(--wm-font-primary) !important;
  font-size: var(--wm-font-size-base) !important;
  line-height: 52px !important;
  padding: 0 !important;
  direction: rtl !important;
  text-align: right !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 52px !important;
  left: 14px !important;
  right: auto !important;
  top: 0 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
  border-color: var(--wm-color-accent-dark) transparent transparent transparent !important;
  border-width: 6px 5px 0 5px !important;
}

.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
  border-color: transparent transparent var(--wm-color-accent-dark) transparent !important;
  border-width: 0 5px 6px 5px !important;
}

/* Dropdown panel */
.select2-container--default .select2-dropdown {
  border: 1px solid var(--wm-color-border) !important;
  border-radius: var(--wm-radius-md) !important;
  box-shadow: var(--wm-shadow-md) !important;
  background: var(--wm-color-surface) !important;
  overflow: hidden !important;
  direction: rtl !important;
}

/* Search box inside dropdown */
.select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid var(--wm-color-border) !important;
  border-radius: var(--wm-radius-sm) !important;
  font-family: var(--wm-font-primary) !important;
  font-size: 13px !important;
  padding: 8px 12px !important;
  direction: rtl !important;
  text-align: right !important;
  width: calc(100% - 24px) !important;
  margin: 12px !important;
  box-sizing: border-box !important;
}

.select2-container--default .select2-search--dropdown .select2-search__field:focus {
  outline: none !important;
  border-color: var(--wm-color-accent) !important;
}

/* Options */
.select2-container--default .select2-results__option {
  font-family: var(--wm-font-primary) !important;
  font-size: 14px !important;
  padding: 10px 16px !important;
  direction: rtl !important;
  text-align: right !important;
  transition: background 0.15s ease !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
  background: var(--wm-color-soft) !important;
  color: var(--wm-color-text) !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
  background: rgba(193, 164, 136, 0.15) !important;
  color: var(--wm-color-primary) !important;
  font-weight: 600 !important;
}
```

---

### FIX 3 — SHIPPING METHODS: Replace Radio Buttons with Cards

**Problem:** Default WooCommerce radio buttons inside a `<ul>` — no visual hierarchy, too small to tap on mobile.

**Solution:** Style the existing `<ul class="woocommerce-shipping-methods">` and its children as cards using pure CSS (no HTML change needed):

```css
/* === Shipping Method Cards === */
.woocommerce-shipping-methods {
  list-style: none !important;
  margin: 0 !important;
  padding: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  gap: var(--wm-space-3) !important;
}

.woocommerce-shipping-methods li {
  position: relative !important;
  display: block !important;
}

/* Hide native radio */
.woocommerce-shipping-methods li input[type="radio"] {
  position: absolute !important;
  opacity: 0 !important;
  width: 100% !important;
  height: 100% !important;
  top: 0 !important;
  left: 0 !important;
  margin: 0 !important;
  cursor: pointer !important;
  z-index: 2 !important;
}

/* Style label as card */
.woocommerce-shipping-methods li label {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: var(--wm-space-3) !important;
  border: 2px solid var(--wm-color-border) !important;
  border-radius: var(--wm-radius-md) !important;
  padding: var(--wm-space-4) var(--wm-space-5) !important;
  background: var(--wm-color-surface) !important;
  cursor: pointer !important;
  transition: var(--wm-transition) !important;
  font-family: var(--wm-font-primary) !important;
  font-size: 14px !important;
  color: var(--wm-color-text) !important;
  direction: rtl !important;
  position: relative !important;
}

/* Custom radio indicator on the card */
.woocommerce-shipping-methods li label::after {
  content: '' !important;
  width: 20px !important;
  height: 20px !important;
  border: 2px solid var(--wm-color-border) !important;
  border-radius: 50% !important;
  background: var(--wm-color-surface) !important;
  flex-shrink: 0 !important;
  transition: var(--wm-transition) !important;
  order: 999 !important;
}

/* Hover state */
.woocommerce-shipping-methods li label:hover {
  border-color: var(--wm-color-accent) !important;
  background: var(--wm-color-soft) !important;
}

/* Checked state — card selected */
.woocommerce-shipping-methods li input[type="radio"]:checked + label,
.woocommerce-shipping-methods li input[type="radio"]:checked ~ label {
  border-color: var(--wm-color-accent-dark) !important;
  background: rgba(193, 164, 136, 0.08) !important;
  font-weight: 600 !important;
}

.woocommerce-shipping-methods li input[type="radio"]:checked + label::after,
.woocommerce-shipping-methods li input[type="radio"]:checked ~ label::after {
  border-color: var(--wm-color-accent-dark) !important;
  background: var(--wm-color-accent-dark) !important;
  box-shadow: inset 0 0 0 4px var(--wm-color-surface) !important;
}

/* Shipping price — push to left (RTL: right side visually) */
.woocommerce-shipping-methods li label .woocommerce-Price-amount {
  margin-right: auto !important;
  font-weight: 700 !important;
  color: var(--wm-color-primary) !important;
  font-size: 15px !important;
}
```

---

### FIX 4 — PAYMENT METHODS: Replace Radio Buttons with Cards

**Problem:** Default browser radio buttons next to payment logos in a `<ul>`. Misaligned logos, bullet points, no visual selection feedback, looks like a default WooCommerce install.

**Solution:** Style the existing `.wc_payment_methods` as branded cards:

```css
/* === Payment Method Cards === */
.wc_payment_methods.payment_methods {
  list-style: none !important;
  margin: 0 !important;
  padding: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  gap: var(--wm-space-3) !important;
}

/* Hide the wallet option if balance is zero — JS handles this */
.pa-wc-disable-gateway {
  display: none !important;
}

.wc_payment_method {
  position: relative !important;
}

/* Hide native radio */
.wc_payment_method input[type="radio"] {
  position: absolute !important;
  opacity: 0 !important;
  width: 100% !important;
  height: 1px !important;
  top: 0 !important;
  left: 0 !important;
  margin: 0 !important;
}

/* Payment card label */
.wc_payment_method > label {
  display: flex !important;
  align-items: center !important;
  gap: var(--wm-space-3) !important;
  border: 2px solid var(--wm-color-border) !important;
  border-radius: var(--wm-radius-md) !important;
  padding: var(--wm-space-4) var(--wm-space-5) !important;
  background: var(--wm-color-surface) !important;
  cursor: pointer !important;
  transition: var(--wm-transition) !important;
  direction: rtl !important;
  width: 100% !important;
  box-sizing: border-box !important;
  position: relative !important;
  user-select: none !important;
}

/* Payment logo */
.wc_payment_method > label img {
  width: auto !important;
  height: 32px !important;
  object-fit: contain !important;
  flex-shrink: 0 !important;
  border-radius: 6px !important;
}

/* Payment name text */
.wc_payment_method > label span,
.wc_payment_method > label {
  font-family: var(--wm-font-primary) !important;
  font-size: 14px !important;
  color: var(--wm-color-text) !important;
  font-weight: 500 !important;
}

/* Custom radio indicator */
.wc_payment_method > label::after {
  content: '' !important;
  width: 20px !important;
  height: 20px !important;
  border: 2px solid var(--wm-color-border) !important;
  border-radius: 50% !important;
  background: var(--wm-color-surface) !important;
  flex-shrink: 0 !important;
  transition: var(--wm-transition) !important;
  margin-right: auto !important; /* pushes to left in RTL */
}

/* Hover state */
.wc_payment_method > label:hover {
  border-color: var(--wm-color-accent) !important;
  background: var(--wm-color-soft) !important;
}

/* Selected state */
.wc_payment_method input[type="radio"]:checked ~ label,
.wc_payment_method input[type="radio"]:checked + label {
  border-color: var(--wm-color-accent-dark) !important;
  background: rgba(193, 164, 136, 0.08) !important;
}

.wc_payment_method input[type="radio"]:checked ~ label::after,
.wc_payment_method input[type="radio"]:checked + label::after {
  border-color: var(--wm-color-accent-dark) !important;
  background: var(--wm-color-accent-dark) !important;
  box-shadow: inset 0 0 0 4px var(--wm-color-surface) !important;
}

/* Payment description box that opens below selected */
.payment_box {
  background: var(--wm-color-soft) !important;
  border: 1px solid var(--wm-color-border) !important;
  border-top: none !important;
  border-radius: 0 0 var(--wm-radius-md) var(--wm-radius-md) !important;
  padding: var(--wm-space-4) var(--wm-space-5) !important;
  font-family: var(--wm-font-primary) !important;
  font-size: 13px !important;
  color: var(--wm-color-muted) !important;
  direction: rtl !important;
  margin-top: -6px !important;
}

/* Wallet — show only if balance > 0 (toggled via JS) */
.wm-wallet-has-balance {
  display: block !important;
}
```

**JavaScript — show wallet only if balance > 0:**
```javascript
// On DOMContentLoaded
const walletLabel = document.querySelector('.payment_method_pa_wallet label');
if (walletLabel) {
  const walletText = walletLabel.textContent || '';
  const balanceMatch = walletText.match(/[\d,،]+/);
  const balance = balanceMatch ? parseInt(balanceMatch[0].replace(/[,،]/g, '')) : 0;
  if (balance <= 0) {
    document.querySelector('.payment_method_pa_wallet').style.display = 'none';
  }
}
```

---

### FIX 5 — TERMS CHECKBOX: Custom Branded Checkbox

**Problem:** Default browser checkbox — tiny, off-brand, hard to tap on mobile.

**Solution:**

```css
/* === Custom Terms Checkbox === */
.woocommerce-terms-and-conditions-wrapper {
  margin-top: var(--wm-space-5) !important;
  padding: var(--wm-space-4) var(--wm-space-5) !important;
  background: var(--wm-color-soft) !important;
  border-radius: var(--wm-radius-md) !important;
  border: 1px solid var(--wm-color-border) !important;
}

.woocommerce-privacy-policy-text {
  font-size: 12px !important;
  color: var(--wm-color-muted) !important;
  font-family: var(--wm-font-primary) !important;
  line-height: 1.8 !important;
  margin-bottom: var(--wm-space-3) !important;
  direction: rtl !important;
  text-align: right !important;
}

.woocommerce-form__label-for-checkbox {
  display: flex !important;
  align-items: center !important;
  gap: var(--wm-space-3) !important;
  cursor: pointer !important;
  direction: rtl !important;
  width: 100% !important;
  padding: var(--wm-space-2) 0 !important;
}

/* Hide native checkbox */
.woocommerce-form__label-for-checkbox input[type="checkbox"] {
  position: absolute !important;
  opacity: 0 !important;
  width: 0 !important;
  height: 0 !important;
}

/* Custom checkbox box */
.woocommerce-form__label-for-checkbox span {
  display: flex !important;
  align-items: center !important;
  gap: var(--wm-space-3) !important;
  font-family: var(--wm-font-primary) !important;
  font-size: 13px !important;
  color: var(--wm-color-text) !important;
  font-weight: 500 !important;
}

.woocommerce-form__label-for-checkbox span::before {
  content: '' !important;
  width: 22px !important;
  height: 22px !important;
  min-width: 22px !important;
  border: 2px solid var(--wm-color-border) !important;
  border-radius: var(--wm-radius-xs) !important;
  background: var(--wm-color-surface) !important;
  transition: var(--wm-transition) !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.woocommerce-form__label-for-checkbox input[type="checkbox"]:checked + span::before {
  background: var(--wm-color-cta) !important;
  border-color: var(--wm-color-cta) !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 10'%3E%3Cpath d='M1 5l3.5 3.5L11 1' stroke='white' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
  background-repeat: no-repeat !important;
  background-position: center !important;
  background-size: 12px !important;
}

.woocommerce-form__label-for-checkbox:hover span::before {
  border-color: var(--wm-color-accent-dark) !important;
}
```

---

### FIX 6 — STEP INDICATOR: Make it More Visual & Branded

**Problem:** Current step nav looks like a plain tab bar. No icons, no completion states, too generic.

```css
/* === Checkout Steps === */
.wm-checkout-steps {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
  background: var(--wm-color-surface) !important;
  border-radius: var(--wm-radius-lg) !important;
  padding: var(--wm-space-2) !important;
  box-shadow: var(--wm-shadow-sm) !important;
  position: relative !important;
  direction: rtl !important;
}

.wm-checkout-steps__item {
  flex: 1 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: var(--wm-space-2) !important;
  padding: var(--wm-space-3) var(--wm-space-4) !important;
  border-radius: var(--wm-radius-md) !important;
  background: transparent !important;
  border: none !important;
  cursor: default !important;
  transition: var(--wm-transition) !important;
  position: relative !important;
}

.wm-checkout-steps__item span {
  width: 28px !important;
  height: 28px !important;
  border-radius: 50% !important;
  background: var(--wm-color-soft) !important;
  border: 2px solid var(--wm-color-border) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 12px !important;
  font-weight: 700 !important;
  color: var(--wm-color-muted) !important;
  flex-shrink: 0 !important;
  transition: var(--wm-transition) !important;
}

.wm-checkout-steps__item strong {
  font-family: var(--wm-font-primary) !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  color: var(--wm-color-muted) !important;
  transition: var(--wm-transition) !important;
}

/* Active step */
.wm-checkout-steps__item.is-active {
  background: var(--wm-color-cta) !important;
}

.wm-checkout-steps__item.is-active span {
  background: rgba(255,255,255,0.2) !important;
  border-color: rgba(255,255,255,0.3) !important;
  color: #fff !important;
}

.wm-checkout-steps__item.is-active strong {
  color: #fff !important;
}

/* Completed step */
.wm-checkout-steps__item.is-done {
  cursor: pointer !important;
}

.wm-checkout-steps__item.is-done span {
  background: var(--wm-color-success-bg) !important;
  border-color: var(--wm-color-success-text) !important;
  color: var(--wm-color-success-text) !important;
}

.wm-checkout-steps__item.is-done strong {
  color: var(--wm-color-success-text) !important;
}

/* Connector line between steps (pseudo element on container) */
.wm-checkout-steps::before {
  content: '' !important;
  position: absolute !important;
  top: 50% !important;
  right: 20% !important;
  left: 20% !important;
  height: 1px !important;
  background: var(--wm-color-border) !important;
  transform: translateY(-50%) !important;
  z-index: 0 !important;
  pointer-events: none !important;
}
```

---

### FIX 7 — MOBILE RESPONSIVE FIXES

```css
@media (max-width: 768px) {
  /* Order summary collapses to accordion on mobile */
  .wm-checkout-sidebar {
    order: -1 !important;
  }

  /* Mobile: single column form fields */
  .wm-checkout-fields__group {
    grid-template-columns: 1fr !important;
  }

  /* Sticky CTA button on mobile */
  .wm-checkout-panel__actions {
    position: sticky !important;
    bottom: 0 !important;
    background: var(--wm-color-surface) !important;
    padding: var(--wm-space-4) var(--wm-space-4) calc(var(--wm-space-4) + env(safe-area-inset-bottom)) !important;
    box-shadow: 0 -4px 20px rgba(17, 24, 39, 0.08) !important;
    z-index: 10 !important;
    border-top: 1px solid var(--wm-color-border) !important;
  }

  /* Payment cards full width on mobile */
  .wc_payment_methods.payment_methods {
    grid-template-columns: 1fr !important;
  }

  /* Step nav text: hide text on very small screens, show only numbers */
  @media (max-width: 380px) {
    .wm-checkout-steps__item strong {
      display: none !important;
    }
  }

  /* Select2 full width */
  .select2-container {
    width: 100% !important;
  }

  /* Shipping cards full width, min touch target */
  .woocommerce-shipping-methods li label {
    min-height: 56px !important;
  }

  /* Coupon input stacks vertically on mobile */
  .wm-coupon-input-row {
    flex-direction: column !important;
  }

  .wm-coupon-input-row button {
    width: 100% !important;
    padding: 12px !important;
  }
}
```

---

### FIX 8 — COUPON SECTION: JavaScript Logic

```javascript
document.addEventListener('DOMContentLoaded', function () {

  // === 1. Hide original coupon toggle & move form ===
  const originalToggle = document.querySelector('.woocommerce-form-coupon-toggle');
  const couponForm = document.querySelector('.checkout_coupon.woocommerce-form-coupon');
  const reviewBox = document.querySelector('.wm-checkout-review-box');

  if (originalToggle) originalToggle.style.display = 'none';

  if (reviewBox && couponForm) {
    // Build the new coupon UI
    const couponBlock = document.createElement('div');
    couponBlock.className = 'wm-coupon-block';
    couponBlock.innerHTML = `
      <button type="button" class="wm-coupon-toggle-btn" aria-expanded="false">
        کد تخفیف دارید؟
      </button>
      <div class="wm-coupon-input-row" style="display:none;">
        <input type="text" id="wm_coupon_code" placeholder="کد تخفیف را وارد کنید" dir="rtl" />
        <button type="button" id="wm_apply_coupon">اعمال</button>
      </div>
      <div class="wm-coupon-success" id="wm_coupon_success" style="display:none;"></div>
    `;
    reviewBox.appendChild(couponBlock);

    // Toggle show/hide input
    const toggleBtn = couponBlock.querySelector('.wm-coupon-toggle-btn');
    const inputRow = couponBlock.querySelector('.wm-coupon-input-row');
    toggleBtn.addEventListener('click', function () {
      const isOpen = inputRow.style.display !== 'none';
      inputRow.style.display = isOpen ? 'none' : 'flex';
      toggleBtn.setAttribute('aria-expanded', !isOpen);
    });

    // Apply coupon — trigger original WooCommerce mechanism
    document.getElementById('wm_apply_coupon').addEventListener('click', function () {
      const code = document.getElementById('wm_coupon_code').value.trim();
      if (!code) return;
      // Fill WooCommerce's own coupon input and submit
      const wcInput = couponForm.querySelector('[name="coupon_code"]');
      const wcBtn = couponForm.querySelector('[name="apply_coupon"]');
      if (wcInput && wcBtn) {
        wcInput.value = code;
        wcBtn.click();
      }
    });

    // Listen for WooCommerce checkout update
    jQuery(document.body).on('applied_coupon', function (e, couponCode) {
      const successEl = document.getElementById('wm_coupon_success');
      successEl.innerHTML = `✓ کد تخفیف <strong>${couponCode}</strong> با موفقیت اعمال شد`;
      successEl.style.display = 'flex';
      inputRow.style.display = 'none';
    });
  }

  // === 2. Add is-done class to completed steps ===
  // (Hook into WooCommerce's step switching logic)
  document.querySelectorAll('.wm-checkout-steps__item').forEach(btn => {
    btn.addEventListener('click', function () {
      // When a step becomes active, mark previous ones as done