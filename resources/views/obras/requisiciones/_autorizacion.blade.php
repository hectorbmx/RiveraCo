@php
    $estadosAutorizables = ['pendiente_revision', 'autorizada', 'en_surtido', 'surtida_parcial', 'surtida'];
    $pendientesAutorizacion = $requisicion->detalles->contains(fn ($partida) => !in_array($partida->estado, ['cancelada', 'rechazada'], true) && (float) $partida->cantidad_solicitada > (float) $partida->cantidad_autorizada);
    $puedeAutorizar = auth()->user()?->can('requisiciones.autorizar.access') && $requisicion->solicitado_por && (int) auth()->id() !== (int) $requisicion->solicitado_por && in_array($requisicion->estado, $estadosAutorizables, true) && $pendientesAutorizacion;
    $historialAutorizaciones = \Illuminate\Support\Facades\DB::table('obra_requisicion_autorizaciones as a')
        ->join('users as u', 'u.id', '=', 'a.usuario_id')
        ->join('obra_requisicion_autorizacion_detalles as d', 'd.autorizacion_id', '=', 'a.id')
        ->join('obra_requisicion_detalles as p', 'p.id', '=', 'd.requisicion_detalle_id')
        ->where('a.obra_requisicion_id', $requisicion->id)
        ->orderByDesc('a.id')->orderBy('p.id')
        ->get(['a.created_at', 'u.name as usuario', 'p.descripcion', 'd.cantidad_anterior', 'd.cantidad_nueva']);
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-bold text-[#0B265A]">Autorización de partidas</h2>
    <p class="mt-1 text-sm text-slate-600">Indica la cantidad total autorizada por partida. Lo restante queda pendiente; autorizar no reserva ni surte material.</p>
    @if($requisicion->estado !== 'pendiente_revision' && $pendientesAutorizacion)
        <p class="mt-2 font-semibold text-amber-700">Autorización parcial: hay cantidades pendientes de revisión.</p>
    @endif
    @if(auth()->user()?->can('requisiciones.autorizar.access') && (int) auth()->id() === (int) $requisicion->solicitado_por)
        <p class="mt-2 text-sm text-amber-700">La persona solicitante no puede autorizar su propia requisición.</p>
    @endif
    @if($puedeAutorizar)<form method="POST" action="{{ route('obras.requisiciones.autorizar', [$obra, $requisicion]) }}">@csrf @endif
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead><tr class="border-b text-slate-600"><th class="p-2">Partida</th><th class="p-2">Solicitado</th><th class="p-2">Autorizado</th><th class="p-2">Pendiente</th><th class="p-2">Surtido</th>@if($puedeAutorizar)<th class="p-2">Total a autorizar</th>@endif</tr></thead>
            <tbody>
            @foreach($requisicion->detalles as $partida)
                <tr class="border-b"><td class="p-2">{{ $partida->descripcion }}</td><td class="p-2">{{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }}</td><td class="p-2">{{ number_format((float) $partida->cantidad_autorizada, 3) }}</td><td class="p-2">{{ number_format(max(0, (float) $partida->cantidad_solicitada - (float) $partida->cantidad_autorizada), 3) }}</td><td class="p-2">{{ number_format((float) $partida->cantidad_surtida, 3) }}</td>
                @if($puedeAutorizar)<td class="p-2">@if(!in_array($partida->estado, ['cancelada', 'rechazada'], true))<input class="w-28 rounded border border-slate-300" type="number" name="cantidades[{{ $partida->id }}]" min="{{ (float) $partida->cantidad_autorizada }}" max="{{ (float) $partida->cantidad_solicitada }}" step="0.001" required value="{{ old('cantidades.'.$partida->id, (float) $partida->cantidad_autorizada) }}" aria-label="Total a autorizar para {{ $partida->descripcion }}">@else — @endif</td>@endif</tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($puedeAutorizar)<div class="mt-4 flex justify-end"><button class="rounded-xl bg-[#0B265A] px-4 py-2 font-semibold text-white" type="submit">Registrar autorización</button></div></form>@endif
    @if($historialAutorizaciones->isNotEmpty())
        <h3 class="mt-6 font-semibold text-slate-800">Historial de autorizaciones</h3>
        <div class="mt-2 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">Fecha</th><th class="p-2">Autorizó</th><th class="p-2">Partida</th><th class="p-2">Antes</th><th class="p-2">Total autorizado</th></tr></thead><tbody>
        @foreach($historialAutorizaciones as $registro)<tr class="border-b"><td class="p-2">{{ \Carbon\Carbon::parse($registro->created_at)->format('d/m/Y H:i') }}</td><td class="p-2">{{ $registro->usuario }}</td><td class="p-2">{{ $registro->descripcion }}</td><td class="p-2">{{ number_format($registro->cantidad_anterior, 3) }}</td><td class="p-2">{{ number_format($registro->cantidad_nueva, 3) }}</td></tr>@endforeach
        </tbody></table></div>
    @endif
</section>
