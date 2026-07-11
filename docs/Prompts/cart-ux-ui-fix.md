You are a senior full-stack developer and UX engineer working on a WooCommerce-based Persian (RTL) e-commerce website called "زنی زیبا" (zaniziba.com). The site uses WordPress + WooCommerce with a custom theme. Your task is to fix, redesign, and improve the cart page (/cart/) to resolve all identified UX/UI problems across desktop, tablet, and mobile breakpoints.

The site is RTL (right-to-left), uses Persian language, and the currency is Toman (تومان).

---

## PROBLEMS TO FIX

### 1. RESPONSIVE LAYOUT — CRITICAL
The cart page has NO responsive behavior. The two-column desktop layout (product table + order summary) does NOT adapt to tablet (768px) or mobile (375px).

**Fix:**
- On mobile (< 768px): Stack columns vertically. Order summary goes BELOW the product list.
- On tablet (768px–1024px): Keep two columns but adjust proportions (60%/40%).
- On desktop (> 1024px): Keep current two-column layout but fix proportions.
- Convert the product table into card-style layout on mobile (one card per product showing image, name, quantity controls, and price).
- Add horizontal scroll prevention — no element should cause overflow on any screen size.

---

### 2. PRODUCT TABLE — MOBILE CARD REDESIGN
On mobile, replace the HTML table with a card-based layout per product:

Each card should contain:
- Product thumbnail (left side, 80x80px minimum)
- Product name (bold, truncated with ellipsis at 2 lines)
- Unit price label + value
- Quantity stepper (− / number / + buttons, minimum 44px touch target each)
- Subtotal (highlighted in brand color)
- Remove (×) button in top-right corner of the card

---

### 3. PRODUCT IMAGE SIZE
On desktop and tablet, the product thumbnail in the cart table is too small.

**Fix:**
- Minimum size: 80×80px on desktop, 70×70px on tablet.
- Make the image clickable (link to product page).
- Add a subtle border-radius (8px) and box-shadow to make images look polished.

---

### 4. "UPDATE CART" BUTTON VISIBILITY
The "به‌روزرسانی سبد" button is invisible/disabled-looking by default.

**Fix:**
- Hide the button by default.
- Show it (with an animated appearance) only when the user changes a quantity input.
- Style it clearly: use the site's primary color with full opacity, minimum height 44px.

---

### 5. ADDRESS DISPLAY IN ORDER SUMMARY
The full raw shipping address is displayed inside the order summary box including raw postal codes.

**Fix:**
- Truncate the address to city + province only (e.g., "گیلان، آستارا").
- Add a small "ویرایش آدرس" (Edit Address) link next to it that opens /my-account/edit-address/.
- Never display raw postal codes or full street addresses in the cart summary.

---

### 6. SHIPPING COST DYNAMIC UPDATE
When the user selects a shipping method (e.g., پست پیشتاز = 206,500 تومان), the "جمع کل" total does NOT update.

**Fix:**
- Use WooCommerce's built-in AJAX cart update or add a JS event listener on shipping radio buttons.
- Dynamically recalculate and re-render the total when shipping method changes.
- Show a loading spinner on the total while recalculating.
- Ensure "تیپاکس – پس کرایه" also shows a price or the label "رایگان" if it is free.

---

### 7. PROGRESS STEPPER
Add a checkout progress indicator at the top of the cart page.

**Implementation:**
- 3 steps: (1) سبد خرید → (2) اطلاعات ارسال → (3) پرداخت
- Step 1 is active/highlighted on this page.
- Use simple numbered circles with step labels beneath them.
- Connected by a horizontal line between steps.
- RTL layout (right to left: step 1 on the right).
- On mobile, show only step icons without text labels to save space.

---

### 8. CALL-TO-ACTION BUTTON TEXT
The main CTA button reads "ادامه فرایند خرید" but navigates to the checkout/payment page.

**Fix:**
- Rename it to: "پرداخت و تکمیل سفارش"
- Make it full-width on all breakpoints.
- Minimum height: 52px.
- Use a strong contrast color (dark or brand primary).
- Add a lock icon (🔒 or SVG) to the right of the text to signal security.

