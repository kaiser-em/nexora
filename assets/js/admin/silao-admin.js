import { renderDashboardView } from './views/dashboard-view.js';
import { renderModelsListView } from './views/models-list-view.js';
import { renderBookingsListView } from './views/bookings-list-view.js';
import { renderResourcesView } from './views/resources-view.js';
import { renderCustomersView } from './views/customers-view.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('silao-admin-app');
    if (!root) return;

    const view = root.getAttribute('data-view');
    switch (view) {
        case 'dashboard':
            renderDashboardView(root);
            break;
        case 'booking-models':
            renderModelsListView(root);
            break;
        case 'bookings':
            renderBookingsListView(root);
            break;
        case 'resources':
            renderResourcesView(root);
            break;
        case 'customers':
            renderCustomersView(root);
            break;
    }
});