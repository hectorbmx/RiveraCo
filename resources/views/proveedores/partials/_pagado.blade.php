@php
    $estadoClases = [
        'programado' => 'bg-amber-100 text-amber-800 border-amber-200',
        'autorizado' => 'bg-blue-100 text-blue-800 border-blue-200',
        'pagado' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'cancelado' => 'bg-red-100 text-red-800 border-red-200',
    ];
@endphp

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-lg font-semibold text-[#0B265A]">Historial de pagos del proveedor</h2>
        <p class="text-sm text-slate-500">Pagos relacionados con sus órdenes de compra.</p>
    </div>
</div>

<form method="GET" class="grid grid-cols-1 gap-3 mb-4 md:grid-cols-4">
    <input type="hidden" name="tab" value="pagado">

    <div>
        <label class="block mb-1 text-xs font-semibold text-slate-600">Estatus</label>
        <select name="estatus" class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
            <option value="">Todos</option>
            @foreach(['programado' => 'Programado', 'autorizado' => 'Autorizado', 'pagado' => 'Pagado', 'cancelado' => 'Cancelado'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(request('estatus') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block mb-1 text-xs font-semibold text-slate-600">Programado desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}"
               class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
    </div>

    <div>
        <label class="block mb-1 text-xs font-semibold text-slate-600">Programado hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}"
               class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
    </div>

    <div class="flex items-end gap-2">
        <button class="rounded-lg bg-[#FFC107] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:opacity-90">
            Filtrar
        </button>
        <a href="{{ route('proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'pagado']) }}"
           class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            Limpiar
        </a>
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="min-w-full text-sm">
        <thead class="border-b border-[#0B265A] bg-[#0B265A]">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Orden</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Programado</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Ejecutado</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Estatus</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-white">Monto</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Método / referencia</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Trazabilidad</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-white">Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($pagosProveedor as $pago)
                @php($orden = $pago->ordenCompra)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-800">{{ $orden?->folio ?? ('OC-' . ($orden?->id ?? $pago->orden_compra_id)) }}</div>
                        <div class="text-xs text-slate-500">{{ $orden?->obra?->nombre ?? 'Compra general' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        {{ optional($pago->fecha_programada)->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        {{ optional($pago->fecha_pago)->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $estadoClases[$pago->estatus] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            {{ ucfirst($pago->estatus ?? '—') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        {{ '$' . number_format((float) $pago->monto, 2) }} {{ $pago->moneda }}
                    </td>
                    <td class="px-4 py-3">
                        <div>{{ $pago->metodo_pago ?: '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $pago->referencia ?: 'Sin referencia' }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        <div>Programó: {{ $pago->programadoPor?->name ?? '—' }}</div>
                        <div>Ejecutó: {{ $pago->pagadoPor?->name ?? '—' }}</div>
                        @if($pago->pagado_at)
                            <div>{{ $pago->pagado_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($orden)
                            <a class="text-blue-600 hover:underline"
                               href="{{ route('ordenes_compra.edit', $orden->id) }}">
                                Abrir
                            </a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                        No hay pagos para este proveedor con los filtros actuales.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($pagosProveedor && $pagosProveedor->hasPages())
    <div class="mt-4">
        {{ $pagosProveedor->links() }}
    </div>
@endif