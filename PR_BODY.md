## Dropdown chevron and animated hover for non-mega submenus

Top-level menu items that have children (`.menu-item-has-children` and `.wm-mega-trigger`) now show a tiny inline-SVG chevron next to their label, rotating up when the item is hovered or active, so users get a clear affordance that the item opens something below.

Plain (non-mega) dropdowns get an entrance animation matching the theme tokens and the mega-menu link style: cubic-bezier ease, subtle scale (origin-aware for RTL) on the panel, and on submenu items a transparent-to-accent border with accent background, accent-dark text, and a small horizontal nudge on hover/focus.

The chevron is suppressed inside the tablet drawer where the submenu is always visible inline.

Theme version bumped from `0.5.1` to `0.5.2`.

## Files

- `assets/css/components/header.css` — chevron pseudo, panel entrance, submenu link hover
- `functions.php`, `style.css` — version bump
