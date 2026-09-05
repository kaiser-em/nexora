export const SilaoPublicApi = {
    async request(endpoint, options = {}) {
        const config = window.silaoFrontendConfig || {};
        const url = `${config.restUrl || '/wp-json/silao/v1'}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        };

        const response = await fetch(url, { ...options, headers });
        const json = await response.json();

        if (!response.ok) {
            const error = new Error(json.message || 'Erreur API');
            error.code = json.code;
            error.status = response.status;
            throw error;
        }

        return json.data;
    },

    getModel(id) { return this.request(`/booking-models/${encodeURIComponent(id)}`, { method: 'GET' }); },
    calculateQuote(payload, signal) { return this.request('/quotes', { method: 'POST', body: JSON.stringify(payload), signal }); },
    checkAvailability(payload, signal) { return this.request('/availability/check', { method: 'POST', body: JSON.stringify(payload), signal }); },
    submitBooking(payload) { return this.request('/bookings', { method: 'POST', body: JSON.stringify(payload) }); }
};