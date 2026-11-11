@extends('layouts.admin')
@section('title', 'Registrar recepción física')
@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-6">
    <div><h1 class="text-2xl font-semibold text-slate-900">Registrar recepción física</h1>
        <p class="text-sm text-slate-600">{{ $solicitud->folio }} · {{ $solicitud->obra?->nombre }}</p></div>
    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Esta captura registra la recepción física. Quedará pendiente de aplicación: todavía no crea una entrada al inventario ni reduce el costo de la obra. Las condiciones diferentes se conservarán para aclaración.</div>
    @if($errors->any())<div class="rounded-lg bg-red-50 p-4 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('inventario.devoluciones-obra.store', $solicitud) }}" class="space-y-5 rounded-xl border bg-white p-5">
        @csrf
        <input type="hidden" name="operacion_uuid" value="{{ old('operacion_uuid', $operacionUuid) }}">
        <div class="grid gap-4 md:grid-cols-2">
            <label>Almacén que recibe <select name="almacen_id" required class="mt-1 w-full rounded-lg border-slate-300"><option value="">Selecciona un almacén</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->id }}" @selected(old('almacen_id') == $almacen->id)>{{ $almacen->nombre }}</option>@endforeach</select></label>
            <label>Fecha de recepción <input type="date" name="fecha_recepcion" required max="{{ now()->toDateString() }}" value="{{ old('fecha_recepcion', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
        </div>
        <p class="text-sm text-slate-600">Captura únicamente las partidas recibidas en esta ocasión. El resto seguirá pendiente.</p>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="bg-slate-50 text-left"><th class="p-3">Material y condición declarada</th><th class="p-3">Pendiente</th><th class="p-3">Cantidad recibida</th><th class="p-3">Condición recibida</th><th class="p-3">Observaciones</th></tr></thead><tbody>
            @foreach($solicitud->detalles as $partida)
                <tr class="border-t align-top"><td class="p-3"><strong>{{ $partida->descripcion }}</strong><br><span class="text-slate-500">{{ ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'][$partida->condicion_declarada] ?? 'Sin definir' }}</span></td>
                    <td class="p-3">{{ $partida->consulta_pendiente === null ? 'Requiere revisión' : number_format($partida->consulta_pendiente, 3).' '.$partida->unidad }}</td>
                    <td class="p-3"><input type="hidden" name="partidas[{{ $partida->id }}][id]" value="{{ $partida->id }}"><input aria-label="Cantidad recibida: {{ $partida->descripcion }}" type="number" step="0.001" min="0.001" @if($partida->consulta_pendiente !== null) max="{{ $partida->consulta_pendiente }}" @endif @disabled($partida->consulta_pendiente === null || $partida->consulta_pendiente <= 0) name="partidas[{{ $partida->id }}][cantidad]" value="{{ old('partidas.'.$partida->id.'.cantidad') }}" class="w-32 rounded-lg border-slate-300"></td>
                    <td class="p-3"><select aria-label="Condición recibida: {{ $partida->descripcion }}" name="partidas[{{ $partida->id }}][condicion]" class="rounded-lg border-slate-300"><option value="">Selecciona</option>@foreach(['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('partidas.'.$partida->id.'.condicion') === $valor)>{{ $etiqueta }}</option>@endforeach</select></td>
                    <td class="p-3"><input aria-label="Observaciones: {{ $partida->descripcion }}" name="partidas[{{ $partida->id }}][observaciones]" maxlength="2000" value="{{ old('partidas.'.$partida->id.'.observaciones') }}" class="rounded-lg border-slate-300"></td></tr>
            @endforeach
        </tbody></table></div>
        <label class="block">Observaciones generales <textarea name="observaciones" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300">{{ old('observaciones') }}</textarea></label>
        <div class="flex gap-3"><button class="rounded-lg bg-[#0B265A] px-4 py-2 font-semibold text-white">Confirmar recepción física</button><a href="{{ route('inventario.documentos.index', ['tipo' => 'devolucion', 'obra_material_id' => $solicitud->obra_id]) }}" class="rounded-lg border px-4 py-2">Cancelar</a></div>
    </form>
</div>
@endsection
