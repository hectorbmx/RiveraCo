@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        @php
            $esMaquina = !empty($mantenimiento->maquina_id);
            $cancelUrl = $esMaquina
                ? route('maquinas.show', ['maquina' => $mantenimiento->maquina_id, 'tab' => 'servicios'])
                : route('mantenimiento.mantenimientos.index');
        @endphp

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Editar mantenimiento</h1>
                <p class="text-sm text-slate-500">
                    #{{ $mantenimiento->id }} -
                    @if($esMaquina)
                        {{ $mantenimiento->maquina->nombre ?? ('Maquina #' . $mantenimiento->maquina_id) }}
                    @else
                        {{ $mantenimiento->vehiculo->marca ?? '' }} {{ $mantenimiento->vehiculo->modelo ?? '' }}
                    @endif
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
            <form method="POST" action="{{ route('mantenimiento.mantenimientos.update', $mantenimiento) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($esMaquina)
                        <input type="hidden" name="maquina_id" value="{{ old('maquina_id', $mantenimiento->maquina_id) }}">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Maquina</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ $mantenimiento->maquina->nombre ?? ('Maquina #' . $mantenimiento->maquina_id) }}
                            </div>
                        </div>
                    @else
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Vehiculo <span class="text-red-500">*</span>
                            </label>
                            <select name="vehiculo_id"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required>
                                @foreach($vehiculos as $vehiculo)
                                    <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id', $mantenimiento->vehiculo_id) == $vehiculo->id)>
                                        {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->placas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tipo *</label>
                        <select name="tipo"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                            <option value="programado" @selected(old('tipo', $mantenimiento->tipo) === 'programado')>Programado</option>
                            <option value="emergencia" @selected(old('tipo', $mantenimiento->tipo) === 'emergencia')>Emergencia</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Estatus *</label>
                        <select name="estatus"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                            <option value="pendiente" @selected(old('estatus', $mantenimiento->estatus) === 'pendiente')>Pendiente</option>
                            <option value="en_proceso" @selected(old('estatus', $mantenimiento->estatus) === 'en_proceso')>En proceso</option>
                            <option value="completado" @selected(old('estatus', $mantenimiento->estatus) === 'completado')>Completado</option>
                            <option value="cancelado" @selected(old('estatus', $mantenimiento->estatus) === 'cancelado')>Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Categoria</label>
                        <input type="text" name="categoria_mantenimiento"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]"
                               value="{{ old('categoria_mantenimiento', $mantenimiento->categoria_mantenimiento) }}"
                               placeholder="Ej. Cambio de aceite, servicio general...">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha programada</label>
                        <input type="datetime-local" name="fecha_programada"
                               value="{{ old('fecha_programada', optional($mantenimiento->fecha_programada)->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Mecanico asignado</label>
                        <select name="mecanico_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin asignar</option>
                            @foreach($mecanicos as $mecanico)
                                <option value="{{ $mecanico->id_Empleado }}" @selected(old('mecanico_id', $mantenimiento->mecanico_id) == $mecanico->id_Empleado)>
                                    {{ trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')) ?: ('Empleado '.$mecanico->id_Empleado) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($esMaquina)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Horometro actual</label>
                            <input type="number" step="0.1" name="horometro"
                                   value="{{ old('horometro', $mantenimiento->horometro) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km actuales</label>
                            <input type="number" name="km_actuales"
                                   value="{{ old('km_actuales', $mantenimiento->km_actuales) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Km proximo servicio</label>
                            <input type="number" name="km_proximo_servicio"
                                   value="{{ old('km_proximo_servicio', $mantenimiento->km_proximo_servicio) }}"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Descripcion</label>
                    <textarea name="descripcion" rows="4"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion', $mantenimiento->descripcion) }}</textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ $cancelUrl }}"
                       class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
