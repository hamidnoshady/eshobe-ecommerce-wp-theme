🧪 Add tests for wm_get_product_brand_terms and fix CI dist checkout auth

🎯 **What:** The testing gap for `wm_get_product_brand_terms` is addressed by setting up PHPUnit and writing comprehensive tests. I also fixed the CI GitHub actions failure (`Bad credentials`) when checking out the dist repo.
📊 **Coverage:** All 4 execution paths of `wm_get_product_brand_terms` are tested: match on 1st taxonomy, match on 2nd, match on 3rd, and no match.
✨ **Result:** Test coverage for this function goes from 0% to 100%, preventing regressions in future refactoring. The GitHub Actions workflows will now correctly checkout the dist repository using the proper GitHub token.
