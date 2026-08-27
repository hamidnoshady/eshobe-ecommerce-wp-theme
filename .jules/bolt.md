## 2026-06-25 - WordPress Option Lookup Memoization Pattern
**Learning:** WordPress configuration array builder functions (like `wm_product_archive_filter_config`) that parse multiple taxonomy structures, hit standard WordPress option getters, and map/filter arrays multiple times are a prime target for redundant processing when rendering complex UI like shop sidebars and product listings. They are repeatedly invoked across multiple hooks or internal functions.
**Action:** Use a static variable pattern (`static $cache = null; if ( null !== $cache ) return $cache;`) inside configuration builder functions that are called frequently during a single request lifecycle but whose underlying state doesn't mutate.

## 2024-08-12 - O(n²) Array Filtering in Recursive Tree Rendering
**Learning:** In PHP, using `array_filter` inside a recursive tree rendering function to find a node's children creates an O(N²) time complexity bottleneck, especially when the total number of terms is large. The overhead of repeatedly scanning the entire array for every node at every depth significantly impacts frontend performance for deep or large taxonomies.
**Action:** When recursively rendering trees from a flat list, always pre-compute a parent-to-children map (e.g., `$hierarchy[ $parent_id ][] = $term`) once in a static variable. Similarly, use `array_flip` to convert active selection lists into hash maps (`isset($map[$key])`) to eliminate nested O(N) `in_array` lookups.
## 2023-10-27 - Double array processing in Product Gallery
**Learning:** `wm_render_product_gallery()` was calling `wm_get_product_gallery_ids( $product )` twice. Because `wm_get_product_gallery_ids` performs array manipulation (`array_merge`, `array_values`, `array_unique`, `array_filter`, `array_map`), repeating the call redundantly processes the image array.
**Action:** When a helper function performs array manipulation or object property extraction without internal caching, always assign its return value to a local variable and reuse that variable (e.g., using `count( $variable )`) rather than calling the function again for related derivations.

## 2026-06-25 - Memoizing ACF get_field('...', 'option') calls
**Learning:** Helper functions like `wm_get_option` that wrap ACF's `get_field('...', 'option')` can introduce severe performance bottlenecks because ACF option retrieval involves formatting and potentially extra database queries. When these helpers are called frequently (e.g. within `*_defaults()` and `*_get_option()` builders across different components), the overhead stacks up.
**Action:** Memoize wrapper functions for `get_field` using a static array cache (`static $cache = array();`) so that redundant lookups within the same request are prevented.
## 2024-05-18 - Memoize taxonomy descendant checks
**Learning:** Memoizing recursive descendant checks in taxonomy trees prevents O(N^2) evaluation overhead when generating or evaluating complex filter trees.
**Action:** Use a static `$memo` array to cache intermediate boolean results in recursive taxonomy logic, and invalidate it when the structure or selection state changes.
