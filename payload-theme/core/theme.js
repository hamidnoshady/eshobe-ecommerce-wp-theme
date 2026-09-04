/**
 * Eshobe Payload theme — core runtime helpers.
 *
 * This module is deliberately small, framework-free and dependency-free so it
 * can be reused by other themes verbatim. It implements the exact contract in
 * docs/THEME_API.md:
 *
 *   getSite({host, locale})      -> GET /api/site
 *   formatPrice(price, cur, loc) -> never converts, only formats
 *   formatDate(date, locale)     -> localized date
 *   dirFor(locale)               -> 'rtl' | 'ltr'
 *   themeCss(theme)              -> <style> override block
 *
 * Everything here is pure (no DOM) except themeCss() which can build a
 * <style> node; browsers ignore the forbidden Host header (we set it anyway
 * for server-side / Node consumers and for the bundled mock API).
 */

const DEFAULT_LOCALE = 'fa';

/** Locales that are right-to-left. */
const RTL_LOCALES = ['fa', 'ar', 'he', 'ur', 'sd', 'ug', 'dv', 'ps'];

/** Currency display labels for the handful we ship by default. */
const CURRENCY_LABELS = {
  fa: { IRR: 'تومان', 'IRT': 'تومان', USD: 'دلار', EUR: 'یورو' },
  en: { IRR: 'IRR', 'IRT': 'IRT', USD: '$', EUR: '€' },
};

/** Design-token defaults — mirror assets/css/tokens.css. */
const THEME_DEFAULTS = {
  colors: {
    primary: '#111827',
    secondary: '#6B7280',
    text: '#1F2937',
    accent: '#C89B3C',
    accentDark: '#9F7425',
    background: '#F6F5F2',
    surface: '#FFFFFF',
    border: '#E5E0D8',
    muted: '#6B7280',
    cta: '#111827',
  },
  font: {
    family: "'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    sizeBase: '15px',
    sizeH1: '32px',
    sizeH2: '26px',
    sizeH3: '20px',
    sizeSmall: '13px',
    lineHeightBody: 1.9,
    weightHeading: 800,
    weightBody: 400,
  },
  layout: {
    contentWidth: 1200,
    globalRadius: 24,
  },
};

/** Resolve the active display locale for formatting. */
export function resolveLocale(data, requested) {
  const available = Array.isArray(data.availableLocales) ? data.availableLocales : [];
  const locale = requested || data.defaultLocale || available[0] || DEFAULT_LOCALE;
  return locale;
}

/** True when the locale is right-to-left. */
export function dirFor(locale) {
  const l = String(locale || '').toLowerCase().split(/[-_]/)[0];
  return RTL_LOCALES.includes(l) ? 'rtl' : 'ltr';
}

/**
 * Format a number as a localized digit string (Persian digits for fa locales)
 * without converting it into another currency.
 */
function formatNumber(value, locale) {
  const num = Number(value);
  if (!Number.isFinite(num)) return '0';
  const isFa = /^fa/i.test(String(locale || ''));
  const fmtLocale = isFa ? 'fa-IR' : locale || 'en-US';
  // Clamp fractional part to a sane maximum; most Eshobe prices are whole rial.
  const hasFraction = Math.abs(num % 1) > 0.005;
  return new Intl.NumberFormat(fmtLocale, {
    style: 'decimal',
    minimumFractionDigits: 0,
    maximumFractionDigits: hasFraction ? 2 : 0,
    useGrouping: true,
  }).format(num);
}

function currencyLabel(currency, locale) {
  const c = String(currency || '').toUpperCase();
  const l = String(locale || '').toLowerCase();
  const fam = /^fa/i.test(l);
  const bucket = fam ? 'fa' : 'en';
  const label = (CURRENCY_LABELS[bucket] || {})[c] || c;
  return label;
}

/**
 * Format a price in the store currency using the given display locale.
 * IMPORTANT: the amount is never converted — the value passed in is the value
 * formatted. Returns HTML-safe markup (numeric run wrapped in <bdi> so RTL
 * documents don't reverse the digits, mirroring assets/css/theme.css).
 */
export function formatPrice(price, currency, locale) {
  const digits = formatNumber(price, locale);
  const label = currencyLabel(currency, locale);
  const escapedLabel = escapeHtml(label);
  return `<span class="amount wm-price-amount"><bdi>${digits}</bdi> <span class="wm-price-currency">${escapedLabel}</span></span>`;
}

/** Format a date in a locale. Accepts Date | timestamp | ISO string. */
export function formatDate(input, locale) {
  const date = input instanceof Date ? input : new Date(input);
  if (!date || Number.isNaN(date.getTime())) return '';
  const isFa = /^fa/i.test(String(locale || ''));
  const fmtLocale = isFa ? 'fa-IR' : locale || 'en-US';
  return new Intl.DateTimeFormat(fmtLocale, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date);
}

/**
 * Build the CSS :root override block from a `site.theme` object. Any omitted
 * token falls back to THEME_DEFAULTS. Returns a plain CSS string.
 */
