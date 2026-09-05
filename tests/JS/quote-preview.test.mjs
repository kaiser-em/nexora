import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { QuotePreview } from '../../assets/js/frontend/components/quote-preview.js';
import { MockElement } from './setup.mjs';

describe('QuotePreview', () => {
    test('Renders quote breakdown with server-authoritative formatted totals', () => {
        const container = new MockElement('div');
        const preview = new QuotePreview(container);

        const serverQuote = {
            currency: 'EUR',
            formatted_total: '138.00 €',
            lines: [
                { description: 'Base Transfer', formatted_total: '100.00 €' },
                { description: 'Siège bébé', formatted_total: '38.00 €' }
            ]
        };

        preview.render(serverQuote);
        assert.ok(container.innerHTML.includes('138.00 €'));
        assert.ok(container.innerHTML.includes('Base Transfer'));
    });

    test('Sets and clears aria-busy during recalculation', () => {
        const container = new MockElement('div');
        const preview = new QuotePreview(container);

        preview.setBusy(true);
        assert.strictEqual(container.getAttribute('aria-busy'), 'true');

        preview.setBusy(false);
        assert.strictEqual(container.getAttribute('aria-busy'), 'false');
    });
});