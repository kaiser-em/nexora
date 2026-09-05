export class OptionSelector {
    constructor(container, options = [], state) {
        this.container = container;
        this.options = options;
        this.state = state;
    }

    render() {
        if (!this.options || this.options.length === 0) {
            this.container.innerHTML = '';
            return;
        }

        this.container.innerHTML = '';
        const listDiv = document.createElement('div');
        listDiv.className = 'silao-options-list';

        const heading = document.createElement('h3');
        heading.textContent = 'Options & Extras';
        heading.style.fontSize = '16px';
        heading.style.marginBottom = '12px';
        listDiv.appendChild(heading);

        const currentSelected = this.state.get().selectedOptions || {};

        this.options.forEach(opt => {
            const item = document.createElement('div');
            item.className = 'silao-option-item';

            const infoDiv = document.createElement('div');
            const nameEl = document.createElement('div');
            nameEl.style.fontWeight = '600';
            nameEl.textContent = `${opt.name} (${opt.formatted_price})`;
            infoDiv.appendChild(nameEl);

            if (opt.description) {
                const descEl = document.createElement('div');
                descEl.style.fontSize = '12px';
                descEl.style.color = '#646970';
                descEl.textContent = opt.description;
                infoDiv.appendChild(descEl);
            }
            item.appendChild(infoDiv);

            const qtyControls = document.createElement('div');
            qtyControls.className = 'silao-qty-controls';

            const currentQty = currentSelected[opt.id] || 0;

            const btnMinus = document.createElement('button');
            btnMinus.type = 'button';
            btnMinus.className = 'silao-btn-qty';
            btnMinus.textContent = '-';
            btnMinus.disabled = currentQty <= (opt.min_quantity || 0);

            const qtySpan = document.createElement('span');
            qtySpan.style.minWidth = '20px';
            qtySpan.style.textAlign = 'center';
            qtySpan.textContent = String(currentQty);

            const btnPlus = document.createElement('button');
            btnPlus.type = 'button';
            btnPlus.className = 'silao-btn-qty';
            btnPlus.textContent = '+';
            btnPlus.disabled = currentQty >= (opt.max_quantity || 99);

            btnMinus.addEventListener('click', () => {
                this.state.setOptionQuantity(opt.id, currentQty - 1);
            });

            btnPlus.addEventListener('click', () => {
                this.state.setOptionQuantity(opt.id, currentQty + 1);
            });

            qtyControls.appendChild(btnMinus);
            qtyControls.appendChild(qtySpan);
            qtyControls.appendChild(btnPlus);
            item.appendChild(qtyControls);

            listDiv.appendChild(item);
        });

        this.container.appendChild(listDiv);
    }
}