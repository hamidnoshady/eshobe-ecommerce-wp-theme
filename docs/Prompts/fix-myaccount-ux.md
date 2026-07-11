# Claude Code Prompt — UX/UI Fix: My Account Page (zaniziba.com)

## Context & Stack

You are working on a **WordPress + WooCommerce** e-commerce store (`zaniziba.com`) using the custom theme **eshobe-ecommerce-wp-theme**. The site is **RTL (right-to-left)** Persian. The My Account page lives at `/my-account/` (WordPress page ID 11).

### Key files to modify:

| File | Purpose |
|---|---|
| `wp-content/themes/eshobe-ecommerce-wp-theme/assets/css/components/myaccount.css` | Primary account page styles |
| `wp-content/themes/eshobe-ecommerce-wp-theme/assets/css/theme.css` | Global theme styles |
| `wp-content/themes/eshobe-ecommerce-wp-theme/assets/css/tokens.css` | Design tokens / CSS variables |
| `wp-content/themes/eshobe-ecommerce-wp-theme/assets/css/components/mobile-nav.css` | Mobile bottom nav styles |
| `wp-content/themes/eshobe-ecommerce-wp-theme/assets/css/components/footer.css` | Footer styles |
| `wp-content/themes/eshobe-ecommerce-wp-theme/rtl.css` | RTL overrides |
| `wp-content/themes/eshobe-ecommerce-wp-theme/woocommerce/myaccount/dashboard.php` | WooCommerce dashboard template |
| `wp-content/themes/eshobe-ecommerce-wp-theme/woocommerce/myaccount/navigation.php` | WooCommerce nav template |
| `wp-content/themes/eshobe-ecommerce-wp-theme/functions.php` | Theme functions |
| `wp-content/themes/eshobe-ecommerce-wp-theme/page.php` or `single.php` | Page template (for H1 fix) |

### Key CSS classes (from live DOM inspection):

```
.wm-account-page                  → root section wrapper
.wm-account-page__container       → inner container
.wm-account-page__header          → hero/banner area (currently empty)
.wm-account-page__eyebrow         → "پنل کاربری" label
.wm-account-page__title           → H1 inside the banner (duplicate!)
.wm-account-layout                → CSS grid: 258px sidebar + 921px content
.woocommerce-MyAccount-navigation → sidebar nav element
.woocommerce-MyAccount-navigation-link           → each nav item <li>
.woocommerce-MyAccount-navigation-link.is-active → active nav item
.woocommerce-MyAccount-navigation-link--customer-logout → logout item
.woocommerce-MyAccount-content    → main content area
.wm-site-footer                   → footer wrapper
.wm-site-footer__inner            → footer inner container
.wm-mobile-nav                    → bottom mobile navigation bar
.entry-title                      → H1 in article (display: none — correct, keep hidden)
```

### Design tokens (from CSS variables):
```
--wm-color-primary: #111827   (dark text)
--wm-color-accent:  #c1a488   (warm gold — brand accent)
Logout red:         rgb(180, 35, 24)  →  #b42318
```

### Theme breakpoints used:
```
(max-width: 1023px)   → tablet
(max-width: 767px)    → mobile
(max-width: 480px)    → small mobile
(min-width: 1024px)   → desktop
```

---

## Issues to Fix — Prioritized

---

### 🔴 CRITICAL — Fix 1: Duplicate `<h1>` Tags

**Problem:** Two `<h1>` elements exist on the page simultaneously:
1. `.entry-title` → "حساب کاربری من" (from the WordPress page template — currently `display: none`, which is correct)
2. `.wm-account-page__title` (an `<h1>`) → "حساب کاربری" (inside the WooCommerce section)

Having two H1s is a semantic and SEO violation, even if one is visually hidden. Screen readers still parse hidden H1s.

**Fix:**
In `woocommerce/myaccount/` template or via PHP filter, change the `<h1 class="wm-account-page__title">` to `<h2 class="wm-account-page__title">`. The `.entry-title` H1 remains the single true H1 for the page. Remove the `display: none` from `.entry-title` OR keep it hidden via `visibility: hidden` + `position: absolute` (visually hidden but accessible).

