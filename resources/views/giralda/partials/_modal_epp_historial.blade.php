<div x-data="{ open: false }" class="inline-block text-right">
    <button
        type="button"
        @click="open = true"
        class="font-semibold text-[#0B265A] hover:underline {{ ($empleado->epp_entregas_count ?? $empleado->eppEntregas->count()) > 0 ? '' : 'text-slate-400' }}"
        title="Ver entregas EPP"
    >
        {{ $empleado->epp_entregas_count ?? $empleado->eppEntregas->count() }}
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.away="open = false" class="w-full max-w-5xl bg-white rounded-lg shadow-xl text-left">
            <div class="p-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-[#0B265A]">Historial EPP</h3>
                    <p class="text-xs text-slate-500">{{ $empleado->nombre_completo }}</p>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700">X</button>
            </div>

            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 sticky top-0">
                        <tr>
                            <th class="text-left p-3">Fecha</th>
                            <th class="text-left p-3">Articulo</th>
                            <th class="text-right p-3">Cantidad</th>
                            <th class="text-left p-3">Talla</th>
                            <th class="text-left p-3">Condicion</th>
                            <th class="text-left p-3">Obra</th>
                            <th class="text-left p-3">Area</th>
                            <th class="text-left p-3">Entrego</th>
                            <th class="text-left p-3">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($empleado->eppEntregas as $entrega)
                            <tr>
                                <td class="p-3">{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</td>
                                <td class="p-3 font-medium">{{ $entrega->articulo }}</td>
                                <td class="p-3 text-right">{{ number_format((float)$entrega->cantidad, 2) }}</td>
                                <td class="p-3">{{ $entrega->talla ?: '-' }}</td>
                                <td class="p-3">{{ ucfirst($entrega->condicion) }}</td>
                                <td class="p-3">
                                    {{ $entrega->obra ? trim(($entrega->obra->clave_obra ? $entrega->obra->clave_obra . ' - ' : '') . $entrega->obra->nombre) : '-' }}
                                </td>
                                <td class="p-3">{{ $entrega->area?->nombre ?? '-' }}</td>
                                <td class="p-3">{{ $entrega->entregadoPor?->name ?? '-' }}</td>
                                <td class="p-3">{{ $entrega->observaciones ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-6 text-center text-slate-400">Sin entregas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t flex justify-end">
                <button type="button" @click="open = false" class="px-4 py-2 rounded bg-slate-200">Cerrar</button>
            </div>
        </div>
    </div>
</div>
