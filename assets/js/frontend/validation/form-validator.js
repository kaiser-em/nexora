export class FormValidator {
    static validate(model, stateData) {
        const errors = {};
        const fields = model.fields || [];

        fields.forEach(f => {
            const val = stateData.formData[f.name];
            if (f.is_required && (val === undefined || val === null || String(val).trim() === '')) {
                errors[f.name] = 'Ce champ est requis.';
            }

            if (f.type === 'email' && val) {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(val))) {
                    errors[f.name] = 'Adresse email invalide.';
                }
            }
        });

        // Customer contact validation
        const c = stateData.customer;
        if (!c.first_name || c.first_name.trim() === '') errors['customer.first_name'] = 'Le prénom est requis.';
        if (!c.last_name || c.last_name.trim() === '') errors['customer.last_name'] = 'Le nom est requis.';
        if (!c.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c.email)) errors['customer.email'] = 'Email de contact invalide.';

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }
}