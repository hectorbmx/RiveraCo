@extends('layouts.admin')

@section('title', 'Nueva Obra')

@section('content')

<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#0B265A]">Nueva Obra</h1>

        <a href="{{ route('obras.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            ← Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la información.
            </div>
        @endif

        <form action="{{ route('obras.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Cliente y clave --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="cliente_id" class="block text-sm font-medium text-slate-700">
                        Cliente <span class="text-red-500">*</span>
                    </label>
                    <select id="cliente_id" name="cliente_id"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                   focus:border-[#FFC107] focus:ring-[#FFC107]" required>
                        <option value="">-- Selecciona un cliente --</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>
                                {{ $cliente->nombre_comercial }}
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="clave_obra" class="block text-sm font-medium text-slate-700">
                        Clave de obra <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="clave_obra" name="clave_obra"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('clave_obra') }}" required>
                    @error('clave_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="block text-sm font-medium text-slate-700">
                    Nombre de la obra <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nombre" name="nombre"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('nombre') }}" required>
                @error('nombre')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo, status y responsable --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="tipo_obra" class="block text-sm font-medium text-slate-700">
                        Tipo de obra
                    </label>
                    <input type="text" id="tipo_obra" name="tipo_obra"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('tipo_obra') }}">
                    @error('tipo_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

              <div class="mb-3">
    <label for="estatus_nuevo" class="block text-xs font-semibold text-slate-600 mb-1">
        Estatus de la obra
    </label>

    <select id="estatus_nuevo" name="estatus_nuevo"
        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
               focus:border-[#FFC107] focus:ring-[#FFC107]">

        @foreach(\App\Models\Obra::$estatusLabels as $value => $label)
            <option value="{{ $value }}"
                @selected(old('estatus_nuevo', $obra->estatus_nuevo ?? 1) == $value)>
                {{ $label }}
            </option>
        @endforeach

    </select>
</div>


                <div>
                    <label for="responsable_id" class="block text-sm font-medium text-slate-700">
                        Responsable
                    </label>
                    <select id="responsable_id" name="responsable_id"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                   focus:border-[#FFC107] focus:ring-[#FFC107]">
                        <option value="">-- Sin asignar --</option>
                        @foreach($responsables as $user)
                            <option value="{{ $user->id }}" @selected(old('responsable_id') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsable_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="fecha_inicio_programada" class="block text-sm font-medium text-slate-700">
                        Inicio prog.
                    </label>
                    <input type="date" id="fecha_inicio_programada" name="fecha_inicio_programada"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_inicio_programada') }}">
                    @error('fecha_inicio_programada')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_inicio_real" class="block text-sm font-medium text-slate-700">
                        Inicio real
                    </label>
                    <input type="date" id="fecha_inicio_real" name="fecha_inicio_real"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_inicio_real') }}">
                    @error('fecha_inicio_real')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_fin_programada" class="block text-sm font-medium text-slate-700">
                        Fin prog.
                    </label>
                    <input type="date" id="fecha_fin_programada" name="fecha_fin_programada"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_fin_programada') }}">
                    @error('fecha_fin_programada')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_fin_real" class="block text-sm font-medium text-slate-700">
                        Fin real
                    </label>
                    <input type="date" id="fecha_fin_real" name="fecha_fin_real"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_fin_real') }}">
                    @error('fecha_fin_real')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Montos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="monto_contratado" class="block text-sm font-medium text-slate-700">
                        Monto contratado
                    </label>
                    <input type="number" step="0.01" id="monto_contratado" name="monto_contratado"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('monto_contratado') }}">
                    @error('monto_contratado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monto_modificado" class="block text-sm font-medium text-slate-700">
                        Monto modificado
                    </label>
                    <input type="number" step="0.01" id="monto_modificado" name="monto_modificado"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('monto_modificado') }}">
                    @error('monto_modificado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Ubicación --}}
            <div>
                <label for="ubicacion" class="block text-sm font-medium text-slate-700">
                    Ubicación (texto libre)
                </label>
                <input type="text" id="ubicacion" name="ubicacion"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('ubicacion') }}">
                @error('ubicacion')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('obras.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-semibold
                               shadow hover:bg-[#e0ac05]">
                    Guardar Obra
                </button>
            </div>

        </form>
    </div>
</div>

@endsection
