@php
    $tiposEquipo = [
        'laptop' => 'Laptop',
        'desktop' => 'Desktop',
        'monitor' => 'Monitor',
        'impresora' => 'Impresora',
        'tablet' => 'Tablet',
        'otro' => 'Otro',
    ];

    $estatusEquipo = [
        'activo' => 'Activo',
        'asignado' => 'Asignado',
        'mantenimiento' => 'Mantenimiento',
        'resguardo' => 'Resguardo',
        'baja' => 'Baja',
    ];

    $estatusBadge = fn ($estatus) => match ($estatus) {
        'asignado' => 'bg-blue-100 text-blue-700',
        'mantenimiento' => 'bg-amber-100 text-amber-700',
        'resguardo' => 'bg-slate-100 text-slate-700',
        'baja' => 'bg-red-100 text-red-700',
        default => 'bg-green-100 text-green-700',
    };
@endphp

<div x-show="tab === 'equipos_computo'" x-cloak class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Equipo de computo</h2>
        <p class="text-sm text-gray-600">Inventario interno, responsables asignados y kardex de cambios.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Nuevo equipo</h3>
            <p class="text-sm text-slate-500 mt-1">Captura datos de compra, factura, ubicacion y responsable inicial.</p>
        </div>

        <form method="POST" action="{{ route('empresa_config.equipos-computo.store') }}" enctype="multipart/form-data"
              class="p-6 grid grid-cols-1 md:grid-cols-6 gap-4 bg-slate-50">
            @csrf

            <div>
                <label class="block text-xs text-slate-600 mb-1">Codigo</label>
                <input type="text" name="codigo_inventario" class="w-full rounded-xl border-slate-300" placeholder="LAP-001">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-xl border-slate-300">
                    @foreach($tiposEquipo as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Marca</label>
                <input type="text" name="marca" required class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Modelo</label>
                <input type="text" name="modelo" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Serie</label>
                <input type="text" name="numero_serie" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Precio</label>
                <input type="number" step="0.01" min="0" name="precio" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Fecha compra</label>
                <input type="date" name="fecha_compra" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Factura folio</label>
                <input type="text" name="factura_folio" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Factura UUID</label>
                <input type="text" name="factura_uuid" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Factura archivo</label>
                <input type="file" name="factura_archivo" accept=".pdf,.xml,.jpg,.jpeg,.png" class="w-full text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Ubicacion</label>
                <input type="text" name="ubicacion" class="w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Area</label>
                <select name="area_id" class="w-full rounded-xl border-slate-300">
                    <option value="">Sin area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Responsable</label>
                <select name="responsable_actual_id" class="w-full rounded-xl border-slate-300">
                    <option value="">Sin asignar</option>
                    @foreach($empleadosResponsables as $empleado)
                        <option value="{{ $empleado->id_Empleado }}">{{ $empleado->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Estatus</label>
                <select name="estatus" class="w-full rounded-xl border-slate-300">
                    @foreach($estatusEquipo as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-slate-600 mb-1">Notas</label>
                <input type="text" name="notas" class="w-full rounded-xl border-slate-300" placeholder="Cargador, condicion, accesorios, etc.">
            </div>
            <div class="md:col-span-6 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
                    Guardar equipo
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Inventario de computo</h3>
            <p class="text-sm text-slate-500 mt-1">{{ $equiposComputo->count() }} equipo(s) registrados.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Equipo</th>
                        <th class="px-4 py-3 text-left font-semibold">Serie</th>
                        <th class="px-4 py-3 text-left font-semibold">Responsable</th>
                        <th class="px-4 py-3 text-left font-semibold">Ubicacion</th>
                        <th class="px-4 py-3 text-left font-semibold">Factura</th>
                        <th class="px-4 py-3 text-left font-semibold">Estatus</th>
                        <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($equiposComputo as $equipo)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $equipo->codigo_inventario ?: 'Sin codigo' }}</div>
                            <div class="text-xs text-slate-500">{{ ucfirst($equipo->tipo) }} | {{ $equipo->nombre }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $equipo->numero_serie ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $equipo->responsableActual?->nombre_completo ?: 'Sin asignar' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            <div>{{ $equipo->ubicacion ?: '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $equipo->area?->nombre ?: 'Sin area' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <div>{{ $equipo->factura_folio ?: ($equipo->factura_uuid ?: '-') }}</div>
                            @if($equipo->factura_path)
                                <a href="{{ asset('storage/'.$equipo->factura_path) }}" target="_blank" class="text-xs text-blue-600 hover:underline">Ver archivo</a>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs {{ $estatusBadge($equipo->estatus) }}">
                                {{ $estatusEquipo[$equipo->estatus] ?? ucfirst($equipo->estatus) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <details class="text-left">
                                <summary class="inline-flex cursor-pointer px-3 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-800 hover:bg-slate-200">
                                    Gestionar
                                </summary>

                                <div class="mt-4 p-4 rounded-xl border border-slate-200 bg-white space-y-5 min-w-[760px]">
                                    <form method="POST" action="{{ route('empresa_config.equipos-computo.update', $equipo) }}" enctype="multipart/form-data"
                                          class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                        @csrf
                                        @method('PUT')

                                        <input type="text" name="codigo_inventario" value="{{ $equipo->codigo_inventario }}" class="rounded-lg border-slate-300 text-sm" placeholder="Codigo">
                                        <select name="tipo" class="rounded-lg border-slate-300 text-sm">
                                            @foreach($tiposEquipo as $value => $label)
                                                <option value="{{ $value }}" @selected($equipo->tipo === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="marca" value="{{ $equipo->marca }}" required class="rounded-lg border-slate-300 text-sm" placeholder="Marca">
                                        <input type="text" name="modelo" value="{{ $equipo->modelo }}" class="rounded-lg border-slate-300 text-sm" placeholder="Modelo">
                                        <input type="text" name="numero_serie" value="{{ $equipo->numero_serie }}" class="rounded-lg border-slate-300 text-sm" placeholder="Serie">
                                        <input type="number" step="0.01" min="0" name="precio" value="{{ $equipo->precio }}" class="rounded-lg border-slate-300 text-sm" placeholder="Precio">
                                        <input type="date" name="fecha_compra" value="{{ optional($equipo->fecha_compra)->format('Y-m-d') }}" class="rounded-lg border-slate-300 text-sm">
                                        <input type="text" name="factura_folio" value="{{ $equipo->factura_folio }}" class="rounded-lg border-slate-300 text-sm" placeholder="Factura folio">
                                        <input type="text" name="factura_uuid" value="{{ $equipo->factura_uuid }}" class="rounded-lg border-slate-300 text-sm" placeholder="Factura UUID">
                                        <input type="file" name="factura_archivo" accept=".pdf,.xml,.jpg,.jpeg,.png" class="text-xs">
                                        <input type="text" name="ubicacion" value="{{ $equipo->ubicacion }}" class="rounded-lg border-slate-300 text-sm" placeholder="Ubicacion">
                                        <select name="area_id" class="rounded-lg border-slate-300 text-sm">
                                            <option value="">Sin area</option>
                                            @foreach($areas as $area)
                                                <option value="{{ $area->id }}" @selected((int) $equipo->area_id === (int) $area->id)>{{ $area->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <select name="responsable_actual_id" class="md:col-span-2 rounded-lg border-slate-300 text-sm">
                                            <option value="">Sin asignar</option>
                                            @foreach($empleadosResponsables as $empleado)
                                                <option value="{{ $empleado->id_Empleado }}" @selected((int) $equipo->responsable_actual_id === (int) $empleado->id_Empleado)>{{ $empleado->nombre_completo }}</option>
                                            @endforeach
                                        </select>
                                        <select name="estatus" class="rounded-lg border-slate-300 text-sm">
                                            @foreach($estatusEquipo as $value => $label)
                                                <option value="{{ $value }}" @selected($equipo->estatus === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="notas" value="{{ $equipo->notas }}" class="md:col-span-2 rounded-lg border-slate-300 text-sm" placeholder="Notas">
                                        <input type="text" name="movimiento_notas" class="md:col-span-3 rounded-lg border-slate-300 text-sm" placeholder="Nota para kardex si hubo cambio">
                                        <div class="md:col-span-6 flex justify-end">
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar cambios</button>
                                        </div>
                                    </form>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <form method="POST" action="{{ route('empresa_config.equipos-computo.asignar', $equipo) }}" class="rounded-xl border border-slate-200 p-4 space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <h4 class="text-sm font-semibold text-slate-900">Cambiar responsable</h4>
                                            <select name="responsable_actual_id" required class="w-full rounded-lg border-slate-300 text-sm">
                                                @foreach($empleadosResponsables as $empleado)
                                                    <option value="{{ $empleado->id_Empleado }}" @selected((int) $equipo->responsable_actual_id === (int) $empleado->id_Empleado)>{{ $empleado->nombre_completo }}</option>
                                                @endforeach
                                            </select>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                <input type="date" name="fecha_movimiento" value="{{ now()->toDateString() }}" required class="rounded-lg border-slate-300 text-sm">
                                                <input type="text" name="ubicacion" value="{{ $equipo->ubicacion }}" class="rounded-lg border-slate-300 text-sm" placeholder="Ubicacion">
                                                <select name="area_id" class="rounded-lg border-slate-300 text-sm">
                                                    <option value="">Sin area</option>
                                                    @foreach($areas as $area)
                                                        <option value="{{ $area->id }}" @selected((int) $equipo->area_id === (int) $area->id)>{{ $area->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <textarea name="notas" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Notas"></textarea>
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-700 text-white text-sm hover:bg-blue-800">Registrar asignacion</button>
                                        </form>

                                        @if($equipo->estatus !== 'baja')
                                            <form method="POST" action="{{ route('empresa_config.equipos-computo.baja', $equipo) }}"
                                                  onsubmit="return confirm('El equipo se marcara como baja sin borrar el historial. Continuar?')"
                                                  class="rounded-xl border border-red-200 bg-red-50 p-4 space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <h4 class="text-sm font-semibold text-red-900">Baja logica</h4>
                                                <input type="date" name="fecha_movimiento" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border-red-200 text-sm">
                                                <textarea name="notas" rows="3" class="w-full rounded-lg border-red-200 text-sm" placeholder="Motivo"></textarea>
                                                <button type="submit" class="px-4 py-2 rounded-lg bg-red-700 text-white text-sm hover:bg-red-800">Marcar como baja</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                                        <div class="px-4 py-3 bg-slate-50 font-semibold text-sm text-slate-900">Kardex</div>
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-white text-slate-500">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Fecha</th>
                                                    <th class="px-3 py-2 text-left">Movimiento</th>
                                                    <th class="px-3 py-2 text-left">Responsable</th>
                                                    <th class="px-3 py-2 text-left">Ubicacion</th>
                                                    <th class="px-3 py-2 text-left">Estatus</th>
                                                    <th class="px-3 py-2 text-left">Notas</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @forelse($equipo->movimientos as $mov)
                                                    <tr>
                                                        <td class="px-3 py-2 whitespace-nowrap">{{ optional($mov->fecha_movimiento)->format('d/m/Y') }}</td>
                                                        <td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($mov->tipo)) }}</td>
                                                        <td class="px-3 py-2">{{ $mov->responsableAnterior?->nombre_completo ?: '-' }} <span class="text-slate-400">a</span> {{ $mov->responsableNuevo?->nombre_completo ?: '-' }}</td>
                                                        <td class="px-3 py-2">{{ $mov->ubicacion_anterior ?: '-' }} <span class="text-slate-400">a</span> {{ $mov->ubicacion_nueva ?: '-' }}</td>
                                                        <td class="px-3 py-2">{{ $mov->estatus_anterior ?: '-' }} <span class="text-slate-400">a</span> {{ $mov->estatus_nuevo ?: '-' }}</td>
                                                        <td class="px-3 py-2">{{ $mov->notas ?: '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="px-3 py-6 text-center text-slate-500">Sin movimientos registrados.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">No hay equipos de computo registrados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
