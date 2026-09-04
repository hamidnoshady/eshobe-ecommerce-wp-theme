/**
 * <wm-site> — theme orchestrator for the Eshobe Payload storefront.
 *
 * Bootstrap flow (exactly docs/THEME_API.md):
 *   1. GET /api/site (Host: <host>)        -> site {availableLocales, blocks, store, theme}
 *   2. document.dir = dirFor(locale); doc.lang = locale
 *   3. inject <style> from themeCss(site.theme)
 *   4. render <wm-header> topbar/nav + <main> blocks + <wm-footer>
 *   5. mount a <wm-buy-form> and listen for `wm:open-buy` from product cards
 *
 * Attributes: host, locale. All other data come from the API.
 */

import { getSite, themeCss, dirFor, escapeHtml, escapeAttr, formatDate } from '../core/theme.js';
import { renderBlocks } from '../core/blocks.js';

class WmSite extends HTMLElement {
  static observedAttributes = ['host', 'locale'];

  constructor() {
    super();
    this._site = null;
    this._inited = false;
  }

  connectedCallback() {
    if (!this._inited) {
      this._inited = true;
      this._boot();
    }
  }

  attributeChangedCallback(name, oldV, newV) {
    if (name === 'locale' && this._site && this.isConnected && oldV !== undefined && oldV !== newV) {
      this._applyLocale(newV);
    }
  }

  get _host() {
    return this.getAttribute('host') || '';
  }

  get _locale() {
    return this.getAttribute('locale') || '';
  }

  async _boot() {
    const host = this._host;
    // Render an empty shell immediately so the page has its RTL direction and
    // header area before the first paint of async data.
    this.renderShell();
    try {
      const site = await getSite({ host, locale: this._locale });
      this._site = site;
      this._applyLocale(site.locale);
      this._injectTheme(site.theme);
      this.render(site);
    } catch (err) {
      this.dataset.error = String(err && err.message || 'boot failed');
      const main = this.querySelector('[data-main]');
      if (main) {
        main.innerHTML = `<p class="wm-site__error">خطا در بارگذاری اطلاعات فروشگاه. لطفاً دوباره تلاش کنید.</p>`;
      }
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
    const header = buildHeader(store);
    const footer = buildFooter(store, locale);
    const main = renderBlocks({ site, locale, host: site.host });

    this.innerHTML = `
      ${header}
      <main id="primary" class="site-main wm-home" data-main>${main}</main>
      ${footer}
      <wm-buy-form host="${escapeAttr(site.host)}" locale="${escapeAttr(locale)}" currency="${escapeAttr(store.currency || '')}"></wm-buy-form>
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

    this._initHero();
    this._initBackToTop();
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
      if (interval > 0 && total > 1) {
        timer = setInterval(() => go(index + 1), interval);
      }
    };
    const stop = () => { if (timer) clearInterval(timer); timer = null; };

    if (pauseHover) {
      hero.addEventListener('mouseenter', stop);
      hero.addEventListener('mouseleave', start);
    }
    start();
  }

  _initBackToTop() {
    const btn = this.querySelector('[data-wm-back-to-top]');
    if (!btn) return;
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    const onScroll = () => {
      btn.classList.toggle('is-visible', (window.scrollY || 0) > 400);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
}

/* ------------------------------------------------------------------ *
 * Header
 * ------------------------------------------------------------------ */
function buildHeader(store) {
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

  return `
    <header id="masthead" class="wm-site-header is-sticky">
      ${topbarHtml}
      <div class="wm-site-header__inner">
        <a class="wm-site-header__brand${logo ? ' wm-site-header__brand--has-logo' : ''}" href="/" rel="home">
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
          <button class="wm-site-header__action wm-site-header__search-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="جستجو">
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('search')}</span>
            <span class="wm-site-header__action-text">جستجو</span>
          </button>
          <a class="wm-site-header__action wm-site-header__account" href="#" aria-label="حساب کاربری">
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('account')}</span>
            <span class="wm-site-header__action-text">حساب</span>
          </a>
          <a class="wm-site-header__action wm-site-header__cart" href="#" aria-label="سبد خرید">
            <span class="wm-site-header__action-icon" aria-hidden="true">${icon('cart')}</span>
            <span class="wm-site-header__action-text">سبد خرید</span>
            <span class="wm-site-header__cart-count wm-site-header__cart-count--hidden" data-wm-cart-count>0</span>
          </a>
        </div>
      </div>
    </header>
  `;
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
    { label: 'درباره ما', href: '/about-us' },
    { label: 'تماس با ما', href: '/contact-us' },
    { label: 'راهنمای خرید', href: '/buying-guide' },
    { label: 'پیگیری سفارش', href: '/order-tracking' },
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
