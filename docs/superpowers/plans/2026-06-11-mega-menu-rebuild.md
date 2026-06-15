# Mega Menu Rebuild Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the flat ACF repeater mega-menu config with a "Mega Menu" custom post type (one post per menu, collapsed repeater rows for columns/links), an auto-match nav-menu-item dropdown to assign a mega menu to a nav item, a frontend positioning fix so every mega menu panel renders in the same overlay slot, and a one-time idempotent migration of existing data.

**Architecture:** New CPT `wm_mega_menu` + ACF field group for its fields; new ACF field group on nav menu items (`wm_mega_menu_post`); `inc/components/mega-menu.php` rewritten to read from the CPT and to inject `data-mega-key` via a `nav_menu_link_attributes` filter; `header.js` updated to read `data-mega-key` from the trigger's `<a>`; CSS positioning fix makes every `.wm-mega-menu__panel` an absolutely-positioned overlay in the same slot; one-time `admin_init` migration converts old repeater data to CPT posts and best-effort assigns nav items.

**Tech Stack:** WordPress (CPT, ACF PHP field groups, `acf_add_local_field_group`), vanilla JS, CSS custom properties (existing `tokens.css`).

---

## File Map

- Create: `inc/acf/mega-menu-cpt.php` — registers `wm_mega_menu` CPT
- Create: `inc/acf/mega-menu-fields.php` — ACF field group for `wm_mega_menu` posts
- Create: `inc/acf/mega-menu-nav-field.php` — ACF field group for nav menu items (`wm_mega_menu_post`)
- Create: `inc/acf/mega-menu-migration.php` — one-time migration from old repeater
- Modify: `inc/acf/home-fields.php` — remove old "Mega Menu" tab/accordion/repeater from `wm_site_settings_header_footer_fields()`
- Modify: `inc/components/mega-menu.php` — rewrite `wm_get_header_mega_menus()` to query CPT posts; add `nav_menu_link_attributes` filter
- Modify: `assets/js/header.js` — read `data-mega-key` from `<a>` instead of parsing `wm-mega-trigger-{key}` classes
- Modify: `assets/css/components/header.css` — positioning fix for `.wm-mega-menu__panel`
- Modify: `functions.php` — require the 4 new files, bump `WATCHMID_VERSION`
- Modify: `style.css` — bump `Version:` header to match

---

### Task 1: Register the `wm_mega_menu` custom post type

**Files:**
- Create: `inc/acf/mega-menu-cpt.php`
- Modify: `functions.php`

- [ ] **Step 1: Create the CPT registration file**

```php
<?php
/**
 * Mega Menu custom post type.
 *
 * @package WatchMid
 */

function wm_register_mega_menu_cpt() {
    register_post_type(
        'wm_mega_menu',
        array(
            'labels'             => array(
                'name'               => 'مگامنوها',
                'singular_name'      => 'مگامنو',
                'menu_name'          => 'مگامنو',
                'add_new'            => 'افزودن مگامنو',
                'add_new_item'       => 'افزودن مگامنوی جدید',
                'edit_item'          => 'ویرایش مگامنو',
                'new_item'           => 'مگامنوی جدید',
                'view_item'          => 'مشاهده مگامنو',
                'search_items'       => 'جستجوی مگامنو',
                'not_found'          => 'مگامنویی یافت نشد',
                'not_found_in_trash' => 'مگامنویی در زباله‌دان یافت نشد',
                'all_items'          => 'همه مگامنوها',
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_admin_bar'  => false,
            'show_in_rest'       => false,
            'menu_icon'          => 'dashicons-screenoptions',
            'menu_position'      => 59,
            'supports'           => array( 'title' ),
            'has_archive'        => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
        )
    );
}
add_action( 'init', 'wm_register_mega_menu_cpt' );
```

- [ ] **Step 2: Require the new file in `functions.php`**

In `functions.php`, find the existing require block (around line 134-148). Add the
new require directly after `inc/helpers/marketing-data.php`:

```php
require get_template_directory() . '/inc/helpers/marketing-data.php';
require get_template_directory() . '/inc/acf/mega-menu-cpt.php';
```

- [ ] **Step 3: Verify with `php -l`**

