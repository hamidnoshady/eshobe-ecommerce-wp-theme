/**
 * Eshobe Payload theme — block registry.
 *
 * Renders the ordered `site.blocks` array from GET /api/site into the home
 * page. Unknown block types render nothing (never crash). The `productGrid`
 * block delegates to the reusable <wm-product-grid> element which fetches
 * GET /api/products itself.
 *
 * All markup uses the exact class names from the original Eshobe WooCommerce
 * theme, so the copied design-system CSS applies unchanged.
 */

import { escapeHtml, escapeAttr, formatPrice, dirFor } from './theme.js';

/**
 * Render the whole `<main>` region from `site.blocks`.
 * @param {{site: object, locale: string}} ctx
 * @returns {string} HTML
 */
export function renderBlocks(ctx) {
  const { site, locale } = ctx;
  const blocks = (site && site.blocks) || [];
  const parts = [];
  for (let i = 0; i < blocks.length; i++) {
    const block = blocks[i];
    const html = renderBlock(block, site, locale, i);
    if (html) parts.push(html);
  }
  return parts.join('\n');
}

/** Dispatch a single block to its renderer. */
export function renderBlock(block, site, locale, index = 0) {
  if (!block || !block.type) return '';
  const renderers = {
    hero: renderHero,
    brandGrid: renderBrandGrid,
    productGrid: renderProductGrid,
    filterBoxes: renderFilterBoxes,
    styleGrid: renderStyleGrid,
    trust: renderTrust,
  };
  const fn = renderers[block.type];
  return fn ? fn(block, site, locale, index) : '';
}

/* ------------------------------------------------------------------ *
 * 5.1 hero
 * ------------------------------------------------------------------ */
function renderHero(block, site, locale) {
  if (!block || !Array.isArray(block.slides) || block.slides.length === 0) return '';
  const slides = block.slides;
  const hasMultiple = slides.length > 1;
  const autoplayInterval = Number(block.autoplayInterval || 5000);
  const autoplayPause = block.autoplayPauseHover !== false;

  const slidesHtml = slides
    .map((slide, index) => renderHeroSlide(slide, site, locale, index))
    .join('\n');

  const progress = hasMultiple && autoplayInterval > 0
    ? '<div class="wm-home-hero__progress" data-home-hero-progress aria-hidden="true"><span class="wm-home-hero__progress-bar"></span></div>'
    : '';

  const nav = hasMultiple
    ? `<div class="wm-home-hero__nav">
        <button type="button" class="wm-home-hero__arrow wm-home-hero__arrow--prev" data-hero-direction="prev" aria-label="قبلی"><span aria-hidden="true">‹</span></button>
        <button type="button" class="wm-home-hero__arrow wm-home-hero__arrow--next" data-hero-direction="next" aria-label="بعدی"><span aria-hidden="true">›</span></button>
      </div>`
    : '';

  return `<section class="wm-home-hero wm-section-decor wm-section-decor--hero wm-section-decor--dots wm-section-decor--ring" data-home-hero data-autoplay="${escapeAttr(autoplayInterval)}" data-autoplay-pause-hover="${autoplayPause ? 1 : 0}">
    <div class="wm-home-hero__track">${slidesHtml}</div>
    ${progress}
    ${nav}
  </section>`;
}

