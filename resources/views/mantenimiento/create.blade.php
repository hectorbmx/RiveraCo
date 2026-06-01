@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        @php
            $selectedVehiculoId = old('vehiculo_id', $vehiculoIdFromUrl ?? '');
            $selectedMaquinaId = old('maquina_id', $maquinaIdFromUrl ?? '');
            $desdeMaquina = !empty($selectedMaquinaId);
            $desdeVehiculo = !empty($selectedVehiculoId) && !$desdeMaquina;
            $maquinaSeleccionada = $desdeMaquina ? $maquinas->firstWhere('id', (int) $selectedMaquinaId) : null;
            $vehiculoSeleccionado = $desdeVehiculo ? $vehiculos->firstWhere('id', (int) $selectedVehiculoId) : null;
            $cancelUrl = $desdeMaquina
                ? route('maquinas.show', ['maquina' => $selectedMaquinaId, 'tab' => 'servicios'])
                : route('mantenimiento.mantenimientos.index');
        @endphp

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Programar mantenimiento</h1>
                <p class="text-sm text-slate-500">
                    Registra un mantenimiento programado o de emergencia.
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

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
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
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                            <option value="programado" @selected(old('tipo', 'programado') === 'programado')>Programado</option>
                            <option value="emergencia" @selected(old('tipo') === 'emergencia')>Emergencia</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Categoria</label>
                        <input type="text" name="categoria_mantenimiento"
                               value="{{ old('categoria_mantenimiento') }}"
                               placeholder="Cambio de aceite, frenos, servicio general..."
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha programada</label>
                        <input type="datetime-local" name="fecha_programada"
                               value="{{ old('fecha_programada') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
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
                            <input type="number" step="0.1" name="horometro" value="{{ old('horometro') }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km actuales</label>
                            <input type="number" name="km_actuales" value="{{ old('km_actuales') }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km proximo servicio</label>
                            <input type="number" name="km_proximo_servicio" value="{{ old('km_proximo_servicio') }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Descripcion</label>
                    <textarea name="descripcion" rows="4"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion') }}</textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ $cancelUrl }}"
                       class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Guardar mantenimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
