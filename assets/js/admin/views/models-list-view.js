import { SilaoApi } from '../core/api.js';
import { SilaoTable } from '../components/table.js';

export async function renderModelsListView(root) {
    root.innerHTML = `
        <h2>Modèles de Réservation Silao</h2>
        <div class="silao-card">
            <div id="models-table-container"></div>
        </div>
    `;

    const table = new SilaoTable(document.getElementById('models-table-container'), [
        { title: 'ID', key: 'model_id' },
        { title: 'Slug', key: 'slug' },
        { title: 'Nom', key: 'name' },
        { title: 'Prix de Base', render: r => r.base_price ? r.base_price.formatted : '-' },
        { title: 'Champs', render: r => String(r.fields ? r.fields.length : 0) },
        { title: 'Options', render: r => String(r.options ? r.options.length : 0) }
    ]);

    table.setState('loading');

    try {
        const models = await SilaoApi.get('/booking-models');
        table.setState(models.length > 0 ? 'loaded' : 'empty', models);
    } catch (e) {
        table.setState('error', [], e.message);
    }
}