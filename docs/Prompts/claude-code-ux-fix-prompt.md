# Claude Code Prompt — UX/UI & Frontend Fix for zaniziba.com Product Page

---

## 🧠 Your Skills & Role

You are a **Senior UX/UI Designer and Frontend Developer** with the following expertise:

- **UX/UI Design**: Information architecture, conversion-focused e-commerce design, RTL (right-to-left) layout design, accessibility (WCAG 2.1 AA), visual hierarchy, micro-interactions, and mobile-first design thinking.
- **Frontend Development**: HTML5, CSS3 (Flexbox, Grid, CSS custom properties), JavaScript (ES6+), responsive design (breakpoints: 375px mobile, 768px tablet, 1440px desktop), WooCommerce/WordPress theme customization, CSS specificity management, and performance optimization.
- **E-commerce UX**: Conversion rate optimization (CRO), product page best practices, trust signal design, variant selector UX, and checkout flow design.
- **RTL/Persian Web Design**: Deep understanding of right-to-left typography, Persian numeral formatting, and RTL layout conventions.

Your task is to **analyze each problem listed below**, **propose a clear design/code idea or solution for each**, and then **implement the fixes** using HTML, CSS, and JavaScript (or WordPress/WooCommerce PHP/template hooks where applicable). Where visual redesign is needed, produce the new CSS and markup. Where behavior is broken, produce the JavaScript fix.

---

## 🌐 Site Context

- **URL**: https://zaniziba.com/shop/رژ-لب-مدادی-مات-آنی-annie/
- **Platform**: WordPress + WooCommerce
- **Language**: Persian (Farsi) — **RTL layout**
- **Audience**: Persian-speaking women, primarily shopping on mobile
- **Product type**: Cosmetics — lip liner with 16 color variants
- **Theme**: Custom WooCommerce theme with RTL support

---

## 🐛 Problems to Fix (27 Issues)

For **each problem below**, you must:
1. ✅ **Propose an idea** — describe the design/UX solution in 1–2 sentences
2. 🛠️ **Implement the fix** — write the actual HTML/CSS/JS/PHP code

---

### DESKTOP (1440px)

**Problem 1 — Reversed RTL column order**
On a Persian RTL site, the product image should be on the RIGHT side and the product info (title, price, CTA) on the LEFT. Currently they are reversed. Fix the two-column flex/grid layout to respect RTL reading flow.

**Problem 2 — Product image too small, excessive whitespace in image column**
The image doesn't fill its container. The column below the image is a large empty beige block. Fix the image to be larger and better centered, and remove unnecessary empty space.

**Problem 3 — Only one product image, no gallery or thumbnails**
There is a single product image with no thumbnail strip. Add a thumbnail gallery component below the main image (even if placeholder slots) with a main image swap interaction on click.

**Problem 4 — Product title centered but info cards are left-aligned — inconsistent hierarchy**
The `<h1>` title uses `text-align: center` while all content below is left/right aligned. Align the title consistently with the rest of the content. In RTL, it should be right-aligned or have a clear visual anchor.

**Problem 5 — Wrong content order: description appears before price and CTA**
The page order is: Title → Description → Brand → Price → Variants → CTA. Fix the order to follow e-commerce best practice: Title → Badges/Tags → Price → Stock status → Variants → Quantity → CTA → Trust badges → Description.

**Problem 6 — Variant label in English on a Persian page**
The color variant label "annie Lip Pencil Crayon W" is in English. Replace or localize this label to Persian (e.g., "انتخاب رنگ:") and ensure all UI labels are in Persian.

**Problem 7 — Color swatches have no tooltip or shade name on hover**
Hovering a swatch shows no label. Add a CSS/JS tooltip that shows the shade name (e.g., "No.05 — رنگ شماره ۵") on hover and on tap for mobile.

**Problem 8 — Price range is confusing before variant selection**
Showing "767,000 تومان — 997,100 تومان" without explanation confuses users. Add a small helper text below the price like "قیمت بسته به رنگ انتخابی متفاوت است" (price varies by selected color) and update the price dynamically when a swatch is selected.

**Problem 9 — Add to Cart button is below the fold on page load**
The primary CTA is not visible on load without scrolling. Restructure the product info column so the CTA button appears within the first viewport. Consider a sticky CTA bar that appears on scroll.

**Problem 10 — Quantity selector is unlabeled and visually disconnected**
The +/− stepper has no visible label above it and floats separately from the Add to Cart button. Redesign the quantity + CTA row to be a single unified row with a clear label "تعداد:" above the stepper.

**Problem 11 — No wishlist / save-for-later button**
Add a heart icon (wishlist) button positioned near the product title or next to the CTA button. Use a toggle animation (empty ↔ filled heart) for the saved state.

