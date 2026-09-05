/**
 * Eshobe Payload theme — live Payload CMS adapter.
 *
 * The theme is written against the idealized contract in docs/THEME_API.md: a
 * `/api/site` that already carries the home page's blocks, a `/api/products`
 * that answers `{products:[…]}`, a `/api/products/:slug`. The real backend
 * (`hamidnoshady/eshobe-cms`, its own docs/THEME_API.md) answers a *different*
 * shape — the descriptor is a bootstrap document, not content, and everything
 * else is Payload's generic REST API:
 *
 *   /api/site      -> { contractVersion, blocks:[slug…], theme:{primary,…}, store:{currency} }
 *   /api/pages     -> { docs:[{hero, layout:[{blockType,…}]}] }   <- the actual content
 *   /api/products  -> { docs:[…], totalDocs }                     <- Payload pagination
 *
 * This module is the translation layer, and it is the *only* place that knows
 * the real CMS exists. It is engaged automatically: `GET /api/site` answering
 * with a `contractVersion` and a `blocks` array of strings is a live CMS, and
 * anything else (the bundled mock in server/index.mjs) is left alone. So the
 * components, the block registry and the design system stay written against one
 * contract, and a second backend costs one file rather than a fork.
 *
 * What it does NOT do: convert money (the CMS's integer minor units are handed
 * to formatPrice untouched, exactly as §1.3 of the contract requires), invent
 * content for a block the CMS did not save, or send a tenant id — the tenant is
 * still the `Host` of the request, which is what `base` resolves to.
 */

/** Payload block slugs this theme knows how to draw. Everything else is reported. */
const BLOCK_MAP = {
  content: 'richText',
  productGrid: 'productGrid',
  features: 'trust',
};

/** CMS `theme.radius` is a t-shirt size; the design system wants pixels. */
const RADIUS_PX = { none: 8, sm: 10, md: 16, lg: 24, xl: 32 };

let state = { active: false, descriptor: null, origin: '' };

/** True for a response body from the real Payload CMS's `GET /api/site`. */
export function isPayloadDescriptor(data) {
  return Boolean(
    data &&
      typeof data === 'object' &&
      data.contractVersion != null &&
      Array.isArray(data.blocks) &&
      data.blocks.every((b) => typeof b === 'string'),
  );
}

/** Whether the live-CMS translation is engaged (set by getSite). */
export function isActive() {
  return state.active;
}

/** The unmapped block types seen on the last page render — read by a caller for diagnostics. */
export let unsupportedBlocks = [];

/* ------------------------------------------------------------------ *
 * Lexical rich text -> HTML
 * ------------------------------------------------------------------ */

const FORMAT = { bold: 1, italic: 2, strikethrough: 4, underline: 8, code: 16, subscript: 32, superscript: 64 };

