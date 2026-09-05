import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { AvailabilityChecker } from '../../assets/js/frontend/components/availability-checker.js';
import { MockElement } from './setup.mjs';

describe('AvailabilityChecker', () => {
    test('Renders advisory badge with accessibility attributes', () => {
        const container = new MockElement('div');
        const checker = new AvailabilityChecker(container);

        checker.render('available', 'Créneau disponible');
        const badge = container.children[0];

        assert.strictEqual(badge.getAttribute('role'), 'status');
        assert.strictEqual(badge.getAttribute('aria-live'), 'polite');
        assert.strictEqual(badge.textContent, 'Créneau disponible');
    });
});