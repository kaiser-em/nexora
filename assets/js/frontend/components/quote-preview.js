export class QuotePreview {
    constructor(container) {
        this.container = container;
    }

    setBusy(isBusy) {
        this.container.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }

    render(quote) {
        this.container.innerHTML = '';
        if (!quote) return;

        const box = document.createElement('div');
        box.className = 'silao-quote-box';
        box.setAttribute('role', 'status');
        box.setAttribute('aria-live', 'polite');

        const title = document.createElement('h4');
        title.style.margin = '0 0 10px 0';
        title.textContent = 'Détail de votre devis';
        box.appendChild(title);

        if (Array.isArray(quote.lines)) {
            quote.lines.forEach(line => {
                const lineDiv = document.createElement('div');
                lineDiv.className = 'silao-quote-line';

                const descSpan = document.createElement('span');
                descSpan.textContent = line.description;

                const amountSpan = document.createElement('span');
                amountSpan.textContent = line.formatted_total;

                lineDiv.appendChild(descSpan);
                lineDiv.appendChild(amountSpan);
                box.appendChild(lineDiv);
            });
        }

        const totalDiv = document.createElement('div');
        totalDiv.className = 'silao-quote-total';

        const totalLabel = document.createElement('span');
        totalLabel.textContent = 'Total estimé';

        const totalVal = document.createElement('span');
        totalVal.textContent = quote.formatted_total;

        totalDiv.appendChild(totalLabel);
        totalDiv.appendChild(totalVal);
        box.appendChild(totalDiv);

        this.container.appendChild(box);
    }
}