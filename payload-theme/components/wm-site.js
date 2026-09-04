/**
 * <wm-site> — theme orchestrator for the Eshobe Payload storefront.
 *
 * Bootstrap flow (exactly docs/THEME_API.md):
 *   1. GET /api/site (Host: <host>)        -> site {availableLocales, blocks, store, theme}
 *   2. document.dir = dirFor(locale); doc.lang = locale
 *   3. inject <style> from themeCss(site.theme)
 *   4. render header (topbar/nav + locale switcher) + <main> blocks + footer
 *   5. mount a <wm-buy-form> and listen for `wm:open-buy` from product cards
 *
 * Deployment attributes:
 *   host     -> value sent as the Host header
 *   base     -> api-base; a real Payload deployment on another origin
 *   locale   -> the initial locale (falls back to availableLocales[0] / defaultLocale)
 *
 * Locale switcher toggles between site.availableLocales; on change the site is
 * re-fetched, dir/lang re-applied and the page re-rendered. Client routing on
 * `#/products/:key` shows the reusable <wm-product-detail>; `#/` shows home.
 */

import { getSite, getProduct, themeCss, dirFor, escapeHtml, escapeAttr } from '../core/theme.js';
import { renderBlocks } from '../core/blocks.js';
import './wm-cart.js';
import './wm-search.js';

class WmSite extends HTMLElement {
  static observedAttributes = ['host', 'base', 'locale'];

  constructor() {
    super();
    this._site = null;
    this._inited = false;
    this._currentKey = '';
  }

  connectedCallback() {
    if (!this._inited) {
      this._inited = true;
      this._boot();
    }
  }

  attributeChangedCallback(name, oldV, newV) {
    if (!this._site) return;
    if (name === 'locale' && oldV !== undefined && oldV !== newV) {
      this._reboot();
    }
  }

  get _host() {
    return this.getAttribute('host') || '';
  }
  get _base() {
    return this.getAttribute('base') || '';
  }
  get _locale() {
    return this.getAttribute('locale') || '';
  }

  async _boot() {
    this.renderShell();
    try {
      const site = await getSite({ host: this._host, base: this._base, locale: this._locale });
      this._site = site;
      this._applyLocale(site.locale);
      this._injectTheme(site.theme);
      this.render(site);
    } catch (err) {
      this.dataset.error = String(err && err.message || 'boot failed');
      const main = this.querySelector('[data-main]');
      if (main) main.innerHTML = `<p class="wm-site__error">خطا در بارگذاری اطلاعات فروشگاه. لطفاً دوباره تلاش کنید.</p>`;
    }
  }

  async _reboot() {
    // keep shell, clear error, re-boot with the new locale
    const main = this.querySelector('[data-main]');
    if (main) main.innerHTML = `<p class="wm-site__loading">در حال بارگذاری…</p>`;
    this._site = null;
    this._currentKey = '';
    try {
      const site = await getSite({ host: this._host, base: this._base, locale: this._locale });
      this._site = site;
      this._applyLocale(site.locale);
      this._injectTheme(site.theme);
      this.render(site);
    } catch (err) {
      if (main) main.innerHTML = `<p class="wm-site__error">خطا در بارگذاری اطلاعات فروشگاه. لطفاً دوباره تلاش کنید.</p>`;
    }
  }

  _applyLocale(locale) {
    const dir = dirFor(locale);
    if (document.documentElement) {
      document.documentElement.setAttribute('dir', dir);
      document.documentElement.setAttribute('lang', String(locale).split(/[-_]/)[0].toLowerCase());
    }
  }

  _injectTheme(theme) {
    if (typeof themeCss !== 'function') return;
    const style = themeCss(theme);
    if (document.head) document.head.appendChild(style);
  }

