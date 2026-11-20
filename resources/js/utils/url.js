// resources/js/utils/url.js
const BASE = import.meta.env.BASE_URL.replace(/\/+$/, ''); // "/whoami"

export function url(path = '/') {
    const clean = String(path).replace(/^\/+/, '');
    return clean ? `${BASE}/${clean}` : `${BASE}/`;
}