```php
// In the account page template or hook:
// Change <h1 class="wm-account-page__title"> to <h2 class="wm-account-page__title">
```

```css
/* In myaccount.css — make the real H1 accessible but visually hidden */
.woocommerce-account .entry-title {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
```

**Acceptance criteria:** `document.querySelectorAll('h1').length === 1` on the page.

---

### 🔴 CRITICAL — Fix 2: Empty `<h2>` in DOM

**Problem:** `.wm-cart-drawer__title` is an `<h2>` with no text content. This creates a ghost heading node in the accessibility tree.

**Fix:** Either add a meaningful text label (e.g., "سبد خرید") or add `aria-hidden="true"` to the element, or replace it with a `<div>` if it serves no semantic purpose.

```php
// In cart drawer template, add aria-hidden or fill the title:
<h2 class="wm-cart-drawer__title" aria-label="سبد خرید"></h2>
// OR simply:
<div class="wm-cart-drawer__title" role="presentation"></div>
```

---

### 🔴 CRITICAL — Fix 3: No Responsive Collapse on Mobile & Tablet

**Problem:** The `.wm-account-layout` uses `grid-template-columns: 258px 921px` at all sizes. On mobile (390px) and tablet (768px), this forces a desktop 2-column layout that overflows or squishes content. The mobile bottom nav (`.wm-mobile-nav`) exists in the DOM but is `display: none` even at mobile widths.

**Fix in `myaccount.css`:**

```css
/* ── Tablet (≤ 1023px) ── */
@media (max-width: 1023px) {
  .wm-account-layout {
    grid-template-columns: 220px 1fr;
    gap: 16px;
  }
}

/* ── Mobile (≤ 767px) ── */
@media (max-width: 767px) {
  .wm-account-layout {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  /* Move sidebar above content on mobile */
  .woocommerce-MyAccount-navigation {
    order: 1;
    width: 100%;
    border-radius: 12px;
    margin-bottom: 16px;
  }

  .woocommerce-MyAccount-content {
    order: 2;
    width: 100%;
  }

  /* Show the mobile bottom nav bar */
  .wm-mobile-nav {
    display: flex !important;
    position: fixed;
    bottom: 0;
    right: 0;
    left: 0;
    z-index: 999;
    background: #fff;
    box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
    padding: 8px 0 env(safe-area-inset-bottom, 8px);
  }

  /* Add bottom padding to main content so it doesn't hide behind the fixed nav */
  .woocommerce-account .site-main {
    padding-bottom: 72px;
  }
}

/* ── Small mobile (≤ 480px) ── */
@media (max-width: 480px) {
  .wm-account-page__container {
    padding: 0 16px;
  }

  .wm-account-page__header {
    padding: 16px;
  }
}
```

**Acceptance criteria:** At 390px viewport width, the sidebar stacks above the content. At 768px the two columns remain but with `1fr` flexible column. The bottom nav bar is visible on mobile.

---

### 🔴 CRITICAL — Fix 4: Completely Empty Hero/Banner Area

**Problem:** `.wm-account-page__header` has `min-height: 102px`, transparent background, and `padding: 26px 34px`, but its only visible content is the eyebrow label and the heading. The left ~60% of this area is a blank beige space. This feels visually broken and wastes prime viewport real-estate above the fold.

**Fix options (choose one based on design direction):**

**Option A — Add a decorative background pattern/illustration:**
```css
/* In myaccount.css */
.wm-account-page__header {
  background-image: url('../../images/account-header-pattern.svg'); /* Add a subtle pattern SVG */
  background-repeat: no-repeat;
  background-position: left center; /* RTL: left is the decorative side */
  background-size: auto 100%;
  min-height: 140px;
}
```

**Option B — Add a personalized user welcome card inside the header (PHP):**
In `woocommerce/myaccount/dashboard.php` or the account header template, inject a welcome summary:

```php
<?php
$current_user = wp_get_current_user();
$order_count  = wc_get_customer_order_count( $current_user->ID );
$avatar_url   = get_avatar_url( $current_user->ID, ['size' => 64] );
?>
<div class="wm-account-hero">
  <img class="wm-account-hero__avatar"
       src="<?php echo esc_url( $avatar_url ); ?>"
       alt="<?php echo esc_attr( $current_user->display_name ); ?>"
       width="64" height="64" />
  <div class="wm-account-hero__info">
    <p class="wm-account-hero__greeting">خوش آمدید، <strong><?php echo esc_html( $current_user->display_name ); ?></strong></p>
    <p class="wm-account-hero__meta"><?php echo $order_count; ?> سفارش ثبت‌شده</p>
  </div>
</div>
```

```css
/* In myaccount.css */
.wm-account-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-direction: row-reverse; /* RTL: avatar on the right */
}

.wm-account-hero__avatar {
  border-radius: 50%;
  width: 64px;
  height: 64px;
  object-fit: cover;
  border: 2px solid var(--wm-color-accent);
}

.wm-account-hero__greeting {
  font-size: 1rem;
  color: var(--wm-color-primary);
  margin: 0 0 4px;
}

.wm-account-hero__meta {
  font-size: 0.85rem;
  color: #6b7280;
  margin: 0;
}
```

**Acceptance criteria:** The banner area is visually filled; no large blank space appears to the right/left of the headings.

---

### 🟠 HIGH — Fix 5: Active State Not Visually Distinguished in Sidebar Nav

**Problem:** The active nav item (`.is-active`) only changes the link color to the accent gold (`#c1a488`) and increases font-weight to `750`. There is no background highlight, left border indicator, or other shape-based affordance. On a beige background, the color-only distinction is insufficient for users with color vision deficiencies.

**Fix in `myaccount.css`:**

```css
/* Active nav item — add background + border indicator */
.woocommerce-MyAccount-navigation-link.is-active {
  background-color: rgba(193, 164, 136, 0.12);
  border-inline-start: 3px solid var(--wm-color-accent); /* RTL-safe */
  border-radius: 6px;
}

.woocommerce-MyAccount-navigation-link.is-active a {
  color: var(--wm-color-accent);
  font-weight: 750;
  padding-inline-start: 13px; /* compensate for the 3px border */
}

/* Hover state for non-active items */
.woocommerce-MyAccount-navigation-link:not(.is-active) a:hover {
  background-color: rgba(193, 164, 136, 0.06);
  color: var(--wm-color-accent);
  border-radius: 6px;
  transition: background-color 0.15s ease, color 0.15s ease;
}
```

**Acceptance criteria:** Active item has a visible background + side border. Visually distinguishable without relying on color alone (WCAG 1.4.1).

---

### 🟠 HIGH — Fix 6: Logout Link Styled as a Danger Action (Red Color)

**Problem:** `.woocommerce-MyAccount-navigation-link--customer-logout a` has `color: rgb(180, 35, 24)` (`#b42318`) — a strong red that signals error/danger. Logout is a secondary/low-frequency action that should not alarm the user.

**Fix in `myaccount.css`:**

```css
/* Restyle logout as a neutral secondary action */
.woocommerce-MyAccount-navigation-link--customer-logout a {
  color: #6b7280; /* neutral gray */
  font-weight: 400;
  font-size: 13px;
  opacity: 0.85;
}

.woocommerce-MyAccount-navigation-link--customer-logout a:hover {
  color: #b42318; /* only show red on hover as a subtle warning */
  opacity: 1;
}
```

**Acceptance criteria:** Logout text is neutral-colored in resting state, does not visually pop as the most prominent nav item.

---

### 🟠 HIGH — Fix 7: Four Duplicate Logout Links in the DOM

**Problem:** There are 4 separate logout links across the page:
1. WordPress admin bar: "بیرون رفتن"
2. WooCommerce sidebar: "خروج"
3. Inline in welcome paragraph: "خارج شوید"
4. Mobile account nav overlay: "خروج از حساب کاربری"

Items 1 and 3 are especially problematic. Item 1 is admin-only and fine. Item 3 (inline in greeting) creates confusion.

**Fix — Remove the inline logout from the greeting paragraph** by overriding the WooCommerce dashboard template:

Copy `woocommerce/templates/myaccount/dashboard.php` to `wp-content/themes/eshobe-ecommerce-wp-theme/woocommerce/myaccount/dashboard.php` and edit:

