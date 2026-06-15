# AJAX Search Modal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the header's inline search panel with a Vercel-style centered modal that shows admin-curated suggested products by default and live AJAX product search results as the user types, with a new "Marketing & Sale" ACF options page to manage it.

**Architecture:** Add a new ACF options sub-page ("بازاریابی و فروش") with search settings (suggested products, placeholder, max results, label) plus reserved placeholder sections for future marketing features. Replace the header search panel markup with a centered overlay modal. Add an `admin-ajax.php` endpoint that searches WooCommerce products and returns JSON. New JS file drives the modal open/close, debounced AJAX fetch, and result rendering using the existing `wm_render_product_card()` markup style (simplified inline row version). New CSS file styles the modal, RTL-first using existing design tokens. Bump `WATCHMID_VERSION`.

**Tech Stack:** PHP (WordPress/WooCommerce/ACF), vanilla JS, CSS (existing token system). No build step — manual verification via the `run`/`verify` skill in a browser.

---

## File Map

- Modify: `inc/acf/home-fields.php` — register new options sub-page + field group
- Create: `inc/helpers/marketing-data.php` — `wm_search_get_option()` accessor
- Modify: `inc/components/site-header.php` — replace inline search panel with modal markup, render suggested products
- Create: `inc/ajax/search.php` — AJAX handler for live product search
- Create: `assets/js/header-search.js` — modal open/close + AJAX search behavior
- Create: `assets/css/components/search-modal.css` — modal styles (RTL)
- Modify: `functions.php` — require new file, enqueue new JS/CSS, localize script, bump `WATCHMID_VERSION`

---

### Task 1: Add `wm_search_get_option()` helper

**Files:**
- Create: `inc/helpers/marketing-data.php`

- [ ] **Step 1: Create the helper file**

```php
<?php
/**
 * Marketing & Sale option accessors (search, future campaigns).
 *
 * @package WatchMid
 */

function wm_search_get_option( $key, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $key, 'option' );
        if ( null !== $value && '' !== $value && false !== $value ) {
            return $value;
        }
    }

    return $default;
}

function wm_search_get_suggested_products() {
    $ids = wm_search_get_option( 'wm_search_suggested_products', array() );

    if ( empty( $ids ) || ! is_array( $ids ) ) {
        return array();
    }

    $products = array();
    foreach ( $ids as $item ) {
        $product_id = is_object( $item ) ? $item->ID : absint( $item );
        $product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( $product instanceof WC_Product && $product->is_visible() ) {
            $products[] = $product;
        }
    }

    return $products;
}
```

- [ ] **Step 2: Register the require in `functions.php`**

In `functions.php`, add after line 122 (`require get_template_directory() . '/inc/helpers/home-data.php';`):

```php
require get_template_directory() . '/inc/helpers/marketing-data.php';
```

- [ ] **Step 3: Commit**

```bash
git add inc/helpers/marketing-data.php functions.php
git commit -m "Add marketing/search options accessor helper"
```

(If this directory is not a git repo, skip the commit step for all tasks below and just save the files.)

---

### Task 2: Register "Marketing & Sale" ACF options page and search fields

**Files:**
- Modify: `inc/acf/home-fields.php`

- [ ] **Step 1: Add the new sub-page to the `$pages` array**

In `inc/acf/home-fields.php`, find the `$pages` array (around line 24-31) and add a new entry after the "هدر و فوتر" entry:

```php
        array( 'page_title' => 'هدر و فوتر', 'menu_title' => 'هدر و فوتر', 'menu_slug' => 'watchmid-header-footer-settings' ),
        array( 'page_title' => 'بازاریابی و فروش', 'menu_title' => 'بازاریابی و فروش', 'menu_slug' => 'watchmid-marketing-settings' ),
        array( 'page_title' => 'تنظیمات فنی', 'menu_title' => 'تنظیمات فنی', 'menu_slug' => 'watchmid-technical-settings' ),
```

