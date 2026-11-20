
const BASE = import.meta.env.BASE_URL.replace(/\/+$/, '');

export function url(path = '/') {
    const clean = String(path).replace(/^\/+/, '');
    return clean ? `${BASE}/${clean}` : `${BASE}/`;
}
