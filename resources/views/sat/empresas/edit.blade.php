@extends('layouts.admin')

@section('title', 'Editar empresa SAT')

@section('content')
<div class="max-w-3xl mx-auto">

    <h1 class="text-xl font-semibold mb-6">
        Editar Empresa SAT
    </h1>

    <form action="{{ route('sat.empresas.update', $empresa->id) }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 md:p-8">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Nombre -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre
                    </label>

                    <input type="text"
                           name="nombre"
                           value="{{ old('nombre', $empresa->nombre) }}"
                           class="w-full rounded-xl border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                           required>
                </div>

                <!-- RFC -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        RFC
                    </label>

                    <input type="text"
                           name="rfc"
                           value="{{ old('rfc', $empresa->rfc) }}"
                           class="w-full rounded-xl border-gray-300 px-4 py-3 uppercase focus:border-indigo-500 focus:ring-indigo-500"
                           required>
                </div>

                <!-- Password FIEL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña FIEL
                    </label>

                    <input type="text"
                           name="fiel_password"
                           class="w-full rounded-xl border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Dejar vacío para conservar">
                </div>

                <!-- Espacio final -->
                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox"
                               name="fiel_password_has_trailing_space"
                               value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                        <span class="text-sm text-gray-700">
                            La contraseña termina con espacio
                        </span>
                    </label>
                </div>

                <!-- SAT PASSWORD -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña SAT / CIEC
                    </label>

                    <input type="text"
                           name="sat_password"
                           class="w-full rounded-xl border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Dejar vacío para conservar">

                    <p class="text-xs text-gray-500 mt-1">
                        Déjalo vacío si no deseas cambiar la contraseña SAT / CIEC actual.
                    </p>
                </div>

                <!-- CER -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Archivo CER
                    </label>

                    <input type="file"
                           name="cer_file"
                           accept=".cer"
                           class="w-full rounded-xl border-gray-300 text-sm
                                  file:mr-4
                                  file:rounded-lg
                                  file:border-0
                                  file:bg-indigo-50
                                  file:px-4
                                  file:py-2
                                  file:text-sm
                                  file:font-medium
                                  file:text-indigo-700
                                  hover:file:bg-indigo-100">
                </div>

                <!-- KEY -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Archivo KEY
                    </label>

                    <input type="file"
                           name="key_file"
                           accept=".key"
                           class="w-full rounded-xl border-gray-300 text-sm
                                  file:mr-4
                                  file:rounded-lg
                                  file:border-0
                                  file:bg-indigo-50
                                  file:px-4
                                  file:py-2
                                  file:text-sm
                                  file:font-medium
                                  file:text-indigo-700
                                  hover:file:bg-indigo-100">
                </div>

                <!-- NUEVA SECCION CSD -->
                <div class="md:col-span-2 border-t border-gray-200 pt-5 mt-2">
                    <h3 class="text-base font-semibold text-gray-900">
                        Certificados CSD para facturación
                    </h3>

                    <p class="text-sm text-gray-600 mt-1">
                        Archivos necesarios para timbrar CFDI.
                    </p>
                </div>

                <!-- CSD CER -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Certificado CSD (.cer)
                    </label>

                    <input type="file"
                           name="csd_cer_file"
                           accept=".cer"
                           class="w-full rounded-xl border-gray-300 text-sm
                                  file:mr-4
                                  file:rounded-lg
                                  file:border-0
                                  file:bg-indigo-50
                                  file:px-4
                                  file:py-2
                                  file:text-sm
                                  file:font-medium
                                  file:text-indigo-700
                                  hover:file:bg-indigo-100">
                </div>

                <!-- CSD KEY -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Llave CSD (.key)
                    </label>

                    <input type="file"
                           name="csd_key_file"
                           accept=".key"
                           class="w-full rounded-xl border-gray-300 text-sm
                                  file:mr-4
                                  file:rounded-lg
                                  file:border-0
                                  file:bg-indigo-50
                                  file:px-4
                                  file:py-2
                                  file:text-sm
                                  file:font-medium
                                  file:text-indigo-700
                                  hover:file:bg-indigo-100">
                </div>

                <!-- PASSWORD CSD -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña CSD
                    </label>

                    <input type="text"
                           name="csd_password"
                           class="w-full rounded-xl border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Dejar vacío para conservar">
                </div>

                <!-- Activo -->
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox"
                               name="activo"
                               value="1"
                               {{ $empresa->activo ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                        <span class="text-sm text-gray-700">
                            Empresa activa
                        </span>
                    </label>
                </div>

            </div>

            <!-- BOTONES -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-200">

                <a href="{{ route('sat.empresas.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-medium">
                    Guardar cambios
                </button>

            </div>

        </div>

    </form>

</div>
@endsection