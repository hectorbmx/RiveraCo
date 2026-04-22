    @extends('layouts.admin')

@section('title', 'Empresas SAT')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
@if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        {{ session('error') }}
    </div>
@endif
 <div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Empresas SAT</h1>
        <p class="text-sm text-gray-600 mt-1">
            Configuración de credenciales SAT para descarga masiva de CFDIs.
        </p>
    </div>

    <div>
        <a href="{{ route('sat.empresas.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 shadow-sm">
            ➕ Nueva empresa
        </a>
    </div>
</div>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Listado de empresas</h3>
        <p class="text-sm text-gray-500">Empresas configuradas para usar credenciales SAT.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Empresa</th>
                    <th class="px-4 py-3 text-left font-medium">RFC</th>
                    <th class="px-4 py-3 text-left font-medium">Certificados</th>
                    <th class="px-4 py-3 text-left font-medium">Estado</th>
                    <th class="px-4 py-3 text-right font-medium">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($empresas as $empresa)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ $empresa->nombre }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                {{ $empresa->rfc }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="space-y-1 text-sm text-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="w-10 text-gray-500">CER:</span>
                                    @if($empresa->cer_path)
                                        <span class="text-green-600 font-medium">✔ Cargado</span>
                                    @else
                                        <span class="text-red-500 font-medium">✖ Pendiente</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="w-10 text-gray-500">KEY:</span>
                                    @if($empresa->key_path)
                                        <span class="text-green-600 font-medium">✔ Cargado</span>
                                    @else
                                        <span class="text-red-500 font-medium">✖ Pendiente</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            @if($empresa->activo)
                                <span class="inline-flex rounded-lg bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('sat.empresas.edit', $empresa->id) }}"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Editar
                                </a>

                              <form action="{{ route('sat.empresas.destroy', $empresa->id) }}" method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta empresa SAT?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="text-sm font-medium text-red-600 hover:text-red-800">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                            No hay empresas SAT registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
    </div>

</div>
@endsection