- [ ] **Step 2: Register the field group**

In `wm_register_home_acf_fields()`, after the header/footer field group registration (around line 96, right before the technical settings group), add:

```php
    acf_add_local_field_group(
        array(
            'key'      => 'group_watchmid_marketing_settings',
            'title'    => 'بازاریابی و فروش WatchMid',
            'fields'   => wm_site_settings_marketing_fields(),
            'location' => wm_site_settings_location( 'watchmid-marketing-settings' ),
        )
    );
```

- [ ] **Step 3: Define `wm_site_settings_marketing_fields()`**

Add this new function after `wm_site_settings_header_footer_fields()` (find where that function ends — search for the function and add after its closing `}`):

```php
function wm_site_settings_marketing_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_marketing_tab_search', 'جستجو' ),
        wm_site_settings_accordion( 'field_wm_marketing_search_accordion', 'تنظیمات جستجوی هدر' ),
        array(
            'key'           => 'field_wm_search_placeholder',
            'label'         => 'متن راهنمای جستجو',
            'name'          => 'wm_search_placeholder',
            'type'          => 'text',
            'default_value' => 'جستجوی ساعت، برند یا مدل...',
        ),
        array(
            'key'           => 'field_wm_search_suggested_label',
            'label'         => 'عنوان بخش پیشنهادها',
            'name'          => 'wm_search_suggested_label',
            'type'          => 'text',
            'default_value' => 'پیشنهاد ویژه',
        ),
        array(
            'key'           => 'field_wm_search_max_results',
            'label'         => 'حداکثر تعداد نتایج جستجو',
            'name'          => 'wm_search_max_results',
            'type'          => 'number',
            'default_value' => 6,
            'min'           => 1,
            'max'           => 20,
        ),
        array(
            'key'          => 'field_wm_search_suggested_products',
            'label'        => 'محصولات پیشنهادی (هنگام خالی بودن جستجو)',
            'name'         => 'wm_search_suggested_products',
            'type'         => 'relationship',
            'post_type'    => array( 'product' ),
            'filters'      => array( 'search' ),
            'max'          => 6,
            'return_format' => 'id',
        ),
        wm_site_settings_tab( 'field_wm_marketing_tab_sales', 'تخفیف‌ها و کمپین‌ها' ),
        wm_site_settings_placeholder_fields( 'marketing_sales', 'تخفیف‌ها و کمپین‌ها', 'این بخش برای مدیریت کمپین‌های تخفیف و بنرهای ویژه در آینده رزرو شده است.' ),
        wm_site_settings_tab( 'field_wm_marketing_tab_promo', 'بنرهای تبلیغاتی' ),
        wm_site_settings_placeholder_fields( 'marketing_promo', 'بنرهای تبلیغاتی', 'این بخش برای مدیریت بنرهای تبلیغاتی صفحه اصلی و آرشیو در آینده رزرو شده است.' ),
    );
}
```

- [ ] **Step 4: Add the relationship query filter**

Near line 140-142 where similar filters are registered (`wm_acf_product_relationship_query`), add:

```php
add_filter( 'acf/fields/relationship/query/name=wm_search_suggested_products', 'wm_acf_product_relationship_query', 10, 3 );
```

- [ ] **Step 5: Verify in WP admin**

Use the `run` skill to start the site, log into `/wp-admin`, and confirm a new "بازاریابی و فروش" sub-page appears under "WatchMid" with three tabs: "جستجو", "تخفیف‌ها و کمپین‌ها", "بنرهای تبلیغاتی". Confirm the search tab shows placeholder text, suggested label, max results, and a relationship picker.

- [ ] **Step 6: Commit**

```bash
git add inc/acf/home-fields.php
git commit -m "Add Marketing & Sale ACF options page with search settings"
```

---

### Task 3: AJAX product search endpoint

**Files:**
- Create: `inc/ajax/search.php`
- Modify: `functions.php`

