# Mega Menu Rebuild — Design Spec

## Background

The current mega menu system is configured via a single flat ACF repeater field
(`wm_header_mega_menus`) on the "هدر و فوتر" options page, accordion "مدیریت مگامنو"
(`inc/acf/home-fields.php`). Every mega menu, with all of its columns and links,
is expanded inline at once — as the number of menus/columns/links grows this becomes
very hard to navigate and edit.

Linking a mega menu to a nav item currently requires the admin to manually type a
CSS class (`wm-mega-trigger-{key}`) into the menu item's "CSS Classes" field in the
WordPress nav menu editor, where `{key}` must match the `mega_trigger_key` field
value typed into the repeater. This is fragile and error-prone.

On the frontend (`inc/components/mega-menu.php`, `assets/css/components/header.css`,
`assets/js/header.js`), there is also a layout bug: `.wm-mega-menu__panel` elements
are not individually positioned. They sit in normal document flow inside
`.wm-mega-menu` (which is `position: absolute`), so when a second mega menu panel is
opened it renders below the first panel's reserved space rather than in the same
overlay location — visually pushing content down the page.

## Goals

1. Replace the flat repeater with a **"Mega Menu" custom post type** (`wm_mega_menu`),
   giving each mega menu its own edit screen and a list-table overview — easier to
   create, find, and manage as the number of menus grows.
2. Within each Mega Menu post, keep columns and links as ACF repeaters but **collapse
   each row to a one-line summary** (column title / link label) so editing a large
   menu doesn't mean scrolling through everything expanded.
3. Replace the manual CSS-class linking mechanism with an **auto-match dropdown**:
   add a "مگامنو" Post Object field to the WordPress nav menu item editor, letting
   admins pick a Mega Menu post directly per menu item.
4. Fix the **frontend positioning bug** so every mega menu panel renders in the same
   absolutely-positioned overlay slot below the header, regardless of which menu
   item triggered it.
5. **Migrate existing data**: the two mega menus currently configured via the old
   repeater ("ساعت زنانه" / "ساعت مردانه" or whichever entries exist) must be
   converted to `wm_mega_menu` posts automatically, and — best effort — auto-assigned
   to the nav menu items that currently reference them via `wm-mega-trigger-{key}`
   CSS classes.
6. **Don't break anything**: the site must continue to render correctly (header,
   nav, mega menu, search modal, etc.) throughout and after this change. The old
   repeater field group and old frontend trigger-key/class mechanism are removed
   only after the migration path is in place and verified.

## Non-goals

- Search modal open/close animations are tracked as a separate follow-up task and
  are NOT part of this spec.
- No changes to the mobile nav (`inc/components/mobile-nav.php`) mega-menu-equivalent
  behavior beyond what's needed to keep it working — mobile already hides
  `.wm-mega-menu` entirely (`display: none` under 1024px) and presumably uses its
  own structure; this spec preserves that.
- No new visual design for the mega menu panel itself (columns/links/feature card
  markup and styling stay as-is) — only the *positioning* of the panel changes.

## Architecture

### 1. New CPT: `wm_mega_menu`

Registered in a new file `inc/acf/mega-menu-cpt.php` (required from `functions.php`):

- `register_post_type( 'wm_mega_menu', [...] )`
  - `public` => false, `show_ui` => true, `show_in_menu` => true
  - `labels`: Persian — "مگامنو" / "مگامنوها" / "افزودن مگامنو" etc.
  - `supports`: `array( 'title' )` (title field doubles as the admin-facing menu name,
    e.g. "ساعت زنانه")
  - `menu_icon`: a relevant dashicon (e.g. `dashicons-menu-alt`)
  - No public archive/single template needed (`publicly_queryable` => false,
    `exclude_from_search` => true).

### 2. ACF field group for `wm_mega_menu` posts

New file `inc/acf/mega-menu-fields.php` (required from `functions.php`), field group
`group_wm_mega_menu_fields`, location: Post Type = `wm_mega_menu`.

Fields (all on the post edit screen, no tabs needed — one menu per screen keeps it
manageable):

- `mega_subtitle` (textarea, 2 rows) — "توضیح کوتاه مگامنو" (optional; the post title
  serves as `mega_title`)
