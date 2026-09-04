/**
 * <wm-product-detail> — the product detail (single product) view.
 *
 * Framework-free and reusable. Set `.product` (an object from
 * GET /api/products/:key) plus `host`/`base`/`locale`/`currency`, and it
 * renders the exact Eshobe single-product markup (`.wm-product-layout`,
 * `.wm-product-gallery`, `.wm-product-intro`, `.wm-product-specs`,
 * `.wm-product-purchase`) that the copied `product-components.css` styles.
 *
 * The buy button emits `wm:open-buy`; the host shows the <wm-buy-form>.
 */

import { escapeHtml, escapeAttr, formatPrice, formatDate } from '../core/theme.js';

class WmProductDetail extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale', 'currency'];

  constructor() {
    super();
    this._product = null;
  }

  connectedCallback() {
    if (!this._product) this._product = this._parseProduct();
    this.render();
  }

  set product(value) {
    this._product = value || null;
    if (this.isConnected) this.render();
  }
  get product() {
    return this._product;
  }

  _parseProduct() {
    const raw = this.getAttribute('data-product') || this.getAttribute('product');
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  get _ctx() {
    return {
      host: this.getAttribute('host') || '',
      base: this.getAttribute('base') || '',
      locale: this.getAttribute('locale') || 'fa',
      currency: this.getAttribute('currency') || '',
    };
  }

  render() {
    const p = this._product;
    if (!p) {
      this.innerHTML = '<p class="wm-site__error">محصول یافت نشد.</p>';
      return;
    }
    const ctx = this._ctx;
    const currency = p.currency || ctx.currency || 'IRR';
    const locale = ctx.locale || 'fa';
    const title = p.title || 'نام محصول';
    const main = p.image && p.image.src ? p.image.src : '';
    const gallery = (p.gallery && p.gallery.length ? p.gallery : []).filter((g) => g && g.src);
    const mainImage = main || (gallery[0] && gallery[0].src) || '';
    const images = gallery.length ? [main, ...gallery.map((g) => g.src)].filter(Boolean) : [main].filter(Boolean);
    const hasThumbs = images.length > 1;
    const price = p.price != null ? formatPrice(p.price, currency, locale) : '';
    const inStock = p.inStock !== false;
    const published = p.publishedAt ? formatDate(p.publishedAt, locale) : '';

    const galleryClass = 'wm-product-gallery ' + (hasThumbs ? 'wm-product-gallery--has-thumbs' : 'wm-product-gallery--single');

    const thumbs = hasThumbs
      ? `<div class="wm-product-gallery__thumbs" aria-label="تصاویر محصول">${images
          .map((src, i) => `<button class="wm-product-gallery__thumb${i === 0 ? ' is-active' : ''}" type="button" data-large="${escapeAttr(src)}" data-full="${escapeAttr(src)}" data-alt="${escapeAttr(title)}" aria-label="نمایش تصویر ${i + 1}" aria-pressed="${i === 0 ? 'true' : 'false'}"><img src="${escapeAttr(src)}" alt="${escapeAttr(title)}" loading="lazy" decoding="async"></button>`)
          .join('')}</div>`
      : '';

    const meta = [
      p.brand ? `<a class="wm-product-intro__meta-item wm-product-intro__meta-item--brand" href="${escapeAttr(p.brandUrl || '#')}"><span class="wm-product-intro__meta-label">برند</span><span class="wm-product-intro__meta-value">${escapeHtml(p.brand)}</span></a>` : '',
      p.category ? `<a class="wm-product-intro__meta-item wm-product-intro__meta-item--category" href="${escapeAttr(p.categoryUrl || '#')}"><span class="wm-product-intro__meta-label">دسته‌بندی</span><span class="wm-product-intro__meta-value">${escapeHtml(p.category)}</span></a>` : '',
      published ? `<span class="wm-product-intro__meta-item wm-product-intro__meta-item--date"><span class="wm-product-intro__meta-label">تاریخ انتشار</span><span class="wm-product-intro__meta-value">${escapeHtml(published)}</span></span>` : '',
    ].filter(Boolean).join('');

    const specs = (p.specs && p.specs.length
      ? p.specs.map((s) => `<div class="wm-product-specs__item"><span class="wm-product-specs__dot" aria-hidden="true"></span><span class="wm-product-specs__label">${escapeHtml(s.label)}:</span><span class="wm-product-specs__value">${escapeHtml(s.value)}</span></div>`).join('')
      : '');

    const stock = inStock
      ? `<div class="wm-product-purchase__stock wm-product-purchase__stock--in_stock"><span class="wm-product-purchase__stock-status">در انبار</span></div>`
      : `<div class="wm-product-purchase__stock wm-product-purchase__stock--out_of_stock"><span class="wm-product-purchase__stock-status">ناموجود</span></div>`;

    const guarantee = p.guarantee ? `<span class="wm-product-purchase__meta-label">گارانتی</span><span class="wm-product-purchase__meta-value">${escapeHtml(p.guarantee)}</span>` : '';
    const sku = p.sku ? `<div class="wm-product-purchase__meta-item"><span class="wm-product-purchase__meta-label">شناسه</span><span class="wm-product-purchase__meta-value">${escapeHtml(p.sku)}</span></div>` : '';

    this.innerHTML = `
      <div class="wm-container">
        <nav class="wm-breadcrumb" aria-label="مسیر"> <ol class="wm-breadcrumb__list">
          <li class="wm-breadcrumb__item"><a href="/">خانه</a></li>
          <li class="wm-breadcrumb__item" aria-hidden="true"><span class="wm-breadcrumb__sep">‹</span></li>
          <li class="wm-breadcrumb__item is-current"><span>${escapeHtml(title)}</span></li>
        </ol></nav>
      </div>
      <div class="wm-container wm-product-layout">
        <div class="wm-product-gallery-column">
          <section class="${galleryClass}" aria-label="گالری محصول">
            ${mainImage ? `<button type="button" class="wm-product-gallery__zoom" data-full="${escapeAttr(mainImage)}" data-alt="${escapeAttr(title)}" aria-label="بزرگ‌نمایی تصویر"><span aria-hidden="true">⌕</span></button>` : ''}
            <div class="wm-product-gallery__main">
              <div class="wm-product-gallery__image" data-full="${escapeAttr(mainImage)}">
                ${mainImage ? `<img class="wm-product-gallery__main-img" src="${escapeAttr(mainImage)}" alt="${escapeAttr(title)}" loading="eager" decoding="async">` : ''}
              </div>
            </div>
            ${thumbs}
          </section>
        </div>
        <div class="wm-product-summary-column">
          <section class="wm-product-intro">
            <div class="wm-product-intro__heading">
              <h1 class="wm-product-intro__title">${escapeHtml(title)}</h1>
              <button type="button" class="wm-product-intro__wishlist" aria-pressed="false" aria-label="افزودن به علاقه‌مندی‌ها"><span class="wm-product-intro__wishlist-icon" aria-hidden="true"></span></button>
            </div>
            ${meta ? `<div class="wm-product-intro__meta">${meta}</div>` : ''}
            ${p.description ? `<div class="wm-product-intro__excerpt">${escapeHtml(p.description)}</div>` : ''}
          </section>
          ${specs ? `<section class="wm-card wm-product-specs" aria-label="مشخصات محصول"><div class="wm-product-specs__grid" id="wm-product-specs-grid">${specs}</div></section>` : ''}
          <section class="wm-card wm-product-purchase">
            ${price ? `<div class="wm-product-purchase__price">${price}</div>` : ''}
            ${stock}
            <div class="wm-product-purchase__cart">
              <button type="button" class="button wm-product-purchase__add single_add_to_cart_button${inStock ? '' : ' disabled'}" data-wm-add-to-cart ${inStock ? '' : 'disabled'}>${inStock ? 'افزودن به سبد' : 'ناموجود'}</button>
              <button type="button" class="button wm-product-purchase__buy-now" data-wm-buy-now ${inStock ? '' : 'disabled'}>خرید مستقیم</button>
            </div>
            ${(sku || guarantee) ? `<div class="wm-product-purchase__meta">${sku}${guarantee ? `<div class="wm-product-purchase__meta-item">${guarantee}</div>` : ''}</div>` : ''}
            <div class="wm-product-purchase__trust"><span>ضمانت اصالت کالا</span><span>ارسال سریع</span><span>پرداخت امن</span></div>
          </section>
          ${renderCtaBanner(p, currency, locale, inStock)}
        </div>
      </div>
    `;

    // Add to cart
    const addBtn = this.querySelector('[data-wm-add-to-cart]');
    if (addBtn && inStock) {
      addBtn.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('wm:add-to-cart', { detail: { product: p, ctx }, bubbles: true, composed: true }));
      });
    }
    // Buy now (direct single-product checkout)
    const buyBtn = this.querySelector('[data-wm-buy-now]');
    if (buyBtn && inStock) {
      buyBtn.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('wm:open-buy', { detail: { product: p, ctx }, bubbles: true, composed: true }));
      });
    }
    const cta = this.querySelector('[data-wm-cta-buy]');
    if (cta && inStock) {
      cta.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('wm:open-buy', { detail: { product: p, ctx }, bubbles: true, composed: true }));
      });
    }
    const ctaAdd = this.querySelector('[data-wm-cta-add]');
    if (ctaAdd && inStock) {
      ctaAdd.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('wm:add-to-cart', { detail: { product: p, ctx }, bubbles: true, composed: true }));
      });
    }

    // Wishlist is cosmetic here; wire to the card's quick-view convention.
    const wish = this.querySelector('.wm-product-intro__wishlist');
    if (wish) {
      wish.addEventListener('click', (e) => {
        e.preventDefault();
        const pressed = wish.getAttribute('aria-pressed') === 'true';
        wish.setAttribute('aria-pressed', pressed ? 'false' : 'true');
      });
    }

    this._wireGallery(images, title);
  }

  _wireGallery(images, title) {
    const mainImg = this.querySelector('.wm-product-gallery__main-img');
    const imageWrap = this.querySelector('.wm-product-gallery__image');
    const thumbs = Array.from(this.querySelectorAll('.wm-product-gallery__thumb'));
    if (!mainImg || thumbs.length === 0) return;

    thumbs.forEach((thumb, i) => {
      thumb.addEventListener('click', () => {
        thumbs.forEach((t) => { t.classList.toggle('is-active', t === thumb); t.setAttribute('aria-pressed', t === thumb ? 'true' : 'false'); });
        const src = thumb.getAttribute('data-large') || images[i];
        mainImg.src = src;
        if (imageWrap) imageWrap.setAttribute('data-full', src);
      });
    });
  }
}

