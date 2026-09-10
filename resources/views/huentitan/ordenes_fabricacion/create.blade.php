@extends('layouts.admin')

@section('title', 'HUENTITAN - Nueva orden fabricacion')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
    @if(isset($errors) && $errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('huentitan.ordenes-fabricacion.store') }}" id="ordenFabricacionForm" class="space-y-6">
        @csrf

        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900">Nueva orden fabricacion</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-[180px_180px_auto] gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-slate-300 focus:ring-slate-300" required>
                    @if(isset($errors) && $errors->has('fecha'))
                        <div class="mt-1 text-xs text-red-600">{{ $errors->first('fecha') }}</div>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                    <input type="text" value="Automatico" class="w-full rounded-md border-slate-200 bg-gray-50 text-sm text-gray-700" disabled>
                </div>
                <a href="{{ route('huentitan.ordenes-fabricacion.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Regresar</a>
            </div>
        </div>

        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Datos base</h2>
            </div>

            <div class="p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Producto a fabricar</label>
                        <select name="producto_id" id="productoFabricar" class="w-full rounded-md border-slate-200 text-sm focus:border-slate-300 focus:ring-slate-300" required>
                            <option value="">Selecciona producto</option>
                            @foreach($productosFabricables as $producto)
                                <option value="{{ $producto->id }}" @selected((int) old('producto_id') === (int) $producto->id)>{{ $producto->sku }} - {{ $producto->nombre }} ({{ $producto->unidad }})</option>
                            @endforeach
                        </select>
                        @if(isset($errors) && $errors->has('producto_id'))
                            <div class="mt-1 text-xs text-red-600">{{ $errors->first('producto_id') }}</div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Cantidad solicitada</label>
                        <input type="number" name="cantidad_solicitada" id="cantidadSolicitada" value="{{ old('cantidad_solicitada') }}" step="0.001" min="0.001" class="w-full rounded-md border-slate-200 text-sm focus:border-slate-300 focus:ring-slate-300" required>
                        @if(isset($errors) && $errors->has('cantidad_solicitada'))
                            <div class="mt-1 text-xs text-red-600">{{ $errors->first('cantidad_solicitada') }}</div>
                        @endif
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                    Al crear se congela la formula vigente y se revisa disponibilidad. El inventario no se aparta ni se descuenta en este paso.
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Materiales requeridos</h2>
                    <p id="formulaResumen" class="text-xs text-gray-500">Selecciona producto y cantidad para calcular materiales.</p>
                </div>
                <div id="faltantesResumen" class="hidden text-xs font-semibold px-2 py-1 rounded border"></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Codigo</th>
                            <th class="px-4 py-3 text-left">Material</th>
                            <th class="px-4 py-3 text-right">Cant. unidad</th>
                            <th class="px-4 py-3 text-right">Merma %</th>
                            <th class="px-4 py-3 text-right">Total requerido</th>
                            <th class="px-4 py-3 text-left">Unidad</th>
                            <th class="px-4 py-3 text-right">Stock actual</th>
                            <th class="px-4 py-3 text-right">Reservado</th>
                            <th class="px-4 py-3 text-right">Disponible</th>
                            <th class="px-4 py-3 text-right">Faltante</th>
                            <th class="px-4 py-3 text-right">Costo est.</th>
                        </tr>
                    </thead>
                    <tbody id="materialesPreviewBody" class="divide-y">
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center text-gray-500">Sin materiales calculados.</td>
                        </tr>
                    </tbody>
                    <tfoot id="materialesPreviewFooter" class="hidden bg-gray-50 text-sm font-semibold text-gray-900">
                        <tr>
                            <td colspan="10" class="px-4 py-3 text-right">Costo estimado materiales</td>
                            <td class="px-4 py-3 text-right" id="costoTotalPreview">$0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Crear orden</button>
        </div>
    </form>
</div>