- `mega_columns` (repeater, collapsed, `button_label` = "افزودن ستون"):
  - row label/summary: `column_title` (so collapsed rows show the column's title)
  - `column_enabled` (true_false, default 1, ui)
  - `column_title` (text)
  - `column_links` (nested repeater, collapsed, `button_label` = "افزودن لینک"):
    - row label/summary: `link_label`
    - `link_enabled` (true_false, default 1, ui)
    - `link_label` (text)
    - `link_url` (url)
    - `link_badge` (text)
    - `link_description` (text)
    - `link_icon` (image, return_format array, preview_size thumbnail)
- `mega_feature_card_enabled` (true_false, default 0, ui) — "نمایش کارت ویژه"
- `mega_feature_title` (text)
- `mega_feature_text` (textarea, 3 rows)
- `mega_feature_image` (image, return_format array, preview_size medium)
- `mega_feature_url` (url)
- `mega_feature_button_text` (text, default "مشاهده")

ACF repeaters support a `collapsed` setting (sub-field key) to set which sub-field
shows in the collapsed row summary — use `column_title` and `link_label`
respectively.

### 3. Removing the old repeater field group

In `inc/acf/home-fields.php`:
- Remove the "Mega Menu" tab/accordion (`field_wm_header_footer_tab_mega_menu`,
  `field_wm_header_mega_menu_accordion` through `field_wm_header_mega_menu_end`,
  lines ~286-339) and the `wm_header_mega_menus` repeater field entirely.
- This is safe to do only **after** the migration (Section 6) has run, since the
  migration reads from this field's stored data (`get_field('wm_header_mega_menus', 'option')`)
  before it disappears. The migration runs once on `admin_init` (or theme
  activation) and is idempotent (checks a flag so it doesn't re-run / doesn't
  duplicate posts).

### 4. Nav menu item linking field

New file `inc/acf/mega-menu-nav-field.php` (required from `functions.php`):

- ACF field group `group_wm_mega_menu_nav_field`, location: Nav Menu = (all menus)
  — i.e. `'param' => 'nav_menu_item', 'operator' => '==', 'value' => 'all'` (ACF
  supports `nav_menu_item` location targeting all menu items when no specific menu
  is targeted, falling back to `'value' => '0'` per ACF docs for "all").
- One field: `wm_mega_menu_post` — Post Object, post_type = `wm_mega_menu`,
  return_format = `id`, allow_null = true, ui = true. Label: "مگامنو".
- This field appears in the nav menu item's expanded settings in
  Appearance → Menus, alongside "Navigation Label", "CSS Classes", etc.

### 5. Frontend: linking nav items to mega menu data

In `inc/components/mega-menu.php`:

- `wm_get_header_mega_menus()` is rewritten:
  - Query published `wm_mega_menu` posts via `get_posts()` (`post_type =>
    'wm_mega_menu', 'post_status' => 'publish', 'numberposts' => -1`).
  - For each post, build the same shape as before (`mega_title` ← post title,
    `mega_subtitle`, `mega_columns`, feature fields ← `get_field()` on the post),
    keyed by **post ID** instead of `mega_trigger_key`.
  - Same emptiness-skipping logic as before (skip if no columns, no feature, no
    intro).
  - Returned array is keyed/tagged by `mega_trigger_key` = `(string) $post->ID` for
    minimal downstream changes (panel `data-mega-key` attribute keeps working the
    same way, just holding a numeric post ID as a string).

- `wm_render_header_mega_menus()`: unchanged markup, `data-mega-key` now holds the
  post ID string.

In `inc/components/site-header.php` (`wm_header_render_menu()`):

- Add a `nav_menu_link_attributes` filter (registered in `mega-menu.php`,
  scoped/unhooked appropriately or just always-on since it only acts when the field
  is set) that:
  - Reads `get_field( 'wm_mega_menu_post', $item->ID )` for the current nav menu
    item (`$item` is available via the 4th filter argument `$args`/`$item` per
    `nav_menu_link_attributes` signature — actually the correct hook for per-item
    data is `nav_menu_css_class` for the `<li>`, combined with
    `nav_menu_link_attributes` for the `<a>`; the spec uses `nav_menu_css_class` to
    add a class and a `wp_nav_menu_objects` filter to stash a data attribute, OR
    simpler: filter `nav_menu_link_attributes` which receives `$item` directly).
  - If a mega menu post is assigned and published, adds:
    - to the `<a>`: `data-mega-key="{post_id}"`, `aria-haspopup="true"`,
      `aria-expanded="false"`
    - to the `<li>`: class `wm-mega-trigger` (generic, no longer per-key)
- This replaces the manual `wm-mega-trigger-{key}` class convention entirely.

### 6. JS changes (`assets/js/header.js`)

- `getMegaKeyFromTrigger(trigger)`: replace class-name parsing with reading
  `trigger.querySelector('a')?.dataset.megaKey` (or `trigger.dataset.megaKey` if the
  data attribute ends up on the `<li>` — finalize during implementation based on
  where the attribute is actually rendered; spec requires it be reachable from the
  trigger element passed to `getMegaKeyFromTrigger`).
- `megaTriggers` selector changes from `[class*="wm-mega-trigger-"]` to
  `.wm-mega-trigger` (or `[data-mega-key]` — pick whichever matches where the
  attribute lives).
- `getMegaTrigger(key)`: changes from
  `.wm-mega-trigger-{key}` class selector to `[data-mega-key="{key}"]` attribute
  selector (CSS.escape still applied to the key for safety, though post IDs are
  numeric).
- All other logic (open/close, hover/focus, escape, outside-click, desktop/mobile
  query) stays the same — it operates on `key` strings and doesn't care that they're
  now numeric IDs instead of slugs.

### 7. CSS positioning fix (`assets/css/components/header.css`)

Within the existing `@media (min-width: 1024px)` block:

- `.wm-mega-menu` stays `position: absolute; inset-inline: 0; top: 100%; z-index:
  1100; pointer-events: none;` (no change — it's already the correct positioning
  context).
- `.wm-mega-menu__panel` gets `position: absolute; inset-inline: 0; top: 0;` added,
  so every panel occupies the exact same box within `.wm-mega-menu` regardless of
  source order. Combine with existing `width: min(100% - 40px, var(--wm-content-width,
  1200px)); margin: 10px auto 0;` (these still center the panel horizontally within
  the full-width absolutely-positioned parent).
- The active-state rule block (`.is-active` / `.is-open` / `[aria-hidden="false"]`)
  stays the same (`display: block !important`, `opacity: 1`, etc.) — now that all
  panels share the same absolute box, only the active one being visible is what
  prevents overlap, exactly as a single-slot overlay.

### 8. Migration (one-time, idempotent)

New file `inc/acf/mega-menu-migration.php` (required from `functions.php`), hooked
on `admin_init`:

```php
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

        $post_id = wp_insert_post( array(
            'post_type'   => 'wm_mega_menu',
            'post_title'  => $title,
            'post_status' => 'publish',
        ) );

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

    // Best-effort: assign new mega menu posts to nav items that used the old
    // wm-mega-trigger-{key} CSS class.
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
                    if ( strpos( $class, 'wm-mega-trigger-' ) === 0 ) {
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

This runs once, is safe to re-run accidentally (guarded by the
`wm_mega_menu_migrated` option), and leaves the old `wm_header_mega_menus` option
data in place (untouched — ACF options data isn't deleted, just the field group
definition is removed from the UI in step 3).

## Data flow summary

```
Admin creates/edits "Mega Menu" CPT post (columns, links, feature card)
        |
        v
Admin opens Appearance > Menus, picks the Mega Menu post in the
"مگامنو" dropdown for a nav item
        |
        v
wp_nav_menu() renders <li class="wm-mega-trigger"><a data-mega-key="{post_id}">
        |
        v
wm_render_header_mega_menus() outputs one .wm-mega-menu__panel
  per published Mega Menu post, data-mega-key="{post_id}"
        |
        v
header.js: hover/focus on .wm-mega-trigger reads data-mega-key from its <a>,
  shows the matching .wm-mega-menu__panel (single absolute overlay slot)
```

## Testing / Verification

- `php -l` on all new/modified PHP files.
- Live browser check (per CLAUDE.md `verify` skill):
  - Confirm the migration ran: new "مگامنو" CPT entries exist in wp-admin matching
    the previously-configured menus.
  - Confirm nav menu items show the "مگامنو" dropdown in Appearance → Menus, with
    the migrated assignments pre-selected for the previously-working triggers.
  - On the front page at desktop width (≥1024px), hover/focus each mega-trigger nav
    item and confirm:
    - The first mega menu panel opens in the correct overlay position.
    - The second mega menu panel **also opens in the same position** (not pushed
      down the page) — this directly verifies the positioning fix.
    - Closing (mouseleave/escape/outside click) still works.
  - Confirm `.wm-mega-menu` remains hidden on mobile (<1024px) as before.
  - Confirm the rest of the header (search modal, account, cart) is unaffected.

## Risks / Open Questions

- **ACF nav menu item field group location rule for "all menus"**: ACF's
  `nav_menu_item` location type historically requires either `'value' => '0'`
  (meaning "any/all") or listing each menu's term ID. The implementer should verify
  the exact `'value'` that makes the field appear on every menu in this ACF version,
  and adjust if `'0'` doesn't work as expected — falling back to listing all current
  menu term IDs if necessary, with a comment explaining the limitation (new menus
  created later may need the field group's location rules updated, or use `'all'`
  if supported).
- **Where `data-mega-key` lives** (on `<li>` vs `<a>`): the spec recommends the
  `<a>` via `nav_menu_link_attributes`, but the implementer should verify this filter
  receives the `$item` object (with its ACF fields accessible via `$item->ID`) in
  the WordPress/ACF version in use, and adjust to `nav_menu_css_class` +
  `wp_nav_menu_objects` if needed. Either way, `header.js` must be updated to match
  wherever the attribute ends up.