function renderHeroSlide(slide, site, locale, index) {
  const type = slide.type || 'content';
  const active = index === 0 ? ' is-active' : '';
  const siteName = escapeAttr((site.store && site.store.name) || 'فروشگاه آنلاین');
  const lazyOrEager = index === 0 ? 'eager' : 'lazy';
  const imgAlt = escapeAttr(slide.imageAlt || slide.title || siteName);

  if (type === 'image') {
    const desktop = slide.image && slide.image.src ? slide.image.src : '';
    const mobile = (slide.imageMobile && slide.imageMobile.src) || desktop;
    const href = slide.url ? escapeAttr(slide.url) : '';
    const picture = (desktop || mobile)
      ? `<picture>${mobile && mobile !== desktop ? `<source media="(max-width: 767px)" srcset="${escapeAttr(mobile)}">` : ''}<img class="wm-home-hero__image" src="${escapeAttr(desktop || mobile)}" alt="${imgAlt}" loading="${lazyOrEager}" decoding="async"></picture>`
      : '';
    return `<article class="wm-home-hero__slide wm-home-hero__slide--image${active}" data-home-hero-slide>${href ? `<a class="wm-home-hero__image-link" href="${href}">` : ''}${picture}${href ? '</a>' : ''}</article>`;
  }

  if (type === 'video') {
    const desktop = slide.video && slide.video.src ? slide.video.src : '';
    const mobile = (slide.videoMobile && slide.videoMobile.src) || desktop;
    const href = slide.url ? escapeAttr(slide.url) : '';
    const video = (desktop || mobile)
      ? `<video class="wm-home-hero__video" autoplay muted loop playsinline preload="metadata" aria-hidden="true" tabindex="-1">${mobile && mobile !== desktop ? `<source media="(max-width: 767px)" src="${escapeAttr(mobile)}">` : ''}<source src="${escapeAttr(desktop || mobile)}"></video>`
      : '';
    return `<article class="wm-home-hero__slide wm-home-hero__slide--video${active}" data-home-hero-slide>${href ? `<a class="wm-home-hero__video-link" href="${href}">` : ''}${video}${href ? '</a>' : ''}</article>`;
  }

  // Content slide
  const eyebrow = slide.eyebrow ? `<span class="wm-home-hero__eyebrow">${escapeHtml(slide.eyebrow)}</span>` : '';
  const title = slide.title
    ? (index === 0
        ? `<h1 class="wm-home-hero__title">${escapeHtml(slide.title)}</h1>`
        : `<p class="wm-home-hero__title">${escapeHtml(slide.title)}</p>`)
    : '';
  const subtitle = slide.subtitle ? `<p class="wm-home-hero__subtitle">${escapeHtml(slide.subtitle)}</p>` : '';
  const actions = (slide.primaryText && slide.primaryUrl)
    ? `<div class="wm-home-hero__actions">
         <a class="wm-home-button wm-home-button--primary" href="${escapeAttr(slide.primaryUrl)}">${escapeHtml(slide.primaryText)}</a>
         ${slide.secondaryText && slide.secondaryUrl ? `<a class="wm-home-button wm-home-button--outline" href="${escapeAttr(slide.secondaryUrl)}">${escapeHtml(slide.secondaryText)}</a>` : ''}
       </div>`
    : '';
  const desktop = slide.image && slide.image.src ? slide.image.src : '';
  const mobile = (slide.imageMobile && slide.imageMobile.src) || desktop;
  const visual = desktop
    ? `<picture>${mobile && mobile !== desktop ? `<source media="(max-width: 767px)" srcset="${escapeAttr(mobile)}">` : ''}<img src="${escapeAttr(desktop)}" alt="${imgAlt}" loading="${lazyOrEager}" decoding="async"></picture>`
    : '<div class="wm-home-hero__placeholder" aria-hidden="true"></div>';

  return `<article class="wm-home-hero__slide${active}" data-home-hero-slide>
    <div class="wm-home-hero__content">${eyebrow}${title}${subtitle}${actions}</div>
    <div class="wm-home-hero__visual">${visual}</div>
  </article>`;
}

/* ------------------------------------------------------------------ *
 * 5.2 brandGrid
 * ------------------------------------------------------------------ */
function renderBrandGrid(block, site, locale) {
  const items = (block && block.items) || [];
  if (items.length === 0) return '';
  const title = block.title || 'برندها';
  const subtitle = block.subtitle || 'انتخاب سریع بر اساس برندهای پربازدید فروشگاه';
  const cards = items
    .map((item) => {
      const cover = item.image && item.image.src;
      const classes = 'wm-home-brand-card' + (cover ? '' : ' wm-home-brand-card--no-cover');
      const count = item.count != null ? `${Number(item.count).toLocaleString('fa-IR')} محصول` : '';
      return `<a class="${classes}" href="${escapeAttr(item.href || '#')}">
        <span class="wm-home-brand-card__media">${cover ? `<img class="wm-home-brand-card__image" src="${escapeAttr(cover)}" alt="${escapeAttr(item.title)}" loading="lazy" decoding="async">` : ''}</span>
        <span class="wm-home-brand-card__body">
          <strong>${escapeHtml(item.title)}</strong>
          ${count ? `<span class="wm-home-brand-card__count">${escapeHtml(count)}</span>` : ''}
          ${item.subtitle ? `<small>${escapeHtml(item.subtitle)}</small>` : ''}
        </span>
      </a>`;
    })
    .join('\n');

  return `<section class="wm-home-brands wm-section-decor wm-section-decor--brands">
    <div class="wm-home-section__header">
      <div>
        <h2 class="wm-home-section__title">${escapeHtml(title)}</h2>
        <p class="wm-home-section__subtitle">${escapeHtml(subtitle)}</p>
      </div>
    </div>
    <div class="wm-home-brands__grid">${cards}</div>
  </section>`;
}

/* ------------------------------------------------------------------ *
 * 5.3 productGrid — delegates to the reusable <wm-product-grid> element
 * ------------------------------------------------------------------ */
