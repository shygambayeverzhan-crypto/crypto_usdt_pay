import '../css/app.css';

/// <reference types="vite/client" />
/// <reference path="./types/globals.d.ts" />

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import axios from 'axios';
// Для корректной работы CSRF в axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const appName = (import.meta as any).env?.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = (import.meta as any).glob('./pages/**/*.vue');
        return resolvePageComponent(`./pages/${name}.vue`, pages);
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
