# Auto

## Configuration
- **Artifacts Path**: {@artifacts_path} → `.zenflow/tasks/{task_id}`

## Agent Instructions

Ask the user questions when anything is unclear or needs their input. This includes:
- Ambiguous or incomplete requirements
- Technical decisions that affect architecture or user experience
- Trade-offs that require business context

Do not make assumptions on important decisions — get clarification first.

**Debug requests, questions, and investigations:** answer or investigate first. Do not create a plan upfront — the user needs an answer, not a plan. A plan may become relevant later once the investigation reveals what needs to change.

**For all other tasks**, before writing any code, assess the scope of the actual change (not the prompt length — a one-sentence prompt can describe a large feature). Scale your approach:

- **Trivial** (typo, config tweak, single obvious change): implement directly, no plan needed.
- **Small** (a few files, clear what to do): write 2–3 sentences in `plan.md` describing what and why, then implement. No substeps.
- **Medium** (multiple components, design decisions, edge cases): write a plan in `plan.md` with requirements, affected files, key decisions, verification. Break into 3–5 steps.
- **Large** (new feature, cross-cutting, unclear scope): gather requirements and write a technical spec first (`requirements.md`, `spec.md` in `{@artifacts_path}/`). Then write `plan.md` with concrete steps referencing the spec.

**Skip planning and implement directly when** the task is trivial, or the user explicitly asks to "just do it" / gives a clear direct instruction.

To reflect the actual purpose of the first step, you can rename it to something more relevant (e.g., Planning, Investigation). Do NOT remove meta information like comments for any step.

Rule of thumb for step size: each step = a coherent unit of work (component, endpoint, test suite). Not too granular (single function), not too broad (entire feature). Unit tests are part of each step, not separate.

Update `{@artifacts_path}/plan.md` if it makes sense to have a plan and task has more than 1 big step.

## Task Plan: Mobile navbar shop drill-down menu

### [x] Step: Investigation
- Read `assets/js/mobile-nav.js`, `assets/css/components/mobile-nav.css`, and `inc/components/mobile-nav.php`.
- Identified: shop sheet renders top-level primary-menu links as plain `<a>`; no child tree is exposed in mobile-sheet context.

### [x] Step: PHP — render hierarchical shop menu with drill-down sub-views
Files: `inc/components/mobile-nav.php`
- Replaced flat-list `wm_mobile_nav_primary_links()` with a hierarchical tree via new `wm_mobile_nav_primary_tree()` (groups children by `menu_item_parent`).
- `wm_render_mobile_shop_sheet()` keeps the root view (top-level items); for each parent with children it renders a sibling drill-down panel (`data-mobile-shop-view`) inside the same sheet body, with parent name as header label, right-arrow back button, and the children list. The parent entry in the root view becomes a `<button class="wm-mobile-shop-sheet__parent">` with `data-mobile-shop-open` and a left-arrow child indicator.
- Fallback links array is still produced (with `id=0`, no children) so direct callers of `wm_mobile_nav_primary_links()` keep working.

### [x] Step: JS — open/close drill-down sub-views
File: `assets/js/mobile-nav.js`
- Delegated handler on `document` listens for clicks on `[data-mobile-shop-open]` and `[data-mobile-shop-back]`. Open swaps visibility & sets `aria-expanded="true"` on the trigger; back reads `data-mobile-shop-parent` on the active sub-view and shows the corresponding ancestor. Body scroll resets to top on every transition.
- `resetShopStage()` collapses all panes to root and clears `aria-expanded` — invoked on page load and on `closeSheets()`.

### [x] Step: JS — minified mirror
File: `assets/js/mobile-nav.min.js`
- Minified via local helper dropped on completion. Re-verified with `node --check` and a smoke test that confirmed the IIFE parses without runtime errors.

### [x] Step: CSS — drill-down styles + arrows
File: `assets/css/components/mobile-nav.css`
- `.wm-mobile-shop-sheet__parent` shares the chip pill styling of existing `.wm-mobile-sheet__links a`; gains `justify-content: space-between` to push the arrow icon to the inline-end (visually left in RTL).
- `.wm-mobile-shop-sheet__arrow` 18×18 chevron SVG holder; stroke colors inherit from the parent muted tone.
- `.wm-mobile-shop-sheet__subview` + `.wm-mobile-shop-sheet__pane` stack as flex columns with `gap: 8px`. Hidden panes forced `display: none !important`.
- `.wm-mobile-shop-sheet__back` 38×38 outlined chip holding a right-pointing chevron SVG (RTL: appears on the inline-start / right side, parent name beside it).
- `.wm-mobile-shop-sheet__subtitle` 14px bold, primary color.
- Active parent (`aria-expanded="true"`) gets a subtle accent-tinted background to mirror the open state.

### [x] Step: Version bump
Files: `functions.php` (ESHOBE_ECOMMERCE_VERSION), `style.css` (Version header).
- 0.5.2 → 0.5.3 (patch). Per CLAUDE.md workflow.
