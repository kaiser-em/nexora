export class SilaoFormState {
    constructor(initialData = {}) {
        this.data = {
            modelId: '',
            resourceId: null,
            startsAt: '',
            endsAt: '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            formData: {},
            customer: { first_name: '', last_name: '', email: '', phone: '' },
            selectedOptions: {}, // { optionId: quantity }
            ...initialData
        };
        this.listeners = [];
        this.requestSequenceId = 0;
    }

    get() { return this.data; }

    set(partial) {
        this.data = { ...this.data, ...partial };
        this.notify();
    }

    setFormField(name, value) {
        this.data.formData = { ...this.data.formData, [name]: value };
        this.notify();
    }

    setCustomerField(key, value) {
        this.data.customer = { ...this.data.customer, [key]: value };
        this.notify();
    }

    setOptionQuantity(optionId, qty) {
        const next = { ...this.data.selectedOptions };
        if (qty <= 0) {
            delete next[optionId];
        } else {
            next[optionId] = qty;
        }
        this.data.selectedOptions = next;
        this.notify();
    }

    nextSequence() {
        this.requestSequenceId += 1;
        return this.requestSequenceId;
    }

    currentSequence() {
        return this.requestSequenceId;
    }

    subscribe(listener) {
        this.listeners.push(listener);
        return () => { this.listeners = this.listeners.filter(l => l !== listener); };
    }

    notify() {
        this.listeners.forEach(fn => fn(this.data));
    }
}