Run: `php -l "inc/acf/mega-menu-cpt.php"`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual check (note for verification phase)**

After this task, the engineer running the `verify` skill should confirm a "مگامنو"
top-level admin menu item appears in wp-admin (it will be empty until Task 2 adds
fields and Task 5 runs the migration). No commit step here — commits happen at the
end of each task per repo convention (no git repo is configured for this theme
directory, so skip git commands entirely; just confirm the file is saved).

---

### Task 2: ACF field group for `wm_mega_menu` posts

**Files:**
- Create: `inc/acf/mega-menu-fields.php`
- Modify: `functions.php`

- [ ] **Step 1: Create the field group file**

This mirrors the sub-field structure of the old `wm_header_mega_menus` repeater
(`inc/acf/home-fields.php` lines 296-337), but drops `mega_enabled` and
`mega_trigger_key` (the post itself is the "enabled" unit, identified by being
published; the post title replaces `mega_title`). Repeater rows use ACF's
`collapsed` setting so each column/link row shows just its title/label until
clicked.

```php
<?php
/**
 * ACF fields for the Mega Menu custom post type.
 *
 * @package WatchMid
 */

function wm_register_mega_menu_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_wm_mega_menu_fields',
            'title'    => 'تنظیمات مگامنو',
            'fields'   => array(
                array(
                    'key'   => 'field_wm_mega_subtitle',
                    'label' => 'توضیح کوتاه مگامنو',
                    'name'  => 'mega_subtitle',
                    'type'  => 'textarea',
                    'rows'  => 2,
                    'instructions' => 'عنوان مگامنو از عنوان این پست (بالای صفحه) خوانده می‌شود.',
                ),
                array(
                    'key'          => 'field_wm_mega_columns',
                    'label'        => 'ستون‌های مگامنو',
                    'name'         => 'mega_columns',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'button_label' => 'افزودن ستون',
                    'collapsed'    => 'field_wm_mega_column_title',
                    'instructions' => 'هر ستون شامل یک عنوان و چند لینک است. ستون‌های غیرفعال یا بدون لینک نمایش داده نمی‌شوند.',
                    'sub_fields'   => array(
                        array( 'key' => 'field_wm_mega_column_enabled', 'label' => 'فعال', 'name' => 'column_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                        array( 'key' => 'field_wm_mega_column_title', 'label' => 'عنوان ستون', 'name' => 'column_title', 'type' => 'text' ),
                        array(
                            'key'          => 'field_wm_mega_column_links',
                            'label'        => 'لینک‌های ستون',
                            'name'         => 'column_links',
                            'type'         => 'repeater',
                            'layout'       => 'block',
                            'button_label' => 'افزودن لینک',
                            'collapsed'    => 'field_wm_mega_link_label',
                            'instructions' => 'لینک‌های غیرفعال یا بدون عنوان/URL نمایش داده نمی‌شوند. badge، توضیح و آیکن اختیاری هستند.',
                            'sub_fields'   => array(
                                array( 'key' => 'field_wm_mega_link_enabled', 'label' => 'فعال', 'name' => 'link_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                                array( 'key' => 'field_wm_mega_link_label', 'label' => 'عنوان لینک', 'name' => 'link_label', 'type' => 'text' ),
                                array( 'key' => 'field_wm_mega_link_url', 'label' => 'آدرس لینک', 'name' => 'link_url', 'type' => 'url' ),
                                array( 'key' => 'field_wm_mega_link_badge', 'label' => 'Badge اختیاری', 'name' => 'link_badge', 'type' => 'text', 'instructions' => 'مثال: محبوب، جدید، اقتصادی.' ),
                                array( 'key' => 'field_wm_mega_link_description', 'label' => 'توضیح کوتاه لینک', 'name' => 'link_description', 'type' => 'text' ),
                                array( 'key' => 'field_wm_mega_link_icon', 'label' => 'آیکن اختیاری', 'name' => 'link_icon', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail' ),
                            ),
                        ),
                    ),
                ),
                array( 'key' => 'field_wm_mega_feature_card_enabled', 'label' => 'نمایش کارت ویژه', 'name' => 'mega_feature_card_enabled', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1 ),
                array( 'key' => 'field_wm_mega_feature_title', 'label' => 'عنوان کارت ویژه', 'name' => 'mega_feature_title', 'type' => 'text' ),
                array( 'key' => 'field_wm_mega_feature_text', 'label' => 'متن کارت ویژه', 'name' => 'mega_feature_text', 'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'field_wm_mega_feature_image', 'label' => 'تصویر کارت ویژه', 'name' => 'mega_feature_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_wm_mega_feature_url', 'label' => 'لینک کارت ویژه', 'name' => 'mega_feature_url', 'type' => 'url' ),
                array( 'key' => 'field_wm_mega_feature_button_text', 'label' => 'متن دکمه کارت ویژه', 'name' => 'mega_feature_button_text', 'type' => 'text', 'default_value' => 'مشاهده' ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'wm_mega_menu',
                    ),
                ),
            ),
        )
    );
}
add_action( 'acf/init', 'wm_register_mega_menu_acf_fields' );
```