  renderShell() {
    this.innerHTML = `
      <div class="wm-shell" data-shell>
        <div class="wm-shell__header wm-site-header" aria-hidden="true"></div>
        <main id="primary" class="site-main wm-home" data-main><p class="wm-site__loading">در حال بارگذاری…</p></main>
        <div class="wm-shell__footer wm-site-footer" aria-hidden="true"></div>
      </div>
    `;
  }

  render(site) {
    const store = site.store || {};
    const locale = site.locale;
    const header = buildHeader(store, { availableLocales: site.availableLocales, locale });
    const footer = buildFooter(store, locale);
    const main = renderBlocks({ site, locale, host: site.host });

    this.innerHTML = `
      ${header}
      <main id="primary" class="site-main wm-home" data-main>${main}</main>
      ${footer}
      <wm-buy-form host="${escapeAttr(site.host)}" base="${escapeAttr(site.base || '')}" locale="${escapeAttr(locale)}" currency="${escapeAttr(store.currency || '')}"></wm-buy-form>
      <wm-search host="${escapeAttr(site.host)}" base="${escapeAttr(site.base || '')}" locale="${escapeAttr(locale)}" currency="${escapeAttr(store.currency || '')}"></wm-search>
      <wm-cart host="${escapeAttr(site.host)}" base="${escapeAttr(site.base || '')}" locale="${escapeAttr(locale)}" currency="${escapeAttr(store.currency || '')}"></wm-cart>
      <div class="wm-toast-container" data-wm-toasts aria-live="polite"></div>
    `;

    this._afterRender();
  }

