## 2024-05-16 - Manual CSS Minification Requirement
**Learning:** This WordPress theme does not use a build tool like Webpack, Vite, or a Node package manager (npm/pnpm). Minified CSS files (like `header.min.css` and `home.min.css`) are loaded directly and correspond to unminified versions.
**Action:** Always manually apply any CSS changes to BOTH the unminified file (e.g., `header.css`) AND its minified counterpart (`header.min.css`). When using search-and-replace, verify that strings are not unintentionally duplicated.
