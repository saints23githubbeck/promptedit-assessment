import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
import '../css/app.css';

createInertiaApp({
    title: title => `${title} · PromptEdit Studio`, resolve: name => {
        const pages = import.meta.glob('./pages/**/*.vue', {eager: true});
        return pages[`./pages/${name}.vue`];
    }, setup({el, App, props, plugin}) {
        createApp({render: () => h(App, props)}).use(plugin).mount(el);
    }, progress: {color: '#6d5dfc'}
});