- [ ] **Step 2: Require the new file in `functions.php`**

```php
require get_template_directory() . '/inc/acf/mega-menu-cpt.php';
require get_template_directory() . '/inc/acf/mega-menu-fields.php';
```

- [ ] **Step 3: Verify with `php -l`**

Run: `php -l "inc/acf/mega-menu-fields.php"`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual check (note for verification phase)**

The engineer running `verify` should: open wp-admin → مگامنو → افزودن مگامنو, confirm
the field group renders with "توضیح کوتاه مگامنو", "ستون‌های مگامنو" (collapsed
repeater), feature-card fields. Add a column with a title and one link, save, and
confirm the column row collapses to show just the column title after saving.

---

### Task 3: ACF field group for nav menu item mega-menu picker

**Files:**
- Create: `inc/acf/mega-menu-nav-field.php`
- Modify: `functions.php`

- [ ] **Step 1: Create the field group file**

ACF's `nav_menu_item` location type uses `'value' => 'all'` to apply a field group
to every nav menu's items (this is the documented "all" wildcard supported by ACF
5.7+; the field appears in the per-item settings of every menu in Appearance →
Menus, including newly created menus).

```php
<?php
/**
 * ACF field for assigning a Mega Menu post to a nav menu item.
 *
 * @package WatchMid
 */

function wm_register_mega_menu_nav_field() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_wm_mega_menu_nav_field',
            'title'    => 'مگامنوی آیتم منو',
            'fields'   => array(
                array(
                    'key'           => 'field_wm_mega_menu_post',
                    'label'         => 'مگامنو',
                    'name'          => 'wm_mega_menu_post',
                    'type'          => 'post_object',
                    'post_type'     => array( 'wm_mega_menu' ),
                    'return_format' => 'id',
                    'allow_null'    => 1,
                    'ui'            => 1,
                    'instructions'  => 'در صورت انتخاب، با هاور/فوکوس روی این آیتم منو، مگامنوی انتخاب‌شده باز می‌شود.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'nav_menu_item',
                        'operator' => '==',
                        'value'    => 'all',
                    ),
                ),
            ),
        )
    );
}
add_action( 'acf/init', 'wm_register_mega_menu_nav_field' );
```

- [ ] **Step 2: Require the new file in `functions.php`**

```php
require get_template_directory() . '/inc/acf/mega-menu-fields.php';
require get_template_directory() . '/inc/acf/mega-menu-nav-field.php';
```

- [ ] **Step 3: Verify with `php -l`**

Run: `php -l "inc/acf/mega-menu-nav-field.php"`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual check (note for verification phase)**

The engineer running `verify` should open Appearance → Menus, expand any nav menu
item, and confirm a "مگامنو" post-object dropdown field appears alongside
"Navigation Label" / "CSS Classes" / "Move", listing published `wm_mega_menu`
posts (empty until Task 2's test post or Task 6's migration creates one).

If `'value' => 'all'` does not make the field appear (verify in the actual ACF
version installed), fall back to listing each menu's term ID explicitly:

```php
'location' => array_map(
    function ( $menu ) {
        return array(
            array(
                'param'    => 'nav_menu',
                'operator' => '==',
                'value'    => (string) $menu->term_id,
            ),
        );
    },
    wp_get_nav_menus()
),
```

Only apply this fallback if `'all'` is confirmed not to work — document which
approach was used in a one-line code comment above the `location` array.

