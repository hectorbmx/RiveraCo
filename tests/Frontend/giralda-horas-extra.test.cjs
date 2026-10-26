const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/giralda/partials/_modal_horas_extra.blade.php'), 'utf8');
const context = vm.createContext({});
vm.runInContext(source.match(/<script>([\s\S]*?)<\/script>/)[1], context);

function modal(defaults = {}) {
    return context.horasExtraModal({
        fecha: '2026-10-26', inicio: '17:30', fin: '19:30',
        tarifas: { automatica: 100, vigencias: [
            { desde: '2026-11-01', hasta: null, modalidad: 'automatica', costo: null },
            { desde: '2026-10-26', hasta: '2026-10-31', modalidad: 'cuota_fija', costo: 150 },
        ] },
        ...defaults,
    });
}

test('cambia tarifa e importe al cambiar fecha, incluyendo limites de vigencia', () => {
    const state = modal();
    state.recalcular();
    assert.equal(state.total, '2.00');
    assert.equal(state.costoHora, 150);
    assert.equal(state.importeCaptura, 300);
    state.fecha = '2026-10-25';
    assert.equal(state.importeCaptura, 200);
    state.fecha = '2026-10-31';
    assert.equal(state.importeCaptura, 300);
    state.fecha = '2026-11-01';
    assert.equal(state.importeCaptura, 200);
    assert.equal(state.modalidadTarifa, 'Automática por sueldo');
});

test('duracion manual y cruce de medianoche conservan el calculo del importe', () => {
    const state = modal({ inicio: '23:30', fin: '01:00' });
    state.recalcular();
    assert.equal(state.total, '1.50');
    assert.equal(state.importeCaptura, 225);
    state.total = '3.25';
    state.actualizarFinDesdeTotal();
    assert.equal(state.fin, '02:45');
    assert.equal(state.importeCaptura, 487.5);
});

test('sin sueldo muestra tarifa pendiente y no inventa importe cero', () => {
    const state = modal({ fecha: '2026-10-25', tarifas: { automatica: null, vigencias: [] } });
    state.recalcular();
    assert.equal(state.costoHora, null);
    assert.equal(state.importeCaptura, null);
    assert.equal(state.moneda(state.importeCaptura), 'Sin tarifa definida');
});