function esc(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function inlineText(node) {
  let html = esc(node.text || '');
  const f = Number(node.format || 0);
  if (f & FORMAT.code) html = `<code>${html}</code>`;
  if (f & FORMAT.bold) html = `<strong>${html}</strong>`;
  if (f & FORMAT.italic) html = `<em>${html}</em>`;
  if (f & FORMAT.underline) html = `<u>${html}</u>`;
  if (f & FORMAT.strikethrough) html = `<s>${html}</s>`;
  return html;
}

/**
 * Render the subset of Lexical the CMS's rich-text fields actually produce.
 * `direction` is carried onto the wrapper because a field can hold content in
 * the other direction from the page (the CMS's own CLAUDE.md makes this point).
 */
export function richTextToHtml(value) {
  const root = value && value.root;
  if (!root || !Array.isArray(root.children)) return '';
  return root.children.map(renderNode).join('');
}

export function richTextDirection(value) {
  const dir = value && value.root && value.root.direction;
  return dir === 'ltr' || dir === 'rtl' ? dir : '';
}

function children(node) {
  return Array.isArray(node.children) ? node.children.map(renderNode).join('') : '';
}

function renderNode(node) {
  if (!node || typeof node !== 'object') return '';
  switch (node.type) {
    case 'text':
      return inlineText(node);
    case 'linebreak':
      return '<br>';
    case 'paragraph': {
      const inner = children(node);
      return inner ? `<p>${inner}</p>` : '';
    }
    case 'heading': {
      const tag = /^h[1-6]$/.test(node.tag || '') ? node.tag : 'h2';
      return `<${tag}>${children(node)}</${tag}>`;
    }
    case 'quote':
      return `<blockquote>${children(node)}</blockquote>`;
    case 'list': {
      const tag = node.listType === 'number' ? 'ol' : 'ul';
      return `<${tag}>${children(node)}</${tag}>`;
    }
    case 'listitem':
      return `<li>${children(node)}</li>`;
    case 'link':
    case 'autolink': {
      const fields = node.fields || {};
      const href = fields.url || (fields.doc && fields.doc.value && fields.doc.value.slug ? `/${fields.doc.value.slug}` : '#');
      const target = fields.newTab ? ' target="_blank" rel="noopener"' : '';
      return `<a href="${esc(href)}"${target}>${children(node)}</a>`;
    }
    case 'horizontalrule':
      return '<hr>';
    default:
      return children(node);
  }
}

/* ------------------------------------------------------------------ *
 * Media
 * ------------------------------------------------------------------ */

/**
 * Resolve a media document to an absolute URL. Local storage serves uploads
 * relative (`/api/media/file/x.png`), which is meaningless from another origin —
 * `site.media.origin` from the descriptor is what makes it resolvable.
 */
export function mediaSrc(media, descriptor) {
  const doc = media && typeof media === 'object' ? media : null;
  const url = doc && (doc.url || (doc.sizes && doc.sizes.card && doc.sizes.card.url));
  if (!url) return '';
  if (/^https?:\/\//i.test(url)) return url;
  const origin = (descriptor && descriptor.media && descriptor.media.origin) || state.origin || '';
  return origin ? `${String(origin).replace(/\/+$/, '')}${url.startsWith('/') ? '' : '/'}${url}` : url;
}

/* ------------------------------------------------------------------ *
 * Products
 * ------------------------------------------------------------------ */

/** A Payload `products` document -> the product shape every component expects. */
export function adaptProduct(doc, descriptor) {
  if (!doc) return null;
  const image = mediaSrc(doc.image, descriptor);
  const currency = (descriptor && descriptor.store && descriptor.store.currency) || 'IRT';
  // `trackInventory` off means "always sellable"; on means the row's count decides.
  const inStock = doc.trackInventory ? Number(doc.inventory || 0) > 0 : true;
  const discounted =
    doc.compareAtPrice != null && Number(doc.compareAtPrice) > Number(doc.price || 0);
  return {
    id: doc.id,
    title: doc.title || '',
    slug: doc.slug || '',
    // Prices are integer minor units of the site's currency and are handed to
    // formatPrice untouched — the theme never converts (contract §1.3).
    price: doc.price,
    compareAtPrice: doc.compareAtPrice ?? null,
    currency,
    sku: doc.sku || '',
    description: doc.summary || '',
    image: image ? { src: image, alt: doc.title || '' } : null,
    gallery: [],
    url: `/products/${doc.slug || doc.id}`,
    badge: discounted ? 'تخفیف ویژه' : '',
    inStock,
    inventory: doc.trackInventory ? doc.inventory ?? null : null,
    publishedAt: doc.publishedAt || doc.createdAt || '',
    specs: [],
  };
}

/* ------------------------------------------------------------------ *
 * Blocks
 * ------------------------------------------------------------------ */

/** The page's `hero` field -> the theme's hero block (one content slide). */
function heroBlock(hero, descriptor, page) {
  if (!hero || hero.type === 'none') return null;
  const html = richTextToHtml(hero.richText);
  if (!html && !hero.media) return null;
  const text = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  const image = mediaSrc(hero.media, descriptor);
  const link = Array.isArray(hero.links) && hero.links[0] && hero.links[0].link;
  return {
    id: 'hero',
    type: 'hero',
    autoplayInterval: 0,
    slides: [
      {
        type: 'content',
        title: (page && page.title) || text.slice(0, 80),
        subtitle: text && page && text !== page.title ? text.slice(0, 200) : '',
        primaryText: link && link.label ? link.label : '',
        primaryUrl: link ? linkHref(link) : '',
        image: image ? { src: image } : null,
      },
    ],
  };
}

function linkHref(link) {
  if (!link) return '';
  if (link.type === 'custom' && link.url) return link.url;
  const doc = link.reference && link.reference.value;
  if (doc && typeof doc === 'object' && doc.slug) {
    const base = link.reference.relationTo === 'products' ? '/products/' : '/';
    return `${base}${doc.slug}`;
  }
  return '';
}

/**
 * A Payload `layout` array -> the theme's block array.
 * Unknown block types are dropped (the registry never crashes on one) and
 * recorded in `unsupportedBlocks` so the gap is visible instead of silent.
 */
export function adaptLayout(page, descriptor) {
  const out = [];
  const unsupported = [];
  const hero = heroBlock(page && page.hero, descriptor, page);
  if (hero) out.push(hero);

  for (const block of (page && page.layout) || []) {
    const type = BLOCK_MAP[block.blockType];
    if (!type) {
      unsupported.push(block.blockType);
      continue;
    }
    if (type === 'richText') {
      const html = (block.columns || [])
        .map((col) => richTextToHtml(col.richText))
        .filter(Boolean)
        .join('');
      const dir = (block.columns || []).map((col) => richTextDirection(col.richText)).find(Boolean);
      if (html) out.push({ id: block.id, type: 'richText', html, dir });
      continue;
    }
    if (type === 'productGrid') {
      out.push({
        id: block.id,
        type: 'productGrid',
        title: block.blockName || 'محصولات',
        subtitle: '',
        limit: Number(block.limit || 6),
        // `populateBy: 'selection'` names its rows; the query builder below turns
        // this into a `where[or]` over those ids.
        products:
          block.populateBy === 'selection'
            ? (block.products || []).map((p) => (typeof p === 'string' ? p : p && p.id)).filter(Boolean)
            : [],
        viewAllUrl: '',
      });
      continue;
    }
    if (type === 'trust') {
      out.push({
        id: block.id,
        type: 'trust',
        title: block.heading || '',
        subtitle: block.intro || '',
        items: (block.items || []).map((item) => ({
          title: item.title || '',
          text: item.description || '',
        })),
      });
    }
  }

  unsupportedBlocks = unsupported;
  if (unsupported.length && typeof console !== 'undefined') {
    console.warn(
      `[payload-adapter] ${unsupported.length} block(s) the theme has no renderer for: ${unsupported.join(', ')}`,
    );
  }
  return out;
}

/* ------------------------------------------------------------------ *
 * Site
 * ------------------------------------------------------------------ */

/** CMS design tokens -> the theme's colors/font/layout token object. */
export function adaptTheme(theme) {
  if (!theme) return {};
  const accent = theme.accent || '#C89B3C';
  return {
    colors: {
      primary: theme.primary || undefined,
      cta: theme.primary || undefined,
      accent,
      accentDark: shade(accent, -0.18),
      text: theme.foreground || undefined,
      background: theme.background || undefined,
      surface: theme.background || undefined,
    },
    font: { lineHeightBody: theme.lineHeight || undefined },
    layout: { globalRadius: RADIUS_PX[theme.radius] ?? undefined },
  };
}

/** Darken (ratio < 0) or lighten a #rrggbb by a ratio, for the accent's hover pair. */
function shade(hex, ratio) {
  const m = /^#?([0-9a-f]{6})$/i.exec(String(hex || ''));
  if (!m) return hex;
  const n = parseInt(m[1], 16);
  const ch = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((c) =>
    Math.max(0, Math.min(255, Math.round(c + c * ratio))),
  );
  return `#${ch.map((c) => c.toString(16).padStart(2, '0')).join('')}`;
}

const NAV_EXCLUDED = new Set(['home']);

/**
 * Build the storefront chrome the descriptor does not carry.
 *
 * The CMS has no navigation collection: a site's pages *are* its nav (the admin
 * orders them, `RESERVED_PAGE_SLUGS` keeps routes from colliding with them), so
 * the menu is the published `pages` list with the home page pulled out and
 * pinned first. Everything else here comes from the descriptor.
 */
async function buildStore(descriptor, pages) {
  const links = pages
    .filter((p) => p.slug && !NAV_EXCLUDED.has(p.slug))
    .map((p) => ({ label: p.title || p.slug, href: `#/p/${p.slug}` }));
  return {
    currency: (descriptor.store && descriptor.store.currency) || 'IRT',
    currencyLocale: descriptor.defaultLocale === 'fa' ? 'fa-IR' : undefined,
    name: descriptor.name || '',
    description: '',
    logo: null,
    topbar: [],
    nav: [{ label: 'خانه', href: '#/' }, ...links],
    footer: {
      title: descriptor.name || '',
      description: '',
      links,
      guarantee: '',
      copyright: `© ${descriptor.name || ''}`,
      badges: [],
    },
  };
}

/**
 * Fetch one page by slug (default: the home page) and return its adapted blocks.
 * `HOME_SLUG` is `home` in the CMS and its URL is `/` — the theme's `#/` maps to it.
 */
export async function fetchPageBlocks({ slug = 'home', locale, host, base, fetchApi }) {
  const params = new URLSearchParams({ depth: '2', limit: '1' });
  params.set('where[slug][equals]', slug);
  if (locale) params.set('locale', locale);
  const res = await fetchApi(`/api/pages?${params}`, { host, base });
  if (!res.ok) return { blocks: [], page: null };
  const data = await res.json();
  const page = (data && data.docs && data.docs[0]) || null;
  return { blocks: page ? adaptLayout(page, state.descriptor) : [], page };
}

/**
 * The real CMS's `GET /api/site` body -> the theme's site object.
 * Engages the translation for every later `/api/products` and `/api/checkout`
 * call (see `payloadRequest`).
 */
export async function adaptSite(data, { host, base = '', locale, fetchApi } = {}) {
  const locales = Array.isArray(data.availableLocales) && data.availableLocales.length
    ? data.availableLocales
    : ['fa'];
  const active = locale && locales.includes(locale) ? locale : data.defaultLocale || locales[0];

  state = { active: true, descriptor: data, origin: (data.media && data.media.origin) || base || '' };

  const pagesParams = new URLSearchParams({ depth: '0', limit: '50', sort: 'title' });
  if (active) pagesParams.set('locale', active);
  const pagesRes = await fetchApi(`/api/pages?${pagesParams}`, { host, base });
  const pages = pagesRes.ok ? ((await pagesRes.json()).docs || []) : [];

  const { blocks } = await fetchPageBlocks({ slug: 'home', locale: active, host, base, fetchApi });

  return {
    availableLocales: locales,
    defaultLocale: data.defaultLocale || locales[0],
    locale: active,
    host: host || '',
    backend: 'payload-cms',
    contractVersion: data.contractVersion,
    siteType: data.type,
    status: data.status,
    pages,
    store: await buildStore(data, pages),
    theme: adaptTheme(data.theme),
    blocks,
  };
}

/* ------------------------------------------------------------------ *
 * Request translation
 * ------------------------------------------------------------------ */

function json(body, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json; charset=utf-8' },
  });
}