- [ ] **Step 1: Create the AJAX handler**

```php
<?php
/**
 * AJAX live product search for the header search modal.
 *
 * @package WatchMid
 */

function wm_ajax_search_products() {
    check_ajax_referer( 'wm_search_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

    if ( '' === $term || mb_strlen( $term ) < 2 || ! function_exists( 'wc_get_products' ) ) {
        wp_send_json_success( array( 'results' => array() ) );
    }

    $max_results = absint( wm_search_get_option( 'wm_search_max_results', 6 ) );
    $max_results = $max_results > 0 ? $max_results : 6;

    $products = wc_get_products(
        array(
            's'        => $term,
            'status'   => 'publish',
            'limit'    => $max_results,
            'orderby'  => 'relevance',
        )
    );

    $results = array();
    foreach ( $products as $product ) {
        if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
            continue;
        }

        $image_id = $product->get_image_id();
        $terms    = get_the_terms( $product->get_id(), 'product_brand' );
        $brand    = '';
        if ( is_array( $terms ) && ! empty( $terms ) ) {
            $brand = $terms[0]->name;
        } else {
            $cat_terms = get_the_terms( $product->get_id(), 'product_cat' );
            if ( is_array( $cat_terms ) && ! empty( $cat_terms ) ) {
                $brand = $cat_terms[0]->name;
            }
        }

        $results[] = array(
            'id'        => $product->get_id(),
            'title'     => $product->get_name(),
            'permalink' => get_permalink( $product->get_id() ),
            'image'     => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' ),
            'price'     => $product->get_price_html(),
            'brand'     => $brand,
        );
    }

    wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_wm_search_products', 'wm_ajax_search_products' );
add_action( 'wp_ajax_nopriv_wm_search_products', 'wm_ajax_search_products' );
```

- [ ] **Step 2: Require the new file in `functions.php`**

After line 131 (`require get_template_directory() . '/inc/woocommerce.php';`), add:

```php
require get_template_directory() . '/inc/ajax/search.php';
```

- [ ] **Step 3: Verify**

Use the `run` skill: with the site running and logged in, visit:
`http://<site>/wp-admin/admin-ajax.php?action=wm_search_products&term=test&nonce=invalid`
Expected: JSON error response (nonce check fails) — e.g. `{"success":false,...}`. This confirms the endpoint is registered and nonce-protected. Full functional test happens in Task 6 once the JS sends a valid nonce.

- [ ] **Step 4: Commit**

```bash
git add inc/ajax/search.php functions.php
git commit -m "Add AJAX product search endpoint for header search modal"
```

---

### Task 4: Replace header search panel with centered modal markup

**Files:**
- Modify: `inc/components/site-header.php:198-216`

- [ ] **Step 1: Replace the search block**

Replace lines 198-216 (the entire `<div class="wm-header-search">...</div>` block) with:

```php
                <?php if ( $show_search ) : ?>
                    <div class="wm-header-search">
                        <button class="wm-site-header__action wm-site-header__search-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="wm-search-modal">
                            <span class="wm-site-header__action-icon" aria-hidden="true">⌕</span>
                            <span class="wm-site-header__action-text"><?php echo esc_html__( 'جستجو', 'watchmid' ); ?></span>
                        </button>
                    </div>
                <?php endif; ?>
```

- [ ] **Step 2: Add the modal markup before `</header>`**

Find the closing `</header>` tag of `wm_render_site_header()` and add the modal markup immediately before it (the modal lives at the end of the header so it can be positioned `fixed` and overlay the whole viewport):

