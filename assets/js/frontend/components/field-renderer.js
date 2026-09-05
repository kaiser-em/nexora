export class FieldRendererRegistry {
    constructor() {
        this.renderers = new Map();
        this.registerDefaults();
    }

    registerDefaults() {
        const createStandardInput = (type) => (field, value, onChange) => {
            const input = document.createElement('input');
            input.type = type;
            input.id = `silao-field-${field.name}`;
            input.className = 'silao-input';
            input.required = Boolean(field.is_required);
            if (value !== undefined && value !== null) input.value = String(value);
            input.addEventListener('input', () => onChange(input.value));
            return input;
        };

        this.renderers.set('text', createStandardInput('text'));
        this.renderers.set('email', createStandardInput('email'));
        this.renderers.set('phone', createStandardInput('tel'));
        this.renderers.set('number', (field, value, onChange) => {
            const input = createStandardInput('number')(field, value, onChange);
            if (field.validation_rules?.min !== undefined) input.min = String(field.validation_rules.min);
            if (field.validation_rules?.max !== undefined) input.max = String(field.validation_rules.max);
            input.addEventListener('input', () => onChange(input.value !== '' ? parseInt(input.value, 10) : null));
            return input;
        });
        this.renderers.set('date', createStandardInput('date'));
        this.renderers.set('time', createStandardInput('time'));
        this.renderers.set('datetime', createStandardInput('datetime-local'));
        this.renderers.set('location', createStandardInput('text'));

        this.renderers.set('textarea', (field, value, onChange) => {
            const textarea = document.createElement('textarea');
            textarea.id = `silao-field-${field.name}`;
            textarea.className = 'silao-textarea';
            textarea.required = Boolean(field.is_required);
            if (value) textarea.value = String(value);
            textarea.addEventListener('input', () => onChange(textarea.value));
            return textarea;
        });

        this.renderers.set('select', (field, value, onChange) => {
            const select = document.createElement('select');
            select.id = `silao-field-${field.name}`;
            select.className = 'silao-select';
            select.required = Boolean(field.is_required);

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = '-- Choisir --';
            select.appendChild(defaultOpt);

            const options = field.validation_rules?.allowed_values || [];
            options.forEach(optVal => {
                const opt = document.createElement('option');
                opt.value = String(optVal);
                opt.textContent = String(optVal);
                if (String(optVal) === String(value)) opt.selected = true;
                select.appendChild(opt);
            });

            select.addEventListener('change', () => onChange(select.value));
            return select;
        });
    }

    render(field, value, onChange) {
        const renderer = this.renderers.get(field.type) || this.renderers.get('text');
        const container = document.createElement('div');
        container.className = 'silao-form-group';
        container.id = `silao-group-${field.name}`;

        const label = document.createElement('label');
        label.className = 'silao-label';
        label.htmlFor = `silao-field-${field.name}`;
        label.textContent = field.label + (field.is_required ? ' *' : '');
        container.appendChild(label);

        const inputEl = renderer(field, value, onChange);
        container.appendChild(inputEl);

        const errorSpan = document.createElement('span');
        errorSpan.className = 'silao-field-error';
        errorSpan.id = `silao-error-${field.name}`;
        errorSpan.style.display = 'none';
        container.appendChild(errorSpan);

        return container;
    }
}