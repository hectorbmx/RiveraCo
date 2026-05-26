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

    $empleadosBuscador = $empleadosResponsables->map(fn ($empleado) => [
        'id' => $empleado->id_Empleado,
        'nombre' => $empleado->nombre_completo,
    ])->values();
@endphp

<div x-show="tab === 'equipos_computo'" x-cloak x-data="equipoComputoUi()" class="space-y-6">
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
                <input type="hidden" name="factura_uuid" :value="facturaUuid('create')">
                <div class="relative">
                    <input type="text"
                           x-model="facturaSearch.create"
                           @input.debounce.350ms="buscarFacturas('create')"
                           @focus="facturaOpen.create = facturaResultados.create?.length > 0"
                           class="w-full rounded-xl border-slate-300"
                           placeholder="Buscar UUID, proveedor o folio">
                    <div x-show="facturaOpen.create" x-cloak @click.away="facturaOpen.create = false"
                         class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div x-show="facturaLoading.create" class="px-3 py-2 text-xs text-slate-500">Buscando...</div>
                        <template x-for="factura in facturaResultados.create || []" :key="factura.id">
                            <button type="button" @click="seleccionarFactura('create', factura)" class="block w-full px-3 py-2 text-left hover:bg-slate-50">
                                <span class="block text-xs font-semibold text-slate-900" x-text="factura.uuid"></span>
                                <span class="block text-xs text-slate-500" x-text="`${factura.emisor || ''} · ${factura.folio || ''} · $${Number(factura.total || 0).toFixed(2)}`"></span>
                            </button>
                        </template>
                    </div>
                </div>
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
                <input type="hidden" name="responsable_actual_id" :value="responsableId('create')">
                <div class="relative">
                    <input type="text"
                           x-model="responsableSearch.create"
                           @focus="responsableOpen.create = true"
                           @input="responsableOpen.create = true"
                           class="w-full rounded-xl border-slate-300"
                           placeholder="Buscar empleado">
                    <div x-show="responsableOpen.create" x-cloak @click.away="responsableOpen.create = false"
                         class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <button type="button" @click="limpiarResponsable('create')" class="block w-full px-3 py-2 text-left text-xs text-slate-500 hover:bg-slate-50">Sin asignar</button>
                        <template x-for="empleado in empleadosFiltrados('create')" :key="empleado.id">
                            <button type="button" @click="seleccionarResponsable('create', empleado)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" x-text="empleado.nombre"></button>
                        </template>
                    </div>
                </div>
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
            <div class="md:col-span-3">
                <label class="block text-xs text-slate-600 mb-1">Fotos del equipo (maximo 3)</label>
                <input type="file" name="fotos[]" accept="image/*" multiple @change="validarFotos($event)" class="w-full text-sm">
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
                            @if($equipo->fotos->count())
                                <div class="mt-2 flex gap-1">
                                    @foreach($equipo->fotos->take(3) as $foto)
                                        <a href="{{ asset('storage/'.$foto->path) }}" target="_blank">
                                            <img src="{{ asset('storage/'.$foto->path) }}" alt="Foto equipo" class="h-10 w-10 rounded-lg object-cover border border-slate-200">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
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
                                          x-init="initResponsable('edit-{{ $equipo->id }}', @json($equipo->responsable_actual_id), @json($equipo->responsableActual?->nombre_completo)); initFactura('edit-{{ $equipo->id }}', @json($equipo->factura_uuid))"
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
                                        <div class="relative">
                                            <input type="hidden" name="factura_uuid" :value="facturaUuid('edit-{{ $equipo->id }}')">
                                            <input type="text"
                                                   x-model="facturaSearch['edit-{{ $equipo->id }}']"
                                                   @input.debounce.350ms="buscarFacturas('edit-{{ $equipo->id }}')"
                                                   @focus="facturaOpen['edit-{{ $equipo->id }}'] = facturaResultados['edit-{{ $equipo->id }}']?.length > 0"
                                                   class="w-full rounded-lg border-slate-300 text-sm"
                                                   placeholder="Factura UUID">
                                            <div x-show="facturaOpen['edit-{{ $equipo->id }}']" x-cloak @click.away="facturaOpen['edit-{{ $equipo->id }}'] = false"
                                                 class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                                <div x-show="facturaLoading['edit-{{ $equipo->id }}']" class="px-3 py-2 text-xs text-slate-500">Buscando...</div>
                                                <template x-for="factura in facturaResultados['edit-{{ $equipo->id }}'] || []" :key="factura.id">
                                                    <button type="button" @click="seleccionarFactura('edit-{{ $equipo->id }}', factura)" class="block w-full px-3 py-2 text-left hover:bg-slate-50">
                                                        <span class="block text-xs font-semibold text-slate-900" x-text="factura.uuid"></span>
                                                        <span class="block text-xs text-slate-500" x-text="`${factura.emisor || ''} · ${factura.folio || ''}`"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <input type="file" name="factura_archivo" accept=".pdf,.xml,.jpg,.jpeg,.png" class="text-xs">
                                        <input type="text" name="ubicacion" value="{{ $equipo->ubicacion }}" class="rounded-lg border-slate-300 text-sm" placeholder="Ubicacion">
                                        <select name="area_id" class="rounded-lg border-slate-300 text-sm">
                                            <option value="">Sin area</option>
                                            @foreach($areas as $area)
                                                <option value="{{ $area->id }}" @selected((int) $equipo->area_id === (int) $area->id)>{{ $area->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <div class="md:col-span-2 relative">
                                            <input type="hidden" name="responsable_actual_id" :value="responsableId('edit-{{ $equipo->id }}')">
                                            <input type="text"
                                                   x-model="responsableSearch['edit-{{ $equipo->id }}']"
                                                   @focus="responsableOpen['edit-{{ $equipo->id }}'] = true"
                                                   @input="responsableOpen['edit-{{ $equipo->id }}'] = true"
                                                   class="w-full rounded-lg border-slate-300 text-sm"
                                                   placeholder="Buscar responsable">
                                            <div x-show="responsableOpen['edit-{{ $equipo->id }}']" x-cloak @click.away="responsableOpen['edit-{{ $equipo->id }}'] = false"
                                                 class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                                <button type="button" @click="limpiarResponsable('edit-{{ $equipo->id }}')" class="block w-full px-3 py-2 text-left text-xs text-slate-500 hover:bg-slate-50">Sin asignar</button>
                                                <template x-for="empleado in empleadosFiltrados('edit-{{ $equipo->id }}')" :key="empleado.id">
                                                    <button type="button" @click="seleccionarResponsable('edit-{{ $equipo->id }}', empleado)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" x-text="empleado.nombre"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <select name="estatus" class="rounded-lg border-slate-300 text-sm">
                                            @foreach($estatusEquipo as $value => $label)
                                                <option value="{{ $value }}" @selected($equipo->estatus === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="notas" value="{{ $equipo->notas }}" class="md:col-span-2 rounded-lg border-slate-300 text-sm" placeholder="Notas">
                                        <input type="text" name="movimiento_notas" class="md:col-span-3 rounded-lg border-slate-300 text-sm" placeholder="Nota para kardex si hubo cambio">
                                        <label class="md:col-span-3 text-xs text-slate-600">
                                            Fotos del cambio (maximo 3)
                                            <input type="file" name="fotos[]" accept="image/*" multiple @change="validarFotos($event)" class="mt-1 block w-full text-xs">
                                        </label>
                                        <div class="md:col-span-6 flex justify-end">
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar cambios</button>
                                        </div>
                                    </form>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <form method="POST" action="{{ route('empresa_config.equipos-computo.asignar', $equipo) }}" enctype="multipart/form-data"
                                              x-init="initResponsable('assign-{{ $equipo->id }}', @json($equipo->responsable_actual_id), @json($equipo->responsableActual?->nombre_completo))"
                                              class="rounded-xl border border-slate-200 p-4 space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <h4 class="text-sm font-semibold text-slate-900">Cambiar responsable</h4>
                                            <input type="hidden" name="responsable_actual_id" :value="responsableId('assign-{{ $equipo->id }}')">
                                            <div class="relative">
                                                <input type="text"
                                                       x-model="responsableSearch['assign-{{ $equipo->id }}']"
                                                       @focus="responsableOpen['assign-{{ $equipo->id }}'] = true"
                                                       @input="responsableOpen['assign-{{ $equipo->id }}'] = true"
                                                       required
                                                       class="w-full rounded-lg border-slate-300 text-sm"
                                                       placeholder="Buscar empleado">
                                                <div x-show="responsableOpen['assign-{{ $equipo->id }}']" x-cloak @click.away="responsableOpen['assign-{{ $equipo->id }}'] = false"
                                                     class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                                    <template x-for="empleado in empleadosFiltrados('assign-{{ $equipo->id }}')" :key="empleado.id">
                                                        <button type="button" @click="seleccionarResponsable('assign-{{ $equipo->id }}', empleado)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" x-text="empleado.nombre"></button>
                                                    </template>
                                                </div>
                                            </div>
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
                                            <label class="block text-xs text-slate-600">
                                                Fotos del cambio (maximo 3)
                                                <input type="file" name="fotos[]" accept="image/*" multiple @change="validarFotos($event)" class="mt-1 block w-full text-xs">
                                            </label>
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-700 text-white text-sm hover:bg-blue-800">Registrar asignacion</button>
                                        </form>

                                        @if($equipo->estatus !== 'baja')
                                            <form method="POST" action="{{ route('empresa_config.equipos-computo.baja', $equipo) }}"
                                                  enctype="multipart/form-data"
                                                  onsubmit="return confirm('El equipo se marcara como baja sin borrar el historial. Continuar?')"
                                                  class="rounded-xl border border-red-200 bg-red-50 p-4 space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <h4 class="text-sm font-semibold text-red-900">Baja logica</h4>
                                                <input type="date" name="fecha_movimiento" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border-red-200 text-sm">
                                                <textarea name="notas" rows="3" class="w-full rounded-lg border-red-200 text-sm" placeholder="Motivo"></textarea>
                                                <label class="block text-xs text-red-800">
                                                    Fotos de baja (maximo 3)
                                                    <input type="file" name="fotos[]" accept="image/*" multiple @change="validarFotos($event)" class="mt-1 block w-full text-xs">
                                                </label>
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
                                                    <th class="px-3 py-2 text-left">Fotos</th>
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
                                                        <td class="px-3 py-2">
                                                            @if($mov->fotos->count())
                                                                <div class="flex gap-1">
                                                                    @foreach($mov->fotos as $foto)
                                                                        <a href="{{ asset('storage/'.$foto->path) }}" target="_blank">
                                                                            <img src="{{ asset('storage/'.$foto->path) }}" alt="Foto movimiento" class="h-9 w-9 rounded-md object-cover border border-slate-200">
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2">{{ $mov->notas ?: '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">Sin movimientos registrados.</td>
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

@push('scripts')
<script>
function equipoComputoUi() {
    const config = {
        empleados: @json($empleadosBuscador),
        facturasUrl: @json(route('empresa_config.equipos-computo.buscar-facturas')),
    };

    return {
        empleados: config.empleados || [],
        facturasUrl: config.facturasUrl,
        responsableSearch: { create: '' },
        responsableSelected: { create: null },
        responsableOpen: { create: false },
        facturaSearch: { create: '' },
        facturaSelected: { create: null },
        facturaOpen: { create: false },
        facturaLoading: { create: false },
        facturaResultados: { create: [] },

        initResponsable(key, id, nombre) {
            this.responsableSelected[key] = id ? { id, nombre: nombre || '' } : null;
            this.responsableSearch[key] = nombre || '';
            this.responsableOpen[key] = false;
        },

        responsableId(key) {
            return this.responsableSelected[key]?.id || '';
        },

        empleadosFiltrados(key) {
            const term = (this.responsableSearch[key] || '').trim().toLowerCase();
            return this.empleados
                .filter((empleado) => !term || empleado.nombre.toLowerCase().includes(term))
                .slice(0, 12);
        },

        seleccionarResponsable(key, empleado) {
            this.responsableSelected[key] = empleado;
            this.responsableSearch[key] = empleado.nombre;
            this.responsableOpen[key] = false;
        },

        limpiarResponsable(key) {
            this.responsableSelected[key] = null;
            this.responsableSearch[key] = '';
            this.responsableOpen[key] = false;
        },

        initFactura(key, uuid) {
            this.facturaSelected[key] = uuid ? { uuid } : null;
            this.facturaSearch[key] = uuid || '';
            this.facturaResultados[key] = [];
            this.facturaOpen[key] = false;
            this.facturaLoading[key] = false;
        },

        facturaUuid(key) {
            return this.facturaSelected[key]?.uuid || (this.facturaSearch[key] || '').trim();
        },

        async buscarFacturas(key) {
            const term = (this.facturaSearch[key] || '').trim();
            this.facturaSelected[key] = null;

            if (term.length < 2) {
                this.facturaResultados[key] = [];
                this.facturaOpen[key] = false;
                return;
            }

            this.facturaLoading[key] = true;
            this.facturaOpen[key] = true;

            try {
                const url = new URL(this.facturasUrl);
                url.searchParams.set('q', term);
                const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                const body = await response.json();

                if (!response.ok) {
                    throw new Error(body.message || 'No se pudo buscar la factura.');
                }

                this.facturaResultados[key] = body.data || [];
            } catch (error) {
                this.facturaResultados[key] = [];
            } finally {
                this.facturaLoading[key] = false;
            }
        },

        seleccionarFactura(key, factura) {
            this.facturaSelected[key] = factura;
            this.facturaSearch[key] = factura.uuid;
            this.facturaOpen[key] = false;
        },

        validarFotos(event) {
            if (event.target.files.length > 3) {
                event.target.value = '';
                alert('Selecciona maximo 3 fotos por registro.');
            }
        },
    };
}
</script>
@endpush