```php
        <?php if ( $show_search ) : ?>
            <div id="wm-search-modal" class="wm-search-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'جستجو', 'watchmid' ); ?>" hidden>
                <div class="wm-search-modal__backdrop" data-search-modal-close></div>
                <div class="wm-search-modal__panel">
                    <form role="search" method="get" class="wm-search-modal__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <label class="screen-reader-text" for="wm-search-modal-field"><?php echo esc_html__( 'جستجو', 'watchmid' ); ?></label>
                        <span class="wm-search-modal__icon" aria-hidden="true">⌕</span>
                        <input
                            id="wm-search-modal-field"
                            class="wm-search-modal__field"
                            type="search"
                            name="s"
                            autocomplete="off"
                            placeholder="<?php echo esc_attr( wm_search_get_option( 'wm_search_placeholder', 'جستجوی ساعت، برند یا مدل...' ) ); ?>"
                            value="<?php echo esc_attr( get_search_query() ); ?>"
                        >
                        <?php if ( function_exists( 'wc_get_product_types' ) ) : ?>
                            <input type="hidden" name="post_type" value="product">
                        <?php endif; ?>
                        <button type="button" class="wm-search-modal__close" data-search-modal-close aria-label="<?php echo esc_attr__( 'بستن', 'watchmid' ); ?>">×</button>
                    </form>

                    <div class="wm-search-modal__body">
                        <?php
                        $suggested_products = wm_search_get_suggested_products();
                        $suggested_label    = wm_search_get_option( 'wm_search_suggested_label', 'پیشنهاد ویژه' );
                        ?>
                        <div class="wm-search-modal__suggestions" data-search-suggestions <?php echo empty( $suggested_products ) ? 'hidden' : ''; ?>>
                            <?php if ( ! empty( $suggested_products ) ) : ?>
                                <h3 class="wm-search-modal__section-title"><?php echo esc_html( $suggested_label ); ?></h3>
                                <ul class="wm-search-modal__results">
                                    <?php foreach ( $suggested_products as $product ) : ?>
                                        <?php echo wm_render_search_result_row( $product ); ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="wm-search-modal__results-wrap" data-search-results hidden>
                            <ul class="wm-search-modal__results" data-search-results-list></ul>
                            <a href="#" class="wm-search-modal__view-all" data-search-view-all hidden><?php echo esc_html__( 'مشاهده همه نتایج', 'watchmid' ); ?></a>
                        </div>

                        <p class="wm-search-modal__empty" data-search-empty hidden><?php echo esc_html__( 'نتیجه‌ای یافت نشد.', 'watchmid' ); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>
    <?php
```

(Note: the `<?php }` was the original end of `wm_render_site_header()` — keep that closing brace after `</header>`.)

- [ ] **Step 3: Add the `wm_render_search_result_row()` helper**

