@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Registrar vehículo</h1>
                <p class="text-sm text-slate-500">
                    Captura los datos básicos del vehículo.
                </p>
            </div>

            <a href="{{ route('mantenimiento.vehiculos.index') }}"
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
            <form method="POST" action="{{ route('mantenimiento.vehiculos.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Marca --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Marca
                        </label>
                        <input type="text" name="marca" value="{{ old('marca') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Modelo --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Modelo
                        </label>
                        <input type="text" name="modelo" value="{{ old('modelo') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Año --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Año
                        </label>
                        <input type="number" name="anio" value="{{ old('anio') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                               min="1950" max="{{ date('Y') + 1 }}">
                    </div>

                    {{-- Color --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Color
                        </label>
                        <input type="text" name="color" value="{{ old('color') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Placas --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Placas <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="placas" value="{{ old('placas') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>

                    {{-- Serie (VIN) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Serie (VIN)
                        </label>
                        <input type="text" name="serie" value="{{ old('serie') }}"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Tipo
                        </label>
                        <input type="text" name="tipo" value="{{ old('tipo') }}"
                               placeholder="Camioneta, auto, pickup..."
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Estatus --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Estatus <span class="text-red-500">*</span>
                        </label>
                        <select name="estatus"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                            <option value="activo" {{ old('estatus', 'activo') === 'activo' ? 'selected' : '' }}>
                                Activo
                            </option>
                            <option value="en_taller" {{ old('estatus') === 'en_taller' ? 'selected' : '' }}>
                                En taller
                            </option>
                            <option value="baja" {{ old('estatus') === 'baja' ? 'selected' : '' }}>
                                Baja
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('mantenimiento.vehiculos.index') }}"
                       class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Guardar vehículo
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
