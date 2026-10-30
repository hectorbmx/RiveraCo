@extends('layouts.admin')
@section('title', 'Agregar complemento de pago')
@section('content')
@php
    $facturasPayload = $facturasPendientes->map(fn ($f) => [
        'id' => $f->id, 'folio' => trim(($f->serie ? $f->serie.'-' : '').$f->folio) ?: 'Factura #'.$f->id,
        'uuid' => $f->uuid, 'cliente' => $f->receptor_nombre ?: $f->cliente?->razon_social,
        'rfc' => $f->receptor_rfc, 'obra' => $f->obra?->nombre, 'moneda' => $f->moneda ?: 'MXN',
        'grupo' => implode('|', [$f->sat_empresa_id, $f->cliente_id, $f->facturapi_customer_id, strtoupper(trim((string) $f->receptor_rfc)), $f->moneda ?: 'MXN']),
        'saldo' => (float) $f->saldo_por_complementar,
        'monto' => (float) ($f->monto_interno_pendiente > 0 ? min($f->monto_interno_pendiente, $f->saldo_por_complementar) : $f->saldo_por_complementar),
    ])->values();
    $capturaAnterior = old('facturas', []);
    $seleccionInicial = $capturaAnterior ? array_column($capturaAnterior, 'factura_id') : ($facturasSeleccionadasIds ?? []);
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6" x-data="complementoPagoMultiple(@js($facturasPayload), @js($seleccionInicial), @js($capturaAnterior))">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div><h1 class="text-2xl font-bold text-slate-900">Un pago para una o varias facturas</h1><p class="text-sm text-slate-500 mt-1">Selecciona las facturas del mismo cliente e indica cuánto se aplica a cada una. Se generará un solo complemento, XML y PDF.</p></div>
        <a href="{{ route('sat.complementos-pago.index', ['tab' => 'pendientes']) }}" class="rounded-xl border px-4 py-2 bg-white text-sm">Volver</a>
    </div>
    @if(session('error'))<div class="rounded-xl bg-red-50 text-red-800 p-4 mb-4" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-red-50 text-red-800 p-4 mb-4" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <p x-show="seleccionIncompatible" class="rounded-xl bg-amber-50 text-amber-900 p-4 mb-4" role="alert">Algunas facturas seleccionadas no pertenecen al mismo emisor, cliente o moneda y no se incluyeron. Revisa la selección antes de timbrar.</p>
    @if($facturasPendientes->isEmpty())
        <div class="bg-white border rounded-2xl p-8 text-center">No hay facturas PPD pendientes por complementar.</div>
    @else
    <form method="POST" action="{{ route('sat.complementos-pago.store') }}" @submit="if (!valido || enviando) { $event.preventDefault(); } else { enviando = true; }" class="space-y-5">
        @csrf
        <input type="hidden" name="solicitud_id" value="{{ old('solicitud_id', (string) \Illuminate\Support\Str::uuid()) }}">
        <section class="rounded-2xl bg-white border overflow-hidden">
            <h2 class="px-5 py-3 bg-[#0B265A] text-white font-semibold">Datos del pago recibido</h2>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="text-sm font-semibold text-slate-700">Fecha de pago<input type="datetime-local" name="fecha_pago" value="{{ old('fecha_pago', \Carbon\Carbon::now('America/Mexico_City')->format('Y-m-d\TH:i')) }}" required class="mt-2 block w-full rounded-xl border-slate-300"></label>
                <label class="text-sm font-semibold text-slate-700">Forma de pago<select name="forma_pago" required class="mt-2 block w-full rounded-xl border-slate-300">@foreach($formasPago as $clave => $label)<option value="{{ $clave }}" @selected((string) old('forma_pago', '03') === (string) $clave)>{{ $clave }} · {{ $label }}</option>@endforeach</select></label>
                <label class="text-sm font-semibold text-slate-700">Total aplicado (<span x-text="moneda"></span>)<input name="monto" type="number" step="0.01" min="0.01" :value="(total / 100).toFixed(2)" readonly required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 font-bold text-emerald-700"></label>
                <template x-if="moneda === 'MXN'"><input type="hidden" name="tipo_cambio" value="1"></template>
                <template x-if="moneda !== 'MXN'"><label class="text-sm font-semibold text-slate-700">Tipo de cambio del pago a MXN<input type="number" name="tipo_cambio" step="0.000001" min="0.000001" value="{{ old('tipo_cambio') }}" required class="mt-2 block w-full rounded-xl border-slate-300"><span class="block mt-1 text-xs font-normal">Pesos mexicanos por unidad de la moneda recibida.</span></label></template>
            </div>
        </section>
        <section class="rounded-2xl bg-white border overflow-hidden">
            <div class="px-5 py-3 bg-[#0B265A] text-white flex flex-wrap justify-between gap-2"><h2 class="font-semibold">Facturas del complemento</h2><span class="text-sm" x-text="seleccionadas.length + ' seleccionada(s)'"></span></div>
            <div class="p-4 text-sm text-slate-600">La primera factura habilita las del mismo emisor, cliente y moneda. Puedes aplicar el saldo completo o un importe parcial por factura.</div>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-3 text-left">Incluir</th><th class="p-3 text-left">Factura / cliente</th><th class="p-3 text-left">Obra</th><th class="p-3 text-right">Saldo pendiente</th><th class="p-3 text-right">Importe aplicado</th><th class="p-3 text-right">Saldo restante</th></tr></thead>
                <tbody class="divide-y"><template x-for="factura in facturas" :key="factura.id"><tr :class="!compatible(factura) ? 'opacity-50' : ''">
                    <td class="p-3"><input type="checkbox" :checked="incluida(factura.id)" :disabled="!compatible(factura) || enviando" @change="alternar(factura.id)" :aria-label="'Incluir factura ' + factura.folio" class="rounded border-slate-300"></td>
                    <td class="p-3"><strong x-text="factura.folio"></strong><div class="text-xs text-slate-500" x-text="factura.cliente"></div><div class="text-xs text-slate-500" x-text="factura.rfc"></div><div class="text-xs font-mono break-all" x-text="factura.uuid"></div></td>
                    <td class="p-3" x-text="factura.obra || 'Sin obra'"></td>
                    <td class="p-3 text-right whitespace-nowrap" x-text="money(factura.saldo, factura.moneda)"></td>
                    <td class="p-3"><input type="number" step="0.01" min="0.01" :max="factura.saldo" x-model="factura.monto" :disabled="!incluida(factura.id) || enviando" :required="incluida(factura.id)" :aria-label="'Importe para ' + factura.folio" class="w-36 rounded-xl border-slate-300 text-right"><template x-if="incluida(factura.id)"><div><input type="hidden" :name="'facturas[' + factura.id + '][factura_id]'" :value="factura.id"><input type="hidden" :name="'facturas[' + factura.id + '][monto]'" :value="factura.monto"></div></template></td>
                    <td class="p-3 text-right whitespace-nowrap" x-text="money(factura.saldo - (incluida(factura.id) ? Number(factura.monto || 0) : 0), factura.moneda)"></td>
                </tr></template></tbody>
            </table></div>
            <p class="p-4 text-sm text-slate-600">Los impuestos, saldos y parcialidades se obtendrán de FacturAPI para cada factura antes del timbrado.</p>
        </section>
        <div class="rounded-xl bg-amber-50 text-amber-800 p-4 text-sm">Al confirmar se timbrará un único CFDI tipo Pago por el total aplicado a las facturas seleccionadas.</div>
        <div class="flex flex-wrap justify-end gap-3"><span x-show="!valido" class="text-sm text-red-700 self-center">Selecciona de 1 a 20 facturas compatibles con importes válidos.</span><button type="submit" :disabled="!valido || enviando" class="rounded-xl bg-emerald-600 disabled:opacity-50 text-white px-5 py-3 font-semibold" x-text="enviando ? 'Procesando complemento…' : 'Generar un solo complemento'"></button></div>
    </form>
    @endif
