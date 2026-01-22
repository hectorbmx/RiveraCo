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

                        <form method="POST" action="{{ route('mantenimiento.vehiculos.asignar', $vehiculo) }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Empleado --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Empleado <span class="text-red-500">*</span>
                                    </label>
                                    <select name="empleado_id"
                                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            required>
                                        <option value="">Selecciona un empleado...</option>
                                        @foreach($empleadosAsignables as $empleado)
                                            <option value="{{ $empleado->id_Empleado }}">
                                                {{ $empleado->Nombre . ' ' . $empleado->Apellidos ?? 'Empleado #' . $empleado->id_Empleado }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

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
                                        Póliza: {{ $polizaVigente->numero_poliza }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-xs">Vigencia</div>
                                    <div class="text-slate-800 font-medium">
                                        {{ \Carbon\Carbon::parse($polizaVigente->fecha_inicio)->format('d/m/Y') }}
                                        —
                                        {{ \Carbon\Carbon::parse($polizaVigente->fecha_fin)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-slate-500 text-xs mt-1">
                                        Estatus:
                                        <span class="font-semibold
                                            @if($polizaVigente->estatus === 'activa') text-emerald-700
                                            @elseif($polizaVigente->estatus === 'vencida') text-red-600
                                            @else text-slate-700 @endif">
                                            {{ ucfirst($polizaVigente->estatus) }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-xs">Costo anual</div>
                                    <div class="text-slate-800 font-medium">
                                        ${{ number_format($polizaVigente->costo_anual, 2) }}
                                    </div>

                                    @if($polizaVigente->archivo_poliza)
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/'.$polizaVigente->archivo_poliza) }}"
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

                    {{-- FORMULARIO NUEVA PÓLIZA --}}
                    <div class="border border-slate-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">
                            Registrar / actualizar póliza de seguro
                        </h3>

                        <form method="POST"
                            action="{{ route('mantenimiento.vehiculos.seguro.store', $vehiculo) }}"
                            enctype="multipart/form-data"
                            class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Aseguradora --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Aseguradora <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="aseguradora"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="{{ old('aseguradora') }}">
                                </div>

                                {{-- Número de póliza --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Número de póliza <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="numero_poliza"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="{{ old('numero_poliza') }}">
                                </div>

                                {{-- Tipo de cobertura --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Tipo de cobertura
                                    </label>
                                    <input type="text" name="tipo_cobertura"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Amplia, limitada, RC, etc."
                                        value="{{ old('tipo_cobertura') }}">
                                </div>

                                {{-- Costo anual --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Costo anual
                                    </label>
                                    <input type="number" step="0.01" min="0" name="costo_anual"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="{{ old('costo_anual') }}">
                                </div>

                                {{-- Fecha inicio --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Fecha inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="fecha_inicio"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="{{ old('fecha_inicio') }}">
                                </div>

                                {{-- Fecha fin --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Fecha fin <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="fecha_fin"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="{{ old('fecha_fin') }}">
                                </div>

                                {{-- Estatus --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Estatus <span class="text-red-500">*</span>
                                    </label>
                                    <select name="estatus"
                                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="activa" {{ old('estatus') === 'activa' ? 'selected' : '' }}>Activa</option>
                                        <option value="vencida" {{ old('estatus') === 'vencida' ? 'selected' : '' }}>Vencida</option>
                                        <option value="cancelada" {{ old('estatus') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                    </select>
                                </div>

                                {{-- Archivo póliza --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Archivo póliza (PDF/JPG/PNG)
                                    </label>
                                    <input type="file" name="archivo_poliza"
                                        class="w-full text-sm text-slate-600">
                                </div>
                            </div>

                            {{-- Notas --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Notas
                                </label>
                                <textarea name="notas" rows="3"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Comentarios adicionales sobre la póliza">{{ old('notas') }}</textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                                    Guardar póliza
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- HISTORIAL DE PÓLIZAS --}}
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
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Aseguradora</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Póliza</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Vigencia</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Estatus</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Costo anual</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Archivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historialSeguros as $seguro)
                                            <tr class="border-b border-slate-100">
                                                <td class="px-3 py-2 text-slate-700">
                                                    {{ $seguro->aseguradora }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ $seguro->numero_poliza }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ \Carbon\Carbon::parse($seguro->fecha_inicio)->format('d/m/Y') }}
                                                    —
                                                    {{ \Carbon\Carbon::parse($seguro->fecha_fin)->format('d/m/Y') }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    {{ ucfirst($seguro->estatus) }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    ${{ number_format($seguro->costo_anual, 2) }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">
                                                    @if($seguro->archivo_poliza)
                                                        <a href="{{ asset('storage/'.$seguro->archivo_poliza) }}"
                                                        target="_blank"
                                                        class="text-xs text-blue-600 hover:underline">
                                                            Ver archivo
                                                        </a>
                                                    @else
                                                        —
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
                <h2 class="text-lg font-semibold mb-4">Documentos del vehículo</h2>
                <p class="text-sm text-slate-500">
                    Aquí podrás subir y gestionar tarjeta de circulación, verificaciones, tenencias y otros documentos del vehículo.
                </p>
            @endif
            {{-- FIN TAB: DOCUMENTOS --}}

        </div> {{-- FIN CONTENEDOR TABS --}}
    </div>
@endsection
