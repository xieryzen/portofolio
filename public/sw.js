/* Portfolio CMS — service worker (app shell + halaman offline) */
const VERSION = 'v1';
const CACHE = `portfolio-${VERSION}`;

const SHELL = [
    './',
    './offline',
    './assets/css/app.css',
    './assets/js/app.js',
    './assets/js/theme.js',
    './icons/icon-192.png',
    './icons/icon-512.png',
    './manifest.webmanifest',
].map((path) => new URL(path, self.location).href);

const OFFLINE_URL = new URL('./offline', self.location).href;

/* Path yang tidak boleh disentuh service worker (area privat / unduhan). */
const PRIVATE = /\/(admin|login|register|logout|download|media|profil|dashboard)(\/|$)/;

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE);
        await Promise.allSettled(SHELL.map((url) => cache.add(new Request(url, { cache: 'reload' }))));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(
            keys
                .filter((key) => key !== CACHE && key.startsWith('portfolio-'))
                .map((key) => caches.delete(key))
        );
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (PRIVATE.test(url.pathname)) return;

    // Halaman: ambil versi terbaru dulu, kalau gagal pakai cache / halaman offline.
    if (request.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    const cache = await caches.open(CACHE);
                    cache.put(request, response.clone());
                }
                return response;
            } catch (error) {
                const cache = await caches.open(CACHE);
                const cached = await cache.match(request);
                if (cached) return cached;
                const offline = await cache.match(OFFLINE_URL);
                return offline || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
            }
        })());
        return;
    }

    // Aset statis: tampilkan dari cache, perbarui di belakang layar.
    if (['style', 'script', 'font', 'image'].includes(request.destination)) {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE);
            const cached = await cache.match(request);

            const network = fetch(request)
                .then((response) => {
                    if (response.ok) cache.put(request, response.clone());
                    return response;
                })
                .catch(() => null);

            return cached || (await network) || new Response('', { status: 504 });
        })());
    }
});
