/**
 * Eshobe Payload — local mock API + static dev server.
 *
 * Implements the exact contract in docs/THEME_API.md so the theme can be
 * previewed without a real Payload backend:
 *
 *   GET  /api/site       Host: <host>  -> availableLocales, blocks, store, theme
 *   GET  /api/products   Host: <host>  -> products
 *   POST /api/checkout   Host: <host>  -> 302 (or 400 on honeypot)
 *
 * Serving the payload-theme/ directory over HTTP. Use with `node server/index.mjs`
 * then open the printed URL (the bind host is 0.0.0.0 so the Arena preview works).
 */

import http from 'node:http';
import { promises as fs } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PORT = Number(process.env.PORT || 4173);
const HOST = '0.0.0.0';

/* ------------------------------------------------------------------ *
 * Fixture data
 * ------------------------------------------------------------------ */

function svgPlaceholder(title, bg = '#F6F5F2', fg = '#111827') {
  const label = String(title).slice(0, 18);
  const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='640' height='640' viewBox='0 0 640 640'>` +
    `<rect width='640' height='640' fill='${bg}'/>` +
    `<circle cx='320' cy='290' r='150' fill='none' stroke='${fg}' stroke-opacity='0.25' stroke-width='28'/>` +
    `<circle cx='320' cy='290' r='88' fill='none' stroke='${fg}' stroke-opacity='0.18' stroke-width='18'/>` +
    `<text x='320' y='470' font-family='sans-serif' font-size='34' fill='${fg}' fill-opacity='0.7' text-anchor='middle'>${label}</text>` +
    `</svg>`;
  return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
}

const PRODUCTS = [
  { title: 'کیف چرم دست‌دوز', price: 2450000, badge: 'تخفیف ویژه', brand: 'چرم برند' },
  { title: 'کفش اسپرت روزمره', price: 1890000, badge: '', brand: 'اسپرت' },
  { title: 'شومیز دخترانه', price: 980000, badge: '', brand: 'پوشاک' },
  { title: 'عطر ادکلن مردانه', price: 3120000, badge: 'پیشنهاد شیک', brand: 'ادکلن' },
  { title: 'دستبند نقره', price: 1450000, badge: '', brand: 'زیورآلات' },
  { title: 'هودی زمستانی', price: 1200000, badge: '', brand: 'پوشاک' },
  { title: 'ساعت مچی کلاسیک', price: 4280000, badge: 'کالای لوکس', brand: 'ساعت' },
  { title: 'کوله‌پشتی شهری', price: 1580000, badge: '', brand: 'کیف و کوله' },
  { title: 'بافت پشمی', price: 1100000, badge: '', brand: 'پوشاک' },
  { title: 'عینک آفتابی', price: 800000, badge: '', brand: 'عینک' },
  { title: 'پالتو چرمی', price: 5600000, badge: 'تخفیف ویژه', brand: 'چرم برند' },
  { title: 'کفش راحتی', price: 720000, badge: '', brand: 'اسپرت' },
].map((p, i) => {
  const slug = p.title.replace(/\s+/g, '-').replace(/[^\u0600-\u06FF\w-]/g, '');
  const first = i % 2 === 0 ? '#F6F5F2' : '#EFECE6';
  return {
    id: `prod-${String(i + 1).padStart(2, '0')}`,
    title: p.title,
    slug,
    price: p.price,
    currency: 'IRR',
    image: { src: svgPlaceholder(p.title, first), alt: p.title },
    gallery: [{ src: svgPlaceholder(p.title, '#E9E4DB'), alt: p.title }],
    brand: p.brand,
    url: `/products/${slug}`,
    badge: p.badge,
    inStock: i !== 5,
  };
});

const HERO_SLIDES = [
  {
    type: 'content',
    eyebrow: 'مجموعه ویژه',
    title: 'محصولات منتخب برای امروز',
    subtitle: 'مجموعه‌ای منتخب با تجربه خرید تمیز و سریع.',
    primaryText: 'مشاهده فروشگاه',
    primaryUrl: '/shop',
    secondaryText: 'پرفروش‌ها',
    secondaryUrl: '#products-bestsellers',
    image: { src: svgPlaceholder('مجموعه ویژه', '#EFECE6') },
  },
  {
    type: 'image',
    url: '/shop',
    image: { src: svgPlaceholder('استایل پاییزی', '#E9E4DB') },
    imageMobile: { src: svgPlaceholder('استایل پاییزی', '#E9E4DB') },
  },
  {
    type: 'image',
    url: '/shop',
    image: { src: svgPlaceholder('کالای لوکس', '#EFECE6') },
    imageMobile: { src: svgPlaceholder('کالای لوکس', '#EFECE6') },
  },
];

const SITE = {
  availableLocales: ['fa', 'en'],
  defaultLocale: 'fa',
  store: {
    currency: 'IRR',
    currencyLocale: 'fa-IR',
    name: 'فروشگاه آنلاین',
    description: 'انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.',
    logo: null,
    topbar: ['ضمانت اصالت کالا', 'ارسال سریع', 'پرداخت امن', 'پشتیبانی قبل از خرید'],
    nav: [
      { label: 'خانه', href: '/' },
      { label: 'فروشگاه', href: '/shop' },
      { label: 'تماس با ما', href: '/contact-us' },
    ],
    footer: {
      title: 'فروشگاه آنلاین',
      description: 'انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.',
      links: [
        { label: 'درباره ما', href: '/about-us' },
        { label: 'تماس با ما', href: '/contact-us' },
        { label: 'راهنمای خرید', href: '/buying-guide' },
        { label: 'پیگیری سفارش', href: '/order-tracking' },
      ],
      guarantee: 'ضمانت بازگشت کالا تا ۷ روز پس از تحویل سفارش',
      copyright: '© ۲۰۲۶ فروشگاه آنلاین. کلیه حقوق محفوظ است.',
      badges: [],
    },
  },
  theme: {
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
    layout: { contentWidth: 1200, globalRadius: 24 },
  },
  blocks: [
    { id: 'hero', type: 'hero', autoplayInterval: 5000, autoplayPauseHover: true, slides: HERO_SLIDES },
    {
      id: 'brands',
      type: 'brandGrid',
      title: 'برندها',
      subtitle: 'انتخاب سریع بر اساس برندهای پربازدید فروشگاه',
      items: [
        { title: 'چرم برند', href: '/brand/چرم-برند', count: 12, image: { src: svgPlaceholder('چرم برند') } },
        { title: 'اسپرت', href: '/brand/اسپرت', count: 18, image: { src: svgPlaceholder('اسپرت') } },
        { title: 'پوشاک', href: '/brand/پوشاک', count: 24, image: { src: svgPlaceholder('پوشاک') } },
        { title: 'زیورآلات', href: '/brand/زیورآلات', count: 9, image: { src: svgPlaceholder('زیورآلات') } },
      ],
    },
    {
      id: 'recommended',
      type: 'productGrid',
      title: 'پیشنهاد ما',
      subtitle: 'محصولاتی که برای شروع انتخاب، ارزش دیدن دارند.',
      limit: 10,
      collection: 'recommended',
      viewAllUrl: '/shop',
    },
    {
      id: 'filters',
      type: 'filterBoxes',
      title: 'خرید بر اساس نیاز',
      subtitle: 'دسته‌بندی سریع بر اساس سبک و کاربرد',
      columns: 3,
      items: [
        { title: 'کلاسیک', subtitle: 'ساده، ماندگار', href: '/shop?style=classic', image: { src: svgPlaceholder('کلاسیک') }, variant: 'light' },
        { title: 'اسپرت', subtitle: 'روزمره و فعال', href: '/shop?style=sport', image: { src: svgPlaceholder('اسپرت') }, variant: 'light' },
        { title: 'رسمی', subtitle: 'کاری و مهمانی', href: '/shop?style=formal', image: { src: svgPlaceholder('رسمی') }, variant: 'minimal' },
      ],
    },
    {
      id: 'bestsellers',
      type: 'productGrid',
      title: 'پرفروش‌ها',
      subtitle: 'محصولاتی که بیشتر انتخاب شده‌اند.',
      limit: 10,
      collection: 'bestsellers',
      viewAllUrl: '/shop',
    },
    {
      id: 'styles',
      type: 'styleGrid',
      title: 'سبک‌های محبوب',
      subtitle: 'بر اساس زبان طراحی و موقعیت استفاده انتخاب کنید.',
      items: [
        { title: 'کلاسیک', subtitle: 'ساده، ماندگار و همیشه قابل استفاده', href: '/shop?style=classic', image: { src: svgPlaceholder('کلاسیک') } },
        { title: 'اسپرت', subtitle: 'برای استفاده روزمره و فعال', href: '/shop?style=sport', image: { src: svgPlaceholder('اسپرت') } },
        { title: 'رسمی', subtitle: 'هماهنگ با استایل کاری و مهمانی', href: '/shop?style=formal', image: { src: svgPlaceholder('رسمی') } },
      ],
    },
    {
      id: 'trust',
      type: 'trust',
      items: [
        { title: 'ضمانت اصالت کالا', text: 'انتخاب مطمئن از محصولات معتبر' },
        { title: 'ارسال سریع', text: 'پردازش و ارسال منظم سفارش‌ها' },
        { title: 'پرداخت امن', text: 'خرید امن با تجربه ساده' },
        { title: 'پشتیبانی خرید', text: 'راهنمایی پیش از انتخاب نهایی' },
      ],
    },
  ],
};

/* ------------------------------------------------------------------ *
 * HTTP helpers
 * ------------------------------------------------------------------ */

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.woff2': 'font/woff2',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
};

function sendJson(res, code, body) {
  const buf = Buffer.from(JSON.stringify(body));
  res.writeHead(code, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': buf.length,
    'Cache-Control': 'no-store',
  });
  res.end(buf);
}

function sendHtml(res, code, body) {
  const buf = Buffer.from(body);
  res.writeHead(code, {
    'Content-Type': 'text/html; charset=utf-8',
    'Content-Length': buf.length,
  });
  res.end(buf);
}

/* ------------------------------------------------------------------ *
 * API handlers
 * ------------------------------------------------------------------ */

function handleSite(req, res) {
  // Host header routing is honored (browsers can't set it, but the Node
  // client and this server can). Default to acme.ir when absent.
  sendJson(res, 200, SITE);
}

function handleProducts(req, res, url) {
  const params = url.searchParams;
  const collection = params.get('collection') || '';
  const limit = Math.max(1, Math.min(Number(params.get('limit') || 10), 24)) || 10;
  const locale = params.get('locale') || 'fa';

  let list = PRODUCTS;
  if (collection === 'bestsellers') {
    // a deterministic "most popular" subset
    list = [...PRODUCTS].sort((a, b) => b.price - a.price);
  } else if (collection === 'recommended') {
    list = PRODUCTS.filter((p) => p.badge);
  }

  const products = list.slice(0, limit).map((p) => ({ ...p }));
  sendJson(res, 200, { products, total: PRODUCTS.length, locale, collection });
}

function handleCheckout(req, res, body) {
  let parsed;
  try {
    parsed = JSON.parse(body || '{}');
  } catch {
    parsed = {};
  }

  // Honeypot: a filled "company" field means a bot.
  if (parsed.company) {
    return sendJson(res, 400, { error: 'invalid_request', message: 'درخواست نامعتبر است.' });
  }

  if (!parsed.product) {
    return sendJson(res, 400, { error: 'product_required', message: 'محصول انتخاب نشده است.' });
  }
  if (!parsed.phone || !parsed.name) {
    return sendJson(res, 400, { error: 'fields_required', message: 'لطفاً نام و شماره تماس را وارد کنید.' });
  }

  const redirectUrl = `/checkout/success?order=${encodeURIComponent(parsed.product)}&qty=${encodeURIComponent(parsed.quantity || 1)}`;
  // 302 redirect (fetch follows it; the client reads res.url and navigates).
  res.writeHead(302, { Location: redirectUrl, 'Content-Type': 'text/plain; charset=utf-8' });
  res.end('Redirecting to ' + redirectUrl);
}

/* ------------------------------------------------------------------ *
 * Static file server
 * ------------------------------------------------------------------ */

function safePath(filePath) {
  const resolved = path.normalize(path.join(ROOT, filePath));
  return resolved.startsWith(ROOT) ? resolved : null;
}

async function serveStatic(req, res, url) {
  let filePath = decodeURIComponent(url.pathname);
  if (filePath === '/') filePath = '/index.html';
  const resolved = safePath(filePath);
  if (!resolved) return sendHtml(res, 403, 'Forbidden');

  // Directory fallback -> index.html
  let stat = null;
  try {
    stat = await fs.stat(resolved);
  } catch {
    stat = null;
  }
  if (stat && stat.isDirectory()) {
    try {
      const index = path.join(resolved, 'index.html');
      const data = await fs.readFile(index);
      return sendRaw(res, 200, data, MIME['.html']);
    } catch {
      return sendHtml(res, 404, 'Not Found');
    }
  }

  try {
    const data = await fs.readFile(resolved);
    return sendRaw(res, 200, data, MIME[path.extname(resolved)] || 'application/octet-stream');
  } catch {
    return sendHtml(res, 404, 'Not Found');
  }
}

function sendRaw(res, code, buf, type) {
  res.writeHead(code, { 'Content-Type': type || 'application/octet-stream', 'Content-Length': buf.length });
  res.end(buf);
}

const successHtml = `<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>سفارش ثبت شد</title>
<style>body{font-family:'Vazirmatn',system-ui,sans-serif;background:#F6F5F2;color:#1F2937;display:grid;place-items:center;min-height:100vh;margin:0}
.card{background:#fff;border:1px solid #E5E0D8;border-radius:18px;max-width:520px;width:90%;padding:40px;text-align:center}
h1{font-size:26px;margin:0 0 12px}.ok{font-size:40px}.meta{color:#6B7280;font-size:14px}
a.button{display:inline-block;margin-top:20px;background:#111827;color:#fff;border-radius:10px;padding:12px 22px;text-decoration:none;font-weight:800}</style></head>
<body><div class="card"><div class="ok">✓</div><h1>سفارش شما ثبت شد</h1><p class="meta">جزئیات سفارش ارسال شد. از خرید شما سپاسگزاریم.</p><a class="button" href="/">بازگشت به فروشگاه</a></div></body></html>`;

/* ------------------------------------------------------------------ *
 * Router
 * ------------------------------------------------------------------ */

function requestBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (chunk) => { data += chunk; });
    req.on('end', () => resolve(data));
    req.on('error', () => resolve(''));
  });
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'acme.ir'}`);
  try {
    if (url.pathname === '/api/site' && req.method === 'GET') {
      return handleSite(req, res);
    }
    if (url.pathname === '/api/products' && req.method === 'GET') {
      return handleProducts(req, res, url);
    }
    if (url.pathname === '/api/checkout' && req.method === 'POST') {
      const body = await requestBody(req);
      return handleCheckout(req, res, body);
    }
    if (url.pathname.startsWith('/checkout/success')) {
      return sendHtml(res, 200, successHtml);
    }
    if (url.pathname.startsWith('/api/')) {
      return sendJson(res, 404, { error: 'not_found', message: 'Endpoint not found' });
    }
    return serveStatic(req, res, url);
  } catch (err) {
    sendHtml(res, 500, 'Internal Server Error');
  }
});

server.listen(PORT, HOST, () => {
  console.log(`Eshobe Payload theme running at http://${HOST}:${PORT}/`);
  console.log(`Mock API: GET /api/site, GET /api/products, POST /api/checkout (Host: acme.ir)`);
});
