/**
 * Unit tests for the live Payload CMS adapter (core/payload-adapter.js).
 *
 * Pure translation only — no DOM, no network — so they run with plain Node:
 *
 *   node --test payload-theme/tests/
 *
 * The fixtures are trimmed copies of real responses from a local
 * `hamidnoshady/eshobe-cms` (`GET /api/site`, `GET /api/pages`,
 * `GET /api/products`), so a contract change on the CMS side shows up here
 * rather than as a blank section in a browser.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import {
  adaptLayout,
  adaptProduct,
  adaptTheme,
  isPayloadDescriptor,
  richTextToHtml,
  mediaSrc,
} from '../core/payload-adapter.js';

const DESCRIPTOR = {
  availableLocales: ['fa'],
  blocks: ['content', 'productGrid', 'features'],
  contractVersion: 1,
  defaultLocale: 'fa',
  domain: 'shop.localhost',
  media: { basePath: '/api/media/file', origin: 'http://shop.localhost:3000' },
  name: 'فروشگاه پارسه',
  store: { currency: 'IRT', paymentProvider: 'bank' },
  theme: { accent: '#b45309', background: '#ffffff', foreground: '#0a0a0a', lineHeight: 1.8, primary: '#166534', radius: 'md' },
  type: 'store',
};

const richText = (children) => ({ root: { type: 'root', direction: 'rtl', children } });
const paragraph = (text) => ({ type: 'paragraph', direction: 'rtl', children: [{ type: 'text', text, format: 0 }] });

test('a live CMS descriptor is told apart from the mock contract', () => {
  assert.equal(isPayloadDescriptor(DESCRIPTOR), true);
  // The mock server answers blocks as *objects* and carries no contractVersion.
  assert.equal(isPayloadDescriptor({ blocks: [{ type: 'hero' }], store: {} }), false);
  assert.equal(isPayloadDescriptor(null), false);
});

test('design tokens map onto the theme’s own token names', () => {
  const theme = adaptTheme(DESCRIPTOR.theme);
  assert.equal(theme.colors.primary, '#166534');
  assert.equal(theme.colors.accent, '#b45309');
  assert.equal(theme.colors.text, '#0a0a0a');
  assert.equal(theme.font.lineHeightBody, 1.8);
  // `radius: 'md'` is a t-shirt size on the CMS; the design system wants pixels.
  assert.equal(theme.layout.globalRadius, 16);
  // The accent's hover pair is derived, not invented from the palette.
  assert.match(theme.colors.accentDark, /^#[0-9a-f]{6}$/);
  assert.notEqual(theme.colors.accentDark, theme.colors.accent);
});

test('a product keeps its price untouched and carries the site currency', () => {
  const product = adaptProduct(
    {
      id: 'c10c1ab7-990d-4f39-a6c8-6eb112c9f8ef',
      title: 'زعفران سرگل',
      slug: 'زعفران-سرگل',
      summary: 'بستهٔ ۴ گرمی.',
      price: 198000,
      compareAtPrice: 260000,
      trackInventory: false,
      image: null,
    },
    DESCRIPTOR,
  );
  // Integer minor units of the *site's* currency, formatted later — never converted.
  assert.equal(product.price, 198000);
  assert.equal(product.currency, 'IRT');
  assert.equal(product.url, '/products/زعفران-سرگل');
  assert.equal(product.inStock, true);
  // compareAtPrice above price is what earns the badge.
  assert.equal(product.badge, 'تخفیف ویژه');
});

test('inventory decides stock only when the row tracks it', () => {
  const base = { id: 'x', title: 'کالا', slug: 'kala', price: 1000 };
  assert.equal(adaptProduct({ ...base, trackInventory: true, inventory: 0 }, DESCRIPTOR).inStock, false);
  assert.equal(adaptProduct({ ...base, trackInventory: true, inventory: 3 }, DESCRIPTOR).inStock, true);
  assert.equal(adaptProduct({ ...base, trackInventory: false, inventory: 0 }, DESCRIPTOR).inStock, true);
});

test('a relative upload URL is resolved against the descriptor’s media origin', () => {
  assert.equal(
    mediaSrc({ url: '/api/media/file/saffron.png' }, DESCRIPTOR),
    'http://shop.localhost:3000/api/media/file/saffron.png',
  );
  assert.equal(mediaSrc({ url: 'https://cdn.example/x.png' }, DESCRIPTOR), 'https://cdn.example/x.png');
  assert.equal(mediaSrc(null, DESCRIPTOR), '');
});

test('lexical rich text renders the subset the CMS actually stores', () => {
  const html = richTextToHtml(
    richText([
      { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'عنوان', format: 0 }] },
      paragraph('متن ساده'),
      { type: 'paragraph', children: [{ type: 'text', text: 'پررنگ', format: 1 }] },
      {
        type: 'link',
        fields: { url: '/products/x', newTab: false },
        children: [{ type: 'text', text: 'پیوند', format: 0 }],
      },
    ]),
  );
  assert.match(html, /<h2>عنوان<\/h2>/);
  assert.match(html, /<p>متن ساده<\/p>/);
  assert.match(html, /<strong>پررنگ<\/strong>/);
  assert.match(html, /<a href="\/products\/x">پیوند<\/a>/);
});

test('rich text escapes markup rather than trusting the field', () => {
  const html = richTextToHtml(richText([paragraph('<img src=x onerror=alert(1)>')]));
  assert.equal(html.includes('<img'), false);
  assert.match(html, /&lt;img/);
});

test('a page layout becomes the theme’s block array, hero first', () => {
  const page = {
    title: 'محصولات',
    hero: { type: 'lowImpact', richText: richText([paragraph('فروشگاه پارسه')]), links: [], media: null },
    layout: [
      { id: 'a', blockType: 'content', columns: [{ richText: richText([paragraph('چیزی که می‌فروشیم.')]) }] },
      { id: 'b', blockType: 'productGrid', populateBy: 'collection', limit: 6, products: [] },
      {
        id: 'c',
        blockType: 'features',
        heading: 'چرا ما',
        items: [{ title: 'ارسال سریع', description: 'همان روز کاری.' }],
      },
    ],
  };
  const blocks = adaptLayout(page, DESCRIPTOR);
  assert.deepEqual(blocks.map((b) => b.type), ['hero', 'richText', 'productGrid', 'trust']);
  assert.match(blocks[1].html, /<p>چیزی که می‌فروشیم\.<\/p>/);
  assert.equal(blocks[1].dir, 'rtl');
  assert.equal(blocks[2].limit, 6);
  assert.equal(blocks[3].items[0].text, 'همان روز کاری.');
});

test('a productGrid block populated by selection carries its ids', () => {
  const blocks = adaptLayout(
    { layout: [{ id: 'b', blockType: 'productGrid', populateBy: 'selection', limit: 4, products: [{ id: 'p1' }, 'p2'] }] },
    DESCRIPTOR,
  );
  assert.deepEqual(blocks[0].products, ['p1', 'p2']);
});

test('a block the theme cannot draw is dropped, never rendered half-way', () => {
  const blocks = adaptLayout({ layout: [{ id: 'x', blockType: 'testimonials', items: [] }] }, DESCRIPTOR);
  assert.deepEqual(blocks, []);
});

test('a page with no hero and no layout yields no blocks', () => {
  assert.deepEqual(adaptLayout({ hero: { type: 'none' }, layout: [] }, DESCRIPTOR), []);
});
