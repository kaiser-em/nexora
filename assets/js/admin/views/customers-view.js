import { SilaoApi } from '../core/api.js';
import { SilaoTable } from '../components/table.js';

export async function renderCustomersView(root) {
    root.innerHTML = `
        <h2>Clients Silao</h2>
        <div class="silao-card">
            <div id="customers-table-container"></div>
        </div>
    `;

    const table = new SilaoTable(document.getElementById('customers-table-container'), [
        { title: 'ID', key: 'customer_id' },
        { title: 'Nom complet', key: 'full_name' },
        { title: 'Email', key: 'email' },
        { title: 'Téléphone', key: 'phone' },
        { title: 'Type', render: r => r.is_guest ? 'Invité' : 'Enregistré' }
    ]);

    table.setState('loading');

    try {
        const customers = await SilaoApi.get('/customers');
        table.setState(customers.length > 0 ? 'loaded' : 'empty', customers);
    } catch (e) {
        table.setState('error', [], e.message);
    }
}