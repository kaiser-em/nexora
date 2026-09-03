export const SilaoApi = {
    async request(endpoint, options = {}) {
        const config = window.silaoAdminConfig || {};
        const url = `${config.restUrl || '/wp-json/silao/v1'}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'X-WP-Nonce': config.nonce || '',
            ...(options.headers || {})
        };

        const response = await fetch(url, { ...options, headers });
        const json = await response.json();

        if (!response.ok) {
            throw new Error(json.message || 'REST API Error');
        }

        return json.data;
    },

    get(endpoint) { return this.request(endpoint, { method: 'GET' }); },
    post(endpoint, body) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(body) }); },
    put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(body) }); },
    patch(endpoint, body) { return this.request(endpoint, { method: 'PATCH', body: JSON.stringify(body) }); }
};