💡 **What:**
Replaced the individual `wc_get_product($related_id)` calls inside the `$related_ids` loop with a single batch `wc_get_products()` call. We fetch all related products at once and store them in an associative array keyed by ID for O(1) lookups inside the existing view logic loop.

🎯 **Why:**
The previous implementation suffered from an N+1 query problem, as it called `wc_get_product()` on every iteration of the `foreach ( $related_ids as $related_id )` loop. If those products were not present in the object cache, this would result in a separate database query for each related product, degrading performance linearly with the number of related items shown.

📊 **Measured Improvement:**
Since this project's tests run in isolation using Brain Monkey without a fully booted WordPress database, a reliable database I/O benchmark is impractical to run via unit tests. However, the theoretical optimization turns an O(N) database query pattern (N = number of related products) into an O(1) bulk fetch operation, resulting in significantly fewer network round-trips and lower database contention when the object cache is cold.
