import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // NativePHP serves bundled assets and manages its own updates.
        if (import.meta.env.PROD && !props.initialPage.props.nativeRuntime) {
            // Remove pages stored by the previous navigation caching policy.
            window.caches?.delete('html-cache').catch(() => {});
            import('virtual:pwa-register').then(({ registerSW }) => {
                registerSW({ immediate: true });
            });
        }

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        // El color de la barra (Azul brillante)
        color: '#2563eb',
        // Mostrar la barra instantáneamente (0 milisegundos de retraso)
        delay: 0,
    },
});
