/**
 * <wm-buy-form> — the checkout modal for a single product.
 *
 * Renders a dialog panel, collects name/phone/quantity plus a hidden `company`
 * honeypot, POSTs JSON to /api/checkout with Host: <host>, then redirects to
 * the server's redirect target. Exposed as a reusable component; the host page
 * opens it by setting `.product` (or the `product` serialized attribute) and
 * calling `.open()` / `.close()`.
 *
 * Honepot: `company` is a real, visually hidden input. A bot fills it and the
 * server responds 400; a human leaves it empty and the order proceeds.
 */

import { api, escapeHtml, escapeAttr, formatPrice } from '../core/theme.js';

class WmBuyForm extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale', 'currency'];

  constructor() {
    super();
    this._product = null;
    this.bind(this);
  }

  bind(scope) {
    const self = scope || this;
    if (!self._bound) {
      self._bound = true;
      self._onSubmit = self._onSubmit.bind(scope);
    }
  }

  connectedCallback() {
    this.render();
    if (!this._product) this._product = this._parseProduct();
  }

  attributeChangedCallback() {
    if (this.isConnected) this.render();
  }

  set product(value) {
    this._product = value || null;
    if (this.isConnected) this.render();
  }
  get product() {
    return this._product;
  }

  _parseProduct() {
    const raw = this.getAttribute('serialized-product') || this.getAttribute('product');
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
    const ctx = this._ctx;
    const currency = (p && (p.currency || ctx.currency)) || 'IRR';
    const title = p ? p.title : '';
    const main = p && p.image && p.image.src ? p.image.src : '';
    const price = p && p.price != null ? formatPrice(p.price, currency, ctx.locale) : '';

    this.innerHTML = `
      <div class="wm-buy-form" role="dialog" aria-modal="true" aria-label="تکمیل خرید" hidden>
        <div class="wm-buy-form__backdrop" data-buy-close></div>
        <div class="wm-buy-form__panel">
          <div class="wm-buy-form__head">
            <strong>تکمیل خرید</strong>
            <button type="button" class="wm-buy-form__close" data-buy-close aria-label="بستن">×</button>
          </div>
          <div class="wm-buy-form__product">
            ${main ? `<img class="wm-buy-form__thumb" src="${escapeAttr(main)}" alt="${escapeAttr(title)}" loading="lazy" decoding="async">` : ''}
            <div class="wm-buy-form__product-info">
              <h3 class="wm-buy-form__title">${escapeHtml(title)}</h3>
              <div class="wm-buy-form__price">${price}</div>
            </div>
          </div>
          <form class="wm-buy-form__form" data-buy-form novalidate>
            <div class="wm-buy-form__field wm-buy-form__field--name">
              <label for="wm-buy-name">نام</label>
              <input id="wm-buy-name" name="name" type="text" autocomplete="name" required placeholder="نام و نام خانوادگی">
            </div>
            <div class="wm-buy-form__field wm-buy-form__field--phone">
              <label for="wm-buy-phone">شماره تماس</label>
              <input id="wm-buy-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="۰۹xxxxxxxxx">
            </div>
            <div class="wm-buy-form__field wm-buy-form__field--quantity">
              <label for="wm-buy-qty">تعداد</label>
              <input id="wm-buy-qty" name="quantity" type="number" min="1" value="1" inputmode="numeric">
            </div>
            <div class="wm-buy-form__honeypot" aria-hidden="true">
              <label for="wm-buy-company">شرکت</label>
              <input id="wm-buy-company" name="company" type="text" tabindex="-1" autocomplete="off">
            </div>
            <div class="wm-buy-form__error" data-buy-error hidden></div>
            <button type="submit" class="wm-buy-form__submit" data-buy-submit>ثبت سفارش</button>
          </form>
        </div>
      </div>
    `;

    const form = this.querySelector('[data-buy-form]');
    if (form) form.addEventListener('submit', this._onSubmit);

    const closeButtons = this.querySelectorAll('[data-buy-close]');
    closeButtons.forEach((btn) => btn.addEventListener('click', () => this.close()));

    this.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.close();
    });
  }

  open() {
    const root = this.querySelector('.wm-buy-form');
    if (root) {
      root.hidden = false;
      document.body.classList.add('wm-body-locked');
      const nameInput = this.querySelector('#wm-buy-name');
      if (nameInput) nameInput.focus();
    }
  }

  close() {
    const root = this.querySelector('.wm-buy-form');
    if (root) root.hidden = true;
    document.body.classList.remove('wm-body-locked');
  }

  get isOpen() {
    const root = this.querySelector('.wm-buy-form');
    return root ? !root.hidden : false;
  }

  async _onSubmit(event) {
    event.preventDefault();
    const p = this._product;
    if (!p || !p.id) return;
    const ctx = this._ctx;
    const form = this.querySelector('[data-buy-form]');
    const errorEl = this.querySelector('[data-buy-error]');
    const submitBtn = this.querySelector('[data-buy-submit]');
    const data = new FormData(form);

    const payload = {
      product: p.id,
      quantity: Math.max(1, parseInt(data.get('quantity'), 10) || 1),
      name: String(data.get('name') || '').trim(),
      phone: String(data.get('phone') || '').trim(),
      company: String(data.get('company') || '').trim(),
    };

    if (!payload.name || !payload.phone) {
      this._showError('لطفاً نام و شماره تماس را وارد کنید.', errorEl);
      return;
    }

    this._setBusy(true, submitBtn);

    try {
      const res = await api('/api/checkout', {
        host: ctx.host,
        base: ctx.base,
        method: 'POST',
        body: payload,
        headers: { Accept: 'application/json' },
      });

      if (res.redirected && res.url) {
        window.location.assign(res.url);
        return;
      }

      if (res.ok) {
        let redirectUrl;
        const txt = await res.text();
        try {
          const j = JSON.parse(txt);
          redirectUrl = j && j.redirectUrl;
        } catch {
          // non-JSON success; expect a Location header
        }
        const location = res.headers.get('location');
        redirectUrl = redirectUrl || location;
        if (redirectUrl) {
          window.location.assign(redirectUrl);
          return;
        }
        this._showError('سفارش ثبت شد. در حال انتقال…', errorEl);
        return;
      }

      // Failure — surface the server's message or a localized fallback.
      let msg = 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.';
      try {
        const j = await res.json();
        msg = j && j.message ? j.message : msg;
      } catch {
        // ignore parse errors, keep the fallback
      }
      if (payload.company) {
        msg = 'درخواست نامعتبر است.';
      }
      this._showError(msg, errorEl);
    } catch (err) {
      this._showError('خطا در ارتباط با سرور.', errorEl);
    } finally {
      this._setBusy(false, submitBtn);
    }
  }

  _setBusy(busy, btn) {
    if (!btn) return;
    btn.disabled = busy;
    btn.classList.toggle('is-loading', busy);
  }

  _showError(msg, el) {
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
  }
}

if (!customElements.get('wm-buy-form')) {
  customElements.define('wm-buy-form', WmBuyForm);
}
