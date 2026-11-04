@extends('layouts.admin')
@section('title', 'Presupuesto ' . $presupuesto->codigo_proyecto)
@section('content')
@php
    $pestanas = ['pilas' => 'Pilas / VOL', 'apupi' => 'APUPI', 'presupuesto' => 'Presupuesto', 'gastos' => 'Relación de gastos'];
    $solicitada = request()->query('tab');
    $tabInicial = is_string($solicitada) && array_key_exists($solicitada, $pestanas) ? $solicitada : 'presupuesto';
    $totalVenta = $presupuesto->resumenes->where('cantidad', '>', 0)->sum('importe') + $presupuesto->pilas->where('cantidad', '>', 0)->sum('total');
@endphp
<div class="space-y-5" x-data="{
    tab: @js($tabInicial),
    cambiar(id) {
        this.tab = id;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        window.history.replaceState({}, '', url);
    },
    mover(event, salto, extremo = null) {
        const botones = Array.from(this.$refs.pestanas.querySelectorAll('[role=tab]'));
        const actual = botones.indexOf(event.target);
        const siguiente = extremo === null ? (actual + salto + botones.length) % botones.length : extremo;
        botones[siguiente].click();
        botones[siguiente].focus();
    }
}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('presupuesto.index') }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Volver a presupuestos</a>
        <a href="{{ route('presupuesto.pdf', $presupuesto->id) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Exportar PDF
        </a>
    </div>
    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Presupuesto sincronizado</span><h1 class="mt-1 text-2xl font-bold text-[#0B265A]">{{ $presupuesto->codigo_proyecto }}</h1><p class="mt-1 text-base text-slate-600">{{ $presupuesto->nombre_cliente }}</p></div>
            <div class="flex flex-wrap gap-6">
                <div><p class="text-xs uppercase text-slate-500">Costo directo</p><p class="mt-1 text-xl font-semibold text-slate-700">${{ number_format($presupuesto->total_costo_directo, 2) }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Total venta</p><p class="mt-1 text-xl font-bold text-emerald-700">${{ number_format($presupuesto->total_presupuesto, 2) }}</p></div>
            </div>
        </div>
    </header>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto border-b border-slate-200 bg-slate-50">
            <div role="tablist" aria-label="Secciones del presupuesto" x-ref="pestanas" class="flex min-w-max gap-1 px-3 pt-3">
                @foreach($pestanas as $id => $nombre)
                    <button type="button" role="tab" id="tab-{{ $id }}" aria-controls="panel-{{ $id }}" :aria-selected="tab === '{{ $id }}'" :tabindex="tab === '{{ $id }}' ? 0 : -1"
                            @click="cambiar('{{ $id }}')" @keydown.arrow-right.prevent="mover($event, 1)" @keydown.arrow-left.prevent="mover($event, -1)" @keydown.home.prevent="mover($event, 0, 0)" @keydown.end.prevent="mover($event, 0, 3)"
                            :class="tab === '{{ $id }}' ? 'border-[#0B265A] bg-white text-[#0B265A]' : 'border-transparent text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                            class="rounded-t-xl border-b-4 px-5 py-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0B265A]">{{ $nombre }}</button>
                @endforeach
            </div>
        </div>
        <section id="panel-pilas" role="tabpanel" aria-labelledby="tab-pilas" tabindex="0" x-show="tab === 'pilas'" @if($tabInicial !== 'pilas') x-cloak @endif>
            <div class="p-5"><h2 class="text-lg font-semibold text-slate-800">Pilas / VOL</h2><p class="mt-1 text-sm text-slate-500">Cantidades y conceptos de perforación importados del Excel.</p></div>
            @include('presupuesto.partials.tabla-escenarios', ['filas' => $presupuesto->pilas, 'esPila' => true])
        </section>
        <section id="panel-apupi" role="tabpanel" aria-labelledby="tab-apupi" tabindex="0" x-show="tab === 'apupi'" @if($tabInicial !== 'apupi') x-cloak @endif>
            <div class="p-5"><h2 class="text-lg font-semibold text-slate-800">APUPI · Análisis de costos</h2><p class="mt-1 text-sm text-slate-500">Equipo, personal, materiales y demás costos, con sus escenarios importados.</p></div>
            @include('presupuesto.partials.tabla-escenarios', ['filas' => $presupuesto->detalles, 'esPila' => false])
        </section>
        <section id="panel-presupuesto" role="tabpanel" aria-labelledby="tab-presupuesto" tabindex="0" x-show="tab === 'presupuesto'" @if($tabInicial !== 'presupuesto') x-cloak @endif>
            <div class="p-5"><h2 class="text-lg font-semibold text-slate-800">Presupuesto · Resumen de venta</h2><p class="mt-1 text-sm text-slate-500">Partidas de venta y perforación que integran la propuesta al cliente.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#0B265A] text-white"><tr><th class="px-4 py-3 text-left">Partida</th><th class="px-4 py-3 text-left">Concepto</th><th class="px-4 py-3 text-center">Unidad</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">P.U. venta</th><th class="px-4 py-3 text-right">Importe</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($presupuesto->resumenes as $res)
                            @if($res->cantidad > 0 && $res->precio_unitario > 0)
                                <tr class="hover:bg-blue-50"><td class="px-4 py-3 text-xs font-semibold text-slate-500">{{ $res->partida }}</td><td class="px-4 py-3 text-slate-700">{{ $res->concepto }}</td><td class="px-4 py-3 text-center text-slate-500">{{ $res->unidad }}</td><td class="px-4 py-3 text-right">{{ number_format($res->cantidad, 2) }}</td><td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($res->precio_unitario, 2) }}</td><td class="px-4 py-3 text-right font-semibold whitespace-nowrap">${{ number_format($res->importe, 2) }}</td></tr>
                            @endif
                        @endforeach
                        @foreach($presupuesto->pilas as $pila)
                            @if($pila->cantidad > 0 && $pila->costo > 0)
                                <tr class="hover:bg-blue-50"><td class="px-4 py-3 text-xs font-semibold text-[#0B265A]">PERFORACIÓN</td><td class="px-4 py-3 text-slate-700">{{ $pila->concepto }}</td><td class="px-4 py-3 text-center text-slate-500">{{ $pila->unidad }}</td><td class="px-4 py-3 text-right">{{ number_format($pila->cantidad, 2) }}</td><td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($pila->costo, 2) }}</td><td class="px-4 py-3 text-right font-semibold whitespace-nowrap">${{ number_format($pila->total, 2) }}</td></tr>
                            @endif
                        @endforeach
                        @if($presupuesto->resumenes->where('cantidad', '>', 0)->where('precio_unitario', '>', 0)->isEmpty() && $presupuesto->pilas->where('cantidad', '>', 0)->where('costo', '>', 0)->isEmpty())
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay conceptos de venta importados.</td></tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-slate-100"><tr><th colspan="5" class="px-4 py-4 text-right text-slate-700">Total general de venta</th><td class="px-4 py-4 text-right text-lg font-bold text-[#0B265A] whitespace-nowrap">${{ number_format($totalVenta, 2) }}</td></tr></tfoot>
                </table>
            </div>
        </section>
        <section id="panel-gastos" role="tabpanel" aria-labelledby="tab-gastos" tabindex="0" x-show="tab === 'gastos'" @if($tabInicial !== 'gastos') x-cloak @endif>
            <div class="p-5"><h2 class="text-lg font-semibold text-slate-800">Relación de gastos · Base presupuestada</h2><p class="mt-1 text-sm text-slate-500">Conceptos previstos de gasto importados del Excel.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#0B265A] text-white"><tr><th class="px-4 py-3 text-left">Partida</th><th class="px-4 py-3 text-left">Concepto</th><th class="px-4 py-3 text-center">Unidad</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">P.U.</th><th class="px-4 py-3 text-right">Monto previsto</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($gastosBase as $gasto)
                            <tr class="hover:bg-blue-50"><td class="px-4 py-3 text-xs font-semibold text-slate-500">{{ $gasto->partida }}</td><td class="px-4 py-3 text-slate-700">{{ $gasto->concepto }}</td><td class="px-4 py-3 text-center text-slate-500">{{ $gasto->unidad }}</td><td class="px-4 py-3 text-right">{{ number_format($gasto->cantidad, 2) }}</td><td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($gasto->precio_unitario, 2) }}</td><td class="px-4 py-3 text-right font-semibold whitespace-nowrap">${{ number_format($gasto->monto_programado, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Este presupuesto todavía no tiene una relación de gastos importada.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100"><tr><th colspan="5" class="px-4 py-4 text-right text-slate-700">Total de gastos previstos</th><td class="px-4 py-4 text-right font-bold text-[#0B265A] whitespace-nowrap">${{ number_format($gastosBase->sum('monto_programado'), 2) }}</td></tr></tfoot>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