Add this function to `inc/components/product-card.php` (it's the shared product-rendering file), after `wm_render_product_card()`:

```php
function wm_render_search_result_row( $product ) {
    if ( is_numeric( $product ) && function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( absint( $product ) );
    }

    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $image_id  = $product->get_image_id();
    $permalink = get_permalink( $product->get_id() );
    $title     = $product->get_name();

    $brand = '';
    $terms = get_the_terms( $product->get_id(), 'product_brand' );
    if ( is_array( $terms ) && ! empty( $terms ) ) {
        $brand = $terms[0]->name;
    } else {
        $cat_terms = get_the_terms( $product->get_id(), 'product_cat' );
        if ( is_array( $cat_terms ) && ! empty( $cat_terms ) ) {
            $brand = $cat_terms[0]->name;
        }
    }

    ob_start();
    ?>
    <li class="wm-search-result">
        <a class="wm-search-result__link" href="<?php echo esc_url( $permalink ); ?>">
            <span class="wm-search-result__media">
                <?php
                if ( $image_id ) {
                    echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wm-search-result__image', 'alt' => $title, 'loading' => 'lazy' ) );
                } elseif ( function_exists( 'wc_placeholder_img' ) ) {
                    echo wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'wm-search-result__image' ) );
                }
                ?>
            </span>
            <span class="wm-search-result__info">
                <span class="wm-search-result__title"><?php echo esc_html( $title ); ?></span>
                <?php if ( $brand ) : ?>
                    <span class="wm-search-result__brand"><?php echo esc_html( $brand ); ?></span>
                <?php endif; ?>
            </span>
            <?php if ( $product->get_price_html() ) : ?>
                <span class="wm-search-result__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php
    return ob_get_clean();
}
```

- [ ] **Step 4: Verify markup renders without PHP errors**

Use the `run` skill to load the homepage. Check the page source for `id="wm-search-modal"` and confirm no PHP warnings/notices appear (check Local's PHP error log or enable `WP_DEBUG`).

- [ ] **Step 5: Commit**

```bash
git add inc/components/site-header.php inc/components/product-card.php
git commit -m "Replace header search panel with centered search modal markup"
```

---

### Task 5: Search modal CSS

**Files:**
- Create: `assets/css/components/search-modal.css`

- [ ] **Step 1: Write the stylesheet**

Use existing tokens (check `assets/css/tokens.css` for variable names like `--wm-color-*`, `--wm-radius-*`, `--wm-space-*` — match whatever naming convention is already in use there; the snippet below uses generic fallbacks that should be aligned to the real token names found in `tokens.css`).

```css
/* Search modal (RTL) */
.wm-search-modal {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 10vh 16px 16px;
}

.wm-search-modal[hidden] {
  display: none;
}

.wm-search-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 15, 20, 0.55);
  backdrop-filter: blur(2px);
}

.wm-search-modal__panel {
  position: relative;
  width: 100%;
  max-width: 640px;
  background: var(--wm-color-surface, #fff);
  border-radius: var(--wm-radius-lg, 12px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 80vh;
}

.wm-search-modal__form {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--wm-color-border, #e5e5e5);
}

.wm-search-modal__icon {
  font-size: 20px;
  color: var(--wm-color-muted, #999);
  flex-shrink: 0;
}

.wm-search-modal__field {
  flex: 1;
  border: 0;
  outline: none;
  font-size: 16px;
  font-family: inherit;
  background: transparent;
  direction: rtl;
}

.wm-search-modal__close {
  border: 0;
  background: transparent;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: var(--wm-color-muted, #999);
  padding: 4px 8px;
}

.wm-search-modal__body {
  overflow-y: auto;
  padding: 8px 16px 16px;
}

.wm-search-modal__section-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--wm-color-muted, #888);
  margin: 12px 0 8px;
}

.wm-search-modal__results {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.wm-search-result__link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border-radius: var(--wm-radius-md, 8px);
  text-decoration: none;
  color: inherit;
  transition: background-color 0.15s ease;
}

.wm-search-result__link:hover,
.wm-search-result__link:focus {
  background: var(--wm-color-surface-muted, #f5f5f5);
}

.wm-search-result__media {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: var(--wm-radius-sm, 6px);
  overflow: hidden;
}

.wm-search-result__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.wm-search-result__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.wm-search-result__title {
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.wm-search-result__brand {
  font-size: 12px;
  color: var(--wm-color-muted, #999);
}

.wm-search-result__price {
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
  white-space: nowrap;
}

.wm-search-modal__view-all {
  display: block;
  text-align: center;
  padding: 10px;
  margin-top: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--wm-color-primary, #111);
  text-decoration: none;
  border-top: 1px solid var(--wm-color-border, #e5e5e5);
}

.wm-search-modal__empty {
  text-align: center;
  color: var(--wm-color-muted, #999);
  font-size: 14px;
  padding: 24px 0;
}

body.wm-search-modal-open {
  overflow: hidden;
}

@media (max-width: 600px) {
  .wm-search-modal {
    padding: 0;
  }

  .wm-search-modal__panel {
    max-width: none;
    width: 100%;
    height: 100%;
    max-height: none;
    border-radius: 0;
  }
}
```

- [ ] **Step 2: Check `tokens.css` for actual variable names and adjust**

Read `assets/css/tokens.css`, find the actual custom property names for surface color, border color, muted text color, radius, and primary color, and replace the fallback-style `var(--wm-color-surface, #fff)` references above with the real token names used elsewhere in `assets/css/components/header.css` (for consistency, match whatever pattern `header.css` uses — either raw `var(--token)` or `var(--token, fallback)`).

- [ ] **Step 3: Commit**

```bash
git add assets/css/components/search-modal.css
git commit -m "Add search modal styles"
```

---

### Task 6: Search modal JS (open/close + AJAX)

**Files:**
- Create: `assets/js/header-search.js`
- Modify: `functions.php`

- [ ] **Step 1: Write the JS**

```js
(function () {
  var toggle = document.querySelector('.wm-site-header__search-toggle');
  var modal = document.getElementById('wm-search-modal');

  if (!toggle || !modal) {
    return;
  }

  var field = modal.querySelector('.wm-search-modal__field');
  var suggestions = modal.querySelector('[data-search-suggestions]');
  var resultsWrap = modal.querySelector('[data-search-results]');
  var resultsList = modal.querySelector('[data-search-results-list]');
  var viewAll = modal.querySelector('[data-search-view-all]');
  var empty = modal.querySelector('[data-search-empty]');
  var closers = modal.querySelectorAll('[data-search-modal-close]');
  var debounceTimer = 0;
  var currentRequest = null;

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'true');
    window.setTimeout(function () {
      field.focus();
    }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function showSuggestions() {
    if (suggestions) {
      suggestions.hidden = suggestions.children.length === 0;
    }
    resultsWrap.hidden = true;
    empty.hidden = true;
  }

  function renderResults(results, term) {
    resultsList.innerHTML = '';

    if (!results.length) {
      resultsWrap.hidden = true;
      empty.hidden = false;
      if (suggestions) {
        suggestions.hidden = true;
      }
      return;
    }

    empty.hidden = true;
    if (suggestions) {
      suggestions.hidden = true;
    }

    results.forEach(function (item) {
      var li = document.createElement('li');
      li.className = 'wm-search-result';

      var link = document.createElement('a');
      link.className = 'wm-search-result__link';
      link.href = item.permalink;

      var media = document.createElement('span');
      media.className = 'wm-search-result__media';
      var img = document.createElement('img');
      img.className = 'wm-search-result__image';
      img.src = item.image;
      img.alt = item.title;
      img.loading = 'lazy';
      media.appendChild(img);

      var info = document.createElement('span');
      info.className = 'wm-search-result__info';
      var title = document.createElement('span');
      title.className = 'wm-search-result__title';
      title.textContent = item.title;
      info.appendChild(title);

      if (item.brand) {
        var brand = document.createElement('span');
        brand.className = 'wm-search-result__brand';
        brand.textContent = item.brand;
        info.appendChild(brand);
      }

      var price = document.createElement('span');
      price.className = 'wm-search-result__price';
      price.innerHTML = item.price;

      link.appendChild(media);
      link.appendChild(info);
      link.appendChild(price);
      li.appendChild(link);
      resultsList.appendChild(li);
    });

    resultsWrap.hidden = false;

    if (viewAll) {
      var url = window.wmSearchData.searchUrl + '?s=' + encodeURIComponent(term) + '&post_type=product';
      viewAll.href = url;
      viewAll.hidden = false;
    }
  }

  function fetchResults(term) {
    if (currentRequest) {
      currentRequest.abort();
    }

    var controller = new AbortController();
    currentRequest = controller;

    var url = window.wmSearchData.ajaxUrl
      + '?action=wm_search_products'
      + '&nonce=' + encodeURIComponent(window.wmSearchData.nonce)
      + '&term=' + encodeURIComponent(term);

    fetch(url, { signal: controller.signal })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success) {
          renderResults(data.data.results || [], term);
        }
      })
      .catch(function (error) {
        if (error.name !== 'AbortError') {
          renderResults([], term);
        }
      });
  }

  field.addEventListener('input', function () {
    var term = field.value.trim();

    window.clearTimeout(debounceTimer);

    if (term.length < 2) {
      showSuggestions();
      return;
    }

    debounceTimer = window.setTimeout(function () {
      fetchResults(term);
    }, 300);
  });

  toggle.addEventListener('click', function (event) {
    event.preventDefault();
    openModal();
  });

  closers.forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
```

- [ ] **Step 2: Enqueue + localize the script in `functions.php`**

In `watchmid_scripts()`, after the line enqueuing `watchmid-header` (line 77: `wp_enqueue_script( 'watchmid-header', ... );`), add:

```php
    wp_enqueue_style( 'watchmid-search-modal', get_template_directory_uri() . '/assets/css/components/search-modal.css', array( 'watchmid-style' ), WATCHMID_VERSION );
    wp_enqueue_script( 'watchmid-header-search', get_template_directory_uri() . '/assets/js/header-search.js', array(), WATCHMID_VERSION, true );
    wp_localize_script(
        'watchmid-header-search',
        'wmSearchData',
        array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wm_search_nonce' ),
            'searchUrl' => home_url( '/' ),
        )
    );
```

- [ ] **Step 3: Bump `WATCHMID_VERSION`**

In `functions.php` line 9, bump the version, e.g. from `'0.4.30'` to `'0.4.31'`.

- [ ] **Step 4: Manual verification with `verify` skill**

Use the `verify` skill to:
1. Start the site, load the homepage.
2. Click the search icon in the header — confirm a centered modal opens with backdrop, RTL input, and (if suggested products are configured in ACF) a "پیشنهاد ویژه" list.
3. Type 2+ characters of a real product name — confirm results replace the suggestions list within ~300ms via AJAX (check Network tab for `admin-ajax.php?action=wm_search_products`).
4. Clear the input — confirm suggestions reappear.
5. Type a term with no matches — confirm "نتیجه‌ای یافت نشد." appears.
6. Press Escape and click the backdrop — confirm modal closes both ways.
7. Confirm the "مشاهده همه نتایج" link points to `/?s=<term>&post_type=product`.
8. Resize to mobile width — confirm modal goes full-screen.
9. Confirm no JS console errors and no PHP notices/warnings.

- [ ] **Step 5: Commit**

```bash
git add assets/js/header-search.js functions.php
git commit -m "Wire up AJAX search modal JS and enqueue assets"
```

---

### Task 7: Update `style.css` version header

**Files:**
- Modify: `style.css`

- [ ] **Step 1: Bump the `Version:` header**

Open `style.css`, find the `Version:` line in the theme header comment, and update it to match the new `WATCHMID_VERSION` from Task 6 Step 3 (e.g. `0.4.31`).

- [ ] **Step 2: Commit**

```bash
git add style.css
git commit -m "Bump theme version for search modal feature"
```

---

## Self-Review Notes

- Old `.wm-header-search__panel`, `.wm-mobile-search-backdrop`, and related JS in `assets/js/header.js` (lines 2-96) become dead code after Task 4. This plan intentionally leaves `header.js` cleanup out of scope to avoid breaking other header behaviors (mega menu, sticky header) that share the file — however, Task 4's verification step should confirm no console errors arise from the now-missing `#wm-header-search-panel` element. If `header.js`'s search-related code throws errors against the new markup, add a follow-up step to remove the dead `closeSearch`/`openSearch`/search-toggle block (lines 1-96 region) from `header.js` — but only after confirming via Step 4 testing that it's actually broken, since the null-checks (`if (searchWrap && searchToggle && searchPanel)`) may make it silently no-op safely.
- All Persian strings use `esc_html__`/`esc_attr__` with the `watchmid` text domain, consistent with existing code.
- New ACF fields follow the existing `*_get_option()` accessor + tab/accordion/placeholder pattern from `home-fields.php`.
