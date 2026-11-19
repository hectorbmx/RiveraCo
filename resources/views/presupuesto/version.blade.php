@extends('layouts.admin')
@section('title', 'Presupuesto ' . $version->folio())

@section('content')
@php
    $datos = $version->datos;
    $cabecera = $datos['presupuesto'];
    $moneda = fn ($valor) => $valor === null ? '—' : '$' . number_format($valor, 2);
    $presentacion = $datos['presentacion'] ?? null;
    $venta = $presentacion
        ? \App\Support\PresentacionPresupuesto::numerar(collect($presentacion['filas']))
        : collect($datos['propuesta']['filas'])->where('cantidad', '>', 0);
    $costos = collect($datos['apupi']['filas'])->where('cantidad', '>', 0);
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('presupuesto.versiones.index', $presupuesto) }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Historial de versiones</a>
        <a href="{{ route('presupuesto.versiones.pdf', [$presupuesto, $version]) }}" target="_blank" rel="noopener"
           class="rounded-xl bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">Exportar PDF</a>
    </div>
    <header class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6">
        <div>
            <p class="text-sm font-medium text-slate-600">Versión guardada</p>
            <h1 class="mt-1 text-2xl font-bold text-[#0B265A]">Presupuesto {{ $version->folio() }}</h1>
            <p class="mt-1 text-slate-800">{{ ($datos['cliente']['nombre_comercial'] ?? null) ?: $cabecera['nombre_cliente'] }}</p>
            @if($cabecera['nombre_obra'])
                <p class="text-sm text-slate-600">{{ $cabecera['nombre_obra'] }}</p>
            @endif
            <dl class="mt-4 space-y-1 text-sm text-slate-700">
                <div class="flex gap-2"><dt class="font-medium">Folio de origen</dt><dd>{{ $cabecera['codigo_proyecto'] }}</dd></div>
                @if($version->origen)
                    <div class="flex gap-2">
                        <dt class="font-medium">Basado en</dt>
                        <dd><a href="{{ route('presupuesto.versiones.show', [$presupuesto, $version->origen]) }}" class="font-semibold text-[#0B265A] hover:underline">{{ $version->origen->folio() }}</a></dd>
                    </div>
                @endif
                <div class="flex gap-2"><dt class="font-medium">Equipo propuesto</dt><dd>{{ $cabecera['equipo'] ?? 'Por definir' }}</dd></div>
                <div class="flex gap-2"><dt class="font-medium">Guardado</dt><dd>{{ $version->created_at->format('d/m/Y H:i') }} @if($version->autor) · {{ $version->autor->name }} @endif</dd></div>
                @if($version->motivo)
                    <div class="flex gap-2"><dt class="font-medium">Motivo</dt><dd>{{ $version->motivo }}</dd></div>
                @endif
            </dl>
        </div>
        <dl class="text-right">
            @if($presentacion)
                <dt class="text-sm text-slate-600">Subtotal</dt>
                <dd>{{ $moneda($presentacion['subtotal']) }}</dd>
                <dt class="text-sm text-slate-600">IVA {{ (float) $presentacion['tasa_iva'] }} %</dt>
                <dd>{{ $moneda($presentacion['iva']) }}</dd>
            @endif
            <dt class="text-sm font-medium text-slate-600">Total de venta</dt>
            <dd class="text-2xl font-bold text-emerald-700">{{ $moneda($presentacion['total'] ?? $version->total_venta) }}</dd>
        </dl>
    </header>
    @include('presupuesto.partials.seguimiento-version')
    @if(isset($datos['relacion_gastos']))
        <details class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                 @if($errors->planeacionSemanal->any()) open @endif
                 x-data="{ relacionGastos: @js($datos['relacion_gastos']) }">
            <summary class="cursor-pointer p-5 font-semibold text-slate-800">Relación de gastos guardada</summary>
            @include('presupuesto.partials.relacion-gastos')
            @if(auth()->user()->hasAnyRole(\App\Support\PrevioPresupuesto::ADMINISTRADORES) && ($datos['relacion_gastos']['planeacion']['publicable'] ?? false))
                @php $disponibles = $obrasPlaneacion->filter(fn ($obra) => !$obra->pivot->planeacion_version_id); @endphp
                <div class="border-t border-slate-200 p-5">
                    @foreach($obrasPlaneacion->where('pivot.planeacion_version_id', $version->id) as $obra)
                        <p class="mb-2 text-sm text-emerald-700">Planeación aplicada en {{ $obra->nombre }}.</p>
                    @endforeach
                    @foreach($obrasPlaneacion->filter(fn ($obra) => $obra->pivot->planeacion_version_id && (int) $obra->pivot->planeacion_version_id !== $version->id) as $obra)
                        <p class="mb-2 text-sm text-slate-600">{{ $obra->nombre }} ya tiene otra versión aplicada.</p>
                    @endforeach
                    @if($errors->planeacionSemanal->any())
                        <p role="alert" class="mb-2 text-sm text-red-700">{{ $errors->planeacionSemanal->first() }}</p>
                    @endif
                    @if($disponibles->isNotEmpty())
                        <form method="POST" action="{{ route('presupuesto.versiones.planeacion.aplicar', [$presupuesto, $version]) }}"
                              onsubmit="return confirm('¿Aplicar la planeación de esta versión a la obra seleccionada?')"
                              class="flex flex-wrap items-end gap-3">
                            @csrf
                            <label class="text-sm font-medium">Obra vinculada
                                <select name="obra_id" required class="mt-1 block rounded-lg border-slate-300 text-sm">
                                    @foreach($disponibles as $obra)
                                        <option value="{{ $obra->id }}">{{ $obra->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Aplicar a planeación de Obra</button>
                        </form>
                    @elseif($obrasPlaneacion->isEmpty())
                        <p class="text-sm text-slate-600">Vincula el presupuesto a una obra para aplicar esta versión.</p>
                    @endif
                </div>
            @endif
        </details>
    @endif
    @if(!empty($datos['previo']))
        <details class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                 x-data="{
                     previo: @js($datos['previo']),
                     escenarioPrevio: @js($datos['previo']['escenario']),
                     formatoImporte(valor) {
                         return '$' + Number(valor).toLocaleString('en-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                     }
                 }">
            <summary class="cursor-pointer p-5 font-semibold text-slate-800">PREVIO guardado</summary>
            @include('presupuesto.partials.previo', ['previo' => $datos['previo'], 'puedeEditarPrevio' => false])
        </details>
    @endif
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-labelledby="venta-version">
        <h2 id="venta-version" class="p-5 text-lg font-semibold text-slate-800">Propuesta 1</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0B265A] text-white"><tr>@if($presentacion)<th class="px-3 py-3 text-left">Partida</th>@endif<th class="px-4 py-3 text-left">Concepto</th><th class="py-3">Unidad</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">P.U. de venta</th><th class="px-4 py-3 text-right">Importe</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($venta as $fila)
                        @if($presentacion && $fila['numero_encabezado'] !== null)
                            <tr class="bg-slate-50"><th scope="row" class="px-3 py-3 text-left align-top">{{ $fila['numero_encabezado'] }}</th><td colspan="5" class="whitespace-pre-line px-4 py-3">{{ $presentacion['descripcion_perforacion'] }}</td></tr>
                        @endif
                        <tr>
                            @if($presentacion)
                                <td class="px-3 py-3 align-top">{{ $fila['numero'] }}{{ $fila['partida'] === 'perforacion' ? ')' : '' }}</td>
                            @endif
                            <th scope="row" class="whitespace-pre-line px-4 py-3 text-left font-medium">{{ $fila['concepto'] }}</th><td class="text-center">{{ $fila['unidad'] }}</td><td class="px-4 py-3 text-right">{{ number_format($fila['cantidad'], 2) }}</td><td class="px-4 py-3 text-right">{{ $moneda($fila['precio_unitario']) }}</td><td class="px-4 py-3 text-right">{{ $moneda($fila['importe']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $presentacion ? 6 : 5 }}" class="p-5 text-center text-slate-500">Esta versión no tiene partidas de venta activas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <details class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <summary class="cursor-pointer p-5 font-semibold text-slate-800">Pilas / VOL</summary>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0B265A] text-white"><tr><th class="px-4 py-3 text-left">Tipo</th><th class="px-4 py-3 text-right">Diámetro (m)</th><th class="px-4 py-3 text-right">Piezas</th><th class="px-4 py-3 text-right">Perforación (ML)</th><th class="px-4 py-3 text-right">Concreto (m³)</th><th class="px-4 py-3 text-right">Acero (kg)</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($datos['vol']['filas'] as $fila)
                        <tr><th scope="row" class="px-4 py-3 text-left font-medium">{{ $fila['pila']['tipo'] }}</th><td class="px-4 py-3 text-right">{{ number_format($fila['pila']['diametro'], 3) }}</td><td class="px-4 py-3 text-right">{{ $fila['pila']['piezas'] }}</td><td class="px-4 py-3 text-right">{{ number_format($fila['vol']['metros_lineales'], 3) }}</td><td class="px-4 py-3 text-right">{{ number_format($fila['vol']['concreto'], 3) }}</td><td class="px-4 py-3 text-right">{{ number_format($fila['vol']['acero'], 3) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-5 text-center text-slate-500">Esta versión no tiene volumetría capturada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
    <details class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <summary class="cursor-pointer p-5 font-semibold text-slate-800">APUPI · Costos y duración</summary>
        <dl class="flex flex-wrap gap-4 px-5 pb-5 text-sm">
            @foreach($datos['duracion'] as $escenario => $duracion)
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="font-semibold capitalize">{{ $escenario }}</dt>
                    <dd>{{ $duracion['jornales'] ?? '—' }} jornales · {{ $duracion['dias'] ?? '—' }} días · {{ $duracion['semanas'] ?? '—' }} semanas</dd>
                </div>
            @endforeach
        </dl>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0B265A] text-white"><tr><th class="px-4 py-3 text-left">Partida / concepto</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">Costo unitario</th><th class="px-4 py-3 text-right">Realista</th><th class="px-4 py-3 text-right">Optimista</th><th class="px-4 py-3 text-right">Pesimista</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($costos as $fila)
                        <tr>
                            <th scope="row" class="px-4 py-3 text-left font-medium"><span class="block text-xs text-slate-500">{{ $fila['partida'] }}</span>{{ $fila['concepto'] }}</th>
                            <td class="px-4 py-3 text-right">{{ number_format($fila['cantidad'], 2) }} {{ $fila['unidad'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $moneda($fila['precio_unitario']) }}</td>
                            @foreach($fila['importes'] as $importe)
                                <td class="px-4 py-3 text-right">{{ $moneda($importe) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-5 text-center text-slate-500">Esta versión no tiene costos activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
</div>
@endsection