**Problem 12 — Footer is too minimal, lacks trust signals**
The footer only has 3 links and a copyright. Add: payment method icon strip (Visa, Mastercard, etc.), a "ضمانت بازگشت کالا" (return guarantee) note, social media icon links, and a newsletter email signup field.

---

### TABLET (768px)

**Problem 13 — Layout does not reflow to single-column on tablet**
At 768px the two-column layout stays. Add a CSS breakpoint at 768px that stacks the image column above the info column in a single-column layout.

**Problem 14 — No hamburger menu on tablet — nav overflows**
The full horizontal nav is visible at 768px and overflows. Add a hamburger menu toggle for viewports ≤ 992px that collapses the nav into a slide-in drawer.

**Problem 15 — Image column takes 50% width on tablet, leaving info column too narrow**
At tablet width the image column is disproportionately large. In tablet single-column layout, the image should be full width (100%) and the info panel below it full width too.

**Problem 16 — Color swatches wrap unevenly on tablet**
Swatches wrap onto 3 rows and are unevenly spaced. Use CSS `flex-wrap: wrap` with consistent `gap` values and ensure swatches align neatly in a grid pattern.

---

### MOBILE (375px)

**Problem 17 — Page does not fully adapt at 375px**
The layout doesn't reflow on mobile. Ensure a proper 375px breakpoint is set with full single-column stacking, appropriate font sizes (min 16px for body), and no horizontal overflow/scroll.

**Problem 18 — Duplicate variant selectors: mobile bar + page form both visible**
There is a mobile sticky purchase bar AND the main product form both rendered simultaneously, resulting in two identical variant selectors and two Add to Cart buttons. On mobile, hide the main form's variant section and quantity/CTA area, and show only the sticky bottom bar instead.

**Problem 19 — No mobile hamburger navigation**
Same as Problem 14 but ensure it works at 375px too. The mobile nav must be a full hamburger drawer.

**Problem 20 — Image does not stack above content on mobile**
On mobile the image stays in a side column making both image and text tiny. Force the image to be full-width and stack above all product info on screens ≤ 767px.

**Problem 21 — Touch targets too small (swatches ~28px, +/− buttons too small)**
All interactive elements must meet the 44×44px minimum touch target. Increase swatch size to at least 44×44px on mobile and increase the +/− button hit area using padding.

---

### CROSS-CUTTING (all breakpoints)

**Problem 22 — No product rating shown near price**
Add a star rating display (using CSS stars or SVG) near the product title/price area. If no reviews exist, show "اولین نفر باشید که نظر می‌دهید" as a CTA link.

**Problem 23 — Duplicate form in DOM (main + mobile bar)**
The entire WooCommerce add-to-cart form is duplicated in the HTML for mobile/desktop. Refactor so there is only ONE form in the DOM, and use CSS/JS to reposition it between the product panel and the sticky bar depending on screen size, rather than duplicating markup.

**Problem 24 — "هیچکدام" (None) label appears as stray text below swatches**
This is the variant clear/reset link but it renders as bare unstyled text. Redesign it as a small pill button "✕ پاک کردن انتخاب" with proper styling.

**Problem 25 — SKU displayed prominently to customers**
Move the SKU to a collapsed "اطلاعات بیشتر" (more info) section or a small muted meta line, not a full-width visible row.

**Problem 26 — Payment installment info lacks visual hierarchy**
The ترب‌پی and اسنپ‌پی rows look like floating unrelated text. Wrap them in a clearly labelled "💳 پرداخت اقساطی" section card with icons, amounts, and a subtle border/background.

**Problem 27 — No back-to-top button**
Add a fixed-position "↑" back-to-top button that appears after the user scrolls 400px down. Use smooth scroll behavior and a fade-in/fade-out CSS transition.

---

## 📦 Deliverables Expected

For each of the 27 problems:
- A brief **idea/rationale** comment in the code
- The actual **implementation** (CSS, JS, HTML, or PHP/WooCommerce template override)

Organize output by:
1. `style.css` — all CSS fixes grouped by breakpoint
2. `scripts.js` — all JavaScript/behavior fixes
3. `template-overrides/` — any WooCommerce PHP template changes
4. A `CHANGES.md` summary of every fix

Prioritize **mobile-first CSS** (base styles for mobile, then `@media (min-width: ...)` for tablet and desktop).

Use **CSS custom properties** for colors and spacing so the design system is easy to update.

Ensure all fixes are **RTL-safe** (use `margin-inline-start`, `padding-inline-end`, `text-align: start`, `direction: rtl`, etc.).