```php
<?php
// Original WooCommerce greeting line includes a logout link — remove it:
// BEFORE (do not use):
// printf( esc_html__( 'Hello %1$s (not %1$s? %2$s)', 'woocommerce' ), ... logout_link ... );

// AFTER — greeting without inline logout:
$display_name = wp_get_current_user()->display_name;
echo '<p>' . sprintf(
    /* translators: %s: user display name */
    esc_html__( 'سلام %s، خوش آمدید.', 'your-theme' ),
    '<strong>' . esc_html( $display_name ) . '</strong>'
) . '</p>';
?>
```

**Acceptance criteria:** The inline "(نیستید؟ خارج شوید)" is removed from the greeting text. Logout only appears in the sidebar nav and mobile nav.

---

### 🟠 HIGH — Fix 8: Large Empty White Area Between Content Box and Footer

**Problem:** After the account sidebar + content box, there is ~130–160px of empty white/beige space before the footer. The content box ends at roughly half-screen height, leaving an uncomfortable visual gap.

**Fix in `myaccount.css`:**

```css
/* Ensure the account layout stretches or the page background fills */
.woocommerce-account .site-main {
  min-height: calc(100vh - 200px); /* prevent premature footer */
}

.wm-account-page {
  padding-block-end: 48px; /* controlled bottom breathing room */
}

/* Optional: add a "continue shopping" CTA at the bottom of the content area */
```

**Option — Add a "Continue Shopping" banner** in the dashboard template:

```php
// At the bottom of dashboard.php:
?>
<div class="wm-account-cta">
  <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wm-account-cta__link">
    بازگشت به فروشگاه ←
  </a>
</div>
<?php
```

```css
.wm-account-cta {
  margin-top: 32px;
  padding: 20px 24px;
  background: rgba(193, 164, 136, 0.08);
  border-radius: 10px;
  text-align: center;
}

.wm-account-cta__link {
  color: var(--wm-color-accent);
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  transition: opacity 0.15s;
}

.wm-account-cta__link:hover {
  opacity: 0.75;
}
```

---

### 🟠 HIGH — Fix 9: Top Navigation Not Collapsing to Hamburger on Mobile/Tablet

**Problem:** The main site header navigation (برندها، آرایشی، اسپری و ادکلن، بهداشت شخصی و حمام، پیشنهادات ویژه) remains as a full horizontal list at all breakpoints including 390px mobile. There is no hamburger menu visible.

**Fix in `header.css` or `theme.css`:**

```css
@media (max-width: 1023px) {
  /* Hide desktop nav links on tablet and below */
  .wm-site-header nav > ul,
  .wm-site-header .wm-header__nav {
    display: none;
  }

  /* Show hamburger toggle button */
  .wm-header__menu-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    cursor: pointer;
  }
}
```

> **Note:** Check if `.wm-mobile-nav-shell` and `.wm-mobile-nav` are already wired up for this purpose. If so, ensure the toggle button's `display` is not hidden at tablet breakpoint and that the mobile drawer is properly triggered.

---

### 🟡 MEDIUM — Fix 10: Missing Alt Text on Gravatar Images

**Problem:** Two `<img>` elements (the Gravatar avatars) have `alt=""` — empty alt text. The visible avatar represents the logged-in user and should have meaningful alt text.

**Fix:**

If the Gravatar is generated via `get_avatar()` in PHP, pass the display name:

```php
// Instead of:
echo get_avatar( $user_id );

// Use:
echo get_avatar( $user_id, 64, '', $current_user->display_name );
```

Or use a filter to globally fix avatar alt text:

```php
// In functions.php:
add_filter( 'get_avatar', function( $avatar, $id_or_email, $size, $default, $alt ) {
    if ( empty( $alt ) && is_user_logged_in() ) {
        $user = wp_get_current_user();
        $avatar = str_replace( "alt=''", "alt='" . esc_attr( $user->display_name ) . "'", $avatar );
    }
    return $avatar;
}, 10, 5 );
```

**Acceptance criteria:** All `<img>` elements have non-empty, meaningful `alt` attributes.

---

### 🟡 MEDIUM — Fix 11: Footer Is Extremely Minimal

