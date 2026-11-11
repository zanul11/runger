/*
 * Service worker kiosk Cek BIB — dirender GtrBibCheckController::sw().
 * Halaman + data: jaringan dulu (selalu terbaru saat online), cadangan dari cache saat offline.
 * Font + logo: cache dulu (tidak pernah berubah tanpa ganti versi).
 * Versi berubah tiap deploy (mtime view/aset) → cache lama dihapus saat activate.
 */
const VERSION = @json($version);
const CACHE = 'gtr-bibcheck-' + VERSION;
const PAGE = @json($page);
const DATA = @json($data);
const PRECACHE = @json($precache);
@verbatim

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE);
    // Satu-satu: satu file gagal tidak menggagalkan semuanya.
    await Promise.all([...PRECACHE, DATA].map((url) => cache.add(new Request(url, { cache: 'reload' })).catch(() => {})));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    for (const key of await caches.keys()) {
      if (key.startsWith('gtr-bibcheck-') && key !== CACHE) await caches.delete(key);
    }
    await self.clients.claim();
  })());
});

const strip = (url) => url.split('#')[0].split('?')[0];

async function networkFirst(request, cacheKey) {
  const cache = await caches.open(CACHE);
  try {
    const res = await fetch(request);
    if (res.ok) cache.put(cacheKey, res.clone());
    return res;
  } catch (e) {
    const hit = await cache.match(cacheKey);
    if (!hit) throw e;
    // Tandai jawaban dari cache supaya halaman bisa menampilkan status "Offline".
    const headers = new Headers(hit.headers);
    headers.set('X-Gtr-Offline', '1');
    return new Response(hit.body, { status: hit.status, statusText: hit.statusText, headers });
  }
}

async function cacheFirst(request) {
  const cache = await caches.open(CACHE);
  const hit = await cache.match(request, { ignoreSearch: true });
  if (hit) return hit;
  const res = await fetch(request);
  if (res.ok) cache.put(request, res.clone());
  return res;
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = strip(req.url);

  // Halaman kiosk (juga ?bib=…) → selalu disimpan di bawah URL polos.
  if (req.mode === 'navigate' && url === PAGE) {
    event.respondWith(networkFirst(req, PAGE));
  } else if (url === DATA) {
    event.respondWith(networkFirst(req, DATA));
  } else if (PRECACHE.includes(url)) {
    event.respondWith(cacheFirst(req));
  }
  // Selain itu: biarkan browser (tidak disentuh SW).
});
@endverbatim
