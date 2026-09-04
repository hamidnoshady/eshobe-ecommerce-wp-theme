/**
 * <wm-product-card> — a single product card.
 *
 * Framework-free custom element. Reusable across themes: set the `product`
 * property (an object from GET /api/products) and the contextual attributes
 * (`host`, `locale`, `currency`) and it renders the exact Eshobe product-card
 * markup (the copied `home.css` styles make it pixel-identical).
 *
 * Clicking the buy button emits a `wm:open-buy` CustomEvent with the product;
 * the host page shows a <wm-buy-form> in response. The card does not know
 * about any particular checkout UI, so it stays reusable.
 */

import { escapeHtml, escapeAttr, formatPrice } from '../core/theme.js';

class WmProductCard extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale', 'currency'];

  constructor() {
    super();
    this._product = null;
  }

  connectedCallback() {
    if (!this._product) this._product = this._parseProduct();
    this.render();
  }

  attributeChangedCallback() {
    if (this.isConnected && this._product) this.render();
  }

  /** Allow setting the product as a JS property (used by <wm-product-grid>). */
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
      this.replaceChildren();
      return;
    }
    const ctx = this._ctx;
    const currency = p.currency || ctx.currency || 'IRR';
    const title = p.title || 'نام محصول';
    const main = p.image && p.image.src ? p.image.src : '';
    const hover = p.gallery && p.gallery[0] && p.gallery[0].src ? p.gallery[0].src : '';
    const hasHover = hover && hover !== main;
    const url = p.url || '#';
    const classes = ['wm-product-card', hasHover ? 'wm-product-card--has-hover-image' : '']
      .filter(Boolean)
      .join(' ');
    const badge = p.badge ? `<span class="wm-product-card__badge">${escapeHtml(p.badge)}</span>` : '';
    const price = p.price != null ? formatPrice(p.price, currency, ctx.locale) : '';
    const buyText = p.inStock === false ? 'ناموجود' : 'افزودن به سبد';
    const disabled = p.inStock === false ? ' disabled' : '';
    const titleAttr = escapeAttr(title);

    this.replaceChildren();
    const frag = document.createRange().createContextualFragment(`
      <article class="${classes}">
        <div class="wm-product-card__media-wrap">
          <a class="wm-product-card__media" href="${escapeAttr(url)}" aria-label="${titleAttr}">
            ${badge}
            ${main ? `<img class="wm-product-card__image wm-product-card__image-main" src="${escapeAttr(main)}" alt="${titleAttr}" loading="lazy" decoding="async">` : ''}
            ${hasHover ? `<img class="wm-product-card__image wm-product-card__image-hover" src="${escapeAttr(hover)}" alt="${titleAttr}" loading="lazy" decoding="async">` : ''}
          </a>
          <button type="button" class="wm-product-card__quick-view" data-wm-quick-view aria-label="نمایش سریع ${titleAttr}" title="نمایش سریع">
            <span aria-hidden="true">⌕</span>
          </button>
        </div>
        <h3 class="wm-product-card__title"><a href="${escapeAttr(url)}">${escapeHtml(title)}</a></h3>
        ${price ? `<div class="wm-product-card__price">${price}</div>` : ''}
        <div class="wm-product-card__actions">
          <button type="button" class="wm-product-card__button button add_to_cart_button product_type_simple" data-wm-buy data-product-id="${escapeAttr(p.id || '')}"${disabled}>${escapeHtml(buyText)}</button>
        </div>
      </article>
    `);

    // Wire the add-to-cart button: emit an event the host listens for.
    const buyBtn = frag.querySelector('[data-wm-buy]');
    if (buyBtn && p.inStock !== false) {
      buyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.dispatchEvent(
          new CustomEvent('wm:add-to-cart', { detail: { product: p, ctx }, bubbles: true, composed: true })
        );
      });
    }

    const quickBtn = frag.querySelector('[data-wm-quick-view]');
    if (quickBtn) {
      quickBtn.addEventListener('click', () => {
        this.dispatchEvent(
          new CustomEvent('wm:quick-view', { detail: { product: p, ctx }, bubbles: true, composed: true })
        );
      });
    }

    this.appendChild(frag);
  }
}

if (!customElements.get('wm-product-card')) {
  customElements.define('wm-product-card', WmProductCard);
}