---

### Task 4: Rewrite `wm_get_header_mega_menus()` to read from the CPT and add nav-item linking filter

**Files:**
- Modify: `inc/components/mega-menu.php`

- [ ] **Step 1: Replace `wm_get_header_mega_menus()`**

Current implementation (lines 40-108) reads `get_field('wm_header_mega_menus',
'option')`. Replace the whole function body with a query against published
`wm_mega_menu` posts. Keep the same output shape (`mega_trigger_key`,
`mega_columns`, `has_feature`, `has_intro`, etc.) so
`wm_render_header_mega_menus()` (lines 148-227) needs no changes — only
`mega_trigger_key` now holds the post ID as a string, and `mega_title` comes from
the post title instead of an ACF field.

```php
function wm_get_header_mega_menus() {
    $GLOBALS['wm_header_mega_debug_messages'] = array();

    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $posts = get_posts(
        array(
            'post_type'      => 'wm_mega_menu',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        )
    );

    $output = array();

    foreach ( $posts as $post ) {
        $key = (string) $post->ID;

        $columns = array();
        foreach ( (array) get_field( 'mega_columns', $post->ID ) as $column ) {
            if ( empty( $column['column_enabled'] ) ) {
                continue;
            }

            $links = array();
            foreach ( (array) ( $column['column_links'] ?? array() ) as $link ) {
                if ( empty( $link['link_enabled'] ) || empty( $link['link_label'] ) || empty( $link['link_url'] ) ) {
                    continue;
                }

                $links[] = $link;
            }

            $has_column_title = ! empty( $column['column_title'] );
            if ( empty( $links ) && ! $has_column_title ) {
                continue;
            }

            $column['column_links'] = $links;
            $columns[] = $column;
        }

        $menu = array(
            'mega_trigger_key'          => $key,
            'mega_title'                => $post->post_title,
            'mega_subtitle'             => get_field( 'mega_subtitle', $post->ID ),
            'mega_columns'              => $columns,
            'mega_feature_card_enabled' => get_field( 'mega_feature_card_enabled', $post->ID ),
            'mega_feature_title'        => get_field( 'mega_feature_title', $post->ID ),
            'mega_feature_text'         => get_field( 'mega_feature_text', $post->ID ),
            'mega_feature_image'        => get_field( 'mega_feature_image', $post->ID ),
            'mega_feature_url'          => get_field( 'mega_feature_url', $post->ID ),
            'mega_feature_button_text'  => get_field( 'mega_feature_button_text', $post->ID ),
        );

        $has_feature = ! empty( $menu['mega_feature_card_enabled'] ) && ( ! empty( $menu['mega_feature_title'] ) || ! empty( $menu['mega_feature_text'] ) || ! empty( $menu['mega_feature_image'] ) );
        $has_intro = ! empty( $menu['mega_title'] ) || ! empty( $menu['mega_subtitle'] );
        if ( empty( $columns ) && ! $has_feature && ! $has_intro ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $GLOBALS['wm_header_mega_debug_messages'][] = 'skipped post ' . $key . ': no title, active column, active link, or feature';
            }
            continue;
        }

        $menu['mega_columns'] = $columns;
        $menu['has_feature'] = $has_feature;
        $menu['has_intro'] = $has_intro;
        $output[] = $menu;
    }

    return $output;
}
```

- [ ] **Step 2: Add a `nav_menu_link_attributes` filter to inject `data-mega-key`**

Add this new function anywhere after `wm_get_header_mega_menus()` in the same
file. It reads the `wm_mega_menu_post` ACF field (set up in Task 3) on each nav
menu item and, if a published `wm_mega_menu` post is assigned, adds
`data-mega-key`, `aria-haspopup`, and `aria-expanded` attributes to that item's
`<a>` tag, plus a `wm-mega-trigger` class on the parent `<li>` via
`nav_menu_css_class`.

