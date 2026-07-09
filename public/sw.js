// v2 — SW lama (wealthid-v1) meng-cache SEMUA halaman GET dan menyajikan
// dashboard basi saat server tidak terjangkau (tidur/cold start/deploy).
// Akibatnya: halaman tampak hidup dengan bundle JS lama, sementara request
// /api/ yang tidak di-cache gagal — error "Gagal memuat data" yang menyesatkan
// di produksi. Sekarang hanya aset statis ber-hash yang di-cache (cache-first,
// aman karena nama file berubah tiap build); halaman & API selalu ke network,
// jadi kalau server down, error-nya tampak apa adanya, bukan disamarkan.
const CACHE = 'wealthid-v2'

self.addEventListener('install', () => {
    self.skipWaiting()
})

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    )
})

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return

    const url = new URL(e.request.url)
    const isStatic = url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.json'

    if (!isStatic) return

    e.respondWith(
        caches.match(e.request).then(hit => hit || fetch(e.request).then(res => {
            if (res.ok) {
                const clone = res.clone()
                caches.open(CACHE).then(cache => cache.put(e.request, clone))
            }
            return res
        }))
    )
})
