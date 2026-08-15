## 2024-05-16 - Manual CSS Minification Requirement
**Learning:** This WordPress theme does not use a build tool like Webpack, Vite, or a Node package manager (npm/pnpm). Minified CSS files (like `header.min.css` and `home.min.css`) are loaded directly and correspond to unminified versions.
**Action:** Always manually apply any CSS changes to BOTH the unminified file (e.g., `header.css`) AND its minified counterpart (`header.min.css`). When using search-and-replace, verify that strings are not unintentionally duplicated.

## 2024-08-09 - Icon-Only Button Accessibility in Product Components
**Learning:** Icon-only buttons used for expanding/collapsing UI panels (like the mobile purchase bottom bar handle) often lack accessible names, making them difficult for screen reader users to understand their purpose. Specifically, the `wm-mobile-bottom-bar__handle` relied purely on an SVG chevron hidden via `aria-hidden="true"` and `aria-expanded` attributes without an overarching `aria-label`.
**Action:** Always verify that interactive elements containing only presentational elements (like `aria-hidden` icons) have an explicit `aria-label` or `aria-labelledby` attribute. When implementing custom expandable panels, the toggle button needs a clear, localized name (e.g., `aria-label="<?php esc_attr_e( 'نمایش نوار خرید', 'eshobe-ecommerce' ); ?>"`).
## 2024-08-15 - [Translated Product Gallery Lightbox Aria-Label]
**Learning:** Found a hardcoded English `aria-label="Close"` in the dynamically injected HTML string for the product gallery lightbox, which breaks accessibility for Persian screen readers on a fully RTL theme.
**Action:** Replaced hardcoded English text with Persian equivalent ("بستن") in injected JS templates. Need to ensure future dynamically injected components use localized strings or localize them via `wp_localize_script()`.