```php
function wm_mega_menu_nav_link_attributes( $atts, $item, $args, $depth ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $atts;
    }

    $mega_post_id = get_field( 'wm_mega_menu_post', $item->ID );
    if ( empty( $mega_post_id ) || 'publish' !== get_post_status( $mega_post_id ) ) {
        return $atts;
    }

    $atts['data-mega-key']  = (string) $mega_post_id;
    $atts['aria-haspopup']  = 'true';
    $atts['aria-expanded']  = 'false';

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'wm_mega_menu_nav_link_attributes', 10, 4 );

function wm_mega_menu_nav_css_class( $classes, $item ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $classes;
    }

    $mega_post_id = get_field( 'wm_mega_menu_post', $item->ID );
    if ( empty( $mega_post_id ) || 'publish' !== get_post_status( $mega_post_id ) ) {
        return $classes;
    }

    $classes[] = 'wm-mega-trigger';

    return $classes;
}
add_filter( 'nav_menu_css_class', 'wm_mega_menu_nav_css_class', 10, 2 );
```

- [ ] **Step 3: Verify with `php -l`**

Run: `php -l "inc/components/mega-menu.php"`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual check (note for verification phase)**

Deferred until Task 6 (migration) creates real CPT posts and assigns them to nav
items — at that point the engineer running `verify` should confirm
`wm_get_header_mega_menus()` returns non-empty data and the rendered `<li>`/`<a>`
for assigned nav items have `class="...wm-mega-trigger"` and
`data-mega-key="{post_id}"`.

---

### Task 5: Update `header.js` to read `data-mega-key` instead of parsing CSS classes

**Files:**
- Modify: `assets/js/header.js`

- [ ] **Step 1: Replace `getMegaKeyFromTrigger`**

Current (lines 19-29) parses `wm-mega-trigger-{key}` from the trigger's class list.
Replace with reading `data-mega-key` from the trigger's `<a>` (or the trigger
itself if it's the link):

```js
  function getMegaKeyFromTrigger(trigger) {
    if (!trigger) {
      return '';
    }

    var link = trigger.matches && trigger.matches('a') ? trigger : trigger.querySelector('a');
    if (!link) {
      return '';
    }

    return link.dataset.megaKey || '';
  }
```

- [ ] **Step 2: Update the `megaTriggers` selector**

Current (line 6):
```js
  const megaTriggers = Array.from(document.querySelectorAll('[class*="wm-mega-trigger-"]'));
```

Replace with:
```js
  const megaTriggers = Array.from(document.querySelectorAll('.wm-mega-trigger'));
```

- [ ] **Step 3: Update `getMegaTrigger(key)`**

Current (lines 31-33):
```js
  function getMegaTrigger(key) {
    return key ? document.querySelector('.wm-mega-trigger-' + window.CSS.escape(key)) : null;
  }
```

Replace with an attribute selector matching the `<a>`'s `data-mega-key`, then
return its closest `.wm-mega-trigger` ancestor (so it matches what `megaTriggers`
contains):

```js
  function getMegaTrigger(key) {
    if (!key) {
      return null;
    }

    var link = document.querySelector('[data-mega-key="' + window.CSS.escape(key) + '"]');
    if (!link) {
      return null;
    }

    return link.closest('.wm-mega-trigger') || link;
  }
```

- [ ] **Step 4: Verify no other references to the old class pattern remain**

Run a search:
```bash
grep -n "wm-mega-trigger-" assets/js/header.js
```
Expected: no matches (the only remaining usages should be the literal class name
`wm-mega-trigger` without a trailing dash, used in the selectors above).

- [ ] **Step 5: Verify with `node --check`**

Run: `node --check assets/js/header.js`
Expected: no output (success)

- [ ] **Step 6: Manual check (note for verification phase)**

Deferred until Task 6 provides real data. The engineer running `verify` should then
hover/focus a `.wm-mega-trigger` nav item at desktop width (≥1024px) and confirm
`openMegaMenu(key)` receives the post-ID `key` from `data-mega-key` and opens the
matching `.wm-mega-menu__panel[data-mega-key="..."]`.

---

### Task 6: Migration from old repeater data + remove old field group

**Files:**
- Create: `inc/acf/mega-menu-migration.php`
- Modify: `inc/acf/home-fields.php`
- Modify: `functions.php`

- [ ] **Step 1: Create the migration file**

