/**
 * <wm-search> — the header live-search modal.
 *
 * Framework-free and reusable. Given `host`/`base`/`locale` and a currency, it
 * renders a modal (`.wm-search-modal`, styled by the copied `search-modal.css`)
 * with a live search field. Typing debounces a `GET /api/products?search=…`
 * call and renders `.wm-search-result` rows. Emits `wm:select-product` when a
 * result is chosen.
 *
 * API: .open(), .close(), .isOpen.
 */

import { api, escapeHtml, escapeAttr, formatPrice } from '../core/theme.js';

class WmSearch extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale', 'currency'];

  constructor() {
    super();
    this._timer = null;
    this._results = [];
    this._bound = false;
  }

  connectedCallback() {
    if (!this._bound) {
      this._bound = true;
      this._onInput = this._onInput.bind(this);
      this._onKeydown = this._onKeydown.bind(this);
      document.addEventListener('keydown', this._onKeydown);
    }
    this.render();
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
    this.innerHTML = `
      <div class="wm-search-modal" data-search-modal hidden>
        <div class="wm-search-modal__backdrop" data-search-close></div>
        <div class="wm-search-modal__panel" role="dialog" aria-modal="true" aria-label="جستجو">
          <form class="wm-search-modal__form" data-search-form>
            <span class="wm-search-modal__icon" aria-hidden="true">${icon('search')}</span>
            <input
              class="wm-search-modal__field"
              type="search"
              name="s"
              autocomplete="off"
              placeholder="جستجوی محصول، برند یا دسته…"
              aria-label="جستجو"
            >
            <kbd class="wm-search-modal__hint" aria-hidden="true">/</kbd>
            <button type="button" class="wm-search-modal__close" data-search-close aria-label="بستن">×</button>
          </form>
          <div class="wm-search-modal__body">
            <p class="wm-search-modal__empty" data-search-empty hidden>نتیجه‌ای یافت نشد. <a href="/shop">مشاهده همه محصولات</a></p>
            <ul class="wm-search-modal__results" data-search-results></ul>
            <a href="/shop" class="wm-search-modal__view-all" data-search-view-all hidden>مشاهده همه نتایج</a>
          </div>
        </div>
      </div>
    `;

    const field = this.querySelector('.wm-search-modal__field');
    if (field) field.addEventListener('input', this._onInput);

    const form = this.querySelector('[data-search-form]');
    if (form) form.addEventListener('submit', (e) => e.preventDefault());

    this.querySelectorAll('[data-search-close]').forEach((btn) => {
      btn.addEventListener('click', () => this.close());
    });

    this._renderResults(this._results, { initial: true });
  }

  open() {
    const root = this.querySelector('[data-search-modal]');
    if (!root) return;
    root.hidden = false;
    raf(() => root.classList.add('is-open'));
    document.body.classList.add('wm-search-modal-open');
    const field = this.querySelector('.wm-search-modal__field');
    if (field) {
      field.focus();
      field.select();
    }
  }

  close() {
    const root = this.querySelector('[data-search-modal]');
    if (!root) return;
    root.classList.remove('is-open');
    document.body.classList.remove('wm-search-modal-open');
    setTimeout(() => { root.hidden = true; }, 240);
  }

  get isOpen() {
    const root = this.querySelector('[data-search-modal]');
    return root ? !root.hidden : false;
  }

  _onKeydown(e) {
    if (e.key === 'Escape' && this.isOpen) this.close();
    if (e.key === '/' && !this.isOpen && !isTypingTarget(e.target)) {
      e.preventDefault();
      this.open();
    }
  }

  async _onInput(e) {
    const q = (e.target.value || '').trim();
    clearTimeout(this._timer);
    if (!q) {
      this._results = [];
      this._renderResults([], { initial: true });
      return;
    }
    this._timer = setTimeout(() => this._fetch(q), 220);
  }

  async _fetch(q) {
    const ctx = this._ctx;
    const params = new URLSearchParams({ search: q, locale: ctx.locale, limit: '8' });
    const resultsEl = this.querySelector('[data-search-results]');
    try {
      const res = await api(`/api/products?${params}`, { host: ctx.host, base: ctx.base });
      if (!res.ok) throw new Error(`search failed (${res.status})`);
      const data = await res.json();
      const products = (data && data.products) || [];
      // Ignore stale responses that trailed an earlier keystroke.
      const field = this.querySelector('.wm-search-modal__field');
      if (field && (field.value || '').trim() !== q) return;
      this._results = products;
      this._renderResults(products);
    } catch (err) {
      this._results = [];
      this._renderResults([], { error: true });
    }
  }

  _renderResults(products, opts = {}) {
    const list = this.querySelector('[data-search-results]');
    const empty = this.querySelector('[data-search-empty]');
    const viewAll = this.querySelector('[data-search-view-all]');
    if (!list) return;

    if (products.length === 0) {
      list.innerHTML = '';
      if (empty) empty.hidden = opts.initial;
      if (viewAll) viewAll.hidden = true;
      return;
    }

    if (empty) empty.hidden = true;
    if (viewAll) viewAll.hidden = false;

    const ctx = this._ctx;
    list.innerHTML = products
      .map((p) => {
        const currency = p.currency || ctx.currency || 'IRR';
        const price = p.price != null ? formatPrice(p.price, currency, ctx.locale) : '';
        return `<li class="wm-search-result">
          <a class="wm-search-result__link" href="${escapeAttr(p.url || '#')}" data-product-key="${escapeAttr(p.id || p.slug || '')}">
            <span class="wm-search-result__media">${p.image && p.image.src ? `<img class="wm-search-result__image" src="${escapeAttr(p.image.src)}" alt="${escapeAttr(p.title)}" loading="lazy" decoding="async">` : ''}</span>
            <span class="wm-search-result__info">
              <span class="wm-search-result__title">${escapeHtml(p.title)}</span>
              ${p.brand ? `<span class="wm-search-result__brand">${escapeHtml(p.brand)}</span>` : ''}
            </span>
            <span class="wm-search-result__price">${price}</span>
          </a>
        </li>`;
      })
      .join('');

    list.querySelectorAll('[data-product-key]').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const key = a.getAttribute('data-product-key');
        this.dispatchEvent(new CustomEvent('wm:select-product', { detail: { key, product: products.find((p) => (p.id || p.slug) === key) }, bubbles: true, composed: true }));
        this.close();
      });
    });
  }
}

function isTypingTarget(target) {
  return target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);
}

function raf(fn) {
  return window.requestAnimationFrame ? window.requestAnimationFrame(fn) : setTimeout(fn, 0);
}

function icon(name) {
  const icons = {
    search: '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><line x1="20" y1="20" x2="16.2" y2="16.2"></line></svg>',
  };
  return icons[name] || '';
}

if (!customElements.get('wm-search')) {
  customElements.define('wm-search', WmSearch);
}
