import { SilaoApi } from '../core/api.js';
import { SilaoTable } from '../components/table.js';

export async function renderResourcesView(root) {
    root.innerHTML = `
        <h2>Ressources Silao</h2>
        <div class="silao-card">
            <div id="resources-table-container"></div>
        </div>
    `;

    const table = new SilaoTable(document.getElementById('resources-table-container'), [
        { title: 'ID', key: 'resource_id' },
        { title: 'Nom', key: 'name' },
        { title: 'Capacité', key: 'capacity' },
        { title: 'Statut', render: r => `<span class="silao-badge silao-badge-${r.status}">${r.status}</span>` },
        { title: 'Horaires', render: r => String(r.schedules ? r.schedules.length : 0) }
    ]);

    table.setState('loading');

    try {
        const resources = await SilaoApi.get('/resources');
        table.setState(resources.length > 0 ? 'loaded' : 'empty', resources);
    } catch (e) {
        table.setState('error', [], e.message);
    }
}