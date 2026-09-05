import './setup.mjs';
import { test, describe } from 'node:test';
import assert from 'node:assert';
import { SilaoPublicApi } from '../../assets/js/frontend/core/api.js';

describe('SilaoPublicApi', () => {
    test('Handles 200/201 success and parses JSON data', async () => {
        globalThis.fetch = async () => ({
            ok: true,
            status: 200,
            json: async () => ({ success: true, data: { model_id: 'm1' } })
        });

        const data = await SilaoPublicApi.getModel('m1');
        assert.strictEqual(data.model_id, 'm1');
    });

    test('Throws structured error on 400/409/422/500', async () => {
        globalThis.fetch = async () => ({
            ok: false,
            status: 409,
            json: async () => ({ code: 'silao_rest_unavailable', message: 'Créneau complet' })
        });

        await assert.rejects(
            async () => { await SilaoPublicApi.checkAvailability({}); },
            (err) => err.status === 409 && err.message === 'Créneau complet'
        );
    });
});