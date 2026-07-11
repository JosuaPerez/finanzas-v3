import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import VueApexCharts from 'vue3-apexcharts';
import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        // El color de la barra (Azul brillante)
        color: '#2563eb',
        // Mostrar la barra instantáneamente (0 milisegundos de retraso)
        delay: 0,
    },
});

// ── PWA Service Worker registration ───────────────────────────────────────────
// Only active in production builds (devOptions.enabled: false in vite.config.js).
// registerType: 'autoUpdate' — no user prompt, the SW refreshes silently.
if (import.meta.env.PROD) {
    import('virtual:pwa-register').then(({ registerSW }) => {
        registerSW({ immediate: true });
    });
}