</div>
<script>
function complementoPagoMultiple(facturas, seleccionInicial, anteriores) {
    return {
        facturas, ids: [], enviando: false, seleccionIncompatible: false,
        init() {
            for (const fila of Object.values(anteriores || {})) { const f = this.facturas.find(f => Number(f.id) === Number(fila.factura_id)); if (f) f.monto = fila.monto; }
            for (const id of seleccionInicial) { const f = this.facturas.find(f => Number(f.id) === Number(id)); if (f && this.compatible(f) && !this.incluida(id)) this.ids.push(Number(id)); else if (!this.incluida(id)) this.seleccionIncompatible = true; }
        },
        incluida(id) { return this.ids.includes(Number(id)); },
        compatible(factura) { return !this.seleccionadas.length || this.seleccionadas[0].grupo === factura.grupo; },
        alternar(id) { const f = this.facturas.find(f => Number(f.id) === Number(id)); if (!f || !this.compatible(f)) return; this.ids = this.incluida(id) ? this.ids.filter(v => v !== Number(id)) : [...this.ids, Number(id)]; },
        get seleccionadas() { return this.facturas.filter(f => this.incluida(f.id)); },
        get moneda() { return this.seleccionadas[0]?.moneda || 'MXN'; },
        get total() { return this.seleccionadas.reduce((s, f) => s + Math.round(Number(f.monto || 0) * 100), 0); },
        get valido() { return this.seleccionadas.length > 0 && this.seleccionadas.length <= 20 && this.seleccionadas.every(f => this.compatible(f) && /^\d+(\.\d{1,2})?$/.test(String(f.monto)) && Number(f.monto) > 0 && Math.round(Number(f.monto) * 100) <= Math.round(f.saldo * 100)); },
        money(valor, moneda = this.moneda) { return Number(valor || 0).toLocaleString('es-MX', {style:'currency', currency:moneda}); }
    };
}
</script>
@endsection
