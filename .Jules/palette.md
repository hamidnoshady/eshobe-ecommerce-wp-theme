## 2024-05-16 - Manual CSS Minification Requirement
**Learning:** This WordPress theme does not use a build tool like Webpack, Vite, or a Node package manager (npm/pnpm). Minified CSS files (like `header.min.css` and `home.min.css`) are loaded directly and correspond to unminified versions.
**Action:** Always manually apply any CSS changes to BOTH the unminified file (e.g., `header.css`) AND its minified counterpart (`header.min.css`). When using search-and-replace, verify that strings are not unintentionally duplicated.

## 2024-08-09 - Icon-Only Button Accessibility in Product Components
**Learning:** Icon-only buttons used for expanding/collapsing UI panels (like the mobile purchase bottom bar handle) often lack accessible names, making them difficult for screen reader users to understand their purpose. Specifically, the `wm-mobile-bottom-bar__handle` relied purely on an SVG chevron hidden via `aria-hidden="true"` and `aria-expanded` attributes without an overarching `aria-label`.
**Action:** Always verify that interactive elements containing only presentational elements (like `aria-hidden` icons) have an explicit `aria-label` or `aria-labelledby` attribute. When implementing custom expandable panels, the toggle button needs a clear, localized name (e.g., `aria-label="<?php esc_attr_e( 'نمایش نوار خرید', 'eshobe-ecommerce' ); ?>"`).

## 2024-08-15 - [Translated Product Gallery Lightbox Aria-Label]
**Learning:** Found a hardcoded English `aria-label="Close"` in the dynamically injected HTML string for the product gallery lightbox, which breaks accessibility for Persian screen readers on a fully RTL theme.
**Action:** Replaced hardcoded English text with Persian equivalent ("بستن") in injected JS templates. Need to ensure future dynamically injected components use localized strings or localize them via `wp_localize_script()`.

## 2026-08-16 - Price Range Slider Accessibility
**Learning:** Native `<input type="range">` elements used in custom dual-thumb slider components (like the price filter in `inc/components/product-archive.php`) often lack explicit labels, making them difficult for screen reader users to identify their purpose (min vs max). Text inputs for manual entry accompanying the sliders can also lack labels if only visually associated via layout.
**Action:** Always verify that interactive form elements within custom widgets, such as sliders and their accompanying manual text inputs, have explicit, localized `aria-label` attributes (e.g., `aria-label="<?php esc_attr_e( 'حداقل قیمت', 'eshobe-ecommerce' ); ?>"`).

## 2026-08-20 - Custom Product Variation Swatches Accessibility
**Learning:** Product variation swatches (like colors or image swatches) that are rendered as custom `<button>` elements replacing standard dropdowns often lack visible text and rely entirely on visual cues or a `title` attribute. Screen readers need an explicit `aria-label` because the `title` attribute is not reliably announced by all screen readers in listbox contexts, especially on mobile devices.
**Action:** Always ensure that custom swatch buttons in variation forms include an explicit `aria-label` (e.g., `aria-label="<?php echo esc_attr( $term->name ); ?>"`) to provide an accessible name for screen reader users.

## 2026-10-25 - Visually Hidden Text in Icon-Only Buttons
**Learning:** Some buttons (like the header search toggle) contain both an icon and text, but the text is visually hidden via CSS (`display: none`) on certain viewpoints (e.g., mobile). Screen readers might not announce this visually hidden text reliably, effectively turning the button into an icon-only button without an accessible name.
**Action:** Always provide an explicit `aria-label` to buttons where the inner text is hidden via CSS (`display: none`), ensuring screen reader users can understand the button's purpose regardless of styling rules.