Add a secondary text link below it: "← ادامه خرید" linking back to /shop/.

---

### 9. BOTTOM NAVIGATION BAR (MOBILE)
The mobile bottom navigation bar (نوار پایین موبایل) exists in the DOM but is not consistently visible.

**Fix:**
- Ensure it is always visible on screens < 768px, fixed at the bottom (position: fixed; bottom: 0).
- Add safe-area-inset-bottom padding for iOS devices.
- Add z-index: 9999 so it never goes behind content.
- Highlight the "سبد خرید" icon as active on this page.
- Add a padding-bottom to the main content equal to the nav bar height (≈ 64px) to prevent content being hidden behind it.

---

### 10. DISCOUNT CODE SECTION
The coupon field and "اعمال" button are too cramped, especially on mobile.

**Fix:**
- On mobile: Stack the input and button vertically (full-width each).
- On tablet/desktop: Keep them side by side but increase input height to 48px.
- Add a success/error message area below the field (green for success, red for error).
- Show a loading spinner on the "اعمال" button while the coupon is being validated.

---

### 11. HEADER — REDUCE WASTED SPACE
The page header ("سبد خرید" title area) has excessive empty space (large padding top/bottom).

**Fix:**
- Reduce the hero/banner section padding: max 32px top and bottom on desktop, 20px on mobile.
- Remove or repurpose the half-visible decorative element (the blurred golden/peach shape at the left edge of the header).
- Integrate the progress stepper (from fix #7) inside this header area to use the space purposefully.

---

### 12. NAVIGATION MENU — TABLET HAMBURGER
On tablet (768px), the full horizontal navigation menu overflows or gets too cramped.

**Fix:**
- At 768px and below, collapse the navigation into a hamburger menu (☰).
- The hamburger icon should appear on the right side of the logo (RTL convention).
- The drawer should slide in from the right.
- Include all menu items + the search, account, and cart icons inside the drawer.

---

### 13. EMPTY CART STATE
Currently there is no graceful empty cart design (for when items are removed).

**Fix:**
- Add an empty cart state with: a clear icon/illustration, the text "سبد خرید شما خالی است", and a large CTA button: "شروع خرید" linking to /shop/.

---

### 14. TOUCH TARGET SIZES
All interactive elements must meet the 44×44px minimum touch target requirement (Apple HIG / WCAG 2.5.5).

**Audit and fix:**
- Quantity input +/− buttons: minimum 44×44px
- Remove (×) button: minimum 44×44px
- Shipping radio buttons: increase label clickable area
- All links and icon buttons in mobile view

---

## TECH STACK & CONSTRAINTS
- WordPress + WooCommerce (do not break WooCommerce hooks or nonces)
- Theme modifications should go in: /wp-content/themes/[active-theme]/
- Use child theme if one exists, otherwise add to functions.php and style.css
- Custom CSS goes in: assets/css/cart-custom.css (enqueued via functions.php)
- Custom JS goes in: assets/js/cart-custom.js (enqueued via functions.php, defer)
- All text is RTL Persian — use `direction: rtl` and `font-family` with a Persian font (e.g., Vazirmatn)
- Do not use jQuery for new code — use vanilla JS or WooCommerce's existing jQuery where necessary for AJAX
- Breakpoints: Mobile < 768px | Tablet 768px–1024px | Desktop > 1024px
- Brand primary color: dark navy (#1a1a2e or current theme dark) and gold/orange accent (currently used for prices)
- Preserve all WooCommerce cart functionality: AJAX add/remove, nonce verification, quantity updates

---

## DELIVERABLES
1. `cart-custom.css` — All responsive and visual fixes
2. `cart-custom.js` — Dynamic behavior (quantity update button reveal, shipping total recalc, coupon spinner)
3. `woocommerce/cart/cart.php` — Modified WooCommerce cart template (overridden in theme)
4. `functions.php` additions — Enqueue scripts/styles, any filter/action hooks needed
5. Brief comment in each file explaining what was changed and why