/**
 * A leading call-to-action banner rendered below the purchase card. Uses the
 * design tokens so any re-skin via themeCss applies. Both CTAs dispatch the
 * standard events (`wm:add-to-cart` / `wm:open-buy`), keeping it reusable.
 */
function renderCtaBanner(p, currency, locale, inStock) {
  const title = p.title || 'این محصول';
  const badge = p.badge ? escapeHtml(p.badge) : '';
  const trustRow = ['ضمانت اصالت کالا', 'ارسال سریع', 'پرداخت امن', 'پشتیبانی قبل از خرید']
    .map((t) => `<span class="wm-product-cta__trust-item">${escapeHtml(t)}</span>`)
    .join('');

  return `
    <section class="wm-product-cta wm-section-decor wm-section-decor--cta" aria-label="خرید ${escapeAttr(title)}">
      <div class="wm-product-cta__body">
        <div class="wm-product-cta__kicker">${badge ? `پیشنهاد ویژه · ${badge}` : 'پیشنهاد ویژه'}</div>
        <h2 class="wm-product-cta__title">${escapeHtml(title)} را همین امروز سفارش دهید</h2>
        <p class="wm-product-cta__text">با ضمانت اصالت کالا، ارسال سریع و پشتیبانی پیش از خرید. در صورت نارضایتی تا ۷ روز پس از تحویل، بازگشت کالا.</p>
        <div class="wm-product-cta__actions">
          <button type="button" class="wm-product-cta__btn wm-product-cta__btn--primary" data-wm-cta-buy ${inStock ? '' : 'disabled'}>${inStock ? 'خرید مستقیم' : 'ناموجود'}</button>
          ${inStock ? `<button type="button" class="wm-product-cta__btn wm-product-cta__btn--secondary" data-wm-cta-add>افزودن به سبد</button>` : ''}
        </div>
        <div class="wm-product-cta__trust">${trustRow}</div>
      </div>
    </section>
  `;
}

if (!customElements.get('wm-product-detail')) {
  customElements.define('wm-product-detail', WmProductDetail);
}
