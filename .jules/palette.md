## 2024-05-16 - Manual CSS Minification Requirement
**Learning:** This WordPress theme does not use a build tool like Webpack, Vite, or a Node package manager (npm/pnpm). Minified CSS files (like `header.min.css` and `home.min.css`) are loaded directly and correspond to unminified versions.
**Action:** Always manually apply any CSS changes to BOTH the unminified file (e.g., `header.css`) AND its minified counterpart (`header.min.css`). When using search-and-replace, verify that strings are not unintentionally duplicated.

## 2024-08-09 - Icon-Only Button Accessibility in Product Components
**Learning:** Icon-only buttons used for expanding/collapsing UI panels (like the mobile purchase bottom bar handle) often lack accessible names, making them difficult for screen reader users to understand their purpose. Specifically, the `wm-mobile-bottom-bar__handle` relied purely on an SVG chevron hidden via `aria-hidden="true"` and `aria-expanded` attributes without an overarching `aria-label`.
**Action:** Always verify that interactive elements containing only presentational elements (like `aria-hidden` icons) have an explicit `aria-label` or `aria-labelledby` attribute. When implementing custom expandable panels, the toggle button needs a clear, localized name (e.g., `aria-label="<?php esc_attr_e( 'نمایش نوار خرید', 'eshobe-ecommerce' ); ?>"`).

## 2026-11-20 - Expand/Collapse Accessibility with aria-controls
**Learning:** Custom expand/collapse toggle buttons (like the one used for showing the coupon code input field on the checkout page) that use `aria-expanded` without a corresponding `aria-controls` attribute are less accessible for screen reader users, as the relationship to the content block being toggled is not explicitly defined.
**Action:** When creating or modifying toggle buttons that show/hide content, always ensure the target content block has an `id` attribute, and the toggle buttons have a matching `aria-controls` attribute.
