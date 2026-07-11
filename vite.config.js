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
            registerType: 'autoUpdate',
            
            // CORRECCIÓN 1: El soldado debe vigilar desde la entrada principal
            outDir: 'public',
            buildBase: '/build/',
            injectRegister: null,

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
                        src:      '/pwa-192x192.png',
                        sizes:    '192x192',
                        type:     'image/png',
                        purpose:  'any',
                    },
                    {
                        src:      '/pwa-512x512.png',
                        sizes:    '512x512',
                        type:     'image/png',
                        purpose:  'any maskable',
                    },
                ],
            },

            workbox: {
                globPatterns: ['**/*.{js,css,woff,woff2}'],
                // Ajustamos la ruta para que busque dentro del build de Laravel
                globDirectory: 'public/build',
                cleanupOutdatedCaches: true,

                runtimeCaching: [
                    // CORRECCIÓN 2: El Escudo Anti-Pantalla Blanca (NetworkFirst para HTML)
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'html-cache',
                            networkTimeoutSeconds: 3, // Si Render tarda más de 3s, carga la interfaz offline
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 7, // 1 semana
                            },
                        },
                    },
                    {
                        urlPattern: /^https?:\/\/.*\/build\/.*/i,
                        handler:    'CacheFirst',
                        options: {
                            cacheName:         'build-assets',
                            expiration: {
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                    {
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
                        urlPattern: /^https:\/\/api\.bpd\.com\.do\/.*/i,
                        handler:    'StaleWhileRevalidate',
                        options: {
                            cacheName:         'api-bpd',
                            expiration: {
                                maxEntries:    5,
                                maxAgeSeconds: 60 * 60 * 12,
                            },
                        },
                    },
                ],
            },

            devOptions: {
                enabled: false,
            },
        }),
    ],
});