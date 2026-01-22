@extends('layouts.admin')

@section('title', 'Nueva máquina')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Nueva máquina</h1>
        <p class="text-sm text-gray-600">Alta en el catálogo corporativo.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm">
            <div class="font-semibold mb-1">Revisa lo siguiente:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('empresa_config.maquinas.update',$maquina->id) }}"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre *</label>
            <input name="nombre" required value="{{ old('nombre',$maquina->nombre) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Año *</label>
            <input name="modelo" required value="{{ old('modelo',$maquina->modelo) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>
         <div>
            <label class="block text-sm font-medium text-gray-700">Tipo *</label>
            <input name="tipo" required value="{{ old('tipo',$maquina->tipo) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Marca *</label>
            <input name="marca" required value="{{ old('marca',$maquina->marca) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Código</label>
            <input name="codigo" value="{{ old('codigo',$maquina->codigo) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Número de serie</label>
            <input name="numero_serie" value="{{ old('numero_serie',$maquina->numero_serie) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Placas</label>
            <input name="placas" value="{{ old('placas',$maquina->placas) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Color</label>
            <input name="color" value="{{ old('color',$maquina->color) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Horómetro base</label>
            <input name="horometro_base" type="number" step="0.01" min="0"
                   value="{{ old('horometro_base',$maquina->horometro_base) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="estado" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                @php $estado = old('estado', 'operativa'); @endphp
                <option value="operativa" @selected($estado === 'operativa')>Operativa</option>
                <option value="fuera_servicio" @selected($estado === 'fuera_servicio')>Fuera de servicio</option>
                <option value="baja_definitiva" @selected($estado === 'baja_definitiva')>Baja definitiva</option>
            </select>
        </div>

        <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <a href="{{ route('empresa_config.edit', ['tab' => 'maquinaria']) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                Guardar
            </button>
        </div>
    </form>
</div>
@endsection
