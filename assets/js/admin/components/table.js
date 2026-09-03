export class SilaoTable {
    constructor(container, columns = [], options = {}) {
        this.container = container;
        this.columns = columns;
        this.options = options;
        this.state = 'loading'; // loading | empty | loaded | error
        this.data = [];
        this.errorMessage = '';
    }

    setState(state, data = [], errorMessage = '') {
        this.state = state;
        this.data = data;
        this.errorMessage = errorMessage;
        this.render();
    }

    render() {
        if (this.state === 'loading') {
            this.container.innerHTML = '<div class="silao-table-state-loading">Chargement des données...</div>';
            return;
        }

        if (this.state === 'error') {
            this.container.innerHTML = `<div class="silao-table-state-error">Erreur: ${this.escapeHtml(this.errorMessage)}</div>`;
            return;
        }

        if (this.state === 'empty' || this.data.length === 0) {
            this.container.innerHTML = '<div class="silao-table-state-empty">Aucun élément trouvé.</div>';
            return;
        }

        let html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        this.columns.forEach(col => { html += `<th>${this.escapeHtml(col.title)}</th>`; });
        html += '</tr></thead><tbody>';

        this.data.forEach(row => {
            html += '<tr>';
            this.columns.forEach(col => {
                const val = typeof col.render === 'function' ? col.render(row) : this.escapeHtml(String(row[col.key] ?? ''));
                html += `<td>${val}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        this.container.innerHTML = html;
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}