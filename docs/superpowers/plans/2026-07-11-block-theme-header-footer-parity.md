# Header/Footer Visual Parity Implementation Plan (Phase 1b)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `eshobe-block-theme`'s header and footer visually match `eshobe-ecommerce-wp-theme`'s, using real ported CSS/JS on the new block markup, without pulling in any of the deferred subsystems (search-AJAX, OTP auth, mega menu, mobile/tablet nav, cart drawer, decorative motifs).

**Architecture:** New CSS/JS files enqueued by the new theme's `functions.php`, targeting fresh `eshobe-*` classes added to block markup in `parts/header.html` and `parts/footer.html`. No PHP template logic — pure static block markup plus vanilla JS, matching the old theme's actual computed styles/behavior for the in-scope elements.

**Tech Stack:** WordPress block markup (`wp:group`, `wp:site-logo`, `wp:site-title`, `wp:navigation`, `wp:search`, `wp:html` for icon-link markup that blocks can't express), plain CSS, plain JS (`IIFE`, `addEventListener`), enqueued via `wp_enqueue_style`/`wp_enqueue_script` in `functions.php`.

## Global Constraints

- Never activate `eshobe-block-theme` on the live site — verify via `?wp_theme_preview=eshobe-block-theme` on frontend URLs (per `eshobe-block-theme/CLAUDE.md`).
- Never edit `wp-content/themes/eshobe-ecommerce-wp-theme/` — it is the reference source only.
- All colors/spacing must reference existing `theme.json` custom properties (`var(--wp--preset--color--*)`, `var(--wp--custom--radius--lg)`, `var(--wp--style--global--content-size)`) — never hardcode a token value that already has a CSS var.
- No search-AJAX, OTP account dropdown, cart drawer, mega menu, tablet nav drawer, or decorative motifs in this phase — search/account/cart render as plain functional controls only (a working `core/search` block, plain links to `/my-account/` and `/cart/`).
- `studio wp` only, never bare `wp` — per site root `STUDIO.md`.
- Every verification step must actually be run (via `studio wp eval` or the browser) and its output checked before checking off a step.

---

### Task 1: Header — CSS, markup, sticky-scroll JS

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/assets/css/header.css`
- Create: `wp-content/themes/eshobe-block-theme/assets/js/header.js`
- Modify: `wp-content/themes/eshobe-block-theme/parts/header.html` (full replace)
- Modify: `wp-content/themes/eshobe-block-theme/functions.php` (add enqueue)

**Interfaces:**
- Produces: classes `eshobe-header`, `eshobe-header__topbar`, `eshobe-header__inner`, `eshobe-header__brand`, `eshobe-header__nav`, `eshobe-header__actions`, `eshobe-header__action`, `eshobe-header__search` — Task 2 (footer) does not depend on these, but any later phase touching the header must reuse this naming, not invent a second `wm-`-prefixed set.

- [ ] **Step 1: Create `assets/css/header.css`**

```css
.eshobe-header,
.eshobe-header * {
	box-sizing: border-box;
}

.eshobe-header {
	position: relative;
	direction: rtl;
	z-index: 900;
}

.eshobe-header.is-sticky {
	position: sticky;
	top: 0;
}

.admin-bar .eshobe-header.is-sticky {
	top: 32px;
}

.eshobe-header__topbar {
	border-bottom: 1px solid rgba(229, 224, 216, 0.82);
	background: var(--wp--preset--color--background);
	color: var(--wp--preset--color--muted);
	font-size: 12px;
	font-weight: 650;
}

.eshobe-header__topbar-inner {
	width: min(100% - 40px, var(--wp--style--global--content-size, 1200px));
	min-height: 32px;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto;
	padding: 7px 0;
	white-space: nowrap;
}

.eshobe-header__topbar-item {
	display: inline-flex;
	align-items: center;
	color: inherit;
}

.eshobe-header__topbar-item:not(:last-child)::after {
	content: "";
	width: 4px;
	height: 4px;
	margin-inline: 12px;
	border-radius: 50%;
	background: var(--wp--preset--color--accent);
	opacity: 0.78;
}

.eshobe-header__inner {
	width: min(100% - 40px, var(--wp--style--global--content-size, 1200px));
	min-height: 78px;
	display: grid;
	grid-template-columns: minmax(190px, auto) minmax(0, 1fr) auto;
	align-items: center;
	gap: 28px;
	margin: 12px auto 0;
	padding: 12px 18px;
	border: 1px solid var(--wp--preset--color--border);
	border-radius: 22px;
	background: var(--wp--preset--color--background);
	box-shadow: 0 14px 34px rgba(17, 24, 39, 0.06);
	transition: min-height 180ms ease, margin 180ms ease, box-shadow 180ms ease;
}

.eshobe-header.is-scrolled .eshobe-header__inner {
	min-height: 66px;
	margin-top: 8px;
	box-shadow: 0 16px 38px rgba(17, 24, 39, 0.09);
}

.eshobe-header__brand {
	min-width: 0;
	color: var(--wp--preset--color--primary);
}

.eshobe-header__brand .wp-block-site-logo {
	margin: 0;
}

.eshobe-header__brand img {
	display: block;
	max-width: 180px;
	max-height: 60px;
	width: auto;
	height: auto;
}

.eshobe-header__site-name,
.eshobe-header__site-name a {
	overflow: hidden;
	color: var(--wp--preset--color--primary);
	font-size: 16px;
	font-weight: 850;
	line-height: 1.45;
	text-overflow: ellipsis;
	white-space: nowrap;
	text-decoration: none;
}

.eshobe-header__nav {
	min-width: 0;
	justify-self: center;
}

.eshobe-header__nav .wp-block-navigation__container {
	gap: 4px;
}

.eshobe-header__nav .wp-block-navigation-item > .wp-block-navigation-item__content {
	position: relative;
	display: inline-flex;
	align-items: center;
	min-height: 38px;
	padding: 0 11px;
	border-radius: 13px;
	color: var(--wp--preset--color--primary);
	font-size: 13px;
	font-weight: 760;
	line-height: 1.5;
	text-decoration: none;
	transition: color 180ms ease, background 180ms ease;
}

.eshobe-header__nav .wp-block-navigation-item > .wp-block-navigation-item__content::after {
	content: "";
	position: absolute;
	right: 12px;
	bottom: 5px;
	left: 12px;
	height: 2px;
	border-radius: 999px;
	background: var(--wp--preset--color--accent);
	opacity: 0;
	transform: scaleX(0.45);
	transition: opacity 180ms ease, transform 180ms ease;
}

.eshobe-header__nav .wp-block-navigation-item:hover > .wp-block-navigation-item__content,
.eshobe-header__nav .wp-block-navigation-item.current-menu-item > .wp-block-navigation-item__content {
	color: var(--wp--preset--color--accent-dark);
	background: rgba(200, 155, 60, 0.07);
}

.eshobe-header__nav .wp-block-navigation-item:hover > .wp-block-navigation-item__content::after,
.eshobe-header__nav .wp-block-navigation-item.current-menu-item > .wp-block-navigation-item__content::after {
	opacity: 1;
	transform: scaleX(1);
}

.eshobe-header__nav .wp-block-navigation__submenu-container {
	min-width: 210px;
	padding: 10px;
	border: 1px solid rgba(229, 224, 216, 0.95);
	border-radius: 18px;
	background: var(--wp--preset--color--surface);
	box-shadow: 0 18px 40px rgba(17, 24, 39, 0.1);
}

.eshobe-header__actions {
	position: relative;
	display: inline-flex;
	align-items: center;
	justify-content: flex-end;
	gap: 8px;
}

.eshobe-header__action {
	position: relative;
	min-height: 40px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 7px;
	padding: 0 12px;
	border: 1px solid rgba(229, 224, 216, 0.95);
	border-radius: 999px;
	background: rgba(255, 255, 255, 0.92);
	color: var(--wp--preset--color--primary);
	font-size: 12.5px;
	font-weight: 800;
	line-height: 1;
	text-decoration: none;
	box-shadow: 0 8px 20px rgba(17, 24, 39, 0.045);
	cursor: pointer;
	transition: color 180ms ease, border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, background 180ms ease;
}

.eshobe-header__action:hover,
.eshobe-header__action:focus {
	border-color: rgba(200, 155, 60, 0.48);
	background: #fff;
	color: var(--wp--preset--color--accent-dark);
	box-shadow: 0 12px 24px rgba(200, 155, 60, 0.1);
	transform: translateY(-1px);
}

.eshobe-header__action-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 18px;
	height: 18px;
}

