@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto py-8">
        @php
            $selectedVehiculoId = old('vehiculo_id', $vehiculoIdFromUrl ?? '');
            $selectedMaquinaId = old('maquina_id', $maquinaIdFromUrl ?? '');
            $desdeMaquina = !empty($selectedMaquinaId);
            $desdeVehiculo = !empty($selectedVehiculoId) && !$desdeMaquina;
            $maquinaSeleccionada = $desdeMaquina ? $maquinas->firstWhere('id', (int) $selectedMaquinaId) : null;
            $horometroMaquinaSugerido = $maquinaSeleccionada?->horometro_actual_sugerido;
            $horometroMaquinaValue = $horometroMaquinaSugerido !== null ? number_format((float) $horometroMaquinaSugerido, 1, '.', '') : '';
            $vehiculoSeleccionado = $desdeVehiculo ? $vehiculos->firstWhere('id', (int) $selectedVehiculoId) : null;
            $serviciosPreventivos = $desdeMaquina ? ($serviciosPreventivosMaquinaria ?? collect()) : ($serviciosPreventivosVehiculos ?? collect());
            $cancelUrl = $desdeMaquina
                ? route('maquinas.show', ['maquina' => $selectedMaquinaId, 'tab' => 'servicios'])
                : route('mantenimiento.mantenimientos.index');
        @endphp

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Registrar servicio</h1>
                <p class="text-sm text-slate-500">
                    Registra el trabajo realizado y deja evidencia en la bitácora del equipo.
                </p>
            </div>

            <a href="{{ $cancelUrl }}" class="text-sm text-slate-500 hover:text-slate-700">
                &larr; Volver
            </a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6" x-data="{
            detalles: [],
            tipo: '{{ old('tipo', 'programado') }}',
            servicioId: '{{ old('servicio_preventivo_tipo_id') }}',
            materialesPreventivos: @js($materialesPreventivosMaquina ?? []),
        }">
            <form method="POST" action="{{ route('mantenimiento.mantenimientos.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($desdeMaquina)
                        <input type="hidden" name="maquina_id" value="{{ $selectedMaquinaId }}">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Maquina</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ $maquinaSeleccionada?->nombre ?? ('Maquina #' . $selectedMaquinaId) }}
                                @if($maquinaSeleccionada?->codigo)
                                    <span class="text-slate-400">- {{ $maquinaSeleccionada->codigo }}</span>
                                @endif
                            </div>
                        </div>
                    @elseif($desdeVehiculo)
                        <input type="hidden" name="vehiculo_id" value="{{ $selectedVehiculoId }}">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Vehiculo</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ $vehiculoSeleccionado?->marca }} {{ $vehiculoSeleccionado?->modelo }}
                                @if($vehiculoSeleccionado?->placas)
                                    <span class="text-slate-400">- {{ $vehiculoSeleccionado->placas }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Vehiculo <span class="text-red-500">*</span>
                            </label>
                            <select name="vehiculo_id"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecciona un vehiculo...</option>
                                @foreach($vehiculos as $vehiculo)
                                    <option value="{{ $vehiculo->id }}" @selected((string)$selectedVehiculoId === (string)$vehiculo->id)>
                                        {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->placas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo"
                                x-model="tipo"
                                @change="if (tipo === 'emergencia') servicioId = ''"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                            <option value="programado" @selected(old('tipo', 'programado') === 'programado')>Programado</option>
                            <option value="emergencia" @selected(old('tipo') === 'emergencia')>Emergencia</option>
                        </select>
                    </div>

                    <div x-show="tipo === 'programado'" x-cloak>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de servicio preventivo</label>
                        <select name="servicio_preventivo_tipo_id"
                                x-model="servicioId"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin tipo específico</option>
                            @foreach($serviciosPreventivos as $servicioPreventivo)
                                <option value="{{ $servicioPreventivo->id }}" @selected(old('servicio_preventivo_tipo_id') == $servicioPreventivo->id)>
                                    {{ $servicioPreventivo->nombre }} - cada {{ number_format($servicioPreventivo->intervalo_valor) }} {{ $servicioPreventivo->unidad === 'horas' ? 'h' : 'km' }}
                                </option>
                            @endforeach
                        </select>
                        @error('servicio_preventivo_tipo_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="tipo === 'emergencia'" x-cloak class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Las emergencias se capturan mediante el detalle manual de la reparación.
                    </div>

                    <div x-show="tipo === 'programado' && servicioId && (materialesPreventivos[servicioId] || []).length" x-cloak class="md:col-span-2 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                        <div class="text-sm font-semibold text-slate-800">Materiales requeridos</div>
                        <p class="mt-1 text-xs text-slate-600">Configuración efectiva para esta máquina contra el inventario de Almacén Giralda.</p>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-blue-100 bg-white">
                            <table class="min-w-full text-sm">
                                <thead class="bg-[#0B265A] text-left text-xs font-semibold uppercase text-white">
                                    <tr>
                                        <th class="px-3 py-2">Material</th>
                                        <th class="px-3 py-2 text-right">Requerido</th>
                                        <th class="px-3 py-2 text-right">Disponible</th>
                                        <th class="px-3 py-2 text-right">Faltante</th>
                                        <th class="px-3 py-2">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-blue-50">
                            <template x-for="material in (materialesPreventivos[servicioId] || [])" :key="material.id">
                                <tr>
                                    <td class="px-3 py-2"><div class="font-medium text-slate-800" x-text="material.producto || 'Producto'"></div><div class="text-xs text-slate-500" x-text="material.sku || 'Sin SKU'"></div></td>
                                    <td class="px-3 py-2 text-right font-semibold" x-text="`${material.cantidad} ${material.unidad || ''}`"></td>
                                    <td class="px-3 py-2 text-right" x-text="`${material.disponible} ${material.unidad || ''}`"></td>
                                    <td class="px-3 py-2 text-right" x-text="`${material.faltante} ${material.unidad || ''}`"></td>
                                    <td class="px-3 py-2">
                                        <span x-show="material.estado === 'disponible'" class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Disponible</span>
                                        <span x-show="material.estado === 'parcial'" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Parcial</span>
                                        <span x-show="material.estado === 'sin_existencia'" class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Sin existencia</span>
                                    </td>
                                </tr>
                            </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="(materialesPreventivos[servicioId] || []).some(material => material.faltante > 0)" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">Hay materiales faltantes. El servicio podrá enviarse a compras o manejarse como surtido parcial en la siguiente etapa.</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Categoria</label>
                        <input type="text" name="categoria_mantenimiento"
                               value="{{ old('categoria_mantenimiento') }}"
                               placeholder="Cambio de aceite, frenos, servicio general..."
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Fecha del servicio <span class="text-red-500">*</span>
                            </label>
                        <input type="datetime-local" name="fecha_programada"
                               value="{{ old('fecha_programada') }}"
                               required
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Inicio de ejecución</label>
                        <input type="datetime-local" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fin de ejecución</label>
                        <input type="datetime-local" name="fecha_fin" value="{{ old('fecha_fin') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Mecanico asignado</label>
                        <select name="mecanico_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin asignar</option>
                            @foreach($mecanicos as $mecanico)
                                <option value="{{ $mecanico->id_Empleado }}" @selected(old('mecanico_id') == $mecanico->id_Empleado)>
                                    {{ trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')) ?: ('Empleado '.$mecanico->id_Empleado) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($desdeMaquina)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Horometro actual</label>
                            <input type="number" step="0.1" name="horometro" value="{{ old('horometro', $horometroMaquinaValue) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km actuales</label>
                            <input type="number" name="km_actuales" value="{{ old('km_actuales', request('km_actuales')) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km proximo servicio</label>
                            <input type="number" name="km_proximo_servicio" value="{{ old('km_proximo_servicio', request('km_proximo_servicio')) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Descripcion</label>
                    <textarea name="descripcion" rows="4"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion') }}</textarea>
                </div>

                <div x-show="tipo === 'emergencia'" x-cloak class="mt-6 rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 bg-[#0B265A] px-4 py-3 text-white">
                        <div>
                            <div class="text-sm font-semibold">Detalle de la emergencia</div>
                            <div class="text-xs text-blue-100">Captura manualmente refacciones, consumibles, mano de obra o servicios externos.</div>
                        </div>
                        <button type="button" @click="detalles.push({tipo: 'refaccion', concepto: '', cantidad: 1, costo_unitario: 0})"
                                class="rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold hover:bg-white/25">
                            + Agregar detalle
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Tipo</th>
                                    <th class="px-4 py-3">Concepto</th>
                                    <th class="px-4 py-3">Cantidad</th>
                                    <th class="px-4 py-3">Costo unitario</th>
                                    <th class="px-4 py-3 text-right">Importe</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(detalle, index) in detalles" :key="index">
                                    <tr>
                                        <td class="px-4 py-3">
                                            <select :name="`detalles[${index}][tipo]`" x-model="detalle.tipo"
                                                    class="w-40 rounded-lg border-slate-300 text-sm">
                                                <option value="refaccion">Refacción</option>
                                                <option value="consumible">Consumible</option>
                                                <option value="mano_obra">Mano de obra</option>
                                                <option value="servicio_externo">Servicio externo</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" :name="`detalles[${index}][concepto]`" x-model="detalle.concepto"
                                                   required maxlength="150" placeholder="Ej. Cambio de aceite"
                                                   class="w-56 rounded-lg border-slate-300 text-sm">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" min="0.01" step="0.01" :name="`detalles[${index}][cantidad]`" x-model.number="detalle.cantidad"
                                                   required class="w-24 rounded-lg border-slate-300 text-sm">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" min="0" step="0.01" :name="`detalles[${index}][costo_unitario]`" x-model.number="detalle.costo_unitario"
                                                   class="w-32 rounded-lg border-slate-300 text-sm">
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-700"
                                            x-text="'$' + ((Number(detalle.cantidad) || 0) * (Number(detalle.costo_unitario) || 0)).toFixed(2)"></td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" @click="detalles.splice(index, 1)"
                                                    class="text-red-600 hover:text-red-800" title="Quitar detalle" aria-label="Quitar detalle">
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="detalles.length === 0">
                                    <td colspan="6" class="px-4 py-5 text-center text-sm text-slate-400">Sin detalles agregados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ $cancelUrl }}"
                       class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Guardar servicio como borrador
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
