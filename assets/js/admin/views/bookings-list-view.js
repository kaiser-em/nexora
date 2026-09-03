import { SilaoApi } from '../core/api.js';
import { SilaoTable } from '../components/table.js';

export async function renderBookingsListView(root) {
    root.innerHTML = `
        <h2>Réservations Silao</h2>
        <div class="silao-card">
            <div id="bookings-table-container"></div>
        </div>
    `;

    const table = new SilaoTable(document.getElementById('bookings-table-container'), [
        { title: 'ID', key: 'booking_id' },
        { title: 'Référence', key: 'reference' },
        { title: 'Statut', render: r => `<span class="silao-badge silao-badge-${r.status}">${r.status}</span>` },
        { title: 'Début (UTC)', key: 'starts_at_utc' },
        { title: 'Fin (UTC)', key: 'ends_at_utc' }
    ]);

    table.setState('loading');

    try {
        const bookings = await SilaoApi.get('/bookings');
        table.setState(bookings.length > 0 ? 'loaded' : 'empty', bookings);
    } catch (e) {
        table.setState('error', [], e.message);
    }
}