/**
 * Translate one theme-contract request into the CMS's REST equivalent.
 * Returns a `Response` in the contract's own shape, or `null` when the path is
 * not one this adapter owns (the caller then issues it unchanged).
 */
export async function payloadRequest(path, opts = {}, { fetchApi } = {}) {
  if (!state.active) return null;
  const { host, base = '' } = opts;
  const url = new URL(path, 'http://theme.local');
  const descriptor = state.descriptor;

  // GET /api/products/:key  ->  ?where[slug][equals]= (or the id when it is a UUID)
  const detail = url.pathname.match(/^\/api\/products\/(.+)$/);
  if (detail && (opts.method || 'GET') === 'GET') {
    // A Persian slug arrives percent-encoded twice: once by the router that put
    // it in the hash, once by `getProduct`'s own encodeURIComponent. Decoding
    // to a stable value is what keeps `where[slug][equals]` from asking the CMS
    // for the literal text "%D8%B2%D8%B9…" and being told, correctly, 404.
    const key = fullyDecode(detail[1]);
    const params = new URLSearchParams({ depth: '2', limit: '1' });
    params.set(isUuid(key) ? 'where[id][equals]' : 'where[slug][equals]', key);
    const locale = url.searchParams.get('locale');
    if (locale) params.set('locale', locale);
    const res = await fetchApi(`/api/products?${params}`, { host, base });
    if (!res.ok) return json({ error: 'upstream', status: res.status }, res.status);
    const data = await res.json();
    const doc = data && data.docs && data.docs[0];
    if (!doc) return json({ error: 'not_found', message: 'محصول یافت نشد.' }, 404);
    return json({ product: adaptProduct(doc, descriptor) });
  }

  // GET /api/products?collection=&search=&limit=&locale=  ->  Payload query language
  if (url.pathname === '/api/products' && (opts.method || 'GET') === 'GET') {
    const q = url.searchParams;
    const params = new URLSearchParams({ depth: '2' });
    params.set('limit', String(Math.max(1, Math.min(Number(q.get('limit') || 10) || 10, 50))));
    const locale = q.get('locale');
    if (locale) params.set('locale', locale);

    const ids = (q.get('ids') || '').split(',').map((s) => s.trim()).filter(Boolean);
    const search = (q.get('search') || '').trim();
    if (ids.length) {
      ids.forEach((id, i) => params.set(`where[or][${i}][id][equals]`, id));
    } else if (search) {
      // The CMS exposes no search endpoint; `like` over the two fields a shopper
      // types into the header search is what Payload's query language offers.
      params.set('where[or][0][title][like]', search);
      params.set('where[or][1][summary][like]', search);
    } else {
      params.set('sort', '-createdAt');
    }

    const res = await fetchApi(`/api/products?${params}`, { host, base });
    if (!res.ok) return json({ products: [], total: 0 }, res.status);
    const data = await res.json();
    const products = ((data && data.docs) || []).map((doc) => adaptProduct(doc, descriptor));
    return json({ products, total: data.totalDocs ?? products.length, search });
  }

  // POST /api/checkout — the body already matches the CMS contract; only the
  // answer differs. A bank-provider site has no PSP to redirect to and returns
  // `confirmationUrl` instead, which the theme would otherwise ignore and sit on
  // "در حال انتقال…" forever. The receipt page lives on the CMS origin.
  if (url.pathname === '/api/checkout' && (opts.method || '').toUpperCase() === 'POST') {
    const res = await fetchApi(path, opts);
    let body = null;
    try {
      body = await res.clone().json();
    } catch {
      return res;
    }
    if (body && !body.redirectUrl && body.confirmationUrl) {
      const origin = String(base || state.origin || '').replace(/\/+$/, '');
      return json({ ...body, redirectUrl: `${origin}${body.confirmationUrl}` }, res.status);
    }
    return json(body, res.status);
  }

  return null;
}

/** Decode until the value stops changing — see the note in `payloadRequest`. */
function fullyDecode(value) {
  let out = String(value);
  for (let i = 0; i < 4; i += 1) {
    let next;
    try {
      next = decodeURIComponent(out);
    } catch {
      return out;
    }
    if (next === out) break;
    out = next;
  }
  return out;
}

function isUuid(value) {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(String(value));
}

/** Test seam: forget the engaged backend (used by the mock server's own flows). */
export function reset() {
  state = { active: false, descriptor: null, origin: '' };
  unsupportedBlocks = [];
}
