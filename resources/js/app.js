import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// import.meta.env.VITE_APP_NAME butuh .env tersedia saat `npm run build` —
// stage node-builder di Dockerfile produksi tidak meneruskan itu (env
// backend Render cuma di-inject saat container jalan, bukan saat build
// image), jadi selalu fallback ke default Vite dan judul tab jadi "Laravel".
// App ini single-tenant, namanya tidak perlu dikonfigurasi per-environment.
const appName = 'WealthID';

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
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
