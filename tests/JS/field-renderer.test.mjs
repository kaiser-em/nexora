import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { FieldRendererRegistry } from '../../assets/js/frontend/components/field-renderer.js';

describe('FieldRendererRegistry', () => {
    test('XSS Injection protection via textContent', () => {
        const registry = new FieldRendererRegistry();
        const maliciousLabel = '<script>alert(1)</script><b>Nom</b>';
        const field = { name: 'safe_field', type: 'text', label: maliciousLabel, is_required: false };

        const el = registry.render(field, '', () => {});
        const labelEl = el.children[0];

        assert.strictEqual(labelEl.textContent, maliciousLabel);
        assert.ok(labelEl.innerHTML.includes('&lt;script&gt;'));
        assert.ok(!labelEl.innerHTML.includes('<script>'));
    });

    test('Renders all 13 field types properly', () => {
        const registry = new FieldRendererRegistry();
        const all13Types = [
            'text', 'email', 'phone', 'number',
            'date', 'time', 'datetime',
            'select', 'radio', 'checkbox',
            'textarea', 'location', 'resource'
        ];

        all13Types.forEach(t => {
            const el = registry.render({ name: `field_${t}`, type: t, label: `Label ${t}` }, '', () => {});
            assert.ok(el instanceof Object);
            assert.strictEqual(el.attributes['id'], `silao-group-field_${t}`);
            assert.strictEqual(el.id, `silao-group-field_${t}`);
        });
    });
});