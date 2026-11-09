@php($requisicionOrigen = $requisicionDetalle?->requisicion)
@if($requisicionOrigen)
    <a class="text-blue-800 hover:underline" href="{{ route('obras.requisiciones.show', [$requisicionOrigen->obra_id, $requisicionOrigen->id]) }}">
        {{ $requisicionOrigen->folio ?: 'Requisición #'.$requisicionOrigen->id }} · Partida #{{ $requisicionDetalle->id }}
    </a>
@else
    <span class="text-slate-500">Sin requisición vinculada</span>
@endif
