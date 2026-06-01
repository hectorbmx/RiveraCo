@extends('layouts.admin')

@section('title', 'Editar vehículo')

@section('content')
    @php
        // Aseguramos que $tab siempre tenga algo
        $tab = $tab ?? request('tab', 'general');

        // Valores por defecto para evitar errores si no vienen del controlador
        $asignacionActual      = $asignacionActual      ?? null;
        $historialAsignaciones = $historialAsignaciones ?? collect();
        $empleadosAsignables   = $empleadosAsignables   ?? collect();

        $tabs = [
            'general'        => 'General',
            'asignacion'     => 'Asignación',
            'seguro'         => 'Seguro',
            'mantenimientos' => 'Mantenimientos',
            'documentos'     => 'Documentos',
        ];
    @endphp

    <div class="max-w-6xl mx-auto">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#0B265A]">
                    Editar vehículo
                </h1>
                <p class="text-sm text-slate-500">
                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }} — {{ $vehiculo->placas ?? 'Sin placas' }}
                </p>
            </div>

            <a href="{{ route('mantenimiento.vehiculos.index') }}"
               class="text-sm text-slate-500 hover:text-slate-800">
                ← Volver al listado
            </a>
        </div>

        {{-- ERRORES GLOBALES --}}
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- NAV TABS --}}
        <div class="border-b border-slate-200 mb-4">
            <nav class="-mb-px flex flex-wrap gap-2">
                @foreach($tabs as $key => $label)
                    @php
                        $isActive = $tab === $key;
                    @endphp
                    <a href="{{ route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => $key]) }}"
                       class="px-4 py-2 text-sm border-b-2 {{ $isActive
                            ? 'border-[#FFC107] text-[#0B265A] font-semibold'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- CONTENIDO DEL TAB --}}
        <div class="bg-white rounded-2xl shadow p-6">

            {{-- TAB: GENERAL --}}
            @if($tab === 'general')
                <h2 class="text-lg font-semibold mb-4">Datos generales del vehículo</h2>

                <form action="{{ route('mantenimiento.vehiculos.update', $vehiculo) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Marca --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Marca
                            </label>
                            <input type="text" name="marca"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   value="{{ old('marca', $vehiculo->marca) }}">
                        </div>

                        {{-- Modelo --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Modelo
                            </label>
                            <input type="text" name="modelo"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   value="{{ old('modelo', $vehiculo->modelo) }}">
                        </div>

                        {{-- Año --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Año
                            </label>
                            <input type="number" name="anio"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   min="1950" max="{{ date('Y') + 1 }}"
                                   value="{{ old('anio', $vehiculo->anio) }}">
                        </div>

                        {{-- Color --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Color
                            </label>
                            <input type="text" name="color"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   value="{{ old('color', $vehiculo->color) }}">
                        </div>

                        {{-- Placas --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Placas
                            </label>
                            <input type="text" name="placas"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   value="{{ old('placas', $vehiculo->placas) }}">
                        </div>

                        {{-- Serie --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Serie (VIN)
                            </label>
                            <input type="text" name="serie"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   value="{{ old('serie', $vehiculo->serie) }}">
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Tipo
                            </label>
                            <input type="text" name="tipo"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                          focus:border-[#FFC107] focus:ring-[#FFC107]"
                                   placeholder="Camioneta, auto, pickup..."
                                   value="{{ old('tipo', $vehiculo->tipo) }}">
                        </div>

                        {{-- Estatus --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Estatus
                            </label>
                            <select name="estatus"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                           focus:border-[#FFC107] focus:ring-[#FFC107]">
                                <option value="activo" {{ old('estatus', $vehiculo->estatus) === 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="en_taller" {{ old('estatus', $vehiculo->estatus) === 'en_taller' ? 'selected' : '' }}>En taller</option>
                                <option value="baja" {{ old('estatus', $vehiculo->estatus) === 'baja' ? 'selected' : '' }}>Baja</option>
                            </select>
                        </div>

                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('mantenimiento.vehiculos.index') }}"
                           class="px-4 py-2 text-sm rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 text-sm rounded-xl bg-[#0B265A] text-white font-semibold hover:bg-[#091c43]">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            @endif
            {{-- FIN TAB: GENERAL --}}

            {{-- TAB: ASIGNACIÓN --}}
            @if($tab === 'asignacion')
                <div class="space-y-6">

                    {{-- Mensaje de éxito --}}
                    @if(session('success'))
                        <div class="mb-2 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- RESUMEN ASIGNACIÓN ACTUAL --}}
                    <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/60">
                        <h2 class="text-sm font-semibold text-slate-700 mb-3">
                            Asignación actual
                        </h2>

                        @if($asignacionActual)
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 text-sm">
                                <div>
                                    <div class="text-slate-800 font-medium">
                                        {{ $asignacionActual->empleado->Nombre . ' ' . $asignacionActual->empleado->Apellidos ?? 'Empleado #'.$asignacionActual->empleado_id }}
                                    </div>
                                    <div class="text-slate-500 text-xs">
                                        Asignado desde:
                                        {{ \Carbon\Carbon::parse($asignacionActual->fecha_asignacion)->format('d/m/Y') }}
                                    </div>
                                </div>

                                <div class="text-xs text-slate-500">
                                    Estado:
                                    <span class="font-semibold text-emerald-700">Asignado</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">
                                Este vehículo <span class="font-semibold">no está asignado actualmente</span> a ningún empleado.
                            </p>
                        @endif
                    </div>

                    {{-- FORMULARIO DE ASIGNACIÓN --}}
                    <div class="border border-slate-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">
                            Asignar / cambiar vehículo de empleado
                        </h3>

                        <form id="form_asignar_vehiculo" method="POST" action="{{ route('mantenimiento.vehiculos.asignar', $vehiculo) }}" class="space-y-4" enctype="multipart/form-data">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Empleado --}}
                               <div class="relative">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Empleado <span class="text-red-500">*</span>
                                    </label>

                                    {{-- id real que se enviará al form --}}
                                    <input type="hidden" name="empleado_id" id="empleado_id" required>

                                    {{-- input visible para buscar --}}
                                    <input
                                        type="text"
                                        id="empleado_search"
                                        autocomplete="off"
                                        placeholder="Buscar empleado por nombre o apellidos..."
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                    {{-- empleado seleccionado --}}
                                    <div id="empleado_selected" class="mt-2 hidden">
                                        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm border border-blue-200">
                                            <span id="empleado_selected_text"></span>
                                            <button type="button" id="clear_empleado" class="text-red-500 hover:text-red-700 font-semibold">
                                                ×
                                            </button>
                                        </div>
                                    </div>

                                    {{-- resultados --}}
                                    <div
                                        id="empleado_results"
                                        class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"
                                    >
                                        @foreach($empleadosAsignables as $empleado)
                                            @php
                                                $nombreCompleto = trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? ''));
                                                $texto = $nombreCompleto !== '' ? $nombreCompleto : 'Empleado #'.$empleado->id_Empleado;
                                            @endphp

                                            <button
                                                type="button"
                                                class="empleado-option w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                                data-id="{{ $empleado->id_Empleado }}"
                                                data-text="{{ $texto }}"
                                            >
                                                {{ $texto }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Fecha asignación --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Fecha de asignación
                                    </label>
                                    <input type="date" name="fecha_asignacion"
                                           value="{{ now()->toDateString() }}"
                                           class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                            </div> 
                            {{-- Km inicial --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Km inicial <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="km_inicial"
                                min="0"
                                step="1"
                                value="{{ old('km_inicial', $kmSugeridoAsignacion ?? 0) }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <p class="mt-1 text-xs text-slate-500">
                                Se sugiere el último kilometraje conocido del vehículo. Puedes ajustarlo si esta asignación fue solo administrativa.
                            </p>
                        </div>

                        {{-- Fotos del estado del vehículo --}}
                        <div class="col-span-1 md:col-span-2">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-600">
                                    Fotos del estado del vehículo
                                </label>
                                <span id="fotos_badge"
                                    class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                                    0 / 6
                                </span>
                            </div>

                            {{-- Dropzone --}}
                            <div id="fotos_dropzone"
                                class="flex flex-col items-center justify-center gap-1 border-2 border-dashed border-slate-300
                                        rounded-lg p-5 cursor-pointer hover:bg-slate-50 transition text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-600">Agregar fotos</p>
                                <p class="text-xs text-slate-400">Máximo 6 fotos · JPG, PNG, HEIC</p>
                            </div>

                            {{-- Input real (oculto) --}}
                            <input type="file" id="fotos_input" name="fotos[]"
                                multiple accept="image/*" class="hidden">

                            {{-- Grid de previews --}}
                            <div id="fotos_preview"
                                class="mt-3 flex flex-wrap gap-2"></div>

                            {{-- Hint --}}
                            <p id="fotos_hint" class="hidden mt-1 text-xs text-slate-400">
                                Las imágenes se optimizarán automáticamente al guardar.
                            </p>
                        </div>

                            {{-- Notas --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Notas
                                </label>
                                <textarea name="notas" rows="3"
                                          class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Ej. Asignado al residente de la obra X, uso diario..."></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                                    Guardar asignación
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- HISTORIAL DE ASIGNACIONES --}}
                    <div class="border border-slate-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">
                            Historial de asignaciones
                        </h3>

                        @if($historialAsignaciones->isEmpty())
                            <p class="text-sm text-slate-500">
                                No hay registros de asignaciones previas para este vehículo.
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Empleado</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Inicio</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Km Inicial</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Fin</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">KM final</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Notas</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Fotos</th> 

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historialAsignaciones as $asig)
                                            <tr class="border-b border-slate-100">
                                                <td class="px-3 py-2 text-slate-700">
                                                    {{ $asig->empleado->Nombre . ' ' . $asig->empleado->Apellidos ?? 'Empleado #'.$asig->empleado_id }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d/m/Y') : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $asig->km_inicial !== null ? number_format($asig->km_inicial) : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $asig->fecha_fin ? \Carbon\Carbon::parse($asig->fecha_fin)->format('d/m/Y') : 'Actual' }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $asig->km_final !== null ? number_format($asig->km_final) : ($asig->fecha_fin ? '—' : 'Actual') }}
                                                </td>

                                                <td class="px-3 py-2 text-slate-500">
                                                    {{ $asig->notas ?? '—' }}
                                                </td>
                                                   {{-- ← Columna fotos --}}
                                                    <td class="px-3 py-2">
                                                        @if($asig->fotos->isNotEmpty())
                                                            <button
                                                                type="button"
                                                                onclick="abrirModalFotos({{ $asig->fotos->map(fn($f) => asset('storage/'.$f->url))->toJson() }}, '{{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}')"
                                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v13.5a1.5 1.5 0 001.5 1.5z" />
                                                </svg>
                                                                {{ $asig->fotos->count() }}
                                                            </button>
                                                        @else
                                                            <span class="text-slate-300">—</span>
                                                        @endif
                                                    </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- MODAL FOTOS --}}
                <div id="modal_fotos"
                     class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4"
                     onclick="if(event.target===this) cerrarModalFotos()">

                    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Fotos de recepción</p>
                                <p id="modal_fotos_subtitulo" class="text-xs text-slate-500 mt-0.5"></p>
                            </div>
                            <button type="button" onclick="cerrarModalFotos()"
                                    class="text-slate-400 hover:text-slate-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Grid fotos --}}
                        <div id="modal_fotos_grid"
                             class="overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        </div>

                    </div>
                </div>

                {{-- Lightbox individual --}}
                <div id="modal_lightbox"
                     class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-black/80 p-4"
                     onclick="cerrarLightbox()">
                    <img id="lightbox_img" src="" alt="Foto ampliada"
                         class="max-w-full max-h-[90vh] rounded-lg object-contain">
                </div>
            @endif
            {{-- FIN TAB: ASIGNACIÓN --}}
            
            {{-- TAB: SEGURO --}}
            @if($tab === 'seguro')

            @php
                $polizaVigente = $polizaVigente ?? null;
                $historialSeguros = $historialSeguros ?? collect();
            @endphp

            <div class="space-y-6">

                {{-- Mensaje de éxito --}}
                @if(session('success'))
                    <div class="mb-2 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- BOTON NUEVA POLIZA --}}
                <div class="flex justify-end">
                    <a href="{{ route('vehiculos.seguros.create', $vehiculo) }}"
                       class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Registrar nueva póliza
                    </a>
                </div>


                {{-- PÓLIZA VIGENTE --}}
                <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/60">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">
                        Póliza vigente
                    </h2>

                    @if($polizaVigente)

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                            <div>
                                <div class="text-slate-500 text-xs">Aseguradora</div>
                                <div class="text-slate-800 font-medium">
                                    {{ $polizaVigente->aseguradora }}
                                </div>

                                <div class="text-slate-500 text-xs mt-1">
                                    Póliza: {{ $polizaVigente->poliza_numero }}
                                </div>
                            </div>


                            <div>
                                <div class="text-slate-500 text-xs">Vigencia</div>

                                <div class="text-slate-800 font-medium">
                                    {{ \Carbon\Carbon::parse($polizaVigente->vigencia_desde)->format('d/m/Y') }}
                                    —
                                    {{ \Carbon\Carbon::parse($polizaVigente->vigencia_hasta)->format('d/m/Y') }}
                                </div>

                                <div class="text-slate-500 text-xs mt-1">
                                    Estatus:
                                    <span class="font-semibold
                                        @if($polizaVigente->estatus === 'vigente') text-emerald-700
                                        @elseif($polizaVigente->estatus === 'vencido') text-red-600
                                        @else text-slate-700 @endif">
                                        {{ ucfirst($polizaVigente->estatus) }}
                                    </span>
                                </div>
                            </div>


                            <div>
                                <div class="text-slate-500 text-xs">Costo anual</div>

                                <div class="text-slate-800 font-medium">
                                    ${{ number_format($polizaVigente->costo, 2) }}
                                </div>

                                @if($polizaVigente->documento_path)
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/'.$polizaVigente->documento_path) }}"
                                           target="_blank"
                                           class="text-xs text-blue-600 hover:underline">
                                            Ver archivo de póliza
                                        </a>
                                    </div>
                                @endif
                            </div>

                        </div>

                    @else

                        <p class="text-sm text-slate-500">
                            No hay póliza vigente registrada para este vehículo.
                        </p>

                    @endif

                </div>


                {{-- HISTORIAL --}}
            <div class="border border-slate-200 rounded-lg p-4">

                <h3 class="text-sm font-semibold text-slate-700 mb-3">
                    Historial de pólizas
                </h3>

                @if($historialSeguros->isEmpty())

                    <p class="text-sm text-slate-500">
                        No hay pólizas registradas para este vehículo.
                    </p>

                @else

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-xs">

                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-2 py-2"></th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Aseguradora</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Póliza</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Vigencia</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Estatus</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Costo anual</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Archivo</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500"></th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($historialSeguros as $seguro)
                                    @php
                                        $hoy = \Carbon\Carbon::today();
                                        $fin = $seguro->vigencia_hasta;
                                        $dias = $fin ? $hoy->diffInDays($fin, false) : null;

                                        if ($fin && $fin->lt($hoy)) {
                                            $tooltip = 'Vencido hace ' . abs($dias) . ' días';
                                            $estadoColor = 'rojo';
                                        } elseif ($dias !== null && $dias <= 60) {
                                            $tooltip = 'Vence en ' . $dias . ' días';
                                            $estadoColor = 'amarillo';
                                        } else {
                                            $tooltip = 'Vigente';
                                            $estadoColor = 'verde';
                                        }
                                    @endphp

                                    <tr class="border-b border-slate-100">

                                        <td class="px-2 py-3 align-middle">
                                            <span
                                                title="{{ $tooltip }}"
                                                style="
                                                    display:inline-block;
                                                    width:12px;
                                                    height:12px;
                                                    border-radius:9999px;
                                                    background:
                                                    @if($estadoColor === 'rojo')
                                                        #ef4444
                                                    @elseif($estadoColor === 'amarillo')
                                                        #facc15
                                                    @else
                                                        #22c55e
                                                    @endif
                                                    ;
                                                "
                                            ></span>
                                        </td>

                                        <td class="px-3 py-2 text-slate-700">
                                            {{ $seguro->aseguradora }}
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            {{ $seguro->poliza_numero }}
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            {{ \Carbon\Carbon::parse($seguro->vigencia_desde)->format('d/m/Y') }}
                                            —
                                            {{ \Carbon\Carbon::parse($seguro->vigencia_hasta)->format('d/m/Y') }}
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            {{ ucfirst($seguro->estatus) }}
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            ${{ number_format($seguro->costo, 2) }}
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            @if($seguro->documento_path)
                                                <a href="{{ asset('storage/'.$seguro->documento_path) }}"
                                                   target="_blank"
                                                   class="text-xs text-blue-600 hover:underline">
                                                    Ver archivo
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="px-3 py-2">
                                            <a href="{{ route('vehiculos.seguros.edit', [$vehiculo, $seguro]) }}"
                                               class="text-xs text-blue-600 hover:underline">
                                                Editar
                                            </a>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>

                    </div>

                @endif

            </div>

            </div>

            @endif
            {{-- FIN TAB: SEGURO --}}

           {{-- TAB: MANTENIMIENTOS --}}
            @if($tab === 'mantenimientos')
                @php
                    $mantenimientosVehiculo = $mantenimientosVehiculo ?? collect();
                    $statsMantenimientos = $statsMantenimientos ?? [
                        'total'       => 0,
                        'pendiente'   => 0,
                        'en_proceso'  => 0,
                        'completado'  => 0,
                        'cancelado'   => 0,
                    ];
                @endphp

                <div class="space-y-6">

                    {{-- HEADER + BOTÓN NUEVO --}}
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">
                                Mantenimientos del vehículo
                            </h2>
                            <p class="text-sm text-slate-500">
                                Histórico de mantenimientos programados y de emergencia para esta unidad.
                            </p>
                        </div>

                        <a href="{{ route('mantenimiento.mantenimientos.create', ['vehiculo_id' => $vehiculo->id]) }}"
                           class="inline-flex items-center px-3 py-2 rounded-lg text-sm bg-blue-600 text-white font-medium hover:bg-blue-700">
                            Registrar mantenimiento
                        </a>
                    </div>

                    {{-- RESUMEN / ESTADÍSTICAS --}}
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
                        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                            <div class="text-slate-500">Total</div>
                            <div class="text-lg font-semibold text-slate-800">
                                {{ $statsMantenimientos['total'] }}
                            </div>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                            <div class="text-slate-500">Pendientes</div>
                            <div class="text-lg font-semibold text-amber-600">
                                {{ $statsMantenimientos['pendiente'] }}
                            </div>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                            <div class="text-slate-500">En proceso</div>
                            <div class="text-lg font-semibold text-blue-600">
                                {{ $statsMantenimientos['en_proceso'] }}
                            </div>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                            <div class="text-slate-500">Completados</div>
                            <div class="text-lg font-semibold text-emerald-600">
                                {{ $statsMantenimientos['completado'] }}
                            </div>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                            <div class="text-slate-500">Cancelados</div>
                            <div class="text-lg font-semibold text-slate-500">
                                {{ $statsMantenimientos['cancelado'] }}
                            </div>
                        </div>
                    </div>

                    {{-- LISTADO DE MANTENIMIENTOS --}}
                    <div class="border border-slate-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">
                            Historial de mantenimientos
                        </h3>

                        @if($mantenimientosVehiculo->isEmpty())
                            <p class="text-sm text-slate-500">
                                No hay mantenimientos registrados para este vehículo.
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Fecha prog.</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Tipo</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Categoría</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Obra</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Mecánico</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Estatus</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Costo total</th>
                                            <th class="px-3 py-2 text-right font-semibold text-slate-500">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mantenimientosVehiculo as $mant)
                                            <tr class="border-b border-slate-100">
                                                {{-- Fecha programada --}}
                                                <td class="px-3 py-2 text-slate-700">
                                                    {{ $mant->fecha_programada
                                                        ? \Carbon\Carbon::parse($mant->fecha_programada)->format('d/m/Y')
                                                        : '—' }}
                                                </td>

                                                {{-- Tipo --}}
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ ucfirst($mant->tipo) }}
                                                </td>

                                                {{-- Categoría --}}
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $mant->categoria_mantenimiento ?? '—' }}
                                                </td>

                                                {{-- Obra --}}
                                                <td class="px-3 py-2 text-slate-600">
                                                    @if($mant->obra)
                                                        {{ $mant->obra->nombre_corto ?? $mant->obra->nombre }}
                                                    @else
                                                        <span class="text-slate-400">Sin obra</span>
                                                    @endif
                                                </td>

                                                {{-- Mecánico --}}
                                                <td class="px-3 py-2 text-slate-600">
                                                    @if($mant->mecanico)
                                                        {{ $mant->mecanico->nombre }}
                                                    @else
                                                        <span class="text-slate-400">No asignado</span>
                                                    @endif
                                                </td>

                                                {{-- Estatus --}}
                                                <td class="px-3 py-2">
                                                    @php
                                                        $badgeMant = [
                                                            'pendiente'   => 'bg-amber-100 text-amber-700',
                                                            'en_proceso'  => 'bg-blue-100 text-blue-700',
                                                            'completado'  => 'bg-emerald-100 text-emerald-700',
                                                            'cancelado'   => 'bg-slate-100 text-slate-600',
                                                        ];
                                                    @endphp
                                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        {{ $badgeMant[$mant->estatus] ?? 'bg-slate-100 text-slate-600' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $mant->estatus)) }}
                                                    </span>
                                                </td>

                                                {{-- Costo total --}}
                                                <td class="px-3 py-2 text-slate-700">
                                                    ${{ number_format($mant->costo_total, 2) }}
                                                </td>

                                                {{-- Acciones --}}
                                                <td class="px-3 py-2 text-right">
                                                    <a href="{{ route('mantenimiento.mantenimientos.show', $mant) }}"
                                                       class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">
                                                        Ver
                                                    </a>
                                                    <a href="{{ route('mantenimiento.mantenimientos.edit', $mant) }}"
                                                       class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                                        Editar
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>
            @endif
            {{-- FIN TAB: MANTENIMIENTOS --}}

            {{-- TAB: DOCUMENTOS --}}
            @if($tab === 'documentos')
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold mb-1">Documentos del vehículo</h2>
                        <p class="text-sm text-slate-500">
                            Aquí podrás subir y consultar la tarjeta de circulación del vehículo.
                        </p>
                    </div>

                    @if(session('success'))
                        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Documento vigente --}}
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold mb-4">Tarjeta de circulación vigente</h3>

                        @php
                            $tarjetaVigente = $vehiculo->documentos
                                ->where('tipo', 'tarjeta_circulacion')
                                ->where('vigente', true)
                                ->sortByDesc('created_at')
                                ->first();
                        @endphp

                        @if($tarjetaVigente)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="block text-slate-500">Archivo</span>
                                    <a href="{{ asset('storage/' . $tarjetaVigente->archivo_path) }}"
                                       target="_blank"
                                       class="text-blue-600 hover:underline">
                                        Ver documento
                                    </a>
                                </div>

                                <div>
                                    <span class="block text-slate-500">Nombre original</span>
                                    <span>{{ $tarjetaVigente->nombre_original ?: '—' }}</span>
                                </div>

                                <div>
                                    <span class="block text-slate-500">Fecha del documento</span>
                                    <span>{{ $tarjetaVigente->fecha_documento ? $tarjetaVigente->fecha_documento->format('d/m/Y') : '—' }}</span>
                                </div>

                                <div>
                                    <span class="block text-slate-500">Fecha de vencimiento</span>
                                    <span>{{ $tarjetaVigente->fecha_vencimiento ? $tarjetaVigente->fecha_vencimiento->format('d/m/Y') : '—' }}</span>
                                </div>

                                <div class="md:col-span-2">
                                    <span class="block text-slate-500">Observaciones</span>
                                    <span>{{ $tarjetaVigente->observaciones ?: '—' }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No hay una tarjeta de circulación vigente cargada.</p>
                        @endif
                    </div>

                    {{-- Formulario de carga --}}
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold mb-4">Subir nueva tarjeta de circulación</h3>

                        <form action="{{ route('vehiculos.documentos.store', $vehiculo) }}"
                              method="POST"
                              enctype="multipart/form-data"
                              class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Archivo
                                </label>
                                <input
                                    type="file"
                                    name="archivo"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    required
                                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                >
                                <p class="mt-1 text-xs text-slate-500">
                                    Formatos permitidos: PDF, JPG, JPEG, PNG, WEBP. Máximo 10 MB.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Fecha del documento
                                    </label>
                                    <input
                                        type="date"
                                        name="fecha_documento"
                                        value="{{ old('fecha_documento') }}"
                                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Fecha de vencimiento
                                    </label>
                                    <input
                                        type="date"
                                        name="fecha_vencimiento"
                                        value="{{ old('fecha_vencimiento') }}"
                                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Observaciones
                                </label>
                                <textarea
                                    name="observaciones"
                                    rows="3"
                                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                >{{ old('observaciones') }}</textarea>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-medium text-white hover:bg-[#163a7a]"
                                >
                                    Subir documento
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Historial --}}
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold mb-4">Historial de documentos</h3>

                        @php
                            $documentos = $vehiculo->documentos
                                ->where('tipo', 'tarjeta_circulacion')
                                ->sortByDesc('created_at');
                        @endphp

                        @if($documentos->count())
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b bg-slate-50 text-left">
                                            <th class="px-4 py-3 font-semibold text-slate-700">Fecha carga</th>
                                            <th class="px-4 py-3 font-semibold text-slate-700">Documento</th>
                                            <th class="px-4 py-3 font-semibold text-slate-700">Fecha documento</th>
                                            <th class="px-4 py-3 font-semibold text-slate-700">Vencimiento</th>
                                            <th class="px-4 py-3 font-semibold text-slate-700">Vigente</th>
                                            <th class="px-4 py-3 font-semibold text-slate-700 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($documentos as $documento)
                                            <tr class="border-b">
                                                <td class="px-4 py-3">
                                                    {{ $documento->created_at?->format('d/m/Y H:i') ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <a href="{{ asset('storage/' . $documento->archivo_path) }}"
                                                       target="_blank"
                                                       class="text-blue-600 hover:underline">
                                                        {{ $documento->nombre_original ?: 'Ver archivo' }}
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ $documento->fecha_documento ? $documento->fecha_documento->format('d/m/Y') : '—' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ $documento->fecha_vencimiento ? $documento->fecha_vencimiento->format('d/m/Y') : '—' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($documento->vigente)
                                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                                            Sí
                                                        </span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                                            No
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <form action="{{ route('vehiculos.documentos.destroy', [$vehiculo, $documento]) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('¿Deseas eliminar este documento?');"
                                                          class="inline-block">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">Aún no hay documentos cargados.</p>
                        @endif
                    </div>
                </div>
            @endif
            {{-- FIN TAB: DOCUMENTOS --}}

        </div> {{-- FIN CONTENEDOR TABS --}}
    </div>
@endsection

@push('scripts')
<script>
// Funciones globales para el modal de fotos del historial (fuera del DOMContentLoaded)
function abrirModalFotos(urls, empleado) {
    const grid = document.getElementById('modal_fotos_grid');
    const sub  = document.getElementById('modal_fotos_subtitulo');
    if(!grid || !sub) return;

    sub.textContent = empleado;
    grid.innerHTML  = '';

    urls.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'group relative aspect-square rounded-lg overflow-hidden border border-slate-200 hover:border-slate-400 transition';
        btn.onclick = (e) => { e.stopPropagation(); abrirLightbox(url); };

        const img = document.createElement('img');
        img.src   = url;
        img.alt   = 'Foto ' + (i + 1);
        img.className = 'w-full h-full object-cover group-hover:scale-105 transition duration-200';

        btn.appendChild(img);
        grid.appendChild(btn);
    });

    document.getElementById('modal_fotos').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarModalFotos() {
    const modal = document.getElementById('modal_fotos');
    if(modal) modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function abrirLightbox(url) {
    const lbImg = document.getElementById('lightbox_img');
    const lbModal = document.getElementById('modal_lightbox');
    if(lbImg && lbModal) {
        lbImg.src = url;
        lbModal.classList.remove('hidden');
    }
}

function cerrarLightbox() {
    const lbModal = document.getElementById('modal_lightbox');
    const lbImg = document.getElementById('lightbox_img');
    if(lbModal && lbImg) {
        lbModal.classList.add('hidden');
        lbImg.src = '';
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarLightbox();
        cerrarModalFotos();
    }
});

// Inicialización de scripts dependientes de elementos del DOM
document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. SCRIPT DROPZONE DE FOTOS (Solo pestaña asignación) ---
    const fileInput   = document.getElementById('fotos_input');
    const dropzone    = document.getElementById('fotos_dropzone');
    const preview     = document.getElementById('fotos_preview');
    const badge       = document.getElementById('fotos_badge');
    const hint        = document.getElementById('fotos_hint');
    const formAsignar = document.getElementById('form_asignar_vehiculo');

    if (fileInput && dropzone) {
        const MAX = 6;
        let selectedFiles = [];

        dropzone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            handleNewFiles(Array.from(fileInput.files));
        });

        function handleNewFiles(incoming) {
            const slots = MAX - selectedFiles.length;
            if (slots <= 0) return;
            incoming.slice(0, slots).forEach(file => {
                selectedFiles.push(file);
                renderThumb(file, selectedFiles.length - 1);
            });
            syncInput();
            updateUI();
        }

        function renderThumb(file, idx) {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.dataset.idx = idx;
                wrap.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Foto ' + (idx + 1);
                img.className = 'w-full h-full object-cover';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerHTML = '×';
                btn.className = 'absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center leading-none hover:bg-red-600';
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeFile(idx);
                });

                wrap.appendChild(img);
                wrap.appendChild(btn);
                preview.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        }

        function removeFile(idx) {
            selectedFiles.splice(idx, 1);
            rebuildPreviews();
            syncInput();
            updateUI();
        }

        function rebuildPreviews() {
            preview.innerHTML = '';
            selectedFiles.forEach((file, i) => renderThumb(file, i));
        }

        function syncInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(f => dt.items.add(f));
            fileInput.files = dt.files;
        }

        function updateUI() {
            const count = selectedFiles.length;
            badge.textContent = count + ' / ' + MAX;

            if (count >= MAX) {
                badge.className = 'text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-300';
                dropzone.classList.add('opacity-40', 'pointer-events-none');
            } else {
                badge.className = 'text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200';
                dropzone.classList.remove('opacity-40', 'pointer-events-none');
            }

            hint.classList.toggle('hidden', count === 0);
        }

        if (formAsignar) {
            formAsignar.addEventListener('submit', function(e) {
                syncInput();
            });
        }
    }

    // --- 2. SCRIPT DE BÚSQUEDA DE EMPLEADOS (Solo pestaña asignación) ---
    const searchInput = document.getElementById('empleado_search');
    const hiddenInput = document.getElementById('empleado_id');
    const resultsBox = document.getElementById('empleado_results');
    const options = Array.from(document.querySelectorAll('.empleado-option'));
    const selectedBox = document.getElementById('empleado_selected');
    const selectedText = document.getElementById('empleado_selected_text');
    const clearBtn = document.getElementById('clear_empleado');

    if (searchInput) {
        function showResults() {
            resultsBox.classList.remove('hidden');
        }

        function hideResults() {
            resultsBox.classList.add('hidden');
        }

        function filterOptions() {
            const query = searchInput.value.toLowerCase().trim();
            let visibles = 0;

            options.forEach(option => {
                const text = option.dataset.text.toLowerCase();
                const match = text.includes(query);

                option.classList.toggle('hidden', !match);
                if (match) visibles++;
            });

            if (visibles > 0 && searchInput.value.trim() !== '') {
                showResults();
            } else {
                hideResults();
            }
        }

        function selectEmpleado(id, text) {
            hiddenInput.value = id;
            searchInput.value = text;
            selectedText.textContent = text;
            selectedBox.classList.remove('hidden');
            hideResults();
        }

        function clearEmpleado() {
            hiddenInput.value = '';
            searchInput.value = '';
            selectedText.textContent = '';
            selectedBox.classList.add('hidden');

            options.forEach(option => option.classList.remove('hidden'));
        }

        searchInput.addEventListener('focus', function () {
            if (searchInput.value.trim() !== '') {
                filterOptions();
            }
        });

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            selectedBox.classList.add('hidden');
            filterOptions();
        });

        options.forEach(option => {
            option.addEventListener('click', function () {
                selectEmpleado(this.dataset.id, this.dataset.text);
            });
        });

        clearBtn.addEventListener('click', function () {
            clearEmpleado();
        });

        document.addEventListener('click', function (e) {
            const container = searchInput.closest('.relative');
            if (container && !container.contains(e.target)) {
                hideResults();
            }
        });
    }
});
</script>
@endpush