**Problem:** The footer (`wm-site-footer`) only contains a heading, one sentence of tagline, 3 links, and a copyright line. For an e-commerce site this lacks trust signals and secondary navigation.

**Fix — Expand footer template** (`footer.php` or relevant partial):

```php
<footer class="wm-site-footer wm-section-decor wm-section-decor--footer">
  <div class="wm-site-footer__inner">
    <div class="wm-site-footer__grid">

      <!-- Column 1: Brand -->
      <div class="wm-site-footer__brand">
        <a href="<?php echo home_url(); ?>">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png"
               alt="<?php bloginfo('name'); ?>" width="120" height="40" />
        </a>
        <p class="wm-site-footer__text">انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.</p>
        <!-- Social links -->
        <div class="wm-site-footer__social">
          <a href="#" aria-label="اینستاگرام" class="wm-site-footer__social-link"><!-- Instagram SVG icon --></a>
          <a href="#" aria-label="تلگرام"     class="wm-site-footer__social-link"><!-- Telegram SVG icon --></a>
          <a href="#" aria-label="واتساپ"     class="wm-site-footer__social-link"><!-- WhatsApp SVG icon --></a>
        </div>
      </div>

      <!-- Column 2: Shop links -->
      <div class="wm-site-footer__nav-group">
        <h3 class="wm-site-footer__nav-title">دسته‌بندی‌ها</h3>
        <ul>
          <li><a href="/product-category/آرایشی/">آرایشی</a></li>
          <li><a href="/product-category/اسپری-و-ادکلن/">اسپری و ادکلن</a></li>
          <li><a href="/product-category/بهداشت-شخصی-و-حمام/">بهداشت و حمام</a></li>
          <li><a href="/برندها/">برندها</a></li>
        </ul>
      </div>

      <!-- Column 3: Customer service -->
      <div class="wm-site-footer__nav-group">
        <h3 class="wm-site-footer__nav-title">خدمات مشتریان</h3>
        <ul>
          <li><a href="/faqs/">سوالات متداول</a></li>
          <li><a href="/contact/">تماس با ما</a></li>
          <li><a href="/my-account/orders/">پیگیری سفارش</a></li>
          <li><a href="/blog/">مجله</a></li>
        </ul>
      </div>

      <!-- Column 4: Trust badges -->
      <div class="wm-site-footer__trust">
        <h3 class="wm-site-footer__nav-title">اطمینان از خرید</h3>
        <ul class="wm-site-footer__badges">
          <li>✓ ضمانت اصالت کالا</li>
          <li>✓ ارسال سریع سراسری</li>
          <li>✓ پشتیبانی ۷ روز هفته</li>
          <li>✓ بازگشت آسان کالا</li>
        </ul>
      </div>

    </div><!-- /.wm-site-footer__grid -->

    <div class="wm-site-footer__bottom">
      <p>© <?php echo date_i18n('Y'); ?> فروشگاه آنلاین زنی زیبا. کلیه حقوق محفوظ است.</p>
    </div>
  </div>
</footer>
```

```css
/* In footer.css */
.wm-site-footer__grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 40px;
  padding: 48px 0 32px;
}

.wm-site-footer__nav-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--wm-color-primary);
  margin-bottom: 16px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.wm-site-footer__nav-group ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.wm-site-footer__nav-group a {
  color: #6b7280;
  font-size: 0.875rem;
  text-decoration: none;
  transition: color 0.15s;
}

.wm-site-footer__nav-group a:hover {
  color: var(--wm-color-accent);
}

.wm-site-footer__badges {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  font-size: 0.875rem;
  color: #6b7280;
}

.wm-site-footer__bottom {
  border-top: 1px solid rgba(0,0,0,0.08);
  padding: 20px 0;
  text-align: center;
  font-size: 0.8rem;
  color: #9ca3af;
}

.wm-site-footer__social {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.wm-site-footer__social-link {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(193, 164, 136, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--wm-color-accent);
  transition: background 0.15s;
}

.wm-site-footer__social-link:hover {
  background: rgba(193, 164, 136, 0.25);
}

/* Footer responsive */
@media (max-width: 1023px) {
  .wm-site-footer__grid {
    grid-template-columns: 1fr 1fr;
    gap: 32px;
  }
}

@media (max-width: 767px) {
  .wm-site-footer__grid {
    grid-template-columns: 1fr;
    gap: 24px;
    padding: 32px 0 24px;
  }
}
```