This runs once on `admin_init`, guarded by an option flag so it never re-runs or
duplicates data. It must run BEFORE the old field group is removed from
`home-fields.php` is irrelevant to execution order at runtime (ACF options data in
`wp_options` persists regardless of whether the field group definition still
exists) — but to be safe or maximize the chance of success, this file should be
required BEFORE `inc/acf/home-fields.php` in `functions.php` so
`get_field('wm_header_mega_menus', 'option')` is registered as the right type when
called. In practice ACF's `get_field` on options can read raw stored data even
without a matching local field group, but registering order-first keeps behavior
predictable.

```php
<?php
/**
 * One-time migration: convert legacy wm_header_mega_menus repeater data
 * into wm_mega_menu CPT posts, and best-effort assign them to nav menu
 * items that used the old wm-mega-trigger-{key} CSS class convention.
 *
 * @package WatchMid
 */

function wm_migrate_legacy_mega_menus() {
    if ( get_option( 'wm_mega_menu_migrated' ) ) {
        return;
    }

    if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
        return;
    }

    $legacy_menus = get_field( 'wm_header_mega_menus', 'option' );
    $legacy_menus = is_array( $legacy_menus ) ? $legacy_menus : array();
    $key_to_post_id = array();

    foreach ( $legacy_menus as $menu ) {
        if ( empty( $menu['mega_enabled'] ) ) {
            continue;
        }

        $title = ! empty( $menu['mega_title'] )
            ? $menu['mega_title']
            : ( ! empty( $menu['mega_trigger_key'] ) ? $menu['mega_trigger_key'] : __( 'مگامنو', 'watchmid' ) );

        $post_id = wp_insert_post(
            array(
                'post_type'   => 'wm_mega_menu',
                'post_title'  => $title,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        update_field( 'mega_subtitle', $menu['mega_subtitle'] ?? '', $post_id );
        update_field( 'mega_columns', $menu['mega_columns'] ?? array(), $post_id );
        update_field( 'mega_feature_card_enabled', $menu['mega_feature_card_enabled'] ?? 0, $post_id );
        update_field( 'mega_feature_title', $menu['mega_feature_title'] ?? '', $post_id );
        update_field( 'mega_feature_text', $menu['mega_feature_text'] ?? '', $post_id );
        update_field( 'mega_feature_image', $menu['mega_feature_image'] ?? '', $post_id );
        update_field( 'mega_feature_url', $menu['mega_feature_url'] ?? '', $post_id );
        update_field( 'mega_feature_button_text', $menu['mega_feature_button_text'] ?? '', $post_id );

        if ( ! empty( $menu['mega_trigger_key'] ) ) {
            $key_to_post_id[ sanitize_key( $menu['mega_trigger_key'] ) ] = $post_id;
        }
    }

    if ( ! empty( $key_to_post_id ) ) {
        $menus = wp_get_nav_menus();
        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );
            if ( ! $items ) {
                continue;
            }

            foreach ( $items as $item ) {
                $classes = (array) $item->classes;
                foreach ( $classes as $class ) {
                    if ( 0 === strpos( $class, 'wm-mega-trigger-' ) ) {
                        $key = substr( $class, strlen( 'wm-mega-trigger-' ) );
                        if ( isset( $key_to_post_id[ $key ] ) ) {
                            update_field( 'wm_mega_menu_post', $key_to_post_id[ $key ], $item->ID );
                        }
                    }
                }
            }
        }
    }

    update_option( 'wm_mega_menu_migrated', 1 );
}
add_action( 'admin_init', 'wm_migrate_legacy_mega_menus' );
```

- [ ] **Step 2: Require the new file in `functions.php`**

Place it after the nav field require so CPT + field groups are registered first:

```php
require get_template_directory() . '/inc/acf/mega-menu-nav-field.php';
require get_template_directory() . '/inc/acf/mega-menu-migration.php';
```

- [ ] **Step 3: Verify with `php -l`**

Run: `php -l "inc/acf/mega-menu-migration.php"`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual check (note for verification phase) — DO NOT remove old field group yet**

The engineer running `verify` should load any wp-admin page once (migration runs
on `admin_init`), then:
- Confirm new `wm_mega_menu` posts exist (مگامنو → همه مگامنوها) matching the
  previously-configured menus (e.g. "ساعت زنانه", "ساعت مردانه" or whatever
  titles/trigger keys existed).
