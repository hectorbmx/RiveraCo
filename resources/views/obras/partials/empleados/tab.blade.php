    <h2 class="text-lg font-semibold mb-4">Empleados asignados a la obra</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    @php
        $resumenSueldo = $sueldoObraResumen ?? [
            'activos' => 0,
            'historico' => 0,
            'total' => 0,
            'sin_tipo_sueldo' => 0,
        ];
    @endphp

    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase text-slate-500">Sueldo activos</p>
            <p class="mt-1 text-xl font-bold text-[#0B265A]">${{ number_format((float) ($resumenSueldo['activos'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase text-slate-500">Sueldo historico</p>
            <p class="mt-1 text-xl font-bold text-[#0B265A]">${{ number_format((float) ($resumenSueldo['historico'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase text-slate-500">Total estimado</p>
            <p class="mt-1 text-xl font-bold text-[#0B265A]">${{ number_format((float) ($resumenSueldo['total'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border {{ ($resumenSueldo['sin_tipo_sueldo'] ?? 0) > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-4">
            <p class="text-xs font-semibold uppercase {{ ($resumenSueldo['sin_tipo_sueldo'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-500' }}">Sin tipo de sueldo</p>
            <p class="mt-1 text-xl font-bold {{ ($resumenSueldo['sin_tipo_sueldo'] ?? 0) > 0 ? 'text-amber-700' : 'text-[#0B265A]' }}">{{ $resumenSueldo['sin_tipo_sueldo'] ?? 0 }}</p>
        </div>
    </div>
    {{-- FORM PARA ASIGNAR NUEVO EMPLEADO --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Asignar empleado a esta obra</h3>

        @if($empleadosAsignables->isEmpty())
            <p class="text-sm text-slate-500">
                No hay empleados disponibles sin asignación activa.
            </p>
        @else
            <form id="form-asignar-empleado"
                  method="POST"
                  action="{{ route('obras.empleados.store', $obra) }}"
                  class="bg-white border rounded-xl p-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2 items-end">
                    {{-- Buscar empleado --}}
                    <div class="relative min-w-0">
                        <label class="block text-[10px] font-semibold text-slate-600 mb-1">
                            Buscar empleado
                        </label>

                        <input type="text"
                               id="buscador-empleado"
                               autocomplete="off"
                               placeholder="Escribe apellido o nombre"
                               class="w-full rounded-xl border-slate-200 text-xs px-2.5 py-1.5">

                        <input type="hidden"
                               name="empleado_id"
                               id="empleado_id"
                               value="{{ old('empleado_id') }}">

                        <div id="resultados-empleado"
                             class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow text-sm max-h-60 overflow-y-auto hidden">
                        </div>

                        @error('empleado_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Rol en la obra --}}
                    <div class="min-w-0">
                        <label class="block text-[10px] font-semibold text-slate-600 mb-1">
                            Puesto en la obra
                        </label>

                        <select name="rol_id"
                                class="w-full rounded-xl border-slate-200 text-xs px-2.5 py-1.5">
                            <option value="">Selecciona...</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" @selected(old('rol_id') == $rol->id)>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('rol_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fecha de alta --}}
                    <div class="min-w-0">
                        <label class="block text-[10px] font-semibold text-slate-600 mb-1">
                            Fecha alta
                        </label>
                        <input type="date"
                               name="fecha_alta"
                               value="{{ old('fecha_alta', now()->toDateString()) }}"
                               class="w-full rounded-xl border-slate-200 text-xs px-2.5 py-1.5">

                        @error('fecha_alta')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notas --}}
                    <div class="min-w-0">
                        <label class="block text-[10px] font-semibold text-slate-600 mb-1">
                            Notas
                        </label>
                        <input type="text"
                               name="notas"
                               value="{{ old('notas') }}"
                               class="w-full rounded-xl border-slate-200 text-xs px-2.5 py-1.5">

                        @error('notas')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Botón --}}
                    <div class="flex md:justify-end min-w-0">
                        <button type="submit"
                                class="w-full md:w-auto px-3 py-1.5 bg-teal-600 text-white text-xs rounded-xl hover:bg-teal-700 whitespace-nowrap">
                            Asignar empleado
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- LISTA DE ASIGNACIONES ACTIVAS --}}
    <div>
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Empleados actualmente en la obra</h3>

        <div class="border rounded-xl overflow-x-auto bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b text-slate-500">
                        <th class="py-2 px-3 text-left">Empleado</th>
                        <th class="py-2 px-3 text-left">Puesto</th>
                        <th class="py-2 px-3 text-left">Alta</th>
                        <th class="py-2 px-3 text-left">Dias</th>
                        <th class="py-2 px-3 text-left">Tipo sueldo</th>
                        <th class="py-2 px-3 text-right">Sueldo diario</th>
                        <th class="py-2 px-3 text-right">Sueldo generado</th>
                        <th class="py-2 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignacionesActivas as $asig)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="py-2 px-3">
                                <a href="{{ route('empleados.edit', ['empleado' => $asig->empleado_id, 'tab' => 'datos']) }}" class="font-semibold text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4">{{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}</a><br>
                                <span class="text-[11px] text-slate-400">
                                    {{ $asig->empleado->Area }} | {{ $asig->empleado->Puesto }}
                                </span>
                            </td>
                            <td class="py-2 px-3">
                                @can('obras.empleados.rol.edit.access')
                                    <form action="{{ route('obras.empleados.rol.update', [$obra->id, $asig->id]) }}"
                                          method="POST"
                                          class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="rol_id"
                                                class="w-44 rounded-lg border-slate-200 text-xs px-2 py-1">
                                            @foreach($roles as $rol)
                                                <option value="{{ $rol->id }}" @selected((int) old('rol_id', $asig->rol_id) === (int) $rol->id)>
                                                    {{ $rol->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="text-xs text-teal-700 hover:text-teal-900 font-medium">
                                            Guardar
                                        </button>
                                    </form>
                                    @error('rol_id')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                @else
                                    {{ $asig->puesto_en_obra ?? $asig->rol?->nombre ?? $asig->empleado->Puesto }}
                                @endcan
                            </td>
                            <td class="py-2 px-3">
                                @can('obras.empleados.fecha_alta.edit.access')
                                    <form action="{{ route('obras.empleados.fecha-alta.update', [$obra->id, $asig->id]) }}"
                                          method="POST"
                                          class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="date"
                                               name="fecha_alta"
                                               value="{{ old('fecha_alta', $asig->fecha_alta?->toDateString()) }}"
                                               class="w-36 rounded-lg border-slate-200 text-xs px-2 py-1">
                                        <button type="submit"
                                                class="text-xs text-teal-700 hover:text-teal-900 font-medium">
                                            Guardar
                                        </button>
                                    </form>
                                    @error('fecha_alta')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                @else
                                    {{ $asig->fecha_alta?->format('d/m/Y') }}
                                @endcan
                            </td>
                            <td class="py-2 px-3">
                                {{ $asig->dias_trabajados ?? $asig->fecha_alta?->diffInDays(now())+1 }}
                            </td>
                            <td class="py-2 px-3">
                                @if($asig->falta_tipo_sueldo)
                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                        Sin tipo de sueldo
                                    </span>
                                @else
                                    <div class="font-medium text-slate-700">{{ $asig->sueldo_tipo_nombre }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $asig->sueldo_dias_periodo }} dias</div>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right">
                                @if($asig->sueldo_diario_estimado !== null)
                                    ${{ number_format((float) $asig->sueldo_diario_estimado, 2) }}
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right font-semibold text-[#0B265A]">
                                @if($asig->sueldo_generado_estimado !== null)
                                    ${{ number_format((float) $asig->sueldo_generado_estimado, 2) }}
                                @else
                                    <span class="text-slate-400 font-normal">No calculado</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right">
                                <form action="{{ route('obras.empleados.baja', [$obra->id, $asig->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Dar de baja a este empleado en la obra?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs text-red-600 hover:text-red-800 font-medium">
                                        Dar de baja
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 text-center text-slate-500">
                                No hay empleados asignados actualmente a esta obra.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HISTORICO --}}
        @if($asignacionesHistoricas->count() > 0)
            <h3 class="text-sm font-semibold text-slate-700 mt-6 mb-2">Historial de asignaciones</h3>
            <div class="border rounded-xl max-h-64 overflow-auto bg-white">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Empleado</th>
                            <th class="py-2 px-3 text-left">Alta</th>
                            <th class="py-2 px-3 text-left">Baja</th>
                            <th class="py-2 px-3 text-left">Dias</th>
                            <th class="py-2 px-3 text-left">Tipo sueldo</th>
                            <th class="py-2 px-3 text-right">Sueldo diario</th>
                            <th class="py-2 px-3 text-right">Sueldo generado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($asignacionesHistoricas as $asig)
                            <tr class="border-b">
                                <td class="py-1 px-3">
                                    <a href="{{ route('empleados.edit', ['empleado' => $asig->empleado_id, 'tab' => 'datos']) }}" class="font-semibold text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4">{{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}</a>
                                </td>
                                <td class="py-1 px-3">{{ $asig->fecha_alta?->format('d/m/Y') }}</td>
                                <td class="py-1 px-3">{{ $asig->fecha_baja?->format('d/m/Y') }}</td>
                                <td class="py-1 px-3">
                                    {{ $asig->dias_trabajados }}
                                </td>
                                <td class="py-1 px-3">
                                    @if($asig->falta_tipo_sueldo)
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Sin tipo de sueldo</span>
                                    @else
                                        {{ $asig->sueldo_tipo_nombre }}
                                    @endif
                                </td>
                                <td class="py-1 px-3 text-right">
                                    @if($asig->sueldo_diario_estimado !== null)
                                        ${{ number_format((float) $asig->sueldo_diario_estimado, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-1 px-3 text-right font-semibold text-[#0B265A]">
                                    @if($asig->sueldo_generado_estimado !== null)
                                        ${{ number_format((float) $asig->sueldo_generado_estimado, 2) }}
                                    @else
                                        No calculado
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>