---

### 🟡 MEDIUM — Fix 12: "خارج شوید" Link Embedded in Welcome Greeting Text

**Problem:** The welcome paragraph reads: "سلام zaniziba (zaniziba نیستید؟ خارج شوید)" — a logout link is awkwardly placed mid-sentence in the greeting, making it look like an error message.

**Fix:** Already covered in Fix 7. Remove the inline logout from the greeting. If the "(not you?)" pattern must be kept for non-logged-in edge cases, style it as a very subtle `font-size: 12px; opacity: 0.5` footnote rather than inline in the main greeting.

---

### 🟡 MEDIUM — Fix 13: Touch Target Sizes on Nav Links (Verify)

**Problem:** Nav links in `.woocommerce-MyAccount-navigation a` have `min-height: 44px` (good) but `padding: 0px 16px` with no explicit `min-height` enforcement. Confirm the touch targets are consistently ≥ 44×44px.

**Fix in `myaccount.css`:**

```css
.woocommerce-MyAccount-navigation a {
  display: flex;
  align-items: center;
  min-height: 44px;
  padding: 0 16px;
  width: 100%;
  box-sizing: border-box;
}
```

**On mobile, increase tap targets:**
```css
@media (max-width: 767px) {
  .woocommerce-MyAccount-navigation a {
    min-height: 52px;
    font-size: 15px;
    padding: 0 20px;
  }
}
```

---

### 🟡 MEDIUM — Fix 14: Four Empty/Inaccessible Anchor Elements

**Problem:** `document.querySelectorAll('a')` returns 4 links with no `innerText` and no `aria-label` — likely icon-only links (social links, decorative arrows, etc.).

**Fix:** Audit each empty `<a>` and add `aria-label`:

```php
// For icon-only links:
<a href="https://instagram.com/zaniziba" aria-label="صفحه اینستاگرام زنی زیبا" class="...">
  <!-- SVG icon here -->
</a>
```

Or use `aria-hidden="true"` on the icon and a visually-hidden label span:

```html
<a href="/some-page/" class="icon-link">
  <svg aria-hidden="true" focusable="false">...</svg>
  <span class="sr-only">متن توضیحی</span>
</a>
```

```css
/* Utility: visually hidden but accessible */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
```

---

### 🟢 LOW — Fix 15: Dashboard Welcome Text is Generic

**Problem:** The dashboard intro paragraph ("از پیشخوان حساب کاربری خود می‌توانید...") is the default WooCommerce text. It gives the user no contextual value or personalized information.

**Fix — Override `dashboard.php` with a personalized summary:**

```php
<?php
$current_user     = wp_get_current_user();
$order_count      = wc_get_customer_order_count( $current_user->ID );
$pending_orders   = wc_get_orders(['customer' => $current_user->ID, 'status' => 'pending', 'limit' => 1]);
$processing_count = count( wc_get_orders(['customer' => $current_user->ID, 'status' => 'processing', 'limit' => -1]) );
?>

<div class="wm-dashboard-summary">
  <div class="wm-dashboard-stat">
    <span class="wm-dashboard-stat__value"><?php echo esc_html( $order_count ); ?></span>
    <span class="wm-dashboard-stat__label">کل سفارش‌ها</span>
  </div>
  <div class="wm-dashboard-stat">
    <span class="wm-dashboard-stat__value"><?php echo esc_html( $processing_count ); ?></span>
    <span class="wm-dashboard-stat__label">در حال پردازش</span>
  </div>
  <div class="wm-dashboard-stat">
    <a href="<?php echo wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ); ?>"
       class="wm-dashboard-stat__link">ویرایش پروفایل</a>
  </div>
</div>

<p class="wm-dashboard-welcome">
  از پیشخوان، می‌توانید
  <a href="<?php echo wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ); ?>">سفارش‌های خود</a> را پیگیری کنید،
  <a href="<?php echo wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ); ?>">آدرس‌های ارسال</a> را ویرایش کنید
  و <a href="<?php echo wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ); ?>">اطلاعات حساب</a> خود را به‌روز کنید.
</p>
```

