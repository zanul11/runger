/*
 * Service worker Runger App — mode race day / offline.
 * - Halaman app & kategori GTR: network-first, fallback cache, fallback /offline.html.
 * - Aset statis (Leaflet, font, /assets, /pwa, /storage): stale-while-revalidate.
 * - Tile peta (OpenTopoMap, CARTO, Esri): cache-first, dibatasi jumlahnya.
 * Request lain (POST, API, admin) tidak disentuh.
 */
const VERSION = 'v1';
const PAGES = 'runger-pages-' + VERSION;
const STATIC = 'runger-static-' + VERSION;
const TILES = 'runger-tiles-' + VERSION;
const MAX_TILES = 2500;

self.addEventListener('install', e => {
  e.waitUntil(caches.open(STATIC).then(c => c.addAll(['/offline.html', '/pwa/icon-192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k.startsWith('runger-') && ! k.endsWith(VERSION)).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', e => {
  // dipanggil saat logout: hapus halaman pribadi yang tersimpan
  if (e.data === 'clear-pages') e.waitUntil(caches.delete(PAGES));
});

const isTile = url => /tile\.opentopomap\.org|basemaps\.cartocdn\.com|arcgisonline\.com|tile\.openstreetmap\.org/.test(url.hostname);
const isStatic = url =>
  url.hostname === 'unpkg.com' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com' ||
  (url.origin === self.location.origin && /^\/(assets|pwa|storage|gtr|css|js|fonts)\//.test(url.pathname));
const isPage = (req, url) =>
  req.mode === 'navigate' && url.origin === self.location.origin &&
  (/^\/runger-app(\/|$)/.test(url.pathname) || /^\/gerung-trail-run(\/|$)/.test(url.pathname));

async function trimTiles() {
  const c = await caches.open(TILES);
  const keys = await c.keys();
  for (let i = 0; i < keys.length - MAX_TILES; i++) await c.delete(keys[i]);
}

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  if (isPage(req, url)) {
    e.respondWith(
      fetch(req)
        .then(res => {
          if (res.ok && ! res.redirected) { const copy = res.clone(); caches.open(PAGES).then(c => c.put(req, copy)); }
          return res;
        })
        .catch(() => caches.match(req).then(hit => hit || caches.match('/offline.html')))
    );
    return;
  }

  if (isTile(url)) {
    e.respondWith(
      caches.open(TILES).then(c => c.match(req).then(hit => hit || fetch(req).then(res => {
        if (res.ok || res.type === 'opaque') { c.put(req, res.clone()); trimTiles(); }
        return res;
      })))
    );
    return;
  }

  if (isStatic(url)) {
    e.respondWith(
      caches.open(STATIC).then(c => c.match(req).then(hit => {
        const net = fetch(req).then(res => { if (res.ok || res.type === 'opaque') c.put(req, res.clone()); return res; }).catch(() => hit);
        return hit || net;
      }))
    );
  }
});
