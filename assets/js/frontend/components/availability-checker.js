export class AvailabilityChecker {
    constructor(container) {
        this.container = container;
    }

    render(status = null, message = '') {
        this.container.innerHTML = '';
        if (!status) return;

        const badge = document.createElement('div');
        badge.className = `silao-badge silao-badge-${status}`;
        badge.setAttribute('role', 'status');
        badge.setAttribute('aria-live', 'polite');
        badge.textContent = message || (status === 'available' ? 'Créneau disponible' : 'Indisponible');

        this.container.appendChild(badge);
    }
}