export function buildThemeCssString(theme = {}) {
  const t = theme || {};
  const colors = { ...THEME_DEFAULTS.colors, ...(t.colors || {}) };
  const font = { ...THEME_DEFAULTS.font, ...(t.font || {}) };
  const layout = { ...THEME_DEFAULTS.layout, ...(t.layout || {}) };

  const hex = (key) => {
    const v = colors[key];
    // allow the full color() grammar through (e.g. color-mix), but sanitize
    // against obvious CSS-injection forms.
    const clean = String(v).replace(/[^#a-zA-Z0-9(),.% \t-]/g, '');
    return clean || THEME_DEFAULTS.colors[key];
  };

  const px = (key) => {
    const v = layout[key];
    const n = Number(v);
    return Number.isFinite(n) ? `${Math.min(Math.max(n, 1040), 1440)}px` : `${THEME_DEFAULTS.layout[key]}px`;
  };
  const radius = () => {
    const n = Number(layout.globalRadius);
    const r = Number.isFinite(n) ? n : THEME_DEFAULTS.layout.globalRadius;
    return `${Math.min(Math.max(r, 8), 40)}px`;
  };

  const css = [
    ':root{',
    `--wm-color-primary:${hex('primary')};`,
    `--wm-color-secondary:${hex('secondary')};`,
    `--wm-color-text:${hex('text')};`,
    `--wm-color-accent:${hex('accent')};`,
    `--wm-color-accent-dark:${hex('accentDark')};`,
    `--wm-color-background:${hex('background')};`,
    `--wm-color-bg:${hex('background')};`,
    `--wm-color-surface:${hex('surface')};`,
    `--wm-color-border:${hex('border')};`,
    `--wm-color-muted:${hex('muted')};`,
    `--wm-color-cta:${hex('cta')};`,
    `--wm-font-primary:${font.family};`,
    `--wm-font-size-base:${font.sizeBase};`,
    `--wm-font-size-h1:${font.sizeH1};`,
    `--wm-font-size-h2:${font.sizeH2};`,
    `--wm-font-size-h3:${font.sizeH3};`,
    `--wm-font-size-small:${font.sizeSmall};`,
    `--wm-line-height-body:${Number(font.lineHeightBody) || 1.9};`,
    `--wm-font-weight-heading:${Number(font.weightHeading) || 800};`,
    `--wm-font-weight-body:${Number(font.weightBody) || 400};`,
    `--wm-content-width:${px('contentWidth')};`,
    `--wm-radius-lg:${radius()};`,
    '}',
  ].join('');

  return css;
}

/**
 * Build a <style> node (or, with asString, the CSS string) from a site theme.
 */
export function themeCss(theme, opts = {}) {
  const css = buildThemeCssString(theme);
  if (opts && opts.asString) return css;
  const style = document.createElement('style');
  style.setAttribute('data-wm-theme', '');
  style.textContent = css;
  return style;
}

/**
 * Resolve a relative API path against an optional base origin.
 * A path is left untouched if it is already absolute. This is what lets the
 * theme reach a real Payload deployment on a different origin via `api-base`.
 */
export function resolveApiUrl(path, base) {
  if (/^https?:\/\//i.test(String(path))) return path;
  if (!base) return path;
  const b = String(base).replace(/\/+$/, '');
  return `${b}/${String(path).replace(/^\/+/, '')}`;
}

/**
 * Minimal fetch wrapper that always sets the API Accept header and, when
 * `host` is given, attempts to send the Host header (browsers ignore it —
 * that is fine, the mock/Node server uses it; same-origin browsers already
 * send the correct Host). When `base` is given, path is resolved against it
 * (used for cross-origin Payload deployments via `api-base`).
 */
export async function api(path, { host, base = '', method = 'GET', body, headers = {}, credentials } = {}) {
  const h = { Accept: 'application/json', ...headers };
  if (host) h.Host = host;
  const opts = { method, headers: h };
  if (credentials) opts.credentials = credentials;
  if (body !== undefined) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  return fetch(resolveApiUrl(path, base), opts);
}

/** Normalize a /api/site payload into a flat, typed site object. */
export function normalizeSite(data, ctx = {}) {
  const locales = Array.isArray(data.availableLocales) ? data.availableLocales : [DEFAULT_LOCALE];
  const locale = resolveLocale(data, ctx.locale);
  return {
    availableLocales: locales,
    defaultLocale: data.defaultLocale || locales[0] || DEFAULT_LOCALE,
    locale,
    host: ctx.host || '',
    store: data.store || {},
    theme: data.theme || {},
    blocks: Array.isArray(data.blocks) ? data.blocks : [],
  };
}

/** GET /api/site with Host: <host>, resolved against an optional api-base. */
export async function getSite({ host, base = '', locale } = {}) {
  const res = await api('/api/site', { host, base });
  if (!res.ok) {
    throw new Error(`GET /api/site failed (${res.status})`);
  }
  const data = await res.json();
  const site = normalizeSite(data, { host, locale });
  site.base = base;
  return site;
}

/**
 * Fetch a single product for the product-detail view.
 * Accepts a UUID `id` or `slug`; the mock/real server serves `GET /api/products/:key`.
 */
export async function getProduct(key, { host, base = '', locale } = {}) {
  const k = String(key || '').replace(/^\/+/, '');
  const res = await api(`/api/products/${encodeURIComponent(k)}`, { host, base });
  if (!res.ok) {
    throw new Error(`GET /api/products/${k} failed (${res.status})`);
  }
  const data = await res.json();
  return data && (data.product || data);
}

/** Escape a string for safe interpolation into HTML. */
export function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Escape an attribute value (quotes are the main concern). */
export function escapeAttr(value) {
  return escapeHtml(String(value == null ? '' : value));
}

/**
 * Load a script as an ES module and import its default + named exports. Used
 * by index.html to wire components without a bundler.
 */
export function importScript(src) {
  return import(src);
}