```css
/* In myaccount.css */
.wm-dashboard-summary {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.wm-dashboard-stat {
  flex: 1;
  min-width: 120px;
  background: rgba(193, 164, 136, 0.08);
  border-radius: 10px;
  padding: 16px 20px;
  text-align: center;
}

.wm-dashboard-stat__value {
  display: block;
  font-size: 1.75rem;
  font-weight: 750;
  color: var(--wm-color-accent);
  line-height: 1;
  margin-bottom: 6px;
}

.wm-dashboard-stat__label {
  font-size: 0.8rem;
  color: #6b7280;
}

.wm-dashboard-stat__link {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 44px;
  color: var(--wm-color-accent);
  font-size: 0.875rem;
  text-decoration: none;
  font-weight: 600;
}

.wm-dashboard-welcome {
  font-size: 0.9rem;
  color: #6b7280;
  line-height: 1.7;
}
```

---

## Implementation Order

Execute fixes in this order to avoid regressions:

1. **Fix 1** (Duplicate H1) — PHP template change, no CSS risk
2. **Fix 2** (Empty H2) — PHP/template, no CSS risk
3. **Fix 3** (Responsive collapse) — CSS only, test across all breakpoints after
4. **Fix 7** (Remove inline logout) — PHP template, test logout flow still works
5. **Fix 5** (Active state) — CSS only
6. **Fix 6** (Logout color) — CSS only
7. **Fix 9** (Header nav hamburger) — CSS, test navigation functionality
8. **Fix 4** (Empty hero) — PHP + CSS, test on all breakpoints
9. **Fix 8** (Empty gap) — CSS
10. **Fix 10** (Alt text) — PHP filter in functions.php
11. **Fix 11** (Footer) — PHP template + CSS (largest change, test last)
12. **Fix 12** (Touch targets) — CSS
13. **Fix 13** (Empty links) — HTML/PHP audit
14. **Fix 14** (Welcome text) — PHP template
15. **Fix 15** (Generic dashboard text) — PHP template

---

## Testing Checklist

After all fixes, verify:

- [ ] `document.querySelectorAll('h1').length === 1`
- [ ] `document.querySelectorAll('h2[class="wm-cart-drawer__title"]')[0].textContent !== ''` or element has `aria-hidden`
- [ ] At 390px: layout is single column, sidebar stacks above content
- [ ] At 768px: layout is two columns with `1fr` flexible column
- [ ] At 1440px: layout unchanged from original two-column
- [ ] Logout link is gray/neutral in resting state
- [ ] Active nav item has visible background highlight (not color alone)
- [ ] Welcome text does NOT contain an inline logout link
- [ ] All `<img>` elements have non-empty `alt` attributes
- [ ] Bottom mobile nav (`wm-mobile-nav`) is visible and fixed at bottom on mobile
- [ ] Footer has at least 3 columns of links on desktop, stacks on mobile
- [ ] No horizontal scroll at any breakpoint
- [ ] Page passes WAVE or axe accessibility scan with zero critical errors
- [ ] Lighthouse accessibility score ≥ 90
- [ ] Google PageSpeed mobile score does not regress after CSS additions

---

## Design System Reference

| Token | Value | Usage |
|---|---|---|
| `--wm-color-primary` | `#111827` | Body text, headings |
| `--wm-color-accent` | `#c1a488` | Brand gold — links, active states, highlights |
| Logout red | `#b42318` | Danger (use only on hover, not resting) |
| Neutral gray | `#6b7280` | Secondary text, inactive states |
| Background tint | `rgba(193, 164, 136, 0.08)` | Cards, stat boxes, active bg |
| Border radius | `6px / 10px / 12px` | Consistent with theme components |
| Min touch target | `44×44px` | WCAG 2.5.5 AA |
| Font family | Vazirmatn (loaded via fonts.css) | All text |
| RTL direction | `dir="rtl"` on `.wm-account-page` | All padding/margin must use `inline-start/end` |