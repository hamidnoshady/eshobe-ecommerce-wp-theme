/**
 * <wm-product-grid> — a scrollable product carousel backed by GET /api/products.
 *
 * Framework-free and reusable. Given `collection`, `limit`, `host`, `locale`
 * and the design attributes, it fetches GET /api/products (Host: <host>),
 * renders a set of <wm-product-card> elements and wires the carousel arrows.
 * The outer markup matches `.wm-product-carousel`/`.wm-home-section__header`
 * so the copied `home.css` applies unchanged.
 */

import { api, escapeHtml, escapeAttr } from '../core/theme.js';

class WmProductGrid extends HTMLElement {
  static observedAttributes = ['host', 'locale', 'collection', 'limit', 'title', 'subtitle', 'url', 'class'];

  connectedCallback() {
    this._load();
  }

  attributeChangedCallback(name, oldV, newV) {
    // Reload only when a data-affecting attribute changes after mount.
    if (this.isConnected && name !== 'class' && oldV !== undefined && oldV !== newV) {
      this._load();
    } else if (name === 'class' && this.isConnected) {
      this._renderShell(this._products || []);
    }
  }

  async _load() {
    this._renderShell(null, true);
    const host = this.getAttribute('host') || '';
    const locale = this.getAttribute('locale') || 'fa';
    const collection = this.getAttribute('collection') || '';
    const limit = Number(this.getAttribute('limit') || 10) || 10;
    const params = new URLSearchParams();
    if (collection) params.set('collection', collection);
    params.set('limit', String(limit));
    params.set('locale', locale);
    const qs = params.toString();

    try {
      const res = await api(`/api/products?${qs}`, { host });
      if (!res.ok) throw new Error(`GET /api/products failed (${res.status})`);
      const data = await res.json();
      const products = (data && data.products) || [];
      this._products = products;
      this._renderShell(products);
    } catch (err) {
      this._renderShell(null, false);
      this.dataset.error = String(err && err.message || 'error');
    }
  }

  _renderShell(products, loading) {
    const host = this.getAttribute('host') || '';
    const title = this.getAttribute('title') || '';
    const subtitle = this.getAttribute('subtitle') || '';
    const url = this.getAttribute('url') || '';
    const cls = this.getAttribute('class') || 'wm-home-section wm-home-products';
    const locale = this.getAttribute('locale') || 'fa';

    const viewAll = url
      ? `<a href="${escapeAttr(url)}" class="wm-home-button wm-home-button--outline wm-home-button--sm">مشاهده همه <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg></a>`
      : '';

    let trackHtml;
    if (loading) {
      trackHtml = `<div class="wm-product-carousel__loading">در حال بارگذاری…</div>`;
    } else if (products && products.length) {
      const cards = products
        .map((p) => {
          const el = document.createElement('wm-product-card');
          el.setAttribute('host', host);
          el.setAttribute('locale', locale);
          el.setAttribute('currency', p.currency || '');
          // The product is passed as a serialized attribute so the card can
          // rehydrate when the outerHTML is parsed and the element connects.
          el.setAttribute('data-product', JSON.stringify(p));
          return el.outerHTML;
        })
        .join('');
      trackHtml = cards;
    } else {
      trackHtml = '';
    }

    const header = title
      ? `<div class="wm-home-section__header">
          <div>
            <div class="wm-home-section__title-wrap">
              <h2 class="wm-home-section__title">${escapeHtml(title)}</h2>
              ${viewAll}
            </div>
            ${subtitle ? `<p class="wm-home-section__subtitle">${escapeHtml(subtitle)}</p>` : ''}
          </div>
          <div class="wm-product-carousel__controls">
            <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="prev" aria-label="محصولات قبلی"><span aria-hidden="true">‹</span></button>
            <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="next" aria-label="محصولات بعدی"><span aria-hidden="true">›</span></button>
          </div>
        </div>`
      : '';

    this.innerHTML = `
      <section class="wm-product-carousel ${escapeAttr(cls)}" data-product-carousel>
        ${header}
        <div class="wm-product-carousel__viewport">
          <div class="wm-product-carousel__track" tabindex="0">${trackHtml}</div>
        </div>
      </section>
    `;

    if (products && products.length) {
      this._wireCarousel();
    }
  }

  _wireCarousel() {
    const track = this.querySelector('.wm-product-carousel__track');
    if (!track) return;
    const prev = this.querySelector('[data-carousel-direction="prev"]');
    const next = this.querySelector('[data-carousel-direction="next"]');
    if (prev) prev.addEventListener('click', () => this._scroll(track, -1));
    if (next) next.addEventListener('click', () => this._scroll(track, 1));
  }

  _scroll(track, dir) {
    const card = track.firstElementChild;
    if (!card) return;
    const step = card.getBoundingClientRect().width + 16;
    track.scrollBy({ left: dir * step, behavior: 'smooth' });
  }
}

if (!customElements.get('wm-product-grid')) {
  customElements.define('wm-product-grid', WmProductGrid);
}
