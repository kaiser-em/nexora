import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { SilaoFormState } from '../../assets/js/frontend/core/form-state.js';

describe('SilaoFormState', () => {
    test('Multi-instance state isolation', () => {
        const stateA = new SilaoFormState({ modelId: 'model_a' });
        const stateB = new SilaoFormState({ modelId: 'model_b' });

        stateA.setFormField('passengers', 4);
        stateB.setFormField('passengers', 2);

        assert.strictEqual(stateA.get().formData.passengers, 4);
        assert.strictEqual(stateB.get().formData.passengers, 2);
        assert.notStrictEqual(stateA.get(), stateB.get());
    });

    test('Monotonic sequence counter', () => {
        const state = new SilaoFormState();
        assert.strictEqual(state.nextSequence(), 1);
        assert.strictEqual(state.nextSequence(), 2);
        assert.strictEqual(state.currentSequence(), 2);
    });

    test('Option quantities updates correctly', () => {
        const state = new SilaoFormState();
        state.setOptionQuantity('opt_seat', 2);
        assert.strictEqual(state.get().selectedOptions['opt_seat'], 2);

        state.setOptionQuantity('opt_seat', 0);
        assert.strictEqual(state.get().selectedOptions['opt_seat'], undefined);
    });
});