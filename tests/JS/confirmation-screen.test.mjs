import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { ConfirmationScreen } from '../../assets/js/frontend/components/confirmation-screen.js';
import { MockElement } from './setup.mjs';

describe('ConfirmationScreen', () => {
    test('Renders server-issued reference verbatim', () => {
        const container = new MockElement('div');
        const screen = new ConfirmationScreen(container);

        screen.render({
            reference: 'SIL-2026-X8K9M2',
            quote: { formatted_total: '138.00 €' }
        });

        assert.ok(container.innerHTML.includes('SIL-2026-X8K9M2'));
        assert.ok(container.innerHTML.includes('138.00 €'));
        assert.ok(!container.innerHTML.includes('undefined'));
    });
});