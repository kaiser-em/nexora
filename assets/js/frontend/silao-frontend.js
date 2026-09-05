import { SilaoPublicApi } from './core/api.js';
import { SilaoFormState } from './core/form-state.js';
import { debounce } from './core/debounce.js';
import { FieldRendererRegistry } from './components/field-renderer.js';
import { OptionSelector } from './components/option-selector.js';
import { QuotePreview } from './components/quote-preview.js';
import { ConfirmationScreen } from './components/confirmation-screen.js';
import { FormValidator } from './validation/form-validator.js';

export class SilaoWidgetInstance {
    constructor(rootElement) {
        this.root = rootElement;
        this.modelId = rootElement.getAttribute('data-model-id') || rootElement.getAttribute('data-model-slug');
        this.state = new SilaoFormState({ modelId: this.modelId });
        this.fieldRegistry = new FieldRendererRegistry();
        this.activeAbortController = null;
        this.model = null;

        this.init();
    }

    async init() {
        try {
            this.model = await SilaoPublicApi.getModel(this.modelId);
            this.renderForm();
            this.setupSubscriptions();
        } catch (e) {
            this.root.innerHTML = `<div class="silao-notice silao-notice-error"><p>Impossible de charger le formulaire : ${this.escapeHtml(e.message)}</p></div>`;
        }
    }

    renderForm() {
        this.root.innerHTML = '';

        const form = document.createElement('form');
        form.className = 'silao-form-container';
        form.noValidate = true;

        // 1. Title & Description
        const h2 = document.createElement('h2');
        h2.textContent = this.model.name;
        h2.style.marginBottom = '6px';
        form.appendChild(h2);

        if (this.model.description) {
            const desc = document.createElement('p');
            desc.style.color = '#646970';
            desc.style.marginBottom = '20px';
            desc.textContent = this.model.description;
            form.appendChild(desc);
        }

        // Honeypot anti-bot
        const hp = document.createElement('input');
        hp.type = 'text';
        hp.name = '_silao_hp';
        hp.style.display = 'none';
        hp.tabIndex = -1;
        form.appendChild(hp);

        // 2. Dates/Times
        const datesGroup = document.createElement('div');
        datesGroup.style.display = 'grid';
        datesGroup.style.gridTemplateColumns = '1fr 1fr';
        datesGroup.style.gap = '12px';

        const startInput = this.fieldRegistry.render({ name: 'starts_at', type: 'datetime', label: 'Date et Heure de départ', is_required: true }, '', (val) => {
            this.state.set({ startsAt: val });
        });
        const endInput = this.fieldRegistry.render({ name: 'ends_at', type: 'datetime', label: 'Date et Heure de fin', is_required: true }, '', (val) => {
            this.state.set({ endsAt: val });
        });
        datesGroup.appendChild(startInput);
        datesGroup.appendChild(endInput);
        form.appendChild(datesGroup);

        // 3. Dynamic Fields
        const fieldsContainer = document.createElement('div');
        fieldsContainer.id = 'silao-dynamic-fields';
        (this.model.fields || []).forEach(f => {
            const fieldEl = this.fieldRegistry.render(f, f.default_value, (val) => {
                this.state.setFormField(f.name, val);
            });
            fieldsContainer.appendChild(fieldEl);
        });
        form.appendChild(fieldsContainer);

        // 4. Options
        const optionsContainer = document.createElement('div');
        optionsContainer.id = 'silao-options-container';
        this.optionSelector = new OptionSelector(optionsContainer, this.model.options || [], this.state);
        this.optionSelector.render();
        form.appendChild(optionsContainer);

        // 5. Customer Fields
        const custGroup = document.createElement('div');
        custGroup.style.display = 'grid';
        custGroup.style.gridTemplateColumns = '1fr 1fr';
        custGroup.style.gap = '12px';

        custGroup.appendChild(this.fieldRegistry.render({ name: 'customer_first_name', type: 'text', label: 'Prénom', is_required: true }, '', (v) => this.state.setCustomerField('first_name', v)));
        custGroup.appendChild(this.fieldRegistry.render({ name: 'customer_last_name', type: 'text', label: 'Nom', is_required: true }, '', (v) => this.state.setCustomerField('last_name', v)));
        form.appendChild(custGroup);

        form.appendChild(this.fieldRegistry.render({ name: 'customer_email', type: 'email', label: 'Email', is_required: true }, '', (v) => this.state.setCustomerField('email', v)));
        form.appendChild(this.fieldRegistry.render({ name: 'customer_phone', type: 'phone', label: 'Téléphone', is_required: false }, '', (v) => this.state.setCustomerField('phone', v)));

        // 6. Live Quote Box
        const quoteContainer = document.createElement('div');
        quoteContainer.id = 'silao-quote-container';
        this.quotePreview = new QuotePreview(quoteContainer);
        form.appendChild(quoteContainer);

        // 7. Submit Button & Banner
        this.errorBanner = document.createElement('div');
        this.errorBanner.className = 'silao-field-error';
        this.errorBanner.style.marginBottom = '12px';
        this.errorBanner.style.fontSize = '14px';
        this.errorBanner.style.display = 'none';
        form.appendChild(this.errorBanner);

        this.submitBtn = document.createElement('button');
        this.submitBtn.type = 'submit';
        this.submitBtn.className = 'silao-btn-submit';
        this.submitBtn.textContent = 'Réserver maintenant';
        form.appendChild(this.submitBtn);

        form.addEventListener('submit', (e) => this.handleSubmit(e));

        this.root.appendChild(form);
    }

