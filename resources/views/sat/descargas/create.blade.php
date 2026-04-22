    @extends('layouts.admin')

@section('title', 'Nueva solicitud SAT')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Nueva solicitud SAT</h1>
            <p class="text-sm text-gray-600 mt-1">
                Captura una nueva solicitud para descarga masiva de CFDIs.
            </p>
        </div>

        <a href="{{ route('sat.descargas.index') }}"
           class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="font-medium text-red-800 mb-2">Hay errores en el formulario:</div>
            <ul class="list-disc ml-5 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sat.descargas.store') }}" method="POST"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa SAT</label>
                <select name="sat_empresa_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                    <option value="">Selecciona una empresa</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(old('sat_empresa_id') == $empresa->id)>
                            {{ $empresa->nombre }} — {{ $empresa->rfc }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date"
                       name="fecha_inicio"
                       value="{{ old('fecha_inicio') }}"
                       class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                <input type="date"
                       name="fecha_fin"
                       value="{{ old('fecha_fin') }}"
                       class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de descarga</label>
               <select name="tipo_descarga"
                        class="w-full rounded-xl border-gray-300 text-sm"
                        required>

                    <option value="received" @selected(old('tipo_descarga') === 'received')>
                        Recibidos
                    </option>

                    <option value="issued" @selected(old('tipo_descarga') === 'issued')>
                        Emitidos
                    </option>

                </select>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('sat.descargas.index') }}"
               class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Guardar solicitud
            </button>
        </div>
    </form>
</div>
@endsection