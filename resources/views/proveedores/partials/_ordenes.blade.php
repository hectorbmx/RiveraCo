<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-[#0B265A]">Órdenes de compra</h2>
</div>

<form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <input type="hidden" name="tab" value="ordenes">

    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Estado (legacy)</label>
        <input type="text" name="estado" value="{{ request('estado') }}"
               placeholder="programada/autorizada/cancelada o legacy"
               class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}"
               class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}"
               class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
    </div>

    <div class="flex items-end gap-2">
        <button class="bg-[#FFC107] text-[#0B265A] px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90">
            Filtrar
        </button>
        <a href="{{ route('proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'ordenes']) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold border border-slate-200 text-slate-600 hover:text-slate-900">
            Limpiar
        </a>
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="min-w-full text-sm">
        <thead class="bg-[#0B265A] border-b border-[#0B265A]">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Folio</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Obra</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-white">Área</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Fecha</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Estado</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Pago programado</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-white">Pago ejecutado</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-white">Total</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-white">Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($ordenes as $oc)
                @php($pagoActivo = $oc->pagosProveedor->first())
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-2 font-semibold">{{ $oc->folio ?? ('OC-' . $oc->id) }}</td>
                    <td class="px-4 py-2">{{ $oc->obra->nombre ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $oc->areaCatalogo->nombre ?? ($oc->area ?? '-') }}</td>
                    <td class="px-4 py-2 text-center">{{ $oc->fecha }}</td>
                    <td class="px-4 py-2 text-center capitalize">{{ $oc->estado_normalizado ?? $oc->estado }}</td>
                    <td class="px-4 py-2 text-center">
                        {{ $pagoActivo?->fecha_programada?->format('d/m/Y') ?? 'Sin programar' }}
                    </td>
                    <td class="px-4 py-2 text-center">
                        {{ $pagoActivo?->fecha_pago?->format('d/m/Y') ?? 'No ejecutado' }}
                    </td>
                    <td class="px-4 py-2 text-right">${{ number_format((float)$oc->total, 2) }}</td>
                    <td class="px-4 py-2 text-right">
                        <a class="text-blue-600 hover:underline"
                           href="{{ route('ordenes_compra.edit', $oc->id) }}">
                            Abrir
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-6 text-center text-slate-500">
                        No hay órdenes para este proveedor con los filtros actuales.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($ordenes && $ordenes->hasPages())
    <div class="mt-4">
        {{ $ordenes->links() }}
    </div>
@endif
