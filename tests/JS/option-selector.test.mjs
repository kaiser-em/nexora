import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { OptionSelector } from '../../assets/js/frontend/components/option-selector.js';
import { SilaoFormState } from '../../assets/js/frontend/core/form-state.js';
import { MockElement } from './setup.mjs';

describe('OptionSelector', () => {
    test('Enforces min and max quantities', () => {
        const container = new MockElement('div');
        const state = new SilaoFormState();
        const options = [
            { id: 'opt_seat', name: 'Siège bébé', formatted_price: '10.00 €', min_quantity: 0, max_quantity: 2 }
        ];

        const selector = new OptionSelector(container, options, state);
        selector.render();

        assert.strictEqual(state.get().selectedOptions['opt_seat'], undefined);

        state.setOptionQuantity('opt_seat', 1);
        assert.strictEqual(state.get().selectedOptions['opt_seat'], 1);
    });
});