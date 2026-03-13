<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h2 class="text-lg font-semibold text-[#0B265A]">Documentos del empleado</h2>
        <p class="text-sm text-slate-500">
            Aquí puedes cargar, consultar y administrar el expediente documental del empleado.
        </p>
    </div>

    {{-- Errores del formulario de documentos --}}
    @if ($errors->any() && old())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Revisa los campos del formulario de documentos.
        </div>
    @endif

    {{-- Formulario de carga --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-semibold text-slate-700 mb-4">Subir nuevo documento</h3>

        <form method="POST"
              action="{{ route('empleados.documentos.store', $empleado) }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Tipo --}}
                <div>
                    <label for="tipo_documento" class="block text-sm font-medium text-slate-700 mb-1">
                        Tipo de documento <span class="text-red-500">*</span>
                    </label>

                    <select id="tipo_documento"
                            name="tipo_documento"
                            class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
                        <option value="">Selecciona una opción</option>
                        @foreach(\App\Models\EmpleadoDocumento::TIPOS as $tipo)
                            <option value="{{ $tipo }}" {{ old('tipo_documento') === $tipo ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', $tipo) }}
                            </option>
                        @endforeach
                    </select>

                    @error('tipo_documento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nombre --}}
                <div>
                    <label for="nombre_documento" class="block text-sm font-medium text-slate-700 mb-1">
                        Nombre del documento
                    </label>

                    <input id="nombre_documento"
                           type="text"
                           name="nombre_documento"
                           value="{{ old('nombre_documento') }}"
                           placeholder="Ej. Licencia chofer Jalisco 2026"
                           class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">

                    @error('nombre_documento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fecha documento --}}
                <div>
                    <label for="fecha_documento" class="block text-sm font-medium text-slate-700 mb-1">
                        Fecha del documento
                    </label>

                    <input id="fecha_documento"
                           type="date"
                           name="fecha_documento"
                           value="{{ old('fecha_documento') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">

                    @error('fecha_documento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fecha vencimiento --}}
                <div>
                    <label for="fecha_vencimiento" class="block text-sm font-medium text-slate-700 mb-1">
                        Fecha de vencimiento
                    </label>

                    <input id="fecha_vencimiento"
                           type="date"
                           name="fecha_vencimiento"
                           value="{{ old('fecha_vencimiento') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">

                    <p class="text-xs text-slate-400 mt-1">
                        Por ahora aplica principalmente para licencia de conducir.
                    </p>

                    @error('fecha_vencimiento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Observaciones --}}
                <div class="md:col-span-2">
                    <label for="observaciones" class="block text-sm font-medium text-slate-700 mb-1">
                        Observaciones
                    </label>

                    <textarea id="observaciones"
                              name="observaciones"
                              rows="3"
                              class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]"
                              placeholder="Notas internas del documento">{{ old('observaciones') }}</textarea>

                    @error('observaciones')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Archivo --}}
                <div class="md:col-span-2">
                    <label for="archivo" class="block text-sm font-medium text-slate-700 mb-1">
                        Archivo <span class="text-red-500">*</span>
                    </label>

                    <input id="archivo"
                           type="file"
                           name="archivo"
                           accept=".pdf,.jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-slate-600
                                  file:mr-4 file:rounded-lg file:border-0
                                  file:bg-[#0B265A] file:px-4 file:py-2
                                  file:text-sm file:font-medium file:text-white
                                  hover:file:bg-[#091f47]">

                    <p class="text-xs text-slate-400 mt-1">
                        Formatos permitidos: PDF, JPG, JPEG, PNG, WEBP. Máximo 10 MB.
                    </p>

                    @error('archivo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-medium text-white hover:bg-[#091f47]">
                    Guardar documento
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de documentos --}}
    <div class="rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 bg-white border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-700">Historial de documentos</h3>
        </div>

        @if(($documentos ?? collect())->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-3 text-left font-semibold">Nombre</th>
                            <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                            <th class="px-4 py-3 text-left font-semibold">Vencimiento</th>
                            <th class="px-4 py-3 text-left font-semibold">Vigente</th>
                            <th class="px-4 py-3 text-left font-semibold">Validación</th>
                            <th class="px-4 py-3 text-left font-semibold">Archivo</th>
                            <th class="px-4 py-3 text-left font-semibold">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($documentos as $doc)
                            @php
                                $fileUrl = $doc->archivo_path ? asset('storage/' . $doc->archivo_path) : null;
                                $nombreMostrar = $doc->nombre_documento ?: str_replace('_', ' ', $doc->tipo_documento);
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    {{ str_replace('_', ' ', $doc->tipo_documento) }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-slate-800">
                                        {{ $nombreMostrar }}
                                    </div>

                                    @if($doc->archivo_nombre_original)
                                        <div class="text-xs text-slate-500 mt-1">
                                            Archivo original: {{ $doc->archivo_nombre_original }}
                                        </div>
                                    @endif

                                    @if($doc->observaciones)
                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ $doc->observaciones }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ $doc->fecha_documento ? $doc->fecha_documento->format('d/m/Y') : '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/Y') : '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @if($doc->vigente)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                            Sí
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                            Histórico
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @if($doc->estatus_validacion === 'validado')
                                        <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">
                                            Validado
                                        </span>
                                    @elseif($doc->estatus_validacion === 'rechazado')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                            Rechazado
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">
                                            Pendiente
                                        </span>
                                    @endif

                                    @if($doc->validado_en)
                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ $doc->validado_en->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @if($fileUrl)
                                        <a href="{{ $fileUrl }}"
                                           target="_blank"
                                           class="text-blue-600 hover:underline">
                                            Ver archivo
                                        </a>

                                        @if($doc->mime_type)
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ $doc->mime_type }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-2">
                                        @if($fileUrl)
                                            <a href="{{ $fileUrl }}"
                                               target="_blank"
                                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">
                                                Abrir
                                            </a>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('empleados.documentos.destroy', [$empleado, $doc]) }}"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este documento?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-8 text-sm text-slate-500 bg-white">
                Este empleado todavía no tiene documentos cargados.
            </div>
        @endif
    </div>
</div>