.eshobe-header__action-icon svg {
	width: 18px;
	height: 18px;
	display: block;
}

.eshobe-header__search {
	margin: 0;
}

.eshobe-header__search .wp-block-search__label {
	display: none;
}

.eshobe-header__search .wp-block-search__inside-wrapper {
	max-width: 220px;
	min-height: 40px;
	border: 1px solid var(--wp--preset--color--border);
	border-radius: 999px;
	overflow: hidden;
}

.eshobe-header__search .wp-block-search__input {
	border: 0;
	padding: 0 14px;
}

.eshobe-header__search .wp-block-search__button {
	border: 0 !important;
	border-radius: 0 !important;
	background: var(--wp--preset--color--primary) !important;
	color: #fff !important;
}

@media (max-width: 767px) {
	.eshobe-header__topbar {
		display: none;
	}

	body:not(.admin-bar) .eshobe-header.is-sticky {
		top: 0;
	}

	.admin-bar .eshobe-header.is-sticky {
		top: 46px;
	}

	.eshobe-header__inner {
		width: min(100% - 24px, var(--wp--style--global--content-size, 1200px));
		grid-template-columns: minmax(0, 1fr) auto;
		gap: 10px;
		min-height: 60px;
		margin-top: 0;
		padding: 10px 12px;
		border-radius: 18px;
		box-shadow: 0 10px 28px rgba(17, 24, 39, 0.06);
	}

	.eshobe-header__nav {
		display: none;
	}

	.eshobe-header__action-text {
		display: none;
	}

	.eshobe-header__action {
		width: 38px;
		min-height: 38px;
		padding: 0;
	}
}
```

- [ ] **Step 2: Create `assets/js/header.js`** (ported near-verbatim from the old theme's sticky-scroll logic — the only part of the old `header.js` not tied to mega menu/tablet drawer)

```js
(function () {
	var header = document.querySelector('.eshobe-header');

	if (header && header.classList.contains('is-sticky')) {
		var updateScrolledState = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 16);
		};

		updateScrolledState();
		window.addEventListener('scroll', updateScrolledState, { passive: true });
	}
})();
```

- [ ] **Step 3: Replace `parts/header.html` in full**

```html
<!-- wp:group {"tagName":"header","className":"eshobe-header is-sticky","layout":{"type":"default"}} -->
<header class="wp-block-group eshobe-header is-sticky">

	<!-- wp:html -->
	<div class="eshobe-header__topbar">
		<div class="eshobe-header__topbar-inner">
			<span class="eshobe-header__topbar-item">ضمانت اصالت کالا</span>
			<span class="eshobe-header__topbar-item">ارسال سریع</span>
			<span class="eshobe-header__topbar-item">پرداخت امن</span>
			<span class="eshobe-header__topbar-item">پشتیبانی قبل از خرید</span>
		</div>
	</div>
	<!-- /wp:html -->

	<!-- wp:group {"className":"eshobe-header__inner","layout":{"type":"default"}} -->
	<div class="wp-block-group eshobe-header__inner">

		<!-- wp:group {"className":"eshobe-header__brand","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group eshobe-header__brand">
			<!-- wp:site-logo {"width":180} /-->
			<!-- wp:site-title {"className":"eshobe-header__site-name"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:navigation {"className":"eshobe-header__nav","layout":{"type":"flex"}} /-->

		<!-- wp:group {"className":"eshobe-header__actions","layout":{"type":"flex"}} -->
		<div class="wp-block-group eshobe-header__actions">
			<!-- wp:search {"label":"جستجو","showLabel":false,"buttonText":"جستجو","buttonPosition":"button-inside","className":"eshobe-header__search"} /-->

			<!-- wp:html -->
			<a class="eshobe-header__action" href="/my-account/" aria-label="حساب کاربری">
				<span class="eshobe-header__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 19.2c1.2-3.2 4.2-5.2 7.5-5.2s6.3 2 7.5 5.2"></path></svg></span>
				<span class="eshobe-header__action-text">حساب</span>
			</a>
			<!-- /wp:html -->

			<!-- wp:html -->
			<a class="eshobe-header__action" href="/cart/" aria-label="سبد خرید">
				<span class="eshobe-header__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 6h2l1.6 10.2a1.8 1.8 0 0 0 1.8 1.5h8.4a1.8 1.8 0 0 0 1.78-1.52L20.5 9H7.1"></path><circle cx="9.5" cy="20" r="1.3"></circle><circle cx="17" cy="20" r="1.3"></circle></svg></span>
				<span class="eshobe-header__action-text">سبد خرید</span>
			</a>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</header>
<!-- /wp:group -->
```

- [ ] **Step 4: Enqueue the new assets — add to `functions.php`**

```php
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'eshobe-block-theme-header',
			get_theme_file_uri( 'assets/css/header.css' ),
			array(),
			ESHOBE_BLOCK_THEME_VERSION
		);
		wp_enqueue_script(
			'eshobe-block-theme-header',
			get_theme_file_uri( 'assets/js/header.js' ),
			array(),
			ESHOBE_BLOCK_THEME_VERSION,
			true
		);
	}
);
```

- [ ] **Step 5: Verify markup and CSS load without errors**

Run: `studio wp eval "\$content = file_get_contents( get_theme_root() . '/eshobe-block-theme/parts/header.html' ); switch_theme('eshobe-block-theme'); \$out = do_blocks( \$content ); switch_theme('eshobe-ecommerce-wp-theme'); echo strlen(\$out) > 0 ? 'rendered ' . strlen(\$out) . ' chars' : 'EMPTY';"`
Expected: `rendered N chars` where N > 0.

Then in the browser: navigate to `http://localhost:8881/?wp_theme_preview=eshobe-block-theme`, confirm via `mcp__Claude_Browser__javascript_tool` that:
- `document.querySelector('.eshobe-header__inner')` exists and has `background-color` matching `#F6F5F2`
- `document.querySelector('.eshobe-header__topbar')` contains all 4 trust-badge strings
- Scrolling the page (`window.scrollTo(0, 100)` then re-check) adds `is-scrolled` class to `.eshobe-header`
- No console errors