- Confirm `get_option('wm_mega_menu_migrated')` is now `1` (can check via
  `wp option get wm_mega_menu_migrated` if WP-CLI is available, or by confirming
  the migration doesn't create duplicate posts on a second admin page load).
- Open Appearance → Menus and confirm the nav items that previously had
  `wm-mega-trigger-{key}` CSS classes now have the "مگامنو" dropdown pre-filled
  with the corresponding migrated post.

Only proceed to Step 5 once this manual check passes.

- [ ] **Step 5: Remove the old "Mega Menu" tab/accordion/repeater from `home-fields.php`**

In `inc/acf/home-fields.php`, inside `wm_site_settings_header_footer_fields()`,
remove the entire block from the `field_wm_header_footer_tab_mega_menu` tab through
the `field_wm_header_mega_menu_end` accordion-close (currently lines 286-339 — the
two array entries `wm_site_settings_tab( 'field_wm_header_footer_tab_mega_menu',
'Mega Menu' )` and `wm_site_settings_accordion( 'field_wm_header_mega_menu_accordion',
'مدیریت مگامنو' )` through the closing `wm_site_settings_accordion(
'field_wm_header_mega_menu_end', '', 1 )`, including the `field_wm_header_mega_menus`
repeater array entry between them).

The result: the "Mobile Nav" tab's closing accordion
(`wm_site_settings_accordion( 'field_wm_mobile_nav_settings_end', '', 1 )`, line 284)
should be immediately followed by the "فوتر" tab
(`wm_site_settings_tab( 'field_wm_header_footer_tab_footer', 'فوتر' )`, currently
line 341), with nothing in between.

- [ ] **Step 6: Verify with `php -l`**

Run: `php -l "inc/acf/home-fields.php"`
Expected: `No syntax errors detected`

- [ ] **Step 7: Manual check (note for verification phase)**

The engineer running `verify` should reload the "هدر و فوتر" options page in
wp-admin and confirm:
- The "Mega Menu" tab is gone (only هدر, Mobile Nav, فوتر tabs remain).
- No PHP warnings/notices appear on the page.
- The previously-migrated `wm_mega_menu` posts and nav-item assignments (from Step
  4) are still intact and the frontend mega menu still renders correctly (covered
  fully in Task 8's end-to-end verification).

---

### Task 7: CSS positioning fix for mega menu panels

**Files:**
- Modify: `assets/css/components/header.css`

- [ ] **Step 1: Update `.wm-mega-menu__panel` to be absolutely positioned within `.wm-mega-menu`**

Current (lines 541-554):
```css
  .wm-mega-menu__panel {
    width: min(100% - 40px, var(--wm-content-width, 1200px));
    margin: 10px auto 0;
    padding: 24px;
    border: 1px solid var(--wm-color-border, #e5e0d8);
    border-radius: 26px;
    background: var(--wm-color-surface, #fff);
    box-shadow: 0 24px 64px rgba(17, 24, 39, 0.12);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 220ms ease, transform 220ms ease;
    pointer-events: none;
  }
```

Replace with (adds `position: absolute; inset-inline: 0; top: 0;` so every panel
occupies the same box regardless of source order — this is the fix for the second
panel rendering below the first):

```css
  .wm-mega-menu__panel {
    position: absolute;
    inset-inline: 0;
    top: 0;
    width: min(100% - 40px, var(--wm-content-width, 1200px));
    margin: 10px auto 0;
    padding: 24px;
    border: 1px solid var(--wm-color-border, #e5e0d8);
    border-radius: 26px;
    background: var(--wm-color-surface, #fff);
    box-shadow: 0 24px 64px rgba(17, 24, 39, 0.12);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 220ms ease, transform 220ms ease;
    pointer-events: none;
  }
```

- [ ] **Step 2: Verify `.wm-mega-menu` still establishes the positioning context**

Confirm lines 531-539 are unchanged (no edit needed, just confirm):
```css
@media (min-width: 1024px) {
  .wm-mega-menu {
    display: block;
    position: absolute;
    inset-inline: 0;
    top: 100%;
    z-index: 1100;
    pointer-events: none;
  }
```

`.wm-mega-menu__panel` with `position: absolute` is now positioned relative to this
`.wm-mega-menu` ancestor — so all panels stack in the exact same place.

- [ ] **Step 3: Manual check (note for verification phase)**

Deferred until Task 8's end-to-end check, which will hover/focus two different
mega-trigger nav items at desktop width and confirm both panels render in the same
on-screen position (no page reflow / panel pushed down).

---

### Task 8: Bump version constants and run final end-to-end verification

**Files:**
- Modify: `functions.php`
- Modify: `style.css`

- [ ] **Step 1: Bump `WATCHMID_VERSION` in `functions.php`**

Current:
```php
define( 'WATCHMID_VERSION', '0.4.31' );
```

Change to:
```php
define( 'WATCHMID_VERSION', '0.4.32' );
```

- [ ] **Step 2: Bump the `Version:` header in `style.css`**

Current (line 7): `Version: 0.4.31`
Change to: `Version: 0.4.32`

- [ ] **Step 3: Confirm full require order in `functions.php`**

After Tasks 1-6, the require block (originally lines 131-148) should read, in this
order (new lines bolded via comment markers below — comments are illustrative only,
do not add them to the file):

```php
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/helpers/home-data.php';
require get_template_directory() . '/inc/helpers/marketing-data.php';
require get_template_directory() . '/inc/acf/mega-menu-cpt.php';
require get_template_directory() . '/inc/acf/mega-menu-fields.php';
require get_template_directory() . '/inc/acf/mega-menu-nav-field.php';
require get_template_directory() . '/inc/acf/mega-menu-migration.php';
require get_template_directory() . '/inc/components/mega-menu.php';
require get_template_directory() . '/inc/components/site-header.php';
require get_template_directory() . '/inc/components/product-card.php';
require get_template_directory() . '/inc/components/product-carousel.php';
require get_template_directory() . '/inc/components/product-archive.php';
require get_template_directory() . '/inc/components/site-footer.php';
require get_template_directory() . '/inc/components/mobile-nav.php';
require get_template_directory() . '/inc/product-components.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/ajax/search.php';
require get_template_directory() . '/inc/customizer/design-settings.php';
require get_template_directory() . '/inc/patterns/register-patterns.php';
require get_template_directory() . '/inc/acf/design-tokens.php';
require get_template_directory() . '/inc/acf/home-fields.php';
```

- [ ] **Step 4: `php -l` on every modified/created PHP file**

```bash
php -l "functions.php"
php -l "inc/acf/mega-menu-cpt.php"
php -l "inc/acf/mega-menu-fields.php"
php -l "inc/acf/mega-menu-nav-field.php"
php -l "inc/acf/mega-menu-migration.php"
php -l "inc/acf/home-fields.php"
php -l "inc/components/mega-menu.php"
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: End-to-end browser verification (per `verify` skill)**

On the live site (`http://watchmid1.local/`):

1. Load any wp-admin page to trigger the migration (Task 6).
2. Confirm migrated `wm_mega_menu` posts exist and nav items have the "مگامنو"
   dropdown pre-assigned (carries forward Task 6 Step 4 checks if not already done).
3. On the front page at desktop width (≥1024px):
   - Hover/focus the first mega-trigger nav item → confirm its panel opens in the
     overlay slot below the header.
   - Move to the second mega-trigger nav item → confirm its panel opens in **the
     same on-screen position** as the first (this is the regression test for the
     positioning bug — previously the second panel rendered further down the
     page).
   - Confirm closing (mouseleave with delay, Escape, outside click) works for both.
4. Resize to mobile (<1024px) and confirm `.wm-mega-menu` remains hidden
   (`display: none`), matching pre-existing behavior (lines ~791-793 of
   `header.css`, unchanged by this plan).
5. Confirm the search modal (from the previous AJAX search feature) still opens,
   searches, and closes correctly — i.e. `header.js` changes in Task 5 didn't break
   anything else in the file.
6. Check browser console for JS errors across all of the above.

- [ ] **Step 6: Final manual check**

If any step in Step 5 fails, identify whether the issue is in the CPT data,
the `nav_menu_link_attributes`/`nav_menu_css_class` filters (Task 4), the JS
selector changes (Task 5), or the CSS positioning (Task 7), and fix in the
corresponding task's files before considering the plan complete.