    setupSubscriptions() {
        const debouncedQuote = debounce(() => this.requestQuote(), 300);

        this.state.subscribe(() => {
            this.optionSelector.render();
            debouncedQuote();
        });
    }

    async requestQuote() {
        const s = this.state.get();
        if (!s.startsAt || !s.endsAt) return;

        if (this.activeAbortController) {
            this.activeAbortController.abort();
        }
        this.activeAbortController = new AbortController();

        const seq = this.state.nextSequence();
        this.quotePreview.setBusy(true);

        const selectedOpts = Object.entries(s.selectedOptions).map(([id, qty]) => ({ id, quantity: qty }));

        try {
            const quote = await SilaoPublicApi.calculateQuote({
                model_id: this.model.id,
                resource_id: s.resourceId,
                starts_at: s.startsAt,
                ends_at: s.endsAt,
                timezone: s.timezone,
                selected_options: selectedOpts,
                form_data: s.formData
            }, this.activeAbortController.signal);

            if (seq === this.state.currentSequence()) {
                this.quotePreview.render(quote);
            }
        } catch (e) {
            if (e.name !== 'AbortError' && seq === this.state.currentSequence()) {
                // Ignore silent preview errors
            }
        } finally {
            if (seq === this.state.currentSequence()) {
                this.quotePreview.setBusy(false);
            }
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        this.errorBanner.style.display = 'none';

        const s = this.state.get();
        const validation = FormValidator.validate(this.model, s);

        if (!validation.isValid) {
            this.errorBanner.textContent = 'Veuillez remplir correctement tous les champs obligatoires.';
            this.errorBanner.style.display = 'block';
            return;
        }

        this.submitBtn.disabled = true;
        this.submitBtn.textContent = 'Traitement en cours...';

        const selectedOpts = Object.entries(s.selectedOptions).map(([id, qty]) => ({ id, quantity: qty }));

        try {
            const booking = await SilaoPublicApi.submitBooking({
                model_id: this.model.id,
                resource_id: s.resourceId,
                starts_at: s.startsAt,
                ends_at: s.endsAt,
                timezone: s.timezone,
                customer: s.customer,
                selected_options: selectedOpts,
                form_data: s.formData,
                _silao_hp: ''
            });

            new ConfirmationScreen(this.root).render(booking);
        } catch (err) {
            this.submitBtn.disabled = false;
            this.submitBtn.textContent = 'Réserver maintenant';
            this.errorBanner.textContent = err.message || 'Erreur lors de la réservation.';
            this.errorBanner.style.display = 'block';
        }
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.silao-booking-widget').forEach(el => {
        new SilaoWidgetInstance(el);
    });
});