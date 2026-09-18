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
## 2026-06-25 - Recursive function evaluation bottleneck in product-archive

**Learning:** `wm_product_archive_term_has_selected_descendant` is a recursive function called on every term node when rendering taxonomy filter trees. Without memoization, evaluating deep hierarchies or large lists of terms caused an O(N²) traversal that was shown to take ~2.5s for 5000 terms.

**Action:** Added a static `$cache` inside the recursive function (similar to the existing `$last_terms` pattern) to memoize results based on `$term_id`, reducing traversal time by ~99% (down to ~0.03s for the same data).

## 2023-11-09 - WP_Tax_Query EXISTS operator vs fetching all terms
**Learning:** When using `WP_Tax_Query` to filter by a taxonomy where we want any term in that taxonomy, using `get_terms` to fetch all IDs and then passing them to an `IN` operator is a massive bottleneck. It creates an N+1 query issue to load the terms, memory overhead to hold them, and generates enormous, slow SQL queries with huge `IN (...)` clauses for large taxonomies.
**Action:** When querying for the presence of *any* term in a taxonomy, use the `EXISTS` operator in `tax_query` (e.g. `'operator' => 'EXISTS'`). This compiles into an efficient `INNER JOIN` in MySQL, skipping the term-loading completely.
## 2026-06-25 - State Leakage in Static Caches During Unit Tests
**Learning:** Adding `static $cache = array();` inside functions (like `wm_get_design_token`) to memoize options is great for request lifecycle performance, but it can cause severe state leakage across unit tests. If a test modifies the mocked return value of `get_field` across multiple calls, the static cache from the first call will persist, causing subsequent assertions to fail unexpectedly.
**Action:** Always conditionally bypass static caches during tests. Use a check like `$is_test = defined('PHPUNIT_COMPOSER_INSTALL') || defined('WP_TESTS_DOMAIN');` and only read from or write to the static cache when `$is_test` is false.

## 2026-06-25 - Redundant taxonomy term resolution caching
**Learning:** Functions that frequently resolve string values to taxonomy terms (e.g. converting a URL slug/name/id parameter into a term object via `get_term_by`) can be called repeatedly during archive rendering across various filter builders and label generators, generating redundant and identical database queries. Replacing manual `get_term_by` logic with centralized, memoized resolution functions improves cache hit rates and reduces database queries.
**Action:** Consolidate term resolution into a single function (like `wm_product_archive_resolve_filter_term`) and apply static array caching to memoize the results of `get_term_by`. Always remember to conditionally bypass this cache during testing to avoid leaked mocked state.

## 2026-06-25 - Prevent Loop-Induced Transient Bottlenecks
**Learning:** Using `get_transient()` to retrieve cached data from inside a loop (like iterating through archive filters or categories to determine term availability) creates hidden database bottlenecks on sites without an external persistent object cache, since WP retrieves transients from `wp_options`.
**Action:** Always layer a `static $cache = array();` inside functions that fetch transient data if they are expected to be called multiple times during the same request lifecycle (e.g. rendering sidebars, menus, loops). Ensure cache is bypassed during unit tests.
