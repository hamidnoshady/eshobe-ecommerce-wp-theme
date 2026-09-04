/**
 * <wm-cart> — the mini-cart drawer.
 *
 * Framework-free and reusable. Keeps a lightweight client-side basket in
 * `localStorage` (key: wm-cart v1) so the item list and header badge survive a
 * reload. Styled by the copied `mini-cart.css` (.wm-cart-drawer).
 *
 * Because the storefront contract (docs/THEME_API.md) defines checkout as a
 * SINGLE-product `POST /api/checkout`, each basket row carries its own
 * "ثبت سفارش" button that emits `wm:open-buy` for that product — the host
 * answers with the <wm-buy-form>. The drawer also shows a subtotal and lets the
 * user remove rows.
 *
 * API: .add(product), .remove(key), .items, .count, .open(), .close().
 */

import { escapeHtml, escapeAttr, formatPrice } from '../core/theme.js';

const STORAGE_KEY = 'wm-cart:v1';

class WmCart extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale', 'currency'];

  constructor() {
    super();
    this._items = [];
  }

  connectedCallback() {
    this._load();
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

  /* ---------- persistent state ---------- */
  _load() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      this._items = Array.isArray(parsed) ? parsed : [];
    } catch {
      this._items = [];
    }
  }

  _save() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this._items));
    } catch {
      /* storage may be unavailable (private mode); cart stays in-memory */
    }
  }

  get items() {
    return this._items;
  }

  get count() {
    return this._items.reduce((n, it) => n + (Number(it.quantity) || 1), 0);
  }

  add(product, quantity = 1) {
    if (!product) return;
    const key = String(product.id || product.slug || '');
    if (!key) return;
    const existing = this._items.find((it) => (it.id || it.slug) === key);
    if (existing) {
      existing.quantity = Math.min(99, (existing.quantity || 0) + quantity);
    } else {
      this._items.push({ ...product, quantity });
    }
    this._save();
    this.render();
    this._emitChange();
    this.dispatchEvent(new CustomEvent('wm:cart-updated', { detail: { count: this.count, items: this.items }, bubbles: true, composed: true }));
  }

  remove(key) {
    const k = String(key || '');
    this._items = this._items.filter((it) => (it.id || it.slug) !== k);
    this._save();
    this.render();
    this._emitChange();
    this.dispatchEvent(new CustomEvent('wm:cart-updated', { detail: { count: this.count, items: this.items }, bubbles: true, composed: true }));
  }

  clear() {
    this._items = [];
    this._save();
    this.render();
    this._emitChange();
    this.dispatchEvent(new CustomEvent('wm:cart-updated', { detail: { count: 0, items: [] }, bubbles: true, composed: true }));
  }

  _emitChange() {
    // A dedicated event the host/header listens to for the badge.
    this.dispatchEvent(new CustomEvent('wm:cart-change', { detail: { count: this.count }, bubbles: true, composed: true }));
  }

  /* ---------- rendering ---------- */
  render() {
    const ctx = this._ctx;
    const currency = ctx.currency || 'IRR';
    const count = this.count;
    const items = this._items;
    const subtotal = items.reduce((n, it) => n + (Number(it.price) || 0) * (Number(it.quantity) || 1), 0);

    const itemRows = items.length
      ? items.map((it) => {
          const key = it.id || it.slug || '';
          const price = it.price != null ? formatPrice(it.price, currency, ctx.locale) : '';
          const img = it.image && it.image.src ? it.image.src : '';
          return `<div class="wm-cart-drawer__item" data-cart-key="${escapeAttr(key)}">
            ${img ? `<img class="wm-cart-drawer__item-image" src="${escapeAttr(img)}" alt="${escapeAttr(it.title)}" loading="lazy" decoding="async">` : ''}
            <div class="wm-cart-drawer__item-body">
              <span class="wm-cart-drawer__item-name"><a href="${escapeAttr(it.url || '#')}" data-cart-link="${escapeAttr(key)}">${escapeHtml(it.title)}</a></span>
              <span class="wm-cart-drawer__item-meta">تعداد: ${escapeHtml(String(it.quantity || 1))}</span>
              <span class="wm-cart-drawer__item-price">${price}</span>
            </div>
            <button type="button" class="wm-cart-drawer__item-remove" data-cart-remove="${escapeAttr(key)}" aria-label="حذف ${escapeAttr(it.title)}">×</button>
          </div>`;
        }).join('')
      : `<div class="wm-cart-drawer__empty">
           <span class="wm-cart-drawer__empty-icon" aria-hidden="true">🛍</span>
           <span class="wm-cart-drawer__empty-text">سبد خرید شما خالی است.</span>
         </div>`;

    this.innerHTML = `
      <div class="wm-cart-drawer" data-cart-drawer>
        <div class="wm-cart-drawer__backdrop" data-cart-close></div>
        <aside class="wm-cart-drawer__panel" role="dialog" aria-modal="true" aria-label="سبد خرید">
          <div class="wm-cart-drawer__header">
            <strong class="wm-cart-drawer__title">سبد خرید</strong>
            <button type="button" class="wm-cart-drawer__close" data-cart-close aria-label="بستن">×</button>
          </div>
          <div class="wm-cart-drawer__body">
            <div class="wm-cart-drawer__items">${itemRows}</div>
          </div>
          <div class="wm-cart-drawer__footer">
            ${items.length ? `<div class="wm-cart-drawer__subtotal">جمع کل: ${formatPrice(subtotal, currency, ctx.locale)}</div>` : ''}
            <div class="wm-cart-drawer__actions">
              <button type="button" class="wm-cart-drawer__btn wm-cart-drawer__btn--secondary" data-cart-continue>ادامه خرید</button>
              ${items.length ? `<button type="button" class="wm-cart-drawer__btn wm-cart-drawer__btn--primary" data-cart-checkout>ثبت سفارش</button>` : ''}
            </div>
          </div>
        </aside>
      </div>
    `;

    this.querySelectorAll('[data-cart-close]').forEach((b) => b.addEventListener('click', () => this.close()));
    const continueBtn = this.querySelector('[data-cart-continue]');
    if (continueBtn) continueBtn.addEventListener('click', () => this.close());
    const checkoutBtn = this.querySelector('[data-cart-checkout]');
    if (checkoutBtn) checkoutBtn.addEventListener('click', () => this._checkout());

    this.querySelectorAll('[data-cart-remove]').forEach((b) => {
      b.addEventListener('click', () => this.remove(b.getAttribute('data-cart-remove')));
    });
    this.querySelectorAll('[data-cart-link]').forEach((a) => {
      a.addEventListener('click', (e) => { e.preventDefault(); this.close(); });
    });
  }

  _checkout() {
    if (!this.items.length) return;
    // Single-product checkout per contract: check out the first basket row.
    const first = this.items[0];
    this.dispatchEvent(new CustomEvent('wm:open-buy', { detail: { product: first, ctx: this._ctx }, bubbles: true, composed: true }));
  }

  open() {
    const root = this.querySelector('[data-cart-drawer]');
    if (!root) return;
    raf(() => root.classList.add('is-open'));
    document.body.classList.add('wm-cart-drawer-open');
  }

  close() {
    const root = this.querySelector('[data-cart-drawer]');
    if (!root) return;
    root.classList.remove('is-open');
    document.body.classList.remove('wm-cart-drawer-open');
  }

  get isOpen() {
    const root = this.querySelector('[data-cart-drawer]');
    return root ? root.classList.contains('is-open') : false;
  }
}

function raf(fn) {
  return window.requestAnimationFrame ? window.requestAnimationFrame(fn) : setTimeout(fn, 0);
}

if (!customElements.get('wm-cart')) {
  customElements.define('wm-cart', WmCart);
}