  _afterRender() {
    const buyForm = this.querySelector('wm-buy-form');
    this.addEventListener('wm:open-buy', (e) => {
      if (buyForm && e.detail && e.detail.product) {
        buyForm.product = e.detail.product;
        buyForm.open();
      }
    });

    // Add-to-cart -> cart drawer + toast
    const cart = this.querySelector('wm-cart');
    this.addEventListener('wm:add-to-cart', (e) => {
      if (cart && e.detail && e.detail.product) {
        cart.add(e.detail.product, e.detail.quantity || 1);
        this._toast(`${e.detail.product.title || 'محصول'} به سبد خرید اضافه شد.`, { type: 'success', action: 'مشاهده سبد', onAction: () => cart.open() });
      }
    });

    // Header search toggle opens <wm-search>
    const searchToggle = this.querySelector('[data-search-open]');
    const search = this.querySelector('wm-search');
    if (searchToggle && search) {
      searchToggle.addEventListener('click', () => search.open());
    }

    // Header cart toggle opens <wm-cart>
    const cartToggle = this.querySelector('[data-cart-open]');
    if (cartToggle && cart) {
      cartToggle.addEventListener('click', () => cart.open());
    }

    // Keep the header badge in sync with the cart.
    const badge = this.querySelector('[data-wm-cart-count]');
    const syncBadge = (n) => {
      if (!badge) return;
      badge.textContent = String(n);
      badge.classList.toggle('wm-site-header__cart-count--hidden', n <= 0);
    };
    syncBadge(cart ? cart.count : 0);
    this.addEventListener('wm:cart-change', (e) => syncBadge(e.detail ? e.detail.count : 0));

    // Locale switcher
    const localeBtns = this.querySelectorAll('[data-locale]');
    localeBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const code = btn.getAttribute('data-locale');
        if (code && code !== this.getAttribute('locale')) {
          this.setAttribute('locale', code);
        }
      });
    });

    // Internal link interception (client routing)
    this.addEventListener('click', (e) => this._onLinkClick(e));

    // Hash routing for the product detail view
    this._onHashChange = () => this._route();
    window.addEventListener('hashchange', this._onHashChange);
    this._route({ fromInit: true });

    this._initHero();
    this._initBackToTop();
  }

  _onLinkClick(e) {
    const link = e.target.closest && e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href') || '';
    if (href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    // Hash routing
    const productMatch = href.match(/^\/products\/([^/?#]+)/);
    if (productMatch) {
      e.preventDefault();
      window.location.hash = '#/products/' + productMatch[1];
      return;
    }
    if (href === '/' || href === '#/') {
      e.preventDefault();
      window.location.hash = '#/';
      return;
    }
    if (href.startsWith('#/')) {
      e.preventDefault();
      window.location.hash = href; // force re-route
      return;
    }
    // Other internal links are not real SPA pages in this demo — no-op.
    if (href.startsWith('/')) e.preventDefault();
  }

  async _route(opts = {}) {
    const main = this.querySelector('[data-main]');
    if (!main || !this._site) return;
    const hash = window.location.hash || '#/';
    const productMatch = hash.match(/^#\/products\/([^/?#]+)/);

    if (productMatch && productMatch[1] !== this._currentKey) {
      this._currentKey = productMatch[1];
      main.classList.add('wm-single-product');
      main.innerHTML = `<p class="wm-site__loading">در حال بارگذاری…</p>`;
      try {
        const product = await getProduct(productMatch[1], { host: this._host, base: this._base, locale: this._site.locale });
        const el = document.createElement('wm-product-detail');
        el.setAttribute('host', this._host);
        el.setAttribute('base', this._base);
        el.setAttribute('locale', this._site.locale);
        el.setAttribute('currency', (this._site.store && this._site.store.currency) || '');
        el.product = product;
        main.replaceChildren(el);
      } catch (err) {
        main.innerHTML = `<p class="wm-site__error">محصول یافت نشد.</p>`;
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    if (!productMatch && this._currentKey) {
      // back to home
      this._currentKey = '';
      main.classList.remove('wm-single-product');
      main.innerHTML = renderBlocks({ site: this._site, locale: this._site.locale, host: this._site.host });
      this._initHero();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  _initHero() {
    const hero = this.querySelector('[data-home-hero]');
    if (!hero) return;
    const track = hero.querySelector('.wm-home-hero__track');
    if (!track) return;
    const slides = Array.from(hero.querySelectorAll('.wm-home-hero__slide'));
    if (!slides.length) return;

    let index = 0;
    const total = slides.length;
    const interval = Number(hero.dataset.autoplay || 0);
    const pauseHover = hero.dataset.autoplayPauseHover !== '0';

    const go = (next) => {
      index = (next + total) % total;
      slides.forEach((s, i) => s.classList.toggle('is-active', i === index));
      const w = slides[index].getBoundingClientRect().width;
      track.style.transform = `translateX(${dirFor(this._locale || 'fa') === 'rtl' ? index * w : -index * w}px)`;
    };

    const prevBtn = hero.querySelector('[data-hero-direction="prev"]');
    const nextBtn = hero.querySelector('[data-hero-direction="next"]');
    if (prevBtn) prevBtn.addEventListener('click', () => go(index - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => go(index + 1));

    let timer = null;
    const start = () => {
      if (interval > 0 && total > 1) timer = setInterval(() => go(index + 1), interval);
    };
    const stop = () => { if (timer) clearInterval(timer); timer = null; };

    if (pauseHover) {
      hero.addEventListener('mouseenter', stop);
      hero.addEventListener('mouseleave', start);
    }
    start();
  }

  _toast(message, opts = {}) {
    const container = this.querySelector('[data-wm-toasts]');
    if (!container) return;
    const type = opts.type || 'info';
    const toast = document.createElement('div');
    toast.className = `wm-toast wm-toast--${type}`;
    toast.innerHTML = `<span class="wm-toast__text">${escapeHtml(message)}</span>`;
    if (opts.action) {
      const action = document.createElement('button');
      action.type = 'button';
      action.className = 'wm-toast__action';
      action.textContent = opts.action;
      action.addEventListener('click', () => {
        if (opts.onAction) opts.onAction();
        dismiss();
      });
      toast.appendChild(action);
    }
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'wm-toast__close';
    close.setAttribute('aria-label', 'بستن');
    close.textContent = '×';
    close.addEventListener('click', dismiss);
    toast.appendChild(close);

    function dismiss() {
      toast.classList.remove('is-visible');
      toast.classList.add('is-leaving');
      setTimeout(() => toast.remove(), 220);
    }

    container.appendChild(toast);
    const raf = window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : (fn) => setTimeout(fn, 0);
    raf(() => toast.classList.add('is-visible'));
    if (!opts.persist) setTimeout(dismiss, opts.duration || 2600);
  }

  _initBackToTop() {
    const btn = this.querySelector('[data-wm-back-to-top]');
    if (!btn) return;
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    const onScroll = () => btn.classList.toggle('is-visible', (window.scrollY || 0) > 400);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
}

/* ------------------------------------------------------------------ *
 * Header
 * ------------------------------------------------------------------ */
function buildHeader(store, roles) {
  const name = store.name || 'فروشگاه آنلاین';
  const logo = store.logo && store.logo.src ? store.logo.src : '';
  const logoAlt = (store.logo && store.logo.alt) || name;
  const brands = logo
    ? `<span class="wm-site-header__logo"><img class="wm-site-header__logo-image" src="${escapeAttr(logo)}" alt="${escapeAttr(logoAlt)}" loading="eager" fetchpriority="high" decoding="async"></span>`
    : '';
  const siteName = logo ? '' : `<span class="wm-site-header__site-name">${escapeHtml(name)}</span>`;

  const topbar = (store.topbar || [])
    .filter(Boolean)
    .map((t) => `<span class="wm-header-topbar__item">${escapeHtml(t)}</span>`)
    .join('');
  const topbarHtml = topbar
    ? `<div class="wm-header-topbar"><div class="wm-header-topbar__inner">${topbar}</div></div>`
    : '';

  const navItems = (store.nav || [
    { label: 'خانه', href: '/' },
    { label: 'فروشگاه', href: '/shop' },
    { label: 'تماس با ما', href: '/contact-us' },
  ]).map((n) => `<li><a href="${escapeAttr(n.href)}">${escapeHtml(n.label)}</a></li>`).join('');

  // Locale switcher — one button per available locale.
  const locales = (roles && roles.availableLocales) || ['fa'];
  const current = (roles && roles.locale) || locales[0] || 'fa';
  const localeSwitcher = locales.length > 1
    ? `<div class="wm-locale-switcher" role="group" aria-label="زبان">${locales
        .map((l) => `<button type="button" class="wm-locale-switcher__btn${l === current ? ' is-active' : ''}" data-locale="${escapeAttr(l)}" aria-pressed="${l === current ? 'true' : 'false'}">${escapeHtml(localeLabel(l))}</button>`)
        .join('')}</div>`
    : '';

  return `
    <header id="masthead" class="wm-site-header is-sticky">
      ${topbarHtml}
      <div class="wm-site-header__inner">
        <a class="wm-site-header__brand${logo ? ' wm-site-header__brand--has-logo' : ''}" href="#/" rel="home">
          ${brands}
          <span class="wm-site-header__site-name screen-reader-text">${escapeHtml(name)}</span>
          ${siteName}
        </a>
        <button type="button" class="wm-site-header__tablet-toggle" aria-haspopup="dialog" aria-expanded="false" aria-label="منو">
          <span class="wm-site-header__tablet-toggle-bar"></span>
          <span class="wm-site-header__tablet-toggle-bar"></span>
          <span class="wm-site-header__tablet-toggle-bar"></span>
        </button>
        <nav class="wm-site-header__nav" aria-label="منوی اصلی">
          <ul class="wm-site-header__menu">${navItems}</ul>
        </nav>
        <div class="wm-site-header__actions">
          ${localeSwitcher}
          <button class="wm-site-header__action wm-site-header__search-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="جستجو" data-search-open>
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('search')}</span>
            <span class="wm-site-header__action-text">جستجو</span>
          </button>
          <a class="wm-site-header__action wm-site-header__account" href="#" aria-label="حساب کاربری">
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('account')}</span>
            <span class="wm-site-header__action-text">حساب</span>
          </a>
          <button type="button" class="wm-site-header__action wm-site-header__cart" href="#" aria-label="سبد خرید" data-cart-open>
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('cart')}</span>
            <span class="wm-site-header__action-text">سبد خرید</span>
            <span class="wm-site-header__cart-count wm-site-header__cart-count--hidden" data-wm-cart-count>0</span>
          </button>
        </div>
      </div>
    </header>
  `;
}

function localeLabel(code) {
  const map = { fa: 'فارسی', en: 'EN' };
  return map[String(code).toLowerCase()] || String(code).toUpperCase();
}

/* ------------------------------------------------------------------ *
 * Footer
 * ------------------------------------------------------------------ */
function buildFooter(store, locale) {
  const name = store.name || 'فروشگاه آنلاین';
  const footer = store.footer || {};
  const title = footer.title || name;
  const description = footer.description || 'انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.';
  const links = (footer.links || [
    { label: 'درباره ما', href: '/' },
    { label: 'تماس با ما', href: '/' },
    { label: 'راهنمای خرید', href: '/' },
    { label: 'پیگیری سفارش', href: '/' },
  ]).map((l) => `<li><a href="${escapeAttr(l.href)}">${escapeHtml(l.label)}</a></li>`).join('');

  const guarantee = footer.guarantee || 'ضمانت بازگشت کالا تا ۷ روز پس از تحویل سفارش';
  const copyright = footer.copyright || `© ${new Date().getFullYear()} ${name}. کلیه حقوق محفوظ است.`;
  const badgeStart = footer.badges || [];
  const badges = badgeStart.length
    ? `<div class="wm-site-footer__badges" aria-label="نمادهای اعتماد">${badgeStart
        .map((b) => `<div class="wm-site-footer__badge"><a href="${escapeAttr(b.href || '#')}" target="_blank" rel="nofollow noopener">${b.html || (b.title ? `<span>${escapeHtml(b.title)}</span>` : '')}</a></div>`)
        .join('')}</div>`
    : '';

  return `
    <footer id="colophon" class="wm-site-footer wm-section-decor wm-section-decor--footer">
      <div class="wm-site-footer__inner">
        <div class="wm-site-footer__content">
          <div class="wm-site-footer__brand">
            <h2 class="wm-site-footer__title">${escapeHtml(title)}</h2>
            <p class="wm-site-footer__text">${escapeHtml(description)}</p>
            <ul class="wm-site-footer__links">${links}</ul>
            ${guarantee ? `<div class="wm-site-footer__guarantee"><span class="wm-site-footer__guarantee-icon" aria-hidden="true">✓</span>${escapeHtml(guarantee)}</div>` : ''}
          </div>
          ${badges}
        </div>
        <div class="wm-site-footer__bottom">
          <div class="wm-site-footer__copyright">${escapeHtml(copyright)}</div>
        </div>
      </div>
    </footer>
    <button type="button" class="wm-back-to-top" data-wm-back-to-top aria-label="بازگشت به بالا"><span aria-hidden="true">↑</span></button>
  `;
}

/* Inline SVG actions icons (match wm_header_icon_svg). */
function icon(name) {
  const icons = {
    search: '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><line x1="20" y1="20" x2="16.2" y2="16.2"></line></svg>',
    account: '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 19.2c1.2-3.2 4.2-5.2 7.5-5.2s6.3 2 7.5 5.2"></path></svg>',
    cart: '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3.5 6h2l1.6 10.2a1.8 1.8 0 0 0 1.8 1.5h8.4a1.8 1.8 0 0 0 1.78-1.52L20.5 9H7.1"></path><circle cx="9.5" cy="20" r="1.3"></circle><circle cx="17" cy="20" r="1.3"></circle></svg>',
  };
  return icons[name] || '';
}

if (!customElements.get('wm-site')) {
  customElements.define('wm-site', WmSite);
}
