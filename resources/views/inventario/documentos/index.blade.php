@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Inventario — Documentos</h1>
            <p class="text-sm text-slate-500">Entradas/Salidas, estado y acciones (aplicar/cancelar).</p>
        </div>
        <a href="{{ route('inventario.stock.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 text-sm bg-white">
            Ver Stock
        </a>
    </div>

    @if(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Almacén</label>
                <select name="almacen_id" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Todos</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}" @selected((int)$almacenId === (int)$a->id)>{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">Tipo</label>
                <select name="tipo" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Todos</option>
                    @foreach($tipos as $k=>$label)
                        <option value="{{ $k }}" @selected($tipo===$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">Estado</label>
                <select name="estado" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Todos</option>
                    @foreach($estados as $k=>$label)
                        <option value="{{ $k }}" @selected($estado===$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Buscar (folio / ref / obs)</label>
                <div class="mt-1 flex gap-2">
                    <input name="q" value="{{ $q }}" class="w-full rounded-xl border-slate-300" placeholder="Ej. OC-123, REM-44...">
                    <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm">Filtrar</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-medium px-4 py-3">ID</th>
                        <th class="text-left font-medium px-4 py-3">Fecha</th>
                        <th class="text-left font-medium px-4 py-3">Almacén</th>
                        <th class="text-left font-medium px-4 py-3">Tipo</th>
                        <th class="text-left font-medium px-4 py-3">Estado</th>
                        <th class="text-left font-medium px-4 py-3">Folio/Ref</th>
                        <th class="text-right font-medium px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($docs as $d)
                        @php
                            $isAplicado = $d->estado === 'aplicado';
                            $isCancelado = $d->estado === 'cancelado';
                            $isBorrador = $d->estado === 'borrador';

                            $badge = match($d->estado) {
                                'aplicado' => 'bg-emerald-100 text-emerald-800',
                                'cancelado' => 'bg-rose-100 text-rose-800',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-slate-800">#{{ $d->id }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ optional($d->fecha)->format('Y-m-d') ?? ($d->created_at?->format('Y-m-d') ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $d->almacen?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $tipos[$d->tipo] ?? $d->tipo }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs {{ $badge }}">
                                    {{ $estados[$d->estado] ?? $d->estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="font-medium text-slate-900">{{ $d->folio ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $d->referencia ?? '' }}</div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('inventario.documentos.show', $d) }}"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs">
                                        Ver
                                    </a>

                                    {{-- Aplicar --}}
                                    @if($isBorrador)
                                        <form method="POST" action="{{ route('inventario.documentos.aplicar', $d) }}"
                                              onsubmit="return confirm('¿Aplicar documento #{{ $d->id }}? Esto afectará stock y kardex.');">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs">
                                                Aplicar
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Cancelar (solo si ya aplicado y no cancelado) --}}
                                    @if($isAplicado && !$isCancelado)
                                        <form method="POST" action="{{ route('inventario.documentos.cancelar', $d) }}"
                                              onsubmit="return confirm('¿Cancelar documento #{{ $d->id }}? Se generará reversa (cancelación).');">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Sin documentos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200">
            {{ $docs->links() }}
        </div>
    </div>

</div>
@endsection
