# 🧹 Refactor duplicated code in theme-updater.php

🎯 **What:** The code health issue addressed is a duplicated code block inside `check_for_update` method in `inc/theme-updater.php`. The array construction for `$transient->response` and `$transient->no_update` shared multiple identical key-value assignments.
💡 **Why:** How this improves maintainability: By extracting the common base array into a variable `$update_data` and conditionally appending the 'new_version' key, we reduce duplication, making the code cleaner and less prone to copy-paste errors when changing the structure in the future.
✅ **Verification:** How I confirmed the change is safe: I successfully ran PHPUnit tests via `./vendor/bin/phpunit` before and after the change, ensuring that my refactoring did not introduce any regressions.
✨ **Result:** The improvement achieved is a cleaner and slightly more DRY `check_for_update` function inside the updater without any modification in behavioral logic.
