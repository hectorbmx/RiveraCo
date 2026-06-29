@extends('layouts.admin')

@section('title', 'Agregar complemento de pago')

@section('content')
@php
    $facturasPayload = $facturasPendientes->map(function ($factura) {
        return [
            'id' => $factura->id,
            'folio' => trim(($factura->serie ? $factura->serie . '-' : '') . $factura->folio) ?: 'Factura #' . $factura->id,
            'uuid' => $factura->uuid,
            'cliente' => $factura->receptor_nombre ?: $factura->cliente?->razon_social ?: $factura->cliente?->nombre_comercial ?: 'Sin cliente',
            'rfc' => $factura->receptor_rfc ?: $factura->cliente?->rfc ?: '',
            'obra' => $factura->obra?->nombre,
            'total' => (float) $factura->total,
            'complementado' => (float) $factura->monto_complementado,
            'saldo' => (float) $factura->saldo_por_complementar,
            'monto_interno_pendiente' => (float) $factura->monto_interno_pendiente,
            'pagos_internos' => $factura->pagos_internos_pendientes->count(),
        ];
    })->values();
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
     x-data="complementoPagoForm(@js($facturasPayload), @js($facturaSeleccionada?->id))">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Agregar pago</h1>
            <p class="text-sm text-slate-500 mt-1">Captura los datos del pago para una factura PPD pendiente.</p>
        </div>

        <a href="{{ route('sat.complementos-pago.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver
        </a>
    </div>

    @if($facturasPendientes->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">No hay facturas PPD pendientes</h2>
            <p class="text-sm text-slate-500 mt-2">Cuando exista saldo pendiente por complementar, aparecera aqui.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <form method="POST"
                  action="{{ route('sat.complementos-pago.store') }}"
                  class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Factura PPD</label>
                        <select x-model.number="form.factura_id"
                                name="factura_id"
                                @change="selectFactura()"
                                required
                                class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
                            <template x-for="factura in facturas" :key="factura.id">
                                <option :value="factura.id" x-text="`${factura.folio} - ${factura.cliente} - $${moneyNumber(factura.saldo)}`"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Fecha de pago</label>
                            <input type="datetime-local"
                                   name="fecha_pago"
                                   x-model="form.fecha_pago"
                                   required
                                   class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Forma de pago</label>
                            <select x-model="form.forma_pago"
                                    name="forma_pago"
                                    required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
                                @foreach($formasPago as $clave => $label)
                                    <option value="{{ $clave }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold text-slate-500 uppercase">Total factura</div>
                            <div class="text-lg font-bold text-slate-900" x-text="money(selected?.total || 0)"></div>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <div class="text-xs font-semibold text-emerald-600 uppercase">Complementado</div>
                            <div class="text-lg font-bold text-emerald-700" x-text="money(selected?.complementado || 0)"></div>
                        </div>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <div class="text-xs font-semibold text-amber-600 uppercase">Saldo pendiente</div>
                            <div class="text-lg font-bold text-amber-700" x-text="money(selected?.saldo || 0)"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Monto pagado</label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               :max="selected?.saldo || 0"
                               name="monto"
                               x-model.number="form.monto"
                               required
                               class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
                        <p class="text-xs text-slate-500 mt-2">
                            Maximo permitido: <span x-text="money(selected?.saldo || 0)"></span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Al confirmar se generara y timbrara un CFDI tipo P en Facturapi.
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('sat.complementos-pago.index') }}"
                           class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Generar complemento
                        </button>
                    </div>
                </div>
            </form>

            <aside class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Resumen</h2>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Cliente</div>
                        <div class="font-semibold text-slate-900" x-text="selected?.cliente || '-'"></div>
                        <div class="text-xs text-slate-500" x-text="selected?.rfc || '-'"></div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">UUID factura</div>
                        <div class="font-mono text-xs text-slate-700 break-all" x-text="selected?.uuid || '-'"></div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Obra</div>
                        <div class="text-slate-700" x-text="selected?.obra || 'Sin obra'"></div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold text-amber-700 uppercase">Pago interno pendiente</div>
                        <div class="font-bold text-amber-800" x-text="money(selected?.monto_interno_pendiente || 0)"></div>
                        <div class="text-xs text-amber-700">
                            <span x-text="selected?.pagos_internos || 0"></span> pago(s) sin complemento ligado
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>

<script>
function complementoPagoForm(facturas, selectedId) {
    return {
        facturas,
        selected: null,
        form: {
            factura_id: selectedId || (facturas[0]?.id ?? null),
            fecha_pago: new Date().toISOString().slice(0, 16),
            forma_pago: '03',
            monto: 0,
        },
        init() {
            this.selectFactura();
        },
        selectFactura() {
            this.selected = this.facturas.find((factura) => Number(factura.id) === Number(this.form.factura_id)) || this.facturas[0] || null;
            this.form.monto = this.selected?.monto_interno_pendiente > 0
                ? Math.min(this.selected.monto_interno_pendiente, this.selected.saldo)
                : (this.selected?.saldo || 0);
        },
        money(value) {
            return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        },
        moneyNumber(value) {
            return Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endsection
