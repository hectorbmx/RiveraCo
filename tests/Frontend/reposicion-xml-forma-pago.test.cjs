const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const view = fs.readFileSync('resources/views/reposicion-caja-chica/create.blade.php', 'utf8');
const start = view.indexOf('        async uploadAndParseXmls(files) {');
const end = view.indexOf('        attachXmlToForm(file, rowIndex) {', start);
const method = view.slice(start, end).replace('{{ route("reposicion-caja-chica.parse-xml") }}', '/parse');

async function procesar(cfdi) {
    const attached = [];
    const context = {
        FormData: class { append() {} },
        document: { querySelector: () => ({ getAttribute: () => 'token' }) },
        fetch: async () => ({ ok: true, json: async () => ({ ok: true, data: [cfdi] }) }),
        Math, Date, parseFloat,
    };
    const component = vm.runInNewContext(`({${method}})`, context);
    Object.assign(component, {
        categorias: [{ id: 1, codigo: 'efectivo_factura' }], rows: [], targetDestino: 'almacen', targetAlmacenId: 3,
        getSubcategorias: () => [], attachXmlToForm: (file, index) => attached.push([file.name, index]),
    });
    await component.uploadAndParseXmls([{ name: 'factura.xml' }]);
    return { component, attached };
}

test('una forma SAT desconocida conserva el XML y solicita clasificación manual', async () => {
    const { component, attached } = await procesar({ forma_pago_sat: '99', forma_pago: 'desconocido', total: 5085.44 });
    assert.equal(component.rows.length, 1);
    assert.equal(component.rows[0].forma_pago_sat, '99');
    assert.equal(component.rows[0].forma_pago, '');
    assert.equal(component.rows[0].categoria_id, '');
    assert.equal(component.rows[0].requiere_clasificacion_manual, true);
    assert.equal(component.rows[0].importe_registrado, 5085.44);
    assert.equal(component.xmlErrores.length, 0);
    assert.equal(attached[0][0], 'factura.xml');
});

test('una forma reconocida conserva la clasificación automática', async () => {
    const { component } = await procesar({ forma_pago_sat: '01', forma_pago: 'efectivo', total: 50 });
    assert.equal(component.rows[0].categoria_id, '1');
    assert.equal(component.rows[0].forma_pago, 'efectivo');
    assert.equal(component.rows[0].requiere_clasificacion_manual, false);
});