- [ ] **Step 6: Commit**

```bash
git add assets/css/header.css assets/js/header.js parts/header.html functions.php
git commit -m "Port header chrome CSS/JS from old theme (topbar, nav pills, sticky scroll)"
```

---

### Task 2: Footer — CSS, markup, back-to-top JS

**Files:**
- Create: `wp-content/themes/eshobe-block-theme/assets/css/footer.css`
- Create: `wp-content/themes/eshobe-block-theme/assets/js/back-to-top.js`
- Modify: `wp-content/themes/eshobe-block-theme/parts/footer.html` (full replace)
- Modify: `wp-content/themes/eshobe-block-theme/functions.php` (add enqueue)

**Interfaces:**
- Consumes: nothing from Task 1 (independent).
- Produces: classes `eshobe-footer`, `eshobe-footer__inner`, `eshobe-footer__content`, `eshobe-footer__brand`, `eshobe-footer__bottom`, `eshobe-back-to-top` — for later phases to reuse if the footer is extended (badges, developer credit).

- [ ] **Step 1: Create `assets/css/footer.css`**

```css
.eshobe-footer {
	margin-top: 72px;
	padding: 0 20px 24px;
	direction: rtl;
}

.eshobe-footer__inner {
	max-width: var(--wp--style--global--content-size, 1200px);
	margin: 0 auto;
}

.eshobe-footer__content {
	position: relative;
	padding: 28px 32px;
	overflow: hidden;
	border: 1px solid var(--wp--preset--color--border);
	border-radius: var(--wp--custom--radius--lg);
	background: var(--wp--preset--color--surface);
	box-shadow: 0 18px 45px rgba(17, 24, 39, 0.06);
}

.eshobe-footer__content::before {
	content: "";
	position: absolute;
	inset: 0 0 auto;
	height: 3px;
	background: linear-gradient(90deg, transparent, var(--wp--preset--color--accent), transparent);
}

.eshobe-footer__title {
	margin: 0 0 8px;
	color: var(--wp--preset--color--primary);
	font-size: 20px;
	font-weight: 850;
	line-height: 1.6;
}

.eshobe-footer__text {
	max-width: 560px;
	margin: 0;
	color: var(--wp--preset--color--muted);
	font-size: 14px;
	line-height: 2;
}

.eshobe-footer__links {
	margin: 18px 0 0;
}

.eshobe-footer__links .wp-block-navigation__container {
	flex-wrap: wrap;
	gap: 10px 18px;
}

.eshobe-footer__links .wp-block-navigation-item__content {
	color: var(--wp--preset--color--primary);
	font-size: 13px;
	font-weight: 700;
	text-decoration: none;
	transition: color 0.22s ease;
}

.eshobe-footer__links .wp-block-navigation-item__content:hover {
	color: var(--wp--preset--color--accent-dark);
}

.eshobe-footer__guarantee {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	margin-top: 14px;
	color: var(--wp--preset--color--muted);
	font-size: 12.5px;
	font-weight: 700;
}

.eshobe-footer__guarantee-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	background: var(--wp--preset--color--accent);
	color: #fff;
	font-size: 11px;
	line-height: 1;
}

.eshobe-footer__bottom {
	padding: 14px 6px 0;
	margin-top: 14px;
	border-top: 1px solid var(--wp--preset--color--border);
	color: var(--wp--preset--color--muted);
	font-size: 12px;
	line-height: 1.8;
}

.eshobe-footer__copyright {
	margin: 0;
}

.eshobe-back-to-top {
	position: fixed;
	z-index: 950;
	right: 20px;
	bottom: 20px;
	width: 44px;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1px solid var(--wp--preset--color--border);
	border-radius: 50%;
	background: var(--wp--preset--color--primary);
	color: #fff;
	font-size: 18px;
	line-height: 1;
	cursor: pointer;
	opacity: 0;
	visibility: hidden;
	transform: translateY(8px);
	box-shadow: 0 12px 28px rgba(17, 24, 39, 0.18);
	transition: opacity 220ms ease, visibility 220ms ease, transform 220ms ease;
}

.eshobe-back-to-top.is-visible {
	opacity: 1;
	visibility: visible;
	transform: translateY(0);
}

@media (max-width: 767px) {
	.eshobe-footer {
		margin-top: 48px;
		padding: 0 16px 20px;
	}

	.eshobe-footer__content {
		padding: 24px 20px 22px;
		border-radius: 24px;
		text-align: right;
	}

	.eshobe-footer__bottom {
		text-align: center;
		font-size: 11.5px;
	}

	.eshobe-back-to-top {
		right: 14px;
		bottom: 14px;
	}
}
```