function renderProductGrid(block, site, locale, index) {
  const cls = (block.id === 'recommended')
    ? 'wm-home-section wm-home-products wm-home-products--recommended'
    : 'wm-home-section wm-home-products wm-home-products--' + escapeAttr(sanitizeClass(block.id || 'products'));
  const viewAll = block.viewAllUrl || '';
  return `<wm-product-grid
      host="${escapeAttr(site.host || '')}"
      locale="${escapeAttr(locale)}"
      collection="${escapeAttr(block.collection || '')}"
      limit="${escapeAttr(block.limit || 10)}"
      title="${escapeAttr(block.title || 'پیشنهاد ما')}"
      subtitle="${escapeAttr(block.subtitle || '')}"
      url="${escapeAttr(viewAll)}"
      class="${escapeAttr(cls)}"
    ></wm-product-grid>`;
}

/* ------------------------------------------------------------------ *
 * 5.4 filterBoxes
 * ------------------------------------------------------------------ */
function renderFilterBoxes(block, site, locale) {
  const items = (block && block.items) || [];
  if (items.length === 0) return '';
  const title = block.title || 'خرید بر اساس دسته';
  const subtitle = block.subtitle || '';
  const cards = items
    .map((item) => {
      const cover = item.image && item.image.src;
      const variant = item.variant || 'light';
      const classes = `wm-home-filter-card wm-home-filter-card--${escapeAttr(sanitizeClass(variant))}` + (cover ? ' wm-home-filter-card--image' : ' wm-home-filter-card--no-cover');
      return `<a class="${classes}" href="${escapeAttr(item.href || '#')}">
        <span class="wm-home-filter-card__media">${cover ? `<img src="${escapeAttr(cover)}" alt="${escapeAttr(item.title)}" loading="lazy" decoding="async">` : ''}</span>
        <span class="wm-home-filter-card__body">
          <strong>${escapeHtml(item.title)}</strong>
          ${item.subtitle ? `<small>${escapeHtml(item.subtitle)}</small>` : ''}
        </span>
      </a>`;
    })
    .join('\n');

  return `<section class="wm-home-section wm-home-filters wm-home-filters--cards">
    <div class="wm-home-section__header">
      <div>
        <h2 class="wm-home-section__title">${escapeHtml(title)}</h2>
        ${subtitle ? `<p class="wm-home-section__subtitle">${escapeHtml(subtitle)}</p>` : ''}
      </div>
    </div>
    <div class="wm-home-filters__grid">${cards}</div>
  </section>`;
}

/* ------------------------------------------------------------------ *
 * 5.5 styleGrid
 * ------------------------------------------------------------------ */
function renderStyleGrid(block, site, locale) {
  const items = (block && block.items) || [];
  if (items.length === 0) return '';
  const title = block.title || 'سبک‌های محبوب';
  const subtitle = block.subtitle || 'بر اساس زبان طراحی و موقعیت استفاده انتخاب کنید.';
  const cards = items
    .map((item) => {
      const cover = item.image && item.image.src;
      const classes = 'wm-home-style-card' + (cover ? '' : ' wm-home-style-card--no-cover');
      return `<a class="${classes}" href="${escapeAttr(item.href || '#')}">
        <span class="wm-home-style-card__media">${cover ? `<img src="${escapeAttr(cover)}" alt="${escapeAttr(item.title)}" loading="lazy" decoding="async">` : ''}</span>
        <span class="wm-home-style-card__body">
          <strong>${escapeHtml(item.title)}</strong>
          ${item.subtitle ? `<small>${escapeHtml(item.subtitle)}</small>` : ''}
        </span>
      </a>`;
    })
    .join('\n');

  return `<section class="wm-home-section wm-home-styles wm-section-decor wm-section-decor--styles wm-section-decor--soft-grid">
    <div class="wm-home-section__header">
      <div>
        <h2 class="wm-home-section__title">${escapeHtml(title)}</h2>
        <p class="wm-home-section__subtitle">${escapeHtml(subtitle)}</p>
      </div>
    </div>
    <div class="wm-home-styles__grid">${cards}</div>
  </section>`;
}

/* ------------------------------------------------------------------ *
 * 5.6 trust
 * ------------------------------------------------------------------ */
function renderTrust(block, site, locale) {
  const items = (block && block.items) || [];
  if (items.length === 0) return '';
  const cards = items
    .map((item) => {
      const icon = item.icon && item.icon.src
        ? `<img src="${escapeAttr(item.icon.src)}" alt="" loading="lazy" decoding="async">`
        : '<span class="wm-home-trust__icon-mark" aria-hidden="true">✓</span>';
      return `<div class="wm-home-trust__item">
        <span class="wm-home-trust__icon">${icon}</span>
        <strong>${escapeHtml(item.title)}</strong>
        ${item.text ? `<small>${escapeHtml(item.text)}</small>` : ''}
      </div>`;
    })
    .join('\n');

  return `<section class="wm-home-section wm-home-trust">
    <div class="wm-home-trust__grid">${cards}</div>
  </section>`;
}

/** Lowercase, hyphenates and strips unsafe chars for use as a CSS class. */
export function sanitizeClass(value) {
  return String(value == null ? '' : value).toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
}
