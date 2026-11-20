// resources/js/app.js
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { url } from './utils/url';

createInertiaApp({
    title: (title) => title ? `${title} — PromptEdit Studio` : 'PromptEdit Studio',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.config.globalProperties.url = url;
        app.mount(el);
    },
    progress: {
        color: '#6366f1',
    },
});