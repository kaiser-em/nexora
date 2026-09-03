import { SilaoApi } from '../core/api.js';
import { SilaoTable } from '../components/table.js';

export async function renderDashboardView(root) {
    const config = window.silaoAdminConfig || {};
    const sys = config.system || {};

    root.innerHTML = `
        <h2>Tableau de bord Silao</h2>
        <div class="silao-kpi-grid">
            <div class="silao-kpi-card">
                <div>Modèles Publiés</div>
                <div class="silao-kpi-val" id="kpi-models">-</div>
            </div>
            <div class="silao-kpi-card">
                <div>Ressources Actives</div>
                <div class="silao-kpi-val" id="kpi-resources">-</div>
            </div>
            <div class="silao-kpi-card">
                <div>Version Système</div>
                <div class="silao-kpi-val" style="font-size:16px;">Silao v${sys.pluginVersion || '0.1.0'} (DB v${sys.dbVersion || '1.0.0'})</div>
            </div>
        </div>
        <div class="silao-card">
            <h3>Dernières Réservations</h3>
            <div id="dashboard-recent-bookings"></div>
        </div>
    `;

    const table = new SilaoTable(document.getElementById('dashboard-recent-bookings'), [
        { title: 'Référence', key: 'reference' },
        { title: 'Statut', render: r => `<span class="silao-badge silao-badge-${r.status}">${r.status}</span>` },
        { title: 'Début (UTC)', key: 'starts_at_utc' },
        { title: 'Fin (UTC)', key: 'ends_at_utc' }
    ]);

    table.setState('loading');

    try {
        const [models, resources, bookings] = await Promise.all([
            SilaoApi.get('/booking-models'),
            SilaoApi.get('/resources'),
            SilaoApi.get('/bookings')
        ]);

        document.getElementById('kpi-models').textContent = String(models.length);
        document.getElementById('kpi-resources').textContent = String(resources.length);
        table.setState(bookings.length > 0 ? 'loaded' : 'empty', bookings.slice(0, 5));
    } catch (e) {
        table.setState('error', [], e.message);
    }
}