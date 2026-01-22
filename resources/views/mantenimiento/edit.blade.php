@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Editar mantenimiento</h1>
                <p class="text-sm text-slate-500">
                    #{{ $mantenimiento->id }} — {{ $mantenimiento->vehiculo->marca ?? '' }} {{ $mantenimiento->vehiculo->modelo ?? '' }}
                </p>
            </div>

            <a href="{{ route('mantenimiento.mantenimientos.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700">
                ← Volver al listado
            </a>
        </div>

        {{-- Errores --}}
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
                    {{-- Vehículo --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Vehículo <span class="text-red-500">*</span>
                        </label>
                        <select name="vehiculo_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                            @foreach($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}"
                                    {{ old('vehiculo_id', $mantenimiento->vehiculo_id) == $vehiculo->id ? 'selected' : '' }}>
                                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->placas }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipo --}}
                   <div>
        <label class="block text-sm font-medium text-slate-700">
            Tipo *
        </label>
        <select name="tipo"
                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                       focus:border-[#FFC107] focus:ring-[#FFC107]">
            <option value="programado" {{ old('tipo', $mantenimiento->tipo) === 'programado' ? 'selected' : '' }}>Programado</option>
            <option value="emergencia" {{ old('tipo', $mantenimiento->tipo) === 'emergencia' ? 'selected' : '' }}>Emergencia</option>
        </select>
    </div>

    {{-- Estatus --}}
    <div>
        <label class="block text-sm font-medium text-slate-700">
            Estatus *
        </label>
        <select name="estatus"
                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                       focus:border-[#FFC107] focus:ring-[#FFC107]">
            <option value="pendiente"   {{ old('estatus', $mantenimiento->estatus) === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="en_proceso"  {{ old('estatus', $mantenimiento->estatus) === 'en_proceso' ? 'selected' : '' }}>En proceso</option>
            <option value="completado"  {{ old('estatus', $mantenimiento->estatus) === 'completado' ? 'selected' : '' }}>Completado</option>
            <option value="cancelado"   {{ old('estatus', $mantenimiento->estatus) === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
        </select>
    </div>

    {{-- Categoría --}}
    <div>
        <label class="block text-sm font-medium text-slate-700">
            Categoría
        </label>
        <input type="text" name="categoria_mantenimiento"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('categoria_mantenimiento', $mantenimiento->categoria_mantenimiento) }}"
               placeholder="Ej. Cambio de aceite, servicio general...">
    </div>

                    {{-- Categoría --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Categoría
                        </label>
                        <input type="text" name="categoria_mantenimiento"
                               value="{{ old('categoria_mantenimiento', $mantenimiento->categoria_mantenimiento) }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Fecha programada --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Fecha programada
                        </label>
                        <input type="datetime-local" name="fecha_programada"
                               value="{{ old('fecha_programada', optional($mantenimiento->fecha_programada)->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Mecánico --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Mecánico asignado
                        </label>
                        <select name="mecanico_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin asignar</option>
                            @foreach($mecanicos as $mecanico)
                                <option value="{{ $mecanico->id_Empleado }}"
                                    {{ old('mecanico_id', $mantenimiento->mecanico_id) == $mecanico->id_Empleado ? 'selected' : '' }}>
                                    <!-- {{ $mecanico->nombre ?? ('Empleado '.$mecanico->id_Empleado) }} -->
                                    {{ $mecanico->Nombre . ' ' . $mecanico->Apellidos ?? ('Empleado '.$mecanico->id_Empleado) }}

                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Km actuales --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Km actuales
                        </label>
                        <input type="number" name="km_actuales"
                               value="{{ old('km_actuales', $mantenimiento->km_actuales) }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Km próximo servicio --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Km próximo servicio
                        </label>
                        <input type="number" name="km_proximo_servicio"
                               value="{{ old('km_proximo_servicio', $mantenimiento->km_proximo_servicio) }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                

                {{-- Descripción --}}
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Descripción
                    </label>
                    <textarea name="descripcion" rows="4"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion', $mantenimiento->descripcion) }}</textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('mantenimiento.mantenimientos.index') }}"
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
