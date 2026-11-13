import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scanner/main.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        vue(),

        // PWA khusus scanner (scope /scanner/). Service worker dibangun ke public/build/scanner-sw.js
        // lalu dilayani Laravel di /scanner/sw.js (lihat routes/web.php) supaya scope-nya /scanner/.
        VitePWA({
            injectRegister: false, // didaftarkan manual di resources/js/scanner/main.js
            registerType: 'autoUpdate',
            filename: 'scanner-sw.js',
            manifestFilename: 'scanner.webmanifest',
            scope: '/scanner/',
            includeManifestIcons: false,
            manifest: {
                id: '/scanner/app',
                name: 'GTR Scanner — Timing',
                short_name: 'GTR Scanner',
                description: 'Scanner timing marshal Gerung Trail Run 2026',
                lang: 'id',
                start_url: '/scanner/app',
                scope: '/scanner/',
                display: 'standalone',
                orientation: 'portrait',
                display_override: ['standalone', 'minimal-ui'],
                background_color: '#e9eae5',
                theme_color: '#e9eae5',
                categories: ['sports', 'utilities'],
                prefer_related_applications: false,
                // Entri "any" dan "maskable" dipisah (syarat pemasangan Chrome + ikon adaptif Android).
                icons: [
                    { src: '/pwa/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/pwa/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: '/pwa/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'maskable' },
                    { src: '/pwa/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                // Hanya aset scanner (bukan CSS/font situs utama).
                globPatterns: ['assets/**/*.{js,css,woff2,svg,png}'],
                globIgnores: ['assets/app-*', 'assets/fonts-*', 'assets/instrument-sans-*'],
                // Halaman shell scanner (Blade) ikut di-precache → bisa dibuka setelah HP restart tanpa sinyal.
                additionalManifestEntries: [{ url: '/scanner/app', revision: String(Date.now()) }],
                // URL aset relatif terhadap public/build → jadikan absolut /build/… (SW dilayani di /scanner/).
                manifestTransforms: [
                    async (entries) => ({
                        manifest: entries.map((e) => ({ ...e, url: e.url.startsWith('/') ? e.url : `/build/${e.url}` })),
                        warnings: [],
                    }),
                ],
                navigateFallback: '/scanner/app',
                navigateFallbackAllowlist: [/^\/scanner\//],
                navigateFallbackDenylist: [/^\/scanner\/sw\.js/],
                inlineWorkboxRuntime: true, // tanpa file workbox-*.js terpisah (path relatif /scanner/)
                cleanupOutdatedCaches: true,
                clientsClaim: true,
                skipWaiting: true,
                // API timing TIDAK pernah di-cache SW; data offline dikelola Dexie.
                runtimeCaching: [],
            },
            devOptions: { enabled: false },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