- [ ] **Step 2: Create `assets/js/back-to-top.js`** (ported near-verbatim, self-contained)

```js
(function () {
	var button = document.querySelector('.eshobe-back-to-top');
	if (!button) {
		return;
	}

	var toggle = function () {
		button.classList.toggle('is-visible', window.scrollY > 400);
	};

	toggle();
	window.addEventListener('scroll', toggle, { passive: true });

	button.addEventListener('click', function () {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	});
})();
```

- [ ] **Step 3: Replace `parts/footer.html` in full**

```html
<!-- wp:group {"tagName":"footer","className":"eshobe-footer","layout":{"type":"default"}} -->
<footer class="wp-block-group eshobe-footer">
	<div class="eshobe-footer__inner">

		<!-- wp:group {"className":"eshobe-footer__content","layout":{"type":"default"}} -->
		<div class="wp-block-group eshobe-footer__content">
			<!-- wp:site-title {"level":2,"className":"eshobe-footer__title"} /-->

			<!-- wp:paragraph {"className":"eshobe-footer__text"} -->
			<p class="eshobe-footer__text">انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.</p>
			<!-- /wp:paragraph -->

			<!-- wp:navigation {"className":"eshobe-footer__links","layout":{"type":"flex"},"overlayMenu":"never"} /-->

			<!-- wp:html -->
			<div class="eshobe-footer__guarantee">
				<span class="eshobe-footer__guarantee-icon" aria-hidden="true">✓</span>
				ضمانت بازگشت کالا تا ۷ روز پس از تحویل سفارش
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"eshobe-footer__bottom","layout":{"type":"default"}} -->
		<div class="wp-block-group eshobe-footer__bottom">
			<!-- wp:paragraph {"className":"eshobe-footer__copyright"} -->
			<p class="eshobe-footer__copyright">© 2026 Eshobe Ecommerce theme. کلیه حقوق محفوظ است.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

	</div>

	<!-- wp:html -->
	<button type="button" class="eshobe-back-to-top" aria-label="بازگشت به بالا">
		<span aria-hidden="true">↑</span>
	</button>
	<!-- /wp:html -->
</footer>
<!-- /wp:group -->
```

