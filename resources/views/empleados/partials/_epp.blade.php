<div class="max-w-7xl mx-auto space-y-6">
    {{-- ENCABEZADO DE SECCIÓN --}}
    <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <span>Entrega de Equipo de Protección Personal (EPP)</span>
        </h2>
        <p class="text-xs text-slate-500 mt-1">Historial de botas, cascos, chalecos, guantes, lentes y otros equipos configurables.</p>
    </div>

    {{-- FORMULARIO DE REGISTRO DE EPP --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
            <div class="w-2 h-5 bg-blue-600 rounded-full"></div>
            <h3 class="text-base font-bold text-slate-800">Registrar nueva entrega</h3>
        </div>

        <form method="POST" action="{{ route('empleados.epp.store', $empleado->id_Empleado) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            
            <div>
                <label class="block text-xs font-medium text-slate-700">Artículo</label>
                <input name="articulo" list="articulos_epp" 
                       placeholder="Ej. Botas, Casco..."
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
                <datalist id="articulos_epp">
                    <option value="Botas">
                    <option value="Casco">
                    <option value="Chaleco reflejante">
                    <option value="Guantes">
                    <option value="Lentes">
                </datalist>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700">Cantidad</label>
                <input type="number" step="0.01" min="0.01" name="cantidad" value="1" 
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700">Talla</label>
                <input name="talla" placeholder="Ej. L, M, 27" 
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700">Fecha de entrega</label>
                <input type="date" name="fecha_entrega" value="{{ now()->toDateString() }}" 
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700">Condición</label>
                <select name="condicion" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
                    <option value="nuevo">Nuevo</option>
                    <option value="bueno">Bueno</option>
                    <option value="reposicion">Reposición</option>
                    <option value="usado">Usado</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700">Área</label>
                <select name="area_id" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    <option value="">Sin área</option>
                    @foreach(($areas ?? collect()) as $area)
                        <option value="{{ $area->id }}" @selected((int)($empleado->Area ?? 0) === (int)$area->id)>
                            {{ $area->codigo ? $area->codigo . ' - ' : '' }}{{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-700">Obra</label>
                <select name="obra_id" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    <option value="">Sin obra específica</option>
                    @foreach(($obrasActivas ?? collect()) as $obra)
                        <option value="{{ $obra->id }}">
                            {{ $obra->clave_obra ? $obra->clave_obra . ' - ' : '' }}{{ $obra->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4">
                <label class="block text-xs font-medium text-slate-700">Observaciones</label>
                <input name="observaciones" placeholder="Detalles sobre la entrega, marca o modelo..." 
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
            </div>

            <div class="md:col-span-4 flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#FFC107] text-[#0B265A] text-xs font-bold shadow-md hover:bg-[#e0ac05] active:scale-[0.98] transition-all">
                    Registrar entrega
                </button>
            </div>
        </form>
    </div>

    {{-- TABLA HISTORIAL DE ENTREGAS --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50/50 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-700">Historial de entregas</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80">
                        <th class="p-3.5">Fecha</th>
                        <th class="p-3.5">Artículo</th>
                        <th class="p-3.5 text-right">Cantidad</th>
                        <th class="p-3.5">Talla</th>
                        <th class="p-3.5">Condición</th>
                        <th class="p-3.5">Obra</th>
                        <th class="p-3.5">Área</th>
                        <th class="p-3.5">Entregado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($entregas as $entrega)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-3.5 whitespace-nowrap font-medium text-slate-800">
                                {{ optional($entrega->fecha_entrega)->format('d/m/Y') }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-800">{{ $entrega->articulo }}</td>
                            <td class="p-3.5 text-right font-mono font-medium">{{ number_format((float)$entrega->cantidad, 2) }}</td>
                            <td class="p-3.5">{{ $entrega->talla ?: '-' }}</td>
                            <td class="p-3.5">
                                @php
                                    $condicionColor = match(strtolower($entrega->condicion)) {
                                        'nuevo' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'bueno' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'reposicion' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-100 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $condicionColor }}">
                                    {{ ucfirst($entrega->condicion) }}
                                </span>
                            </td>
                            <td class="p-3.5">{{ $entrega->obra ? trim(($entrega->obra->clave_obra ? $entrega->obra->clave_obra . ' - ' : '') . $entrega->obra->nombre) : '-' }}</td>
                            <td class="p-3.5">{{ $entrega->area?->nombre ?? '-' }}</td>
                            <td class="p-3.5 text-slate-500">{{ $entrega->entregadoPor?->name ?? '-' }}</td>
                        </tr>
                        @if($entrega->observaciones)
                            <tr class="bg-slate-50/30">
                                <td></td>
                                <td colspan="7" class="px-3.5 pb-3 text-[11px] text-slate-500 italic">
                                    <span class="font-semibold text-slate-600">Obs:</span> {{ $entrega->observaciones }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                                Sin entregas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>