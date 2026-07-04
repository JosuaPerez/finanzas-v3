import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            // autoUpdate: silently installs new SW in background, activates on next
            // page load. No "update available" prompt needed for a finance app.
            registerType: 'autoUpdate',

            // Vite writes the SW into /build/ alongside the other assets.
            // Laravel's Vite plugin sets the correct <script> src from there.
            outDir: 'public/build',

            // The SW must be importable from the root, not from /build/.
            // injectRegister: null because we wire registration manually in app.js
            // so we can silence console errors in development.
            injectRegister: null,

            // Manifest is served at /manifest.webmanifest (auto-linked in <head>
            // by vite-plugin-pwa via the Vite virtual:pwa-info module).
            manifest: {
                name:             'FinanzasRPG',
                short_name:       'F-RPG',
                description:      'Tu Centro de Mando Financiero. Vence tus deudas, alcanza tus metas.',
                display:          'standalone',
                orientation:      'portrait',
                theme_color:      '#0f172a',
                background_color: '#0f172a',
                start_url:        '/',
                scope:            '/',
                lang:             'es',
                icons: [
                    {
                        src:   '/pwa-192x192.png',
                        sizes: '192x192',
                        type:  'image/png',
                        purpose: 'any',
                    },
                    {
                        src:   '/pwa-512x512.png',
                        sizes: '512x512',
                        type:  'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },

            workbox: {
                // ── Precache ───────────────────────────────────────────────────
                // Only precache the built assets (JS, CSS). HTML is intentionally
                // excluded — Inertia pages are server-rendered and must be fresh.
                globPatterns: ['**/*.{js,css,woff,woff2}'],
                globDirectory: 'public/build',

                // Remove stale precache entries when a new SW activates.
                cleanupOutdatedCaches: true,

                // ── Runtime caching rules ──────────────────────────────────────
                runtimeCaching: [
                    {
                        // CacheFirst for Vite-fingerprinted build assets.
                        // Safe: filenames change on every build, so stale is impossible.
                        urlPattern: /^https?:\/\/.*\/build\/.*/i,
                        handler:    'CacheFirst',
                        options: {
                            cacheName:         'build-assets',
                            expiration: {
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
                            },
                        },
                    },
                    {
                        // StaleWhileRevalidate for Google Fonts (used by Figtree).
                        // Returns cached font instantly, fetches update in background.
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com\/.*/i,
                        handler:    'StaleWhileRevalidate',
                        options: {
                            cacheName:         'google-fonts',
                            expiration: {
                                maxEntries:    20,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                    {
                        // StaleWhileRevalidate for the BPD exchange-rate API
                        // so /deudas loads instantly even when offline.
                        urlPattern: /^https:\/\/api\.bpd\.com\.do\/.*/i,
                        handler:    'StaleWhileRevalidate',
                        options: {
                            cacheName:         'api-bpd',
                            expiration: {
                                maxEntries:    5,
                                maxAgeSeconds: 60 * 60 * 12, // matches BpdExchangeRateService TTL
                            },
                        },
                    },
                ],
            },

            // Dev options: keep the SW disabled in `vite dev` to avoid cache
            // interference when iterating on UI. Enable only in production builds.
            devOptions: {
                enabled: false,
            },
        }),
    ],
});