<script>
    const formulasHuentitan = @json($vistaPreviaFormulas);
    const productoSelect = document.getElementById('productoFabricar');
    const cantidadInput = document.getElementById('cantidadSolicitada');
    const tbody = document.getElementById('materialesPreviewBody');
    const footer = document.getElementById('materialesPreviewFooter');
    const totalCell = document.getElementById('costoTotalPreview');
    const resumen = document.getElementById('formulaResumen');
    const faltantesResumen = document.getElementById('faltantesResumen');

    const numberFormatter = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
    const moneyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    function renderEmpty(message) {
        tbody.innerHTML = `<tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">${message}</td></tr>`;
        footer.classList.add('hidden');
        totalCell.textContent = moneyFormatter.format(0);
        faltantesResumen.classList.add('hidden');
    }

    function renderPreview() {
        const productoId = productoSelect.value;
        const cantidad = parseFloat(cantidadInput.value || '0');
        const data = formulasHuentitan[productoId];

        if (!productoId || !data) {
            resumen.textContent = 'Selecciona producto y cantidad para calcular materiales.';
            renderEmpty('Sin materiales calculados.');
            return;
        }

        if (!cantidad || cantidad <= 0) {
            resumen.textContent = `${data.producto.sku} - ${data.producto.nombre}`;
            renderEmpty('Captura una cantidad mayor a cero.');
            return;
        }

        const base = parseFloat(data.formula.cantidad_base || '1') || 1;
        const factor = cantidad / base;
        let costoTotal = 0;
        let faltantes = 0;

        resumen.textContent = `Formula base: ${numberFormatter.format(base)} ${data.formula.unidad_base || data.producto.unidad || ''}`;

        const rows = data.materiales.map((material) => {
            const cantidadPorUnidad = parseFloat(material.cantidad_por_unidad || '0');
            const merma = parseFloat(material.merma_porcentaje || '0');
            const requerido = cantidadPorUnidad * factor * (1 + (merma / 100));
            const disponible = parseFloat(material.stock_disponible || '0');
            const faltante = Math.max(0, requerido - disponible);
            const costoLinea = requerido * parseFloat(material.costo_unitario || '0');
            costoTotal += costoLinea;
            if (faltante > 0) {
                faltantes += 1;
            }

            const faltanteClass = faltante > 0 ? 'text-red-700 font-semibold bg-red-50' : 'text-gray-700';

            return `<tr>
                <td class="px-4 py-3 font-mono text-xs text-gray-700">${material.sku || '-'}</td>
                <td class="px-4 py-3 font-medium text-gray-900">${material.nombre}</td>
                <td class="px-4 py-3 text-right">${numberFormatter.format(cantidadPorUnidad)}</td>
                <td class="px-4 py-3 text-right">${numberFormatter.format(merma)}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900">${numberFormatter.format(requerido)}</td>
                <td class="px-4 py-3 text-gray-700">${material.unidad || '-'}</td>
                <td class="px-4 py-3 text-right">${numberFormatter.format(parseFloat(material.stock_actual || '0'))}</td>
                <td class="px-4 py-3 text-right">${numberFormatter.format(parseFloat(material.stock_reservado || '0'))}</td>
                <td class="px-4 py-3 text-right">${numberFormatter.format(disponible)}</td>
                <td class="px-4 py-3 text-right ${faltanteClass}">${numberFormatter.format(faltante)}</td>
                <td class="px-4 py-3 text-right">${moneyFormatter.format(costoLinea)}</td>
            </tr>`;
        }).join('');

        tbody.innerHTML = rows || '<tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">El producto no tiene materiales en formula.</td></tr>';
        footer.classList.remove('hidden');
        totalCell.textContent = moneyFormatter.format(costoTotal);

        faltantesResumen.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'border-green-200', 'bg-red-50', 'text-red-700', 'border-red-200');
        if (faltantes > 0) {
            faltantesResumen.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
            faltantesResumen.textContent = `${faltantes} material(es) con faltante`;
        } else {
            faltantesResumen.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
            faltantesResumen.textContent = 'Material suficiente';
        }
    }

    productoSelect.addEventListener('change', renderPreview);
    cantidadInput.addEventListener('input', renderPreview);
    renderPreview();
</script>
@endsection

