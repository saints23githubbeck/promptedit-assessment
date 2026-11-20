// resources/js/app.js
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { url } from './utils/url';

createInertiaApp({
    title: (title) => (title ? `${title} — PromptEdit Studio` : 'PromptEdit Studio'),

    resolve: (name) => {
        // Lowercase `pages` — matches resources/js/pages/
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        const path = `./pages/${name}.vue`;

        if (!pages[path]) {
            console.error('[Inertia] Page not found', {
                requested: name,
                lookedFor: path,
                available: Object.keys(pages),
            });
            throw new Error(
                `Inertia page not found: "${name}". ` +
                `Expected file at "resources/js/pages/${name}.vue". ` +
                `Available: ${Object.keys(pages).join(', ') || '(none)'}`
            );
        }

        return pages[path];
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