Note: footer trust badges and developer credit are intentionally omitted — on this site's current data, both render empty in the old theme too (no ACF badge images or developer text configured), so there is nothing to port. If badges are configured later, that's new content for a future pass, not a regression here.

- [ ] **Step 4: Enqueue the new assets — add to `functions.php`** (same `wp_enqueue_scripts` callback as Task 1's Step 4 — add these two calls inside that existing closure)

```php
		wp_enqueue_style(
			'eshobe-block-theme-footer',
			get_theme_file_uri( 'assets/css/footer.css' ),
			array(),
			ESHOBE_BLOCK_THEME_VERSION
		);
		wp_enqueue_script(
			'eshobe-block-theme-back-to-top',
			get_theme_file_uri( 'assets/js/back-to-top.js' ),
			array(),
			ESHOBE_BLOCK_THEME_VERSION,
			true
		);
```

- [ ] **Step 5: Verify markup renders and behavior works**

Run: `studio wp eval "\$content = file_get_contents( get_theme_root() . '/eshobe-block-theme/parts/footer.html' ); switch_theme('eshobe-block-theme'); \$out = do_blocks( \$content ); switch_theme('eshobe-ecommerce-wp-theme'); echo strlen(\$out) > 0 ? 'rendered ' . strlen(\$out) . ' chars' : 'EMPTY';"`
Expected: `rendered N chars` where N > 0.

Then in the browser at `http://localhost:8881/?wp_theme_preview=eshobe-block-theme`:
- Confirm `.eshobe-footer__content` background is `#FFFFFF` (surface) with the `#111827`-to-transparent gradient top border visible
- Scroll down past 400px, confirm `.eshobe-back-to-top` gains `is-visible`
- Click it, confirm the page scrolls back to top

- [ ] **Step 6: Commit**

```bash
git add assets/css/footer.css assets/js/back-to-top.js parts/footer.html functions.php
git commit -m "Port footer CSS/JS from old theme (content card, links, back-to-top)"
```

---

### Task 3: End-to-end verification and spec update

**Files:** none created/modified beyond the design spec.

- [ ] **Step 1: Full visual pass against real product data**

Open two browser tabs: old theme (`http://localhost:8881/`) and new theme preview
(`http://localhost:8881/?wp_theme_preview=eshobe-block-theme`). Compare header and
footer side by side. Confirm: topbar text matches, logo/site-name position matches,
nav pill hover state matches (hover a nav item in each tab, compare), sticky
behavior matches (scroll both, header should shrink/gain shadow the same way),
footer card styling matches, back-to-top button appears/scrolls the same way.

- [ ] **Step 2: Check debug.log for new errors**

Run: `tail -30 wp-content/debug.log` (from site root) after loading both preview
pages. Expected: no new fatal/warning entries beyond the pre-existing PHP 8.4
deprecation noise already documented in the Phase 1 spec.

- [ ] **Step 3: Update the design spec with outcome notes**

Add a "Phase 1b outcome" section to
`docs/superpowers/specs/2026-07-11-block-theme-header-footer-parity-design.md`
recording: footer badges/developer-credit omitted (no data to port), copyright
text is static (no dynamic year), search is a persistent compact field rather
than an icon-triggered modal, mobile view hides the nav entirely (no drawer yet
— matches the deferred-mobile-nav decision), and any other deviation discovered
during the visual comparison pass.

- [ ] **Step 4: Commit**

```bash
git add docs/superpowers/specs/2026-07-11-block-theme-header-footer-parity-design.md
git commit -m "Record Phase 1b outcome in header/footer parity design spec"
```
