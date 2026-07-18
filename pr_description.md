🧪 [testing] Add tests for wm_ajax_search_products

🎯 **What:** The testing gap addressed
This PR addresses the missing unit tests for the `wm_ajax_search_products` function, which handles the AJAX live product search for the header modal.

📊 **Coverage:** What scenarios are now tested
- Empty and missing search terms
- Short search terms (less than 2 characters)
- Valid search terms with no matching products
- Valid search terms with matching products
- Fallback logic for determining product brand (using category if brand is missing)
- Edge cases including handling products without images and ignoring non-visible products

✨ **Result:** The improvement in test coverage
The AJAX search functionality is now fully covered by unit tests using Brain\Monkey and PHPUnit, increasing confidence in future refactoring and ensuring the search logic remains stable.
