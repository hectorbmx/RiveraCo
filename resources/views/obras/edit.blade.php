@extends('layouts.admin')

@section('title', 'Editar Obra')

@section('content')

<div class="max-w-8xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                {{ $obra->nombre }}
            </h1>
            <p class="text-sm text-slate-500">
                Cliente: <span class="font-medium">{{ $obra->cliente->nombre_comercial ?? '-' }}</span><br>
                Clave: <span class="font-mono">{{ $obra->clave_obra }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('obras.index') }}"
               class="text-sm text-slate-600 hover:text-slate-900">
                ← Volver a la lista de obras
            </a>
        </div>
    </div>

    {{-- TABS --}}
    @php
        $tabs = [
            'general'      => 'Información general',
            'contratos'    => 'Contratos',
            'planos'       => 'Planos',
            'presupuestos' => 'Presupuestos',
            'planeacion'   => 'Planeacion',
            // 'gastos'       => 'Gastos',
            'reposicion-gastos' => 'Reposición gastos',
            'pilas'        => 'Pilas',
            'empleados'    => 'Empleados',
            'maquinaria'   => 'Maquinaria',
            'horas-maquina'=> 'Horas maquina',
            'comisiones'   => 'Comisiones',
            'facturacion'  => 'Facturacion',
            'relacionar'  => 'Relacionar Facturas',
            'asistencias'  => 'Asistencias',
        ];
    @endphp

    <div class="border-b border-slate-200 mb-4">
        <nav class="-mb-px flex flex-wrap gap-2">
            @foreach($tabs as $key => $label)
                @php
                    $isActive = $tab === $key;
                @endphp
                <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => $key]) }}"
                   class="px-4 py-2 text-sm border-b-2 {{ $isActive ? 'border-[#FFC107] text-[#0B265A] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- CONTENIDO DEL TAB --}}
    <div class="bg-white rounded-2xl shadow p-6">

        {{-- TAB: INFORMACIÓN GENERAL --}}
        <!-- @if($tab === 'general')
            <h2 class="text-lg font-semibold mb-4">Información general</h2>

            <form action="{{ route('obras.update', $obra) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Cliente y clave --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cliente_id" class="block text-sm font-medium text-slate-700">
                            Cliente
                        </label>
                        <select id="cliente_id" name="cliente_id"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                       focus:border-[#FFC107] focus:ring-[#FFC107]">
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(old('cliente_id', $obra->cliente_id) == $cliente->id)>
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
                            Clave de obra
                        </label>
                        <input type="text" id="clave_obra" name="clave_obra"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]"
                               value="{{ old('clave_obra', $obra->clave_obra) }}">
                        @error('clave_obra')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Nombre --}}
                <div>
                    <label for="nombre" class="block text-sm font-medium text-slate-700">
                        Nombre de la obra
                    </label>
                    <input type="text" id="nombre" name="nombre"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('nombre', $obra->nombre) }}">
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
                               value="{{ old('tipo_obra', $obra->tipo_obra) }}">
                        @error('tipo_obra')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700">
                            Status
                        </label>
                        @php
                            $statuses = ['planeacion','ejecucion','suspendida','terminada','cancelada'];
                        @endphp
                       <select id="estatus_nuevo" name="estatus_nuevo"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                    focus:border-[#FFC107] focus:ring-[#FFC107]">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('estatus_nuevo', $currentStatus) == $value)>
                                    {{ ucfirst($label) }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
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
                                <option value="{{ $user->id }}" @selected(old('responsable_id', $obra->responsable_id) == $user->id)>
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
                               value="{{ old('fecha_inicio_programada', optional($obra->fecha_inicio_programada)->format('Y-m-d')) }}">
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
                               value="{{ old('fecha_inicio_real', optional($obra->fecha_inicio_real)->format('Y-m-d')) }}">
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
                               value="{{ old('fecha_fin_programada', optional($obra->fecha_fin_programada)->format('Y-m-d')) }}">
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
                               value="{{ old('fecha_fin_real', optional($obra->fecha_fin_real)->format('Y-m-d')) }}">
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
                               value="{{ old('monto_contratado', $obra->monto_contratado) }}">
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
                               value="{{ old('monto_modificado', $obra->monto_modificado) }}">
                        @error('monto_modificado')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @php
                    $montoBase = $obra->monto_modificado ?? $obra->monto_contratado ?? 0;
                    $cobrado   = $avanceCobrado ?? 0;

                    $pctCobrado = $montoBase > 0
                        ? min(100, round(($cobrado / $montoBase) * 100))
                        : 0;
                @endphp
                <div class="mt-4 bg-white border rounded-xl p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">
                        Avance de cobro
                    </h3>

                    <div>
                        <div class="flex justify-between text-xs text-slate-600 mb-1">
                            <span>Monto cobrado</span>
                            <span>
                                ${{ number_format($cobrado, 2) }}
                                /
                                ${{ number_format($montoBase, 2) }}
                                ({{ $pctCobrado }}%)
                            </span>
                        </div>

                        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-[#0B265A]" style="width: {{ $pctCobrado }}%"></div>
                        </div>
                    </div>
                </div>


                {{-- Totales de obra --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label for="profundidad_total" class="block text-sm font-medium text-slate-700">
                            Profundidad total (m)
                        </label>
                        <input type="number" step="0.01" id="profundidad_total" name="profundidad_total"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                    focus:border-[#FFC107] focus:ring-[#FFC107]"
                            value="{{ old('profundidad_total', $obra->profundidad_total) }}">
                        @error('profundidad_total')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="kg_acero_total" class="block text-sm font-medium text-slate-700">
                            KG Acero total
                        </label>
                        <input type="number" step="0.01" id="kg_acero_total" name="kg_acero_total"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                    focus:border-[#FFC107] focus:ring-[#FFC107]"
                            value="{{ old('kg_acero_total', $obra->kg_acero_total) }}">
                        @error('kg_acero_total')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bentonita_total" class="block text-sm font-medium text-slate-700">
                            Bentonita total (m³)
                        </label>
                        <input type="number" step="0.01" id="bentonita_total" name="bentonita_total"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                    focus:border-[#FFC107] focus:ring-[#FFC107]"
                            value="{{ old('bentonita_total', $obra->bentonita_total) }}">
                        @error('bentonita_total')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="concreto_total" class="block text-sm font-medium text-slate-700">
                            Concreto total (m³)
                        </label>
                        <input type="number" step="0.01" id="concreto_total" name="concreto_total"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                    focus:border-[#FFC107] focus:ring-[#FFC107]"
                            value="{{ old('concreto_total', $obra->concreto_total) }}">
                        @error('concreto_total')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
                

{{-- Barras de avance de obra --}}
@php
    $maxProfundidad = (float) ($obra->profundidad_total ?? 0);
    $avProfundidad  = (float) ($avanceObra['profundidad'] ?? 0);
    $pctProfundidad = $maxProfundidad > 0 ? min(100, round(($avProfundidad / $maxProfundidad) * 100)) : 0;

    $maxAcero = (float) ($obra->kg_acero_total ?? 0);
    $avAcero  = (float) ($avanceObra['kg_acero'] ?? 0);
    $pctAcero = $maxAcero > 0 ? min(100, round(($avAcero / $maxAcero) * 100)) : 0;

    $maxBentonita = (float) ($obra->bentonita_total ?? 0);
    $avBentonita  = (float) ($avanceObra['bentonita'] ?? 0);
    $pctBentonita = $maxBentonita > 0 ? min(100, round(($avBentonita / $maxBentonita) * 100)) : 0;

    $maxConcreto = (float) ($obra->concreto_total ?? 0);
    $avConcreto  = (float) ($avanceObra['concreto'] ?? 0);
    $pctConcreto = $maxConcreto > 0 ? min(100, round(($avConcreto / $maxConcreto) * 100)) : 0;
@endphp


<div class="mt-6 bg-white border rounded-xl p-4 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-700 mb-3">
        Avance de obra (según comisiones)
    </h3>

    <div class="space-y-4">
        {{-- Profundidad --}}
        <div>
            <div class="flex justify-between text-xs text-slate-600 mb-1">
                <span>Profundidad ejecutada</span>
                <span>
                    {{ number_format($avProfundidad, 2) }} / {{ number_format($maxProfundidad, 2) }} m
                    ({{ $pctProfundidad }} %)
                </span>
            </div>
            <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0B265A]" style="width: {{ $pctProfundidad }}%"></div>
            </div>
        </div>

        {{-- KG acero --}}
        <div>
            <div class="flex justify-between text-xs text-slate-600 mb-1">
                <span>KG acero colocado</span>
                <span>
                    {{ number_format($avAcero, 2) }} / {{ number_format($maxAcero, 2) }} kg
                    ({{ $pctAcero }} %)
                </span>
            </div>
            <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0B265A]" style="width: {{ $pctAcero }}%"></div>
            </div>
        </div>

        {{-- Bentonita --}}
        <div>
            <div class="flex justify-between text-xs text-slate-600 mb-1">
                <span>Bentonita utilizada</span>
                <span>
                    {{ number_format($avBentonita, 2) }} / {{ number_format($maxBentonita, 2) }} m³
                    ({{ $pctBentonita }} %)
                </span>
            </div>
            <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0B265A]" style="width: {{ $pctBentonita }}%"></div>
            </div>
        </div>

        {{-- Concreto --}}
        <div>
            <div class="flex justify-between text-xs text-slate-600 mb-1">
                <span>Concreto colado</span>
                <span>
                    {{ number_format($avConcreto, 2) }} / {{ number_format($maxConcreto, 2) }} m³
                    ({{ $pctConcreto }} %)
                </span>
            </div>
            <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0B265A]" style="width: {{ $pctConcreto }}%"></div>
            </div>
        </div>
    </div>
</div>



                {{-- Ubicación --}}
                <div>
                    <label for="ubicacion" class="block text-sm font-medium text-slate-700">
                        Ubicación
                    </label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('ubicacion', $obra->ubicacion) }}">
                    @error('ubicacion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- BOTONES --}}
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-semibold
                                   shadow hover:bg-[#e0ac05]">
                        Guardar cambios
                    </button>
                </div>

            </form>
        @endif -->
        @if($tab === 'general')
    <h2 class="text-xl font-bold text-slate-800 mb-6 tracking-tight">Información general</h2>

    <form action="{{ route('obras.update', $obra) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- CARD 1: Datos Identificadores --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-blue-500 rounded-full"></span> Identificación de la Obra
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="cliente_id" class="block text-xs font-semibold text-slate-600 mb-1">
                        Cliente
                    </label>
                    <select id="cliente_id" name="cliente_id"
                            class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id', $obra->cliente_id) == $cliente->id)>
                                {{ $cliente->nombre_comercial }}
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="clave_obra" class="block text-xs font-semibold text-slate-600 mb-1">
                        Clave de obra
                    </label>
                    <input type="text" id="clave_obra" name="clave_obra"
                           class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                           value="{{ old('clave_obra', $obra->clave_obra) }}">
                    @error('clave_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nombre" class="block text-xs font-semibold text-slate-600 mb-1">
                        Nombre de la obra
                    </label>
                    <input type="text" id="nombre" name="nombre"
                           class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                           value="{{ old('nombre', $obra->nombre) }}">
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ubicacion" class="block text-xs font-semibold text-slate-600 mb-1">
                        Ubicación
                    </label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                           value="{{ old('ubicacion', $obra->ubicacion) }}">
                    @error('ubicacion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- CARD 2: Operación y Montos --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-blue-500 rounded-full"></span> Operación Financiera y Control
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="tipo_obra" class="block text-xs font-semibold text-slate-600 mb-1">
                        Tipo de obra
                    </label>
                    <input type="text" id="tipo_obra" name="tipo_obra"
                           class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                           value="{{ old('tipo_obra', $obra->tipo_obra) }}">
                    @error('tipo_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="estatus_nuevo" class="block text-xs font-semibold text-slate-600 mb-1">
                        Status
                    </label>
                    @php
                        $statuses = ['planeacion','ejecucion','suspendida','terminada','cancelada'];
                    @endphp
                    <select id="estatus_nuevo" name="estatus_nuevo"
                            class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('estatus_nuevo', $currentStatus ?? '') == $value)>
                                {{ ucfirst($label) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="responsable_id" class="block text-xs font-semibold text-slate-600 mb-1">
                        Responsable
                    </label>
                    <select id="responsable_id" name="responsable_id"
                            class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        <option value="">-- Sin asignar --</option>
                        @foreach($responsables as $user)
                            <option value="{{ $user->id }}" @selected(old('responsable_id', $obra->responsable_id) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsable_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monto_contratado" class="block text-xs font-semibold text-slate-600 mb-1">
                        Monto contratado
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 text-sm">$</span>
                        </div>
                        <input type="number" step="0.01" id="monto_contratado" name="monto_contratado"
                               class="block w-full rounded-xl border-slate-200 pl-7 bg-white text-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('monto_contratado', $obra->monto_contratado) }}">
                    </div>
                    @error('monto_contratado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monto_modificado" class="block text-xs font-semibold text-slate-600 mb-1">
                        Monto modificado
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 text-sm">$</span>
                        </div>
                        <input type="number" step="0.01" id="monto_modificado" name="monto_modificado"
                               class="block w-full rounded-xl border-slate-200 pl-7 bg-white text-sm transition duration-150 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('monto_modificado', $obra->monto_modificado) }}">
                    </div>
                    @error('monto_modificado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- FILA DE CARDS COMPARTIDAS (Fechas y Métricas) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- CARD 3: Cronograma --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-3 bg-blue-500 rounded-full"></span> Cronograma de Fechas
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_inicio_programada" class="block text-xs font-semibold text-slate-600 mb-1">Inicio prog.</label>
                        <input type="date" id="fecha_inicio_programada" name="fecha_inicio_programada"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('fecha_inicio_programada', optional($obra->fecha_inicio_programada)->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="fecha_inicio_real" class="block text-xs font-semibold text-slate-600 mb-1">Inicio real</label>
                        <input type="date" id="fecha_inicio_real" name="fecha_inicio_real"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('fecha_inicio_real', optional($obra->fecha_inicio_real)->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="fecha_fin_programada" class="block text-xs font-semibold text-slate-600 mb-1">Fin prog.</label>
                        <input type="date" id="fecha_fin_programada" name="fecha_fin_programada"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('fecha_fin_programada', optional($obra->fecha_fin_programada)->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="fecha_fin_real" class="block text-xs font-semibold text-slate-600 mb-1">Fin real</label>
                        <input type="date" id="fecha_fin_real" name="fecha_fin_real"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('fecha_fin_real', optional($obra->fecha_fin_real)->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            {{-- CARD 4: Volúmenes Técnicos --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-3 bg-blue-500 rounded-full"></span> Volúmenes Totales
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="profundidad_total" class="block text-xs font-semibold text-slate-600 mb-1">Profundidad (m)</label>
                        <input type="number" step="0.01" id="profundidad_total" name="profundidad_total"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('profundidad_total', $obra->profundidad_total) }}">
                    </div>
                    <div>
                        <label for="kg_acero_total" class="block text-xs font-semibold text-slate-600 mb-1">KG Acero</label>
                        <input type="number" step="0.01" id="kg_acero_total" name="kg_acero_total"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('kg_acero_total', $obra->kg_acero_total) }}">
                    </div>
                    <div>
                        <label for="bentonita_total" class="block text-xs font-semibold text-slate-600 mb-1">Bentonita (m³)</label>
                        <input type="number" step="0.01" id="bentonita_total" name="bentonita_total"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('bentonita_total', $obra->bentonita_total) }}">
                    </div>
                    <div>
                        <label for="concreto_total" class="block text-xs font-semibold text-slate-600 mb-1">Concreto (m³)</label>
                        <input type="number" step="0.01" id="concreto_total" name="concreto_total"
                               class="block w-full rounded-xl border-slate-200 text-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                               value="{{ old('concreto_total', $obra->concreto_total) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- LÓGICA DE AVANCES --}}
        @php
            $montoBase = $obra->monto_modificado ?? $obra->monto_contratado ?? 0;
            $cobrado   = $avanceCobrado ?? 0;
            $pctCobrado = $montoBase > 0 ? min(100, round(($cobrado / $montoBase) * 100)) : 0;

            $maxProfundidad = (float) ($obra->profundidad_total ?? 0);
            $avProfundidad  = (float) ($avanceObra['profundidad'] ?? 0);
            $pctProfundidad = $maxProfundidad > 0 ? min(100, round(($avProfundidad / $maxProfundidad) * 100)) : 0;

            $maxAcero = (float) ($obra->kg_acero_total ?? 0);
            $avAcero  = (float) ($avanceObra['kg_acero'] ?? 0);
            $pctAcero = $maxAcero > 0 ? min(100, round(($avAcero / $maxAcero) * 100)) : 0;

            $maxBentonita = (float) ($obra->bentonita_total ?? 0);
            $avBentonita  = (float) ($avanceObra['bentonita'] ?? 0);
            $pctBentonita = $maxBentonita > 0 ? min(100, round(($avBentonita / $maxBentonita) * 100)) : 0;

            $maxConcreto = (float) ($obra->concreto_total ?? 0);
            $avConcreto  = (float) ($avanceObra['concreto'] ?? 0);
            $pctConcreto = $maxConcreto > 0 ? min(100, round(($avConcreto / $maxConcreto) * 100)) : 0;
        @endphp

        {{-- CARD 5: Panel de Monitoreo --}}
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-emerald-500 rounded-full animate-pulse"></span> Monitoreo y Avances Existentes
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Avance Financiero --}}
                <div class="space-y-2 p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700">Avance de Cobro</span>
                        <span class="text-slate-500 font-mono">
                            ${{ number_format($cobrado, 2) }} / ${{ number_format($montoBase, 2) }} ({{ $pctCobrado }}%)
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" style="width: {{ $pctCobrado }}%"></div>
                    </div>
                </div>

                {{-- Profundidad --}}
                <div class="space-y-2 p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700">Profundidad Ejecutada</span>
                        <span class="text-slate-500 font-mono">
                            {{ number_format($avProfundidad, 2) }} / {{ number_format($maxProfundidad, 2) }} m ({{ $pctProfundidad }}%)
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0B265A] rounded-full transition-all duration-500" style="width: {{ $pctProfundidad }}%"></div>
                    </div>
                </div>

                {{-- Acero --}}
                <div class="space-y-2 p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700">Acero Colocado</span>
                        <span class="text-slate-500 font-mono">
                            {{ number_format($avAcero, 2) }} / {{ number_format($maxAcero, 2) }} kg ({{ $pctAcero }}%)
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0B265A] rounded-full transition-all duration-500" style="width: {{ $pctAcero }}%"></div>
                    </div>
                </div>

                {{-- Concreto --}}
                <div class="space-y-2 p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700">Concreto Colado</span>
                        <span class="text-slate-500 font-mono">
                            {{ number_format($avConcreto, 2) }} / {{ number_format($maxConcreto, 2) }} m³ ({{ $pctConcreto }}%)
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0B265A] rounded-full transition-all duration-500" style="width: {{ $pctConcreto }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTONERA DE ACCIÓN --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-bold shadow-md transition duration-200 hover:bg-[#e0ac05] hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-[#FFC107]/20">
                Guardar cambios
            </button>
        </div>

    </form>
@endif

       
       {{-- TAB: CONTRATOS --}}
@if($tab === 'contratos')
    <h2 class="text-lg font-semibold mb-4">Contratos de la obra</h2>

    {{-- MENSAJES --}}
    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE CONTRATOS --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Contratos cargados</h3>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Nombre</th>
                            <th class="py-2 px-3 text-left">Tipo</th>
                            <th class="py-2 px-3 text-left">Fecha firma</th>
                            <th class="py-2 px-3 text-left">Monto</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($obra->contratos as $contrato)
                            <tr class="border-b last:border-b-0 hover:bg-slate-50">
                                <td class="py-2 px-3">
                                    {{ $contrato->nombre ?? 'Contrato' }}<br>
                                    <span class="text-xs text-slate-400">
                                        {{ $contrato->descripcion ? Str::limit($contrato->descripcion, 40) : '' }}
                                    </span>
                                </td>
                                <td class="py-2 px-3">
                                    {{ $contrato->tipo ?? '-' }}
                                </td>
                                <td class="py-2 px-3">
                                    {{ $contrato->fecha_firma ? $contrato->fecha_firma->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-2 px-3">
                                    @if(!is_null($contrato->monto_contrato))
                                        ${{ number_format($contrato->monto_contrato, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-right space-x-2">
                                    @if($contrato->archivo_path)
                                        <a href="{{ asset('storage/'.$contrato->archivo_path) }}"
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            Ver PDF
                                        </a>
                                    @endif

                                    <form action="{{ route('obras.contratos.destroy', [$obra->id, $contrato->id]) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('¿Eliminar este contrato?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 text-xs font-medium">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500 text-sm">
                                    No hay contratos registrados aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FORM PARA SUBIR NUEVO CONTRATO --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Agregar contrato</h3>

            <form action="{{ route('obras.contratos.store', $obra->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div>
                    <label for="nombre_contrato" class="block text-xs font-medium text-slate-700">
                        Nombre del contrato
                    </label>
                    <input type="text" id="nombre_contrato" name="nombre"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('nombre') }}"
                           placeholder="Ej: Contrato principal, Modificatorio 1, etc.">
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tipo" class="block text-xs font-medium text-slate-700">
                        Tipo
                    </label>
                    <input type="text" id="tipo" name="tipo"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('tipo') }}"
                           placeholder="Principal, Modificatorio, Ampliación...">
                    @error('tipo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monto_contrato" class="block text-xs font-medium text-slate-700">
                        Monto del contrato
                    </label>
                    <input type="number" step="0.01" id="monto_contrato" name="monto_contrato"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('monto_contrato') }}">
                    @error('monto_contrato')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_firma" class="block text-xs font-medium text-slate-700">
                        Fecha de firma
                    </label>
                    <input type="date" id="fecha_firma" name="fecha_firma"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_firma') }}">
                    @error('fecha_firma')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="descripcion" class="block text-xs font-medium text-slate-700">
                        Descripción / notas
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="2"
                              class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                     focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="archivo" class="block text-xs font-medium text-slate-700">
                        Archivo PDF del contrato <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="archivo" name="archivo"
                           class="mt-1 block w-full text-sm text-slate-700"
                           accept="application/pdf" required>
                    @error('archivo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-[11px] text-slate-400">
                        Solo PDF, máximo 5 MB.
                    </p>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-xs font-semibold
                                   shadow hover:bg-[#e0ac05]">
                        Guardar contrato
                    </button>
                </div>

            </form>
        </div>
    </div>
@endif


      {{-- TAB: PLANOS --}}
@if($tab === 'planos')
    <h2 class="text-lg font-semibold mb-4">Planos</h2>

    {{-- MENSAJE --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE PLANOS --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Planos cargados</h3>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Nombre</th>
                            <th class="py-2 px-3 text-left">Categoría</th>
                            <th class="py-2 px-3 text-left">Versión</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($obra->planos as $plano)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="py-2 px-3">{{ $plano->nombre }}</td>
                                <td class="py-2 px-3">{{ $plano->categoria->nombre }}</td>
                                <td class="py-2 px-3">{{ $plano->version ?? '-' }}</td>
                                <td class="py-2 px-3 text-right space-x-2">

                                    <a href="{{ asset('storage/'.$plano->archivo_path) }}"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Ver archivo
                                    </a>

                                    <form action="{{ route('obras.planos.destroy', [$obra->id, $plano->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Eliminar plano?')"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 text-xs font-medium">Eliminar</button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-500">
                                    No hay planos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

        {{-- FORM PARA SUBIR NUEVO PLANO --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Agregar plano</h3>

            <form action="{{ route('obras.planos.store', $obra->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-slate-700">Categoría</label>
                    <select name="plano_categoria_id"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                   focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @foreach(\App\Models\PlanoCategoria::all() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Nombre</label>
                    <input type="text" name="nombre"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           required>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Versión</label>
                    <input type="text" name="version"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Archivo</label>
                    <input type="file" name="archivo"
                           accept=".pdf,.dwg,.dxf,.png,.jpg,.jpeg,.zip"
                           class="mt-1 block w-full text-sm"
                           required>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Notas</label>
                    <textarea name="notas" rows="2"
                              class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                     focus:border-[#FFC107] focus:ring-[#FFC107]"></textarea>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit"
                            class="px-4 py-2 bg-[#FFC107] text-[#0B265A] rounded-xl text-xs font-semibold shadow hover:bg-[#e0ac05]">
                        Guardar plano
                    </button>
                </div>
            </form>
        </div>

    </div>
@endif


       {{-- TAB: PRESUPUESTOS --}}
<!-- @if($tab === 'presupuestos')
    <h2 class="text-lg font-semibold mb-4">Presupuestos</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE PRESUPUESTOS --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Presupuestos cargados</h3>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Nombre</th>
                            <th class="py-2 px-3 text-left">Versión</th>
                            <th class="py-2 px-3 text-left">Fecha</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($obra->presupuestos as $presupuesto)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="py-2 px-3">
                                    {{ $presupuesto->nombre }}<br>
                                    @if($presupuesto->notas)
                                        <span class="text-[11px] text-slate-400">
                                            {{ Str::limit($presupuesto->notas, 40) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    {{ $presupuesto->version ?? '-' }}
                                </td>
                                <td class="py-2 px-3">
                                    {{ $presupuesto->fecha ? $presupuesto->fecha->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-2 px-3 text-right space-x-2">
                                    <a href="{{ asset('storage/'.$presupuesto->archivo_path) }}"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Ver PDF
                                    </a>

                                    <form action="{{ route('obras.presupuestos.destroy', [$obra->id, $presupuesto->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Eliminar presupuesto?')"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 text-xs font-medium">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-500">
                                    No hay presupuestos cargados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FORM PARA SUBIR NUEVO PRESUPUESTO --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Subir presupuesto</h3>

            <form action="{{ route('obras.presupuestos.store', $obra->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Nombre del presupuesto
                    </label>
                    <input type="text" name="nombre"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('nombre') }}"
                           placeholder="Ej: Presupuesto inicial, Revisión 2"
                           required>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Versión
                    </label>
                    <input type="text" name="version"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('version') }}"
                           placeholder="Ej: v1, v2">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Fecha
                    </label>
                    <input type="date" name="fecha"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha') }}">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Notas
                    </label>
                    <textarea name="notas" rows="2"
                              class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                     focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('notas') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Archivo PDF
                    </label>
                    <input type="file" name="archivo"
                           accept="application/pdf"
                           class="mt-1 block w-full text-sm"
                           required>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Solo PDF, máximo 10 MB.
                    </p>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit"
                            class="px-4 py-2 bg-[#FFC107] text-[#0B265A] rounded-xl text-xs font-semibold shadow hover:bg-[#e0ac05]">
                        Guardar presupuesto
                    </button>
                </div>

            </form>
        </div>
    </div>
@endif -->
{{-- TAB: PRESUPUESTOS --}}
@if($tab === 'presupuestos')
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-[#0B265A]">Presupuestos de la Obra</h2>
            <p class="text-sm text-slate-500">Gestión de presupuestos maestros vinculados y archivos adicionales.</p>
        </div>
        
        {{-- BOTÓN PARA ABRIR MODAL (Lo vincularemos después) --}}
        <button onclick="openModalPresupuestos()" 
                class="flex items-center gap-2 px-4 py-2 bg-[#0B265A] text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-[#1a3a7a] transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="height="12" M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Vincular Presupuesto Maestro
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_350px] gap-8">

        {{-- COLUMNA IZQUIERDA: DESGLOSE TÉCNICO --}}
        <div class="space-y-6">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Desglose Técnico Consolidado</h3>
            
            @forelse($obra->presupuestos_vinculados as $pv)
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    {{-- Encabezado del Presupuesto Vinculado --}}
                    <div class="bg-slate-50 px-4 py-3 border-b flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-bold text-blue-600 uppercase">Folio: {{ $pv->codigo_proyecto }}</span>
                            <h4 class="font-bold text-slate-800">{{ $pv->nombre_cliente }}</h4>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-slate-500">Total Presupuestado</span>
                            <span class="font-bold text-green-600">${{ number_format($pv->total_presupuesto, 2) }}</span>
                        </div>
                    </div>

                    {{-- Tabla de Conceptos (Misma lógica que en Presupuestos) --}}
                    <table class="w-full text-[11px]">
                        <thead class="bg-slate-50/50 text-slate-500 uppercase">
                            <tr>
                                <th class="py-2 px-4 text-left">Concepto</th>
                                <th class="py-2 px-2 text-center">Unidad</th>
                                <th class="py-2 px-2 text-center">Cant.</th>
                                <th class="py-2 px-2 text-right">P.U.</th>
                                <th class="py-2 px-4 text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            {{-- Aquí mapeamos los resúmenes y pilas vinculados --}}
                            @foreach($pv->resumenes as $r)
                                @if($r->cantidad > 0 && $r->precio_unitario > 0)
                                <tr>
                                    <td class="py-2 px-4">{{ $r->concepto }}</td>
                                    <td class="py-2 px-2 text-center text-slate-400">{{ $r->unidad }}</td>
                                    <td class="py-2 px-2 text-center">{{ number_format($r->cantidad, 2) }}</td>
                                    <td class="py-2 px-2 text-right">${{ number_format($r->precio_unitario, 2) }}</td>
                                    <td class="py-2 px-4 text-right font-medium">${{ number_format($r->importe, 2) }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </div>
                </div>
            @empty
                <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center">
                    <p class="text-slate-500">No hay presupuestos maestros vinculados a esta obra.</p>
                </div>
            @endforelse
        </div>

     
    </div>
@endif
@if($tab === 'planeacion')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Planeación de Gasto Semanal</h3>
            <p class="text-xs text-slate-500">Distribuye el costo directo en las {{ $semanas }} semanas programadas.</p>
        </div>
        <button type="submit" form="formPlaneacion" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-sm transition-all">
            Guardar Planeación
        </button>
    </div>

    <form id="formPlaneacion" action="{{ route('obras.guardarPlaneacion', $obra->id) }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3 border sticky left-0 bg-slate-100 z-10 w-64 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Concepto / Partida</th>
                        <th class="p-3 border text-right w-28">Tope (Presupuesto)</th>
                        <th class="p-3 border text-right w-28">Programado</th>
                        <th class="p-3 border text-right w-28">Diferencia</th>
                        
                        @for($i = 1; $i <= $semanas; $i++)
                            <th class="p-3 border text-center min-w-[120px] bg-blue-50/50">Semana {{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                        @php
                            $gastosPlaneacionVisibles = $gastosBase->filter(function ($gasto) {
                                $tope = (float) ($gasto->monto_programado ?? $gasto->importe ?? $gasto->total ?? 0);

                                return $tope > 0;
                            });

                            $gruposGastos = $gastosPlaneacionVisibles->groupBy('partida');
                        @endphp

                        @forelse($gruposGastos as $partida => $items)
                            <tr class="bg-slate-100">
                                <td colspan="{{ 4 + $semanas }}" class="p-2 font-bold text-slate-700 uppercase border">
                                    {{ $partida ?: 'GENERAL' }}
                                </td>
                            </tr>

                            @foreach($items as $gasto)
                                @include('obras.partials.fila_planeacion', ['item' => $gasto, 'tipo' => 'planeacion'])
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 4 + $semanas }}" class="p-8 text-center text-slate-400 text-sm">
                                    No hay conceptos de planeación para esta obra.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>
        </div>
    </form>
</div>
@endif
{{--  TERMINA TAB : PLANEACION --}}
{{--  TAB :GASTOS --}}
{{-- REPOSICION GASTOS --}}
@if($tab === 'reposicion-gastos')

<div 
    x-data="reposicionGastosUI()"
    class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
>

    <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-slate-800">
                Reposición de gastos
            </h3>
            <p class="text-xs text-slate-500">
                Solicitudes semanales de reposición por caja chica, viáticos y gastos varios.
            </p>
        </div>

        <button
            type="button"
            @click="openModal()"
            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"
        >
            + Nueva reposición
        </button>
    </div>

    <div class="p-4 border-b border-slate-200 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-bold uppercase text-slate-400">Total de Reposiciones</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">
                    {{ $reposicionesStats['total'] ?? 0 }}
                </p>
            </div>

            <div class="rounded-xl border border-yellow-200 p-4 bg-yellow-50">
                <p class="text-xs font-bold uppercase text-yellow-600">Solicitadas</p>
                <p class="text-2xl font-bold text-yellow-700 mt-1">  {{ $reposicionesStats['solicitadas'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-blue-200 p-4 bg-blue-50">
                <p class="text-xs font-bold uppercase text-blue-600">En revisión</p>
                <p class="text-2xl font-bold text-blue-700 mt-1">  {{ $reposicionesStats['en_revision'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-green-200 p-4 bg-green-50">
                <p class="text-xs font-bold uppercase text-green-600">Autorizadas</p>
                <p class="text-2xl font-bold text-green-700 mt-1">  {{ $reposicionesStats['autorizadas'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">

    {{-- MONTO SOLICITADO --}}
    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
        <p class="text-xs font-bold uppercase text-orange-600">
            Monto solicitado
        </p>

        <p class="text-2xl font-bold text-orange-700 mt-1">
            ${{ number_format($reposicionesMontos['solicitado'] ?? 0, 2) }}
        </p>
    </div>

    {{-- MONTO AUTORIZADO --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-xs font-bold uppercase text-blue-600">
            Monto autorizado
        </p>

        <p class="text-2xl font-bold text-blue-700 mt-1">
            ${{ number_format($reposicionesMontos['autorizado'] ?? 0, 2) }}
        </p>
    </div>

    {{-- MONTO PAGADO --}}
    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
        <p class="text-xs font-bold uppercase text-green-600">
            Monto pagado
        </p>

        <p class="text-2xl font-bold text-green-700 mt-1">
            ${{ number_format($reposicionesMontos['pagado'] ?? 0, 2) }}
        </p>
    </div>

</div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 border text-left">Folio</th>
                    <th class="p-3 border text-left">Semana</th>
                    <th class="p-3 border text-left">Tipo</th>
                    <th class="p-3 border text-left">Partida</th>
                    <th class="p-3 border text-right">Monto</th>
                    <th class="p-3 border text-center">Evidencias</th>
                    <th class="p-3 border text-center">Estatus</th>
                    <th class="p-3 border text-right">Acciones</th>
                </tr>
            </thead>

        <tbody>
    @forelse($reposicionesGastos as $reposicion)
        <tr class="hover:bg-slate-50">
            <td class="p-3 border font-semibold">
                REP-{{ str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) }}
            </td>

            <td class="p-3 border">
                {{ $reposicion->semana }}
            </td>

            <td class="p-3 border">
                {{ str_replace('_', ' ', ucfirst($reposicion->tipo_reposicion)) }}
            </td>

         <td class="p-3 border">
            <div class="font-semibold text-slate-800">
                {{ $reposicion->partida->partida ?? 'SIN PARTIDA' }}
            </div>

            <div class="text-xs text-slate-500">
                {{ $reposicion->partida->concepto ?? '-' }}
            </div>

            <div class="text-xs text-slate-400">
                Semana {{ $reposicion->partida->numero_semana ?? '-' }}
            </div>
        </td>
            <td class="p-3 border text-right font-bold">
                ${{ number_format($reposicion->total, 2) }}
            </td>

            <td class="p-3 border text-center">
                {{ $reposicion->detalles->count() }}
            </td>

            <!-- <td class="p-3 border text-center">
                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 font-semibold">
                    {{ str_replace('_', ' ', $reposicion->estatus) }}
                </span>
            </td> -->
            @php
    $estatusClasses = match($reposicion->estatus) {

        'borrador' =>
            'bg-slate-100 text-slate-700 hover:bg-slate-200',

        'solicitado' =>
            'bg-yellow-100 text-yellow-700 hover:bg-yellow-200',

        'en_revision_area' =>
            'bg-blue-100 text-blue-700 hover:bg-blue-200',

        'programado_area' =>
            'bg-indigo-100 text-indigo-700 hover:bg-indigo-200',

        'en_revision_admin' =>
            'bg-cyan-100 text-cyan-700 hover:bg-cyan-200',

        'pendiente_autorizacion' =>
            'bg-amber-100 text-amber-700 hover:bg-amber-200',

        'autorizado' =>
            'bg-green-100 text-green-700 hover:bg-green-200',

        'rechazado' =>
            'bg-red-100 text-red-700 hover:bg-red-200',

        'pagado' =>
            'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',

        'cerrado' =>
            'bg-slate-800 text-white hover:bg-slate-700',

        default =>
            'bg-slate-100 text-slate-700 hover:bg-slate-200',
    };
@endphp

<td class="p-3 border text-center">
    <button
        type="button"
        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.remove('hidden')"
        class="px-3 py-1 rounded-full text-xs font-bold transition {{ $estatusClasses }}"
    >
        {{ str_replace('_', ' ', $reposicion->estatus) }}
    </button>
</td>

            <td class="p-3 border text-right">
                <!-- <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                    Ver
                </button> -->
                <a
                    href="{{ route('obras.reposicion-gastos.show', [$obra, $reposicion]) }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                >
                    Ver
                </a>
            </td>
        </tr>
        <tr class="bg-transparent">
    <td colspan="9" class="p-0 border-0">
        <div
            id="modalEstatusReposicion{{ $reposicion->id }}"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4 transition-all duration-300"
        >
            <div class="w-full max-w-3xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden transform transition-all">

                {{-- Header Elegante --}}
                <div class="flex items-center justify-between bg-slate-50/50 border-b border-slate-100 px-8 py-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">
                                Seguimiento de reposición
                            </h3>
                            <span class="rounded-full bg-blue-100 px-3 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-blue-200">
                                {{ $reposicion->folio ?? 'REP-' . str_pad($reposicion->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1 font-medium">
                            Historial operativo, financiero y administrativo de la solicitud.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.add('hidden')"
                        class="group rounded-full p-2 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

               {{-- Contenido con Estilo de Timeline --}}
<div class="px-8 py-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
    <div class="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-slate-200 before:via-slate-200 before:to-transparent">
        
        {{-- 1. SOLICITUD --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-3">1. Registro de Solicitud</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Solicitante</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->solicitadoPor->name ?? 'Sistema' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Estatus Inicial</p>
                        <p class="text-slate-600 text-xs italic">Solicitud creada en sistema</p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg bg-white px-3 py-1 text-[11px] font-bold text-slate-500 shadow-sm ring-1 ring-slate-200">
                            {{ optional($reposicion->solicitado_at)->format('d/m/Y H:i') ?? '---' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REVISIÓN --}}
     {{-- 2. REVISIÓN --}}
<div class="relative flex items-start gap-6 group">
    <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
        <div class="w-3 h-3 rounded-full {{ $reposicion->revisado_at ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
    </div>
    <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-blue-600 mb-3">2. Primera Revisión Operativa</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div>
                <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Revisó</p>
                <p class="text-slate-700 font-bold text-sm">{{ $reposicion->revisadoPor->name ?? '---' }}</p>
            </div>
            
            {{-- Sección de Programación --}}
            <div>
                <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Programación de Pago</p>
                @if($reposicion->fecha_programada_pago)
                    <p class="text-blue-700 font-black text-xs">
                        🗓️ {{ \Carbon\Carbon::parse($reposicion->fecha_programada_pago)->format('d/M/Y') }}
                    </p>
                @else
                    <p class="text-slate-500 text-xs italic">Pendiente de programar</p>
                @endif
            </div>

            {{-- Columna de Fecha de Acción --}}
            <div class="md:text-right">
                <span class="inline-block rounded-lg {{ $reposicion->revisado_at ? 'bg-blue-50 text-blue-600 ring-blue-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                    {{ optional($reposicion->revisado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                </span>
            </div>
        </div>
        
        {{-- Comentarios en la parte inferior del bloque para no apretar las columnas --}}
        @if($reposicion->comentarios_revision)
            <div class="mt-3 p-2 bg-white/50 rounded-lg border border-slate-100 text-[11px] text-slate-600 italic">
                "{{ $reposicion->comentarios_revision }}"
            </div>
        @endif
    </div>
</div>

        {{-- 3. APROVISIONAMIENTO --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full {{ $reposicion->aprovisionado_at ? 'bg-amber-500 ring-4 ring-amber-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-amber-600 mb-3">3. Aprovisionamiento y Tesorería</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Asignado por</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->aprovisionadoPor->name ?? '---' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Programación</p>
                        <p class="text-amber-700 text-xs font-bold font-mono uppercase">
                            Pago: {{ optional($reposicion->fecha_programada_pago)->format('d/M/Y') ?? 'Sin fecha' }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg {{ $reposicion->aprovisionado_at ? 'bg-amber-50 text-amber-600 ring-amber-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                            {{ optional($reposicion->aprovisionado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. AUTORIZACIÓN --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full {{ $reposicion->aprobado_at ? 'bg-purple-500 ring-4 ring-purple-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-purple-600 mb-3">4. Autorización Final (Dirección)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Autorizó</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->aprobadoPor->name ?? '---' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Dictamen</p>
                        <p class="text-slate-600 text-xs italic truncate" title="{{ $reposicion->comentarios_autorizacion }}">
                            {{ $reposicion->comentarios_autorizacion ?? 'Esperando firma electrónica' }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg {{ $reposicion->aprobado_at ? 'bg-purple-50 text-purple-600 ring-purple-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                            {{ optional($reposicion->aprobado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

                {{-- Footer --}}
                <div class="flex justify-end border-t border-slate-100 px-8 py-5 bg-slate-50/30">
                    <button
                        type="button"
                        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.add('hidden')"
                        class="rounded-xl bg-slate-900 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800 hover:scale-[1.02] transition-all active:scale-95"
                    >
                        Entendido
                    </button>
                </div>

            </div>
        </div>
    </td>
</tr>
    @empty
        <tr>
            <td colspan="8" class="p-8 text-center text-slate-400">
                Aún no hay solicitudes de reposición registradas para esta obra.
            </td>
        </tr>
    @endforelse
</tbody>
        </table>
    </div>

    {{-- MODAL --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm px-4"
        style="display: none;"
    >
        <div
            @click.away="closeModal()"
            class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden"
        >
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">
                        Nueva reposición de gasto
                    </h3>
                    <p class="text-xs text-slate-500">
                        Registra los gastos realizados en obra y adjunta evidencia.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="text-slate-400 hover:text-slate-600"
                >
                    ✕
                </button>
            </div>

           <form action="{{ route('obras.reposicion-gastos.store', $obra) }}" method="POST">
                @csrf
                    <input
                        type="hidden"
                        name="conceptos"
                        :value="JSON.stringify(conceptos)"
                    >
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Tipo de reposición
                            </label>

                            <select
                                name="tipo_reposicion"
                                x-model="tipoReposicion"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Seleccionar...</option>
                                <option value="caja_chica">Caja chica</option>
                                <option value="viaticos">Viáticos</option>
                                <option value="gastos_varios">Gastos varios</option>
                            </select>

                            <p x-show="tipoReposicion === 'caja_chica'" class="text-xs text-red-500 mt-2">
                                * Caja chica requiere factura obligatoria.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Partida de planeación
                            </label>

                            <select
                                name="partida_id"
                                x-model="partidaSeleccionada"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Seleccionar partida...</option>

                                @foreach(collect($gastosBase)->groupBy('partida') as $partidaNombre => $items)
                                    <optgroup label="{{ $partidaNombre ?: 'SIN PARTIDA' }}">
                                        @foreach($items as $partida)
                                            <option value="{{ $partida->id }}">
                                                {{ $partida->concepto ?? 'Sin concepto' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Semana
                            </label>

                            <input
                                type="week"
                                name="semana"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    {{-- BUSCADOR FACTURAS --}}
                    <div
                        x-show="tipoReposicion === 'caja_chica'"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <div class="mb-4">
                            <h4 class="font-bold text-blue-900">
                                Buscar factura SAT
                            </h4>
                            <p class="text-xs text-blue-700">
                                Busca por RFC emisor, fecha, monto exacto o últimos 4 dígitos del UUID.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <input
                                type="text"
                                x-model="busqueda.rfc"
                                placeholder="RFC emisor"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="date"
                                x-model="busqueda.fecha"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="number"
                                step="0.01"
                                x-model="busqueda.monto"
                                placeholder="Monto exacto"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="text"
                                maxlength="4"
                                x-model="busqueda.uuid4"
                                placeholder="Últimos 4 UUID"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <button
                                type="button"
                                @click="buscarCfdis()"
                                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                <span x-show="!loadingCfdi">Buscar</span>
                                <span x-show="loadingCfdi">Buscando...</span>
                            </button>
                        </div>

                        <div class="mt-4 rounded-xl bg-white border border-blue-100 overflow-hidden">
                            <div class="px-4 py-3 bg-blue-100 text-xs font-bold text-blue-800 uppercase">
                                Resultados encontrados
                            </div>

                            <div
                                x-show="resultadosCfdi.length === 0 && !loadingCfdi"
                                class="p-6 text-center text-sm text-slate-400"
                            >
                                Usa los filtros y presiona buscar para encontrar facturas SAT.
                            </div>

                            <div
                                x-show="loadingCfdi"
                                class="p-6 text-center text-sm text-blue-600 font-semibold"
                            >
                                Buscando CFDIs...
                            </div>

                            <div
                                x-show="resultadosCfdi.length > 0"
                                class="divide-y divide-slate-100"
                            >
                                <template x-for="cfdi in resultadosCfdi" :key="cfdi.id">
                                       <button
                                                type="button"
                                                @click="agregarConcepto({
                                                    sat_cfdi_id: cfdi.id,
                                                    partida_id: partidaSeleccionada,
                                                    tipo: 'Caja chica',
                                                    proveedor: cfdi.emisor_nombre,
                                                    descripcion: 'CFDI SAT',
                                                    rfc: cfdi.rfc_emisor,
                                                    uuid: cfdi.uuid,
                                                    monto: cfdi.total,
                                                    fecha: cfdi.fecha
                                                })"
                                                class="w-full px-4 py-3 text-left hover:bg-blue-50 flex justify-between gap-4"
                                            >
                                        <div>
                                            <p class="font-semibold text-slate-800" x-text="cfdi.emisor_nombre"></p>
                                            <p class="text-xs text-slate-500">
                                                <span x-text="cfdi.rfc_emisor"></span>
                                                · UUID:
                                                <span x-text="cfdi.uuid ? cfdi.uuid.slice(-4) : '-'"></span>
                                            </p>
                                            <p class="text-xs text-slate-400 mt-1">
                                                Fecha:
                                                <span x-text="cfdi.fecha_formateada"></span>
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <div
                                                class="font-bold text-slate-800"
                                                x-text="'$' + Number(cfdi.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"
                                            ></div>
                                            <div class="text-xs text-slate-400" x-text="cfdi.metodo_pago || '-'"></div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- CAPTURA MANUAL --}}
                    <div
                        x-show="tipoReposicion !== 'caja_chica'"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="mb-4">
                            <h4 class="font-bold text-slate-800">
                                Agregar concepto manual
                            </h4>
                            <p class="text-xs text-slate-500">
                                Para viáticos o gastos varios puedes capturar notas, tickets o evidencias.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <input
                                type="text"
                                x-model="manual.descripcion"
                                placeholder="Descripción"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="text"
                                x-model="manual.proveedor"
                                placeholder="Proveedor / comercio"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="date"
                                x-model="manual.fecha"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <input
                                type="number"
                                step="0.01"
                                x-model="manual.monto"
                                placeholder="Monto"
                                class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <button
                                type="button"
                                @click="agregarManual()"
                                class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                            >
                                Agregar
                            </button>
                        </div>
                    </div>

                    {{-- TABLA CONCEPTOS --}}
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Conceptos agregados
                                </h4>
                                <p class="text-xs text-slate-500">
                                    Detalle que formará parte de la solicitud de reposición.
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-slate-400 uppercase font-bold">Total</p>
                                <p class="text-lg font-bold text-slate-800">
                                    $<span x-text="totalConceptos().toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                                </p>
                            </div>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="p-3 text-left">Tipo</th>
                                    <th class="p-3 text-left">Proveedor / Descripción</th>
                                    <th class="p-3 text-left">RFC / UUID</th>
                                    <th class="p-3 text-right">Monto</th>
                                    <th class="p-3 text-center">Acción</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(concepto, index) in conceptos" :key="index">
                                    <tr>
                                        <td class="p-3">
                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700" x-text="concepto.tipo"></span>
                                        </td>

                                        <td class="p-3">
                                            <div class="font-semibold text-slate-800" x-text="concepto.proveedor || concepto.descripcion"></div>
                                            <div class="text-xs text-slate-400" x-text="concepto.descripcion"></div>
                                        </td>

                                        <td class="p-3 text-xs text-slate-500">
                                            <div x-text="concepto.rfc || '-'"></div>
                                            <div x-text="concepto.uuid || '-'"></div>
                                        </td>

                                        <td class="p-3 text-right font-bold text-slate-800">
                                            $<span x-text="Number(concepto.monto || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                                        </td>

                                        <td class="p-3 text-center">
                                            <button
                                                type="button"
                                                @click="conceptos.splice(index, 1)"
                                                class="text-xs font-semibold text-red-600 hover:text-red-800"
                                            >
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="conceptos.length === 0">
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        Aún no has agregado conceptos a la reposición.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            rows="3"
                            class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Comentarios para administración de área..."
                        ></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-200"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700"
                    >
                        Guardar reposición
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function reposicionGastosUI() {
        return {
            modalOpen: false,
            tipoReposicion: '',
            loadingCfdi: false,
            resultadosCfdi: [],
            partidaSeleccionada: '',

            busqueda: {
                rfc: '',
                fecha: '',
                monto: '',
                uuid4: ''
            },

            manual: {
                descripcion: '',
                proveedor: '',
                fecha: '',
                monto: ''
            },

            conceptos: [],

            openModal() {
                this.modalOpen = true;
            },

            closeModal() {
                this.modalOpen = false;
            },

            async buscarCfdis() {
                this.loadingCfdi = true;
                this.resultadosCfdi = [];

                try {
                    const params = new URLSearchParams();

                    if (this.busqueda.rfc) {
                        params.append('rfc_emisor', this.busqueda.rfc);
                    }

                    if (this.busqueda.fecha) {
                        params.append('fecha', this.busqueda.fecha);
                    }

                    if (this.busqueda.monto) {
                        params.append('monto', this.busqueda.monto);
                    }

                    if (this.busqueda.uuid4) {
                        params.append('uuid4', this.busqueda.uuid4);
                    }

                    const response = await fetch(
                        "{{ route('obras.reposicion-gastos.buscar-cfdis', $obra) }}?" + params.toString(),
                        {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Error en la búsqueda de CFDIs');
                    }

                    const data = await response.json();

                    this.resultadosCfdi = data.data || [];

                } catch (error) {
                    console.error(error);
                    alert('Error al buscar CFDIs.');
                } finally {
                    this.loadingCfdi = false;
                }
            },

            agregarConcepto(concepto) {
                const existe = this.conceptos.some(item => item.uuid && item.uuid === concepto.uuid);

                if (existe) {
                    alert('Esta factura ya fue agregada.');
                    return;
                }

                this.conceptos.push(concepto);
            },

            agregarManual() {
                if (!this.manual.descripcion || !this.manual.monto) {
                    alert('Captura descripción y monto.');
                    return;
                }
                // if (!this.partidaSeleccionada) {
                //     alert('Selecciona una partida.');
                //     return;
                // }

                this.conceptos.push({
                partida_id: this.partidaSeleccionada,

                tipo: this.tipoReposicion === 'viaticos' ? 'Viáticos' : 'Gastos varios',
                descripcion: this.manual.descripcion,
                proveedor: this.manual.proveedor,
                fecha: this.manual.fecha,
                monto: Number(this.manual.monto || 0),
                rfc: '',
                uuid: ''
            });

                this.manual = {
                    descripcion: '',
                    proveedor: '',
                    fecha: '',
                    monto: ''
                };
            },

            totalConceptos() {
                return this.conceptos.reduce((total, item) => {
                    return total + Number(item.monto || 0);
                }, 0);
            }
        }
    }
</script>

@endif

{{-- TERMINA REPOSICION GASTOS --}}
 {{-- TAB: EMPLEADOS --}}
@if($tab === 'empleados')
    <h2 class="text-lg font-semibold mb-4">Empleados asignados a la obra</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    {{-- FORM PARA ASIGNAR NUEVO EMPLEADO --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Asignar empleado a esta obra</h3>

        @if($empleadosAsignables->isEmpty())
            <p class="text-sm text-slate-500">
                No hay empleados disponibles sin asignación activa.
            </p>
        @else
            <form id="form-asignar-empleado"
                  method="POST"
                  action="{{ route('obras.empleados.store', $obra) }}"
                  class="bg-white border rounded-xl p-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 items-end">
                    {{-- Buscar empleado --}}
                    <div class="relative">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Buscar empleado
                        </label>

                        <input type="text"
                               id="buscador-empleado"
                               autocomplete="off"
                               placeholder="Escribe apellido o nombre"
                               class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">

                        <input type="hidden"
                               name="empleado_id"
                               id="empleado_id"
                               value="{{ old('empleado_id') }}">

                        <div id="resultados-empleado"
                             class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow text-sm max-h-60 overflow-y-auto hidden">
                        </div>

                        @error('empleado_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Rol en la obra --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Puesto en la obra
                        </label>

                        <select name="rol_id"
                                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            <option value="">Selecciona...</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" @selected(old('rol_id') == $rol->id)>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('rol_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notas --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Notas
                        </label>
                        <input type="text"
                               name="notas"
                               value="{{ old('notas') }}"
                               class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">

                        @error('notas')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Botón --}}
                    <div class="flex md:justify-end">
                        <button type="submit"
                                class="w-full md:w-auto px-4 py-2 bg-teal-600 text-white text-sm rounded-xl hover:bg-teal-700">
                            Asignar empleado
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- LISTA DE ASIGNACIONES ACTIVAS --}}
    <div>
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Empleados actualmente en la obra</h3>

        <div class="border rounded-xl overflow-hidden bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b text-slate-500">
                        <th class="py-2 px-3 text-left">Empleado</th>
                        <th class="py-2 px-3 text-left">Puesto</th>
                        <th class="py-2 px-3 text-left">Alta</th>
                        <th class="py-2 px-3 text-left">Días</th>
                        <th class="py-2 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignacionesActivas as $asig)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="py-2 px-3">
                                {{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}<br>
                                <span class="text-[11px] text-slate-400">
                                    {{ $asig->empleado->Area }} | {{ $asig->empleado->Puesto }}
                                </span>
                            </td>
                            <td class="py-2 px-3">
                                {{ $asig->puesto_en_obra ?? $asig->empleado->Puesto }}
                            </td>
                            <td class="py-2 px-3">
                                {{ $asig->fecha_alta?->format('d/m/Y') }}
                            </td>
                            <td class="py-2 px-3">
                                {{ $asig->dias_trabajados ?? $asig->fecha_alta?->diffInDays(now())+1 }}
                            </td>
                            <td class="py-2 px-3 text-right">
                                <form action="{{ route('obras.empleados.baja', [$obra->id, $asig->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Dar de baja a este empleado en la obra?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs text-red-600 hover:text-red-800 font-medium">
                                        Dar de baja
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">
                                No hay empleados asignados actualmente a esta obra.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HISTÓRICO --}}
        @if($asignacionesHistoricas->count() > 0)
            <h3 class="text-sm font-semibold text-slate-700 mt-6 mb-2">Historial de asignaciones</h3>
            <div class="border rounded-xl overflow-hidden max-h-64 overflow-y-auto bg-white">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Empleado</th>
                            <th class="py-2 px-3 text-left">Alta</th>
                            <th class="py-2 px-3 text-left">Baja</th>
                            <th class="py-2 px-3 text-left">Días</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($asignacionesHistoricas as $asig)
                            <tr class="border-b">
                                <td class="py-1 px-3">
                                    {{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}
                                </td>
                                <td class="py-1 px-3">{{ $asig->fecha_alta?->format('d/m/Y') }}</td>
                                <td class="py-1 px-3">{{ $asig->fecha_baja?->format('d/m/Y') }}</td>
                                <td class="py-1 px-3">
                                    {{ $asig->dias_trabajados }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
{{-- TAB ASISTENCIAS --}}
@if($tab === 'asistencias')
  <h2 class="text-lg font-semibold mb-4">Asistencias de empleados asignados a la obra</h2>

  @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
      {{ session('success') }}
    </div>
  @endif
<form method="GET"
      action="{{ route('obras.edit', $obra) }}"
      class="mb-4">

    <input type="hidden" name="tab" value="asistencias">

    <div class="flex flex-wrap items-end gap-4">

        {{-- Desde --}}
        <div class="flex flex-col">
            <label class="text-xs text-gray-500 mb-1">Desde</label>
            <input
                type="date"
                name="asist_desde"
                value="{{ old('asist_desde', $asist_desde ?? '') }}"
                class="w-44 rounded-md border-gray-300 text-sm focus:border-yellow-400 focus:ring-yellow-400"
            >
        </div>

        {{-- Hasta --}}
        <div class="flex flex-col">
            <label class="text-xs text-gray-500 mb-1">Hasta</label>
            <input
                type="date"
                name="asist_hasta"
                value="{{ old('asist_hasta', $asist_hasta ?? '') }}"
                class="w-44 rounded-md border-gray-300 text-sm focus:border-yellow-400 focus:ring-yellow-400"
            >
        </div>

        {{-- Botones --}}
        <div class="flex items-center gap-2 mt-1">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md bg-yellow-400 text-sm font-semibold text-gray-900 hover:bg-yellow-500 transition"
            >
                Filtrar
            </button>

            <a
                href="{{ route('obras.edit', [$obra, 'tab' => 'asistencias']) }}"
                class="text-sm text-gray-500 hover:text-gray-700 underline"
            >
                Limpiar
            </a>

            <a
                href="{{ route('obras.asistencias.reporte', ['obra' => $obra->id, 'asist_desde' => $asist_desde, 'asist_hasta' => $asist_hasta]) }}"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition"
            >
                Generar reporte de asistencia
            </a>
        </div>
    </div>

    {{-- Mensaje de rango activo --}}
    @if(request('asist_desde') || request('asist_hasta'))
        <div class="mt-2 text-xs text-gray-500">
            Mostrando asistencias del
            <strong>{{ request('asist_desde') ?? request('asist_hasta') }}</strong>
            al
            <strong>{{ request('asist_hasta') ?? request('asist_desde') }}</strong>
        </div>
    @endif
</form>

  @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
      Hay errores en el formulario, revisa la información.
    </div>
  @endif

  {{-- full width --}}
  <div class="max-w-6xl">
    {{-- =========================
     VISTA SEMANAL (1 row por empleado)
     ========================= --}}
@if(($daysCount ?? 0) !== 7)
    <div class="text-sm text-gray-500 mb-4">
        La vista semanal solo se muestra cuando el rango es de 7 días (Lun–Dom).
    </div>
@else
    <div class="mb-6">

        <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-semibold text-gray-800">
                Resumen semanal ({{ $weekDays->first()['label'] }} - {{ $weekDays->last()['label'] }})
            </div>

            <div class="text-xs text-gray-500">
                <span class="inline-flex items-center gap-2">
                    <span class="px-2 py-1 rounded bg-gray-100">Ent</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Sal</span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full text-sm">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-3 sticky left-0 bg-gray-50 z-10 min-w-[260px]">
                            Empleado
                        </th>

                        @foreach($weekDays as $wd)
                            <th colspan="2" class="text-center px-3 py-3 border-l border-gray-200 min-w-[120px]">
                                <div class="text-[11px] text-gray-500 leading-none">{{ $wd['dow'] }}</div>
                                <div class="font-semibold text-gray-800 leading-none mt-1">{{ $wd['label'] }}</div>
                            </th>
                        @endforeach
                    </tr>

                    <tr class="bg-gray-50">
                        <th class="sticky left-0 bg-gray-50 z-10"></th>

                        @foreach($weekDays as $wd)
                            <th class="text-center px-2 py-2 border-l border-gray-200 text-[11px] text-gray-500">
                                Ent
                            </th>
                            <th class="text-center px-2 py-2 text-[11px] text-gray-500">
                                Sal
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($asistenciasSemana as $row)
                        <tr class="hover:bg-gray-50/50 transition">
                            {{-- Empleado (sticky) --}}
                            <td class="px-4 py-3 sticky left-0 bg-white z-10 min-w-[260px]">
                                <div class="font-semibold text-gray-900">
                                    {{ $row->empleado->Nombre }} {{ $row->empleado->Apellidos }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $row->empleado->Puesto ?? $row->empleado->puesto_base ?? '' }}
                                </div>
                            </td>

                            {{-- Celdas por día --}}
                            @foreach($weekDays as $wd)
                                @php
                                    $cell = $row->dias[$wd['date']] ?? null;
                                    $ent = $cell['entrada'] ?? null;
                                    $sal = $cell['salida'] ?? null;
                                @endphp

                                <td class="text-center px-2 py-3 border-l border-gray-200 whitespace-nowrap">
                                    <span class="{{ $ent ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                        {{ $ent ?? '—' }}
                                    </span>
                                </td>

                                <td class="text-center px-2 py-3 whitespace-nowrap">
                                    <span class="{{ $sal ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                        {{ $sal ?? '—' }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 1 + ($weekDays->count() * 2) }}" class="px-4 py-6 text-center text-gray-500">
                                Sin asistencias registradas en esta semana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
@endif

    <h3 class="text-sm font-semibold text-slate-700 mb-3">Asistencias registradas</h3>

    <div class="border rounded-xl overflow-hidden w-full">
      <table class="w-full text-sm table-fixed">
        <thead class="bg-slate-50">
          <tr class="border-b text-slate-500">
            <th class="py-2 px-3 text-left w-[40%]">Empleado</th>
            <th class="py-2 px-3 text-left w-[15%]">Día</th>
            <th class="py-2 px-3 text-left w-[15%]">Entrada</th>
            <th class="py-2 px-3 text-left w-[15%]">Salida</th>
            <th class="py-2 px-3 text-center w-[7%]">Foto</th>
            <th class="py-2 px-3 text-center w-[7%]">Estado</th>
            <th class="py-2 px-3 text-right w-[8%]">Acciones</th>
          </tr>
        </thead>

        <tbody>
          @forelse($asistencias as $a)
            <tr class="border-b hover:bg-slate-50">
              <td class="py-2 px-3">
                {{ $a->empleado->Nombre }} {{ $a->empleado->Apellidos }}<br>
                <span class="text-[11px] text-slate-400">
                  {{ $a->empleado->Area }} | {{ $a->empleado->Puesto }}
                </span>
              </td>

              {{-- Día (YYYY-mm-dd) --}}
              <td class="py-2 px-3 whitespace-nowrap">
                <!-- {{ $a->checked_date }} -->
                {{ \Carbon\Carbon::parse($a->checked_date)->format('d/m/Y') }}

              </td>

              {{-- Hora (HH:ii) --}}
              <td class="py-2 px-3 whitespace-nowrap">
                {{ $a->entrada_hora ?? '—' }}
              </td>
              <td>
              {{ $a->salida_hora ?? '—' }}
                </td>   
              @php
                $estado = $a->entrada_hora && $a->salida_hora ? 'completo' : 'pendiente';
                @endphp

            

              {{-- Foto icon --}}
            <td class="py-2 px-3 text-center">
                <div class="flex items-center justify-center gap-2">

                    {{-- Foto entrada --}}
                    @if($a->entrada_foto)
                    <button
                            type="button"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border hover:bg-slate-50"
                            data-photo-url="{{ asset('storage/'.$a->entrada_foto) }}"
                            data-photo-title="Foto de entrada"
                            onclick="openAsistenciaPhoto(this)"
                            title="Ver foto de entrada"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 18 2H9z"/>
                            </svg>
                    </button>

                    @endif

                    {{-- Foto salida --}}
                    @if($a->salida_foto)
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border hover:bg-slate-50"
                        data-photo-url="{{ asset('storage/'.$a->salida_foto) }}"
                        data-photo-title="Foto de salida"
                        onclick="openAsistenciaPhoto(this)"
                        title="Ver foto de salida"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 18 2H9z"/>
                        </svg>
                        </button>

                    @endif

                    {{-- Ninguna foto --}}
                    @if(!$a->entrada_foto && !$a->salida_foto)
                    <span class="text-xs text-slate-400">—</span>
                    @endif
                </div>
                </td>
                <td>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $estado === 'completo' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $estado === 'completo' ? 'Completo' : 'Pendiente' }}
                                    </span>
                </td>



              <td class="py-2 px-3 text-right">
                {{-- aquí luego conectamos delete real de asistencia --}}
                <button class="text-xs text-red-600 hover:text-red-800 font-medium" type="button">
                  Borrar
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-4 text-center text-slate-500">
                No hay asistencias registradas.
              </td>
            </tr>
          @endforelse
        </tbody>
        
      </table>
      {{-- Modal foto asistencia --}}
<div id="asistenciaPhotoModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60" onclick="closeAsistenciaPhoto()"></div>

  <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <div class="font-semibold text-slate-800" id="asistenciaPhotoTitle">Foto</div>
      <button type="button" class="text-slate-500 hover:text-slate-800" onclick="closeAsistenciaPhoto()">
        ✕
      </button>
    </div>

    <div class="p-4 bg-slate-50">
      <img id="asistenciaPhotoImg" src="" alt="Foto asistencia" class="w-full max-h-[70vh] object-contain rounded-lg bg-white" />
    </div>
  </div>
</div>

<script>
  function openAsistenciaPhoto(btn) {
    const url = btn.getAttribute('data-photo-url');
    const title = btn.getAttribute('data-photo-title') || 'Foto';

    const modal = document.getElementById('asistenciaPhotoModal');
    const img = document.getElementById('asistenciaPhotoImg');
    const ttl = document.getElementById('asistenciaPhotoTitle');

    ttl.textContent = title;
    img.src = url;

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeAsistenciaPhoto() {
    const modal = document.getElementById('asistenciaPhotoModal');
    const img = document.getElementById('asistenciaPhotoImg');

    img.src = '';
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAsistenciaPhoto();
  });
</script>

    </div>
  </div>
@endif
{{-- TERMINA TAB ASISTENCIAS --}}
{{-- TAB: COMISIONES --}}
@if($tab === 'comisiones')
    <h2 class="text-lg font-semibold mb-4">Comisiones de la obra</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border rounded-xl p-4 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-semibold text-slate-700">
                Formatos de comisiones registrados
            </h3>
            {{-- Filtro por fecha --}}
            <form method="GET" action="{{ route('obras.edit', $obra) }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="comisiones">

                <select name="fecha"
                        onchange="this.form.submit()"
                        class="px-3 py-2 border rounded-lg text-sm text-slate-700">
                    <option value="">-- Todas las fechas --</option>

                    @foreach($fechasDisponibles as $fecha)
                        <option value="{{ $fecha }}"
                            {{ $selectedFecha == $fecha ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                        </option>
                    @endforeach
                </select>
            </form>


            <a href="{{ route('obras.comisiones.create', $obra) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-medium hover:bg-teal-700">
                Nueva comisión
            </a>
        </div>

        @if($comisiones->isEmpty())
            <p class="text-sm text-slate-500">
                Aún no hay comisiones registradas para esta obra.
            </p>
        @else
               <table class="w-full text-sm">
    <thead class="bg-slate-50 border-b text-slate-500">
        <tr>
            <th class="py-2 px-3 text-left">Fecha</th>
            <th class="py-2 px-3 text-left">Total pilas hechas</th>
            <th class="py-2 px-3 text-left">Comisiones registradas</th>
            <th class="py-2 px-3 text-right">Acciones</th>

        </tr>
    </thead>

    <tbody>
        @foreach($comisionesAgrupadas as $grupo)
            <tr class="border-b hover:bg-slate-50">
                <td class="py-2 px-3">
                    {{ $grupo->fecha->format('d/m/Y') }}
                </td>

                <td class="py-2 px-3 font-semibold">
                    {{ (int) $grupo->total_pilas }}
                </td>

                <td class="py-2 px-3">
                    {{ $grupo->comisiones->count() }}
                </td>
                  <td class="py-2 px-3 text-right text-xs">
                    @foreach($grupo->comisiones as $comision)
                        <a href="{{ route('obras.comisiones.show', [$obra, $comision]) }}"
                           class="inline-flex items-center rounded-md bg-sky-50 px-2 py-1 text-sky-700 hover:bg-sky-100 mr-1 mb-1">
                            Ver #{{ $comision->id }}
                        </a>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
        @endif
    </div>
@endif

       {{-- TAB: MAQUINARIA --}}
@if($tab === 'maquinaria')
    <h2 class="text-lg font-semibold mb-4">Maquinaria asignada a la obra</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE MAQUINARIA ACTIVA --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                Maquinaria actualmente en la obra
            </h3>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Máquina</th>
                            <th class="py-2 px-3 text-left">Tipo</th>
                            <th class="py-2 px-3 text-left">Inicio</th>
                            <th class="py-2 px-3 text-left">Estado</th>
                            <th class="py-2 px-3 text-left">HorometroInicial</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maquinasAsignadasActivas as $asig)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="py-2 px-3">
                                    {{ $asig->maquina->nombre ?? '—' }}<br>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $asig->maquina->codigo ?? '' }} {{ $asig->maquina->modelo ?? '' }}
                                    </span>
                                </td>
                                <td class="py-2 px-3">
                                    {{ $asig->maquina->tipo ?? '—' }}
                                </td>
                                <td class="py-2 px-3">
                                    {{ $asig->fecha_inicio?->format('d/m/Y') }}
                                </td>
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700">
                                        {{ $asig->maquina->estado }}
                                    </span>
                                </td>
                                <td>
                                      {{ $asig->horometro_inicio ?? '—' }}
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <form action="{{ route('obras.maquinaria.baja', [$obra->id, $asig->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Dar de baja esta máquina en la obra?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs text-red-600 hover:text-red-800 font-medium">
                                            Dar de baja
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500">
                                    No hay maquinaria asignada actualmente a esta obra.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- HISTÓRICO --}}
            @if($maquinasAsignadasHistoricas->count() > 0)
                <h3 class="text-sm font-semibold text-slate-700 mt-6 mb-2">
                    Historial de maquinaria en la obra
                </h3>
                <div class="border rounded-xl overflow-hidden max-h-64 overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50">
                            <tr class="border-b text-slate-500">
                                <th class="py-2 px-3 text-left">Máquina</th>
                                <th class="py-2 px-3 text-left">Inicio</th>
                                <th class="py-2 px-3 text-left">Fin</th>
                                <th class="py-2 px-3 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maquinasAsignadasHistoricas as $asig)
                                <tr class="border-b">
                                    <td class="py-1 px-3">
                                        {{ $asig->maquina->nombre ?? '—' }}
                                    </td>
                                    <td class="py-1 px-3">
                                        {{ $asig->fecha_inicio?->format('d/m/Y') }}
                                    </td>
                                    <td class="py-1 px-3">
                                        {{ $asig->fecha_fin?->format('d/m/Y') }}
                                    </td>
                                    <td class="py-1 px-3">
                                        {{ ucfirst($asig->estado) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- FORM PARA ASIGNAR NUEVA MÁQUINA --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                Asignar maquinaria a esta obra
            </h3>

            @if($maquinasDisponibles->isEmpty())
                <p class="text-sm text-slate-500">
                    No hay maquinaria disponible para asignar en este momento.
                </p>
            @else
                <form method="POST"
                      action="{{ route('obras.maquinaria.store', $obra) }}">
                    @csrf

                    <div class="space-y-3">
                        {{-- Select de máquina --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Máquina
                            </label>
                            <select name="maquina_id"
                                    class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecciona una máquina</option>
                                @foreach($maquinasDisponibles as $maq)
                                    <option value="{{ $maq->id }}"
                                        {{ old('maquina_id') == $maq->id ? 'selected' : '' }}>
                                        {{ $maq->nombre }} ({{ $maq->codigo }})
                                    </option>
                                @endforeach
                            </select>
                            @error('maquina_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fecha de inicio --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Fecha de inicio en la obra
                            </label>
                            <input type="date"
                                   name="fecha_inicio"
                                   value="{{ old('fecha_inicio', now()->toDateString()) }}"
                                   class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            @error('fecha_inicio')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Horómetro inicial --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Horómetro al ingresar a la obra
                            </label>
                            <input type="number"
                                name="horometro_inicio"
                                step="0.01"
                                min="0.01"
                                value="{{ old('horometro_inicio') }}"
                                placeholder="Ej. 105.0"
                                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            @error('horometro_inicio')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- Notas --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Notas
                            </label>
                            <textarea name="notas"
                                      rows="3"
                                      class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">{{ old('notas') }}</textarea>
                            @error('notas')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 bg-teal-600 text-white text-sm rounded-xl hover:bg-teal-700">
                            Asignar máquina
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endif
  {{-- TAB:HORAS MAQUINA MAQUINARIA --}}

@if($tab === 'horas-maquina')

<div class="space-y-4">

    {{-- Encabezado / Resumen --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Horas máquina</h2>
            <p class="text-sm text-slate-600">
                Historial de registros por tramo. Cada fila corresponde a una captura.
            </p>
        </div>
    </div>

    {{-- Acciones rápidas: registrar horas (para máquinas activas) --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-medium text-slate-900 mb-3">Registrar horas</div>

        @if($maquinasAsignadasActivas->count())
            <div class="flex flex-wrap says gap-2">
                @foreach($maquinasAsignadasActivas as $asignacion)
                    <button type="button"
                        class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700"
                        x-data
                        @click="$dispatch('open-horas-modal', {
                            obraMaquinaId: {{ $asignacion->id }},
                            maquinaNombre: @js($asignacion->maquina->nombre ?? 'Máquina'),
                            horometroSugerido: {{ (float)($asignacion->horometro_actual ?? $asignacion->horometro_inicio ?? 0) }}
                        })">
                        {{ $asignacion->maquina->nombre ?? 'Máquina' }} · Registrar
                    </button>
                @endforeach
            </div>
        @else
            <div class="text-sm text-slate-500">No hay máquinas activas asignadas a esta obra.</div>
        @endif
    </div>

    {{-- Tabla de registros --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Máquina</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Horómetro inicio</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Horómetro fin</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Horas</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Notas</th>
                </tr>
            </thead>

            <tbody>
                @forelse($registrosHorasMaquina as $r)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 text-slate-700">
                            {{ optional($r->inicio)->format('d/m/Y H:i') ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-slate-700">
                            {{ $r->asignacion?->maquina?->nombre ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-right text-slate-700">
                            {{ number_format((float)$r->horometro_inicio, 2) }}
                        </td>

                        <td class="px-4 py-2 text-right text-slate-700">
                            {{ number_format((float)$r->horometro_fin, 2) }}
                        </td>

                        <td class="px-4 py-2 text-right font-semibold text-slate-900">
                            {{ number_format((float)($r->horas ?? ((float)$r->horometro_fin - (float)$r->horometro_inicio)), 2) }}
                        </td>

                        <td class="px-4 py-2 text-slate-500">
                            {{ $r->notas ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">
                            Aún no hay registros de horas para esta obra.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Modal único (inyectado) --}}
<div
    x-data="{
        open: false,
        obraMaquinaId: null,
        maquinaNombre: '',
        horometroSugerido: 0
    }"
    @open-horas-modal.window="
        open = true;
        obraMaquinaId = $event.detail.obraMaquinaId;
        maquinaNombre = $event.detail.maquinaNombre;
        horometroSugerido = $event.detail.horometroSugerido;
        $nextTick(() => $refs.horometroFin.focus());
    "
>
    <div x-show="open" x-transition class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40" @click="open=false"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Registrar horas</div>
                        <div class="font-semibold text-slate-900" x-text="maquinaNombre"></div>
                    </div>
                    <button class="text-slate-400 hover:text-slate-700" @click="open=false">✕</button>
                </div>

                <form method="POST"
                      :action="`/obras/{{ $obra->id }}/maquinaria/${obraMaquinaId}/horas`"
                      class="p-5 space-y-4">
                    @csrf

                    <div class="text-xs text-slate-500">
                        Último horómetro registrado:
                        <span class="font-medium" x-text="Number(horometroSugerido).toFixed(2)"></span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Horómetro final</label>
                        <input type="number" step="0.01" min="0"
                               name="horometro_fin"
                               x-ref="horometroFin"
                               :value="Number(horometroSugerido).toFixed(2)"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                               required>
                        <p class="text-xs text-slate-500 mt-1">
                            Se calcula automáticamente la diferencia contra el último horómetro.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                        <textarea name="notas" rows="2"
                                  class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50"
                                @click="open=false">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endif

{{-- TAB: PILAS --}}
@if($tab === 'pilas')
    <h2 class="text-lg font-semibold mb-4">Pilas de la obra</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE PILAS ACTIVAS --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                Pilas actualmente asignadas a la obra
            </h3>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b text-slate-500">
                            <th class="py-2 px-3 text-left">Tipo</th>    
                            <th class="py-2 px-3 text-center">Cantidad(proyecto)</th>
                            <th class="py-2 px-3 text-center">Hechas</th>
                            <th class="py-2 px-3 text-center">Faltan</th>
                            <th class="py-2 px-3 text-left">Diámetro (proyecto)</th>
                            <th class="py-2 px-3 text-left">Profundidad (proyecto)</th>
                            <th class="py-2 px-3 text-left">Ubicación</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pilasAsignadasActivas as $pila)
                            @php
                                $programadas = $pila->cantidad_programada ?? 0;
                                // viene del withSum, puede ser null
                                $ejecutadas  = $pila->cantidad_ejecutada ?? 0;
                                $faltan      = max($programadas - $ejecutadas, 0);
                            @endphp
                            <tr class="border-b hover:bg-slate-50">
                                    <td class="py-2 px-3">
                                        {{ $pila->tipo ?? '—' }}
                                    </td>    
                                    <td class="py-2 px-3">
                                    {{ $programadas ?? '—' }}
                                    </td>
                                    <td class="py-2 px-3">
                                    {{ $ejecutadas ?? '—' }}
                                    </td>
                                    <td class="py-2 px-3">
                                    {{ $faltan ?? '—' }}
                                    </td>
                                
                                <td class="py-2 px-3">
                                    @if(!is_null($pila->diametro_proyecto))
                                        {{ number_format($pila->diametro_proyecto, 2) }} m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    @if(!is_null($pila->profundidad_proyecto))
                                        {{ number_format($pila->profundidad_proyecto, 2) }} m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    {{ $pila->ubicacion ?: '—' }}
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <form action="{{ route('obras.pilas.baja', [$obra->id, $pila->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Dar de baja esta pila en la obra?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs text-red-600 hover:text-red-800 font-medium">
                                            Dar de baja
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500">
                                    No hay pilas asignadas actualmente a esta obra.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- HISTÓRICO --}}
            @if($pilasAsignadasHistoricas->count() > 0)
                <h3 class="text-sm font-semibold text-slate-700 mt-6 mb-2">
                    Historial de pilas de la obra
                </h3>
                <div class="border rounded-xl overflow-hidden max-h-64 overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50">
                            <tr class="border-b text-slate-500">
                                <th class="py-2 px-3 text-left">No. pila</th>
                                <th class="py-2 px-3 text-left">Tipo</th>
                                <th class="py-2 px-3 text-left">Diámetro</th>
                                <th class="py-2 px-3 text-left">Profundidad</th>
                                <th class="py-2 px-3 text-left">Ubicación</th>
                                <th class="py-2 px-3 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pilasAsignadasHistoricas as $pila)
                                <tr class="border-b">
                                    <td class="py-1 px-3">{{ $pila->numero_pila ?? '—' }}</td>
                                    <td class="py-1 px-3">{{ $pila->tipo ?? '—' }}</td>
                                    <td class="py-1 px-3">
                                        @if(!is_null($pila->diametro_proyecto))
                                            {{ number_format($pila->diametro_proyecto, 2) }} m
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-1 px-3">
                                        @if(!is_null($pila->profundidad_proyecto))
                                            {{ number_format($pila->profundidad_proyecto, 2) }} m
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-1 px-3">{{ $pila->ubicacion ?: '—' }}</td>
                                    <td class="py-1 px-3">
                                        {{ $pila->activo ? 'Activa' : 'Inactiva' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- FORM PARA ASIGNAR NUEVA PILA --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                Asignar pila a esta obra
            </h3>

            @if($pilasCatalogo->isEmpty())
                <p class="text-sm text-slate-500">
                    No hay pilas configuradas en el catálogo.
                </p>
            @else
                <form method="POST"
                      action="{{ route('obras.pilas.store', $obra) }}">
                    @csrf

                    <div class="space-y-3">
                        {{-- Número de pila --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Cantidad de pilas de este tipo
                            </label>
                            <input type="text"
                                   name="cantidad_programada"
                                   value="{{ old('cantidad_programada') }}"
                                   class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            @error('cantidad_programada')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tipo de pila (catálogo) --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Tipo de pila (catálogo)
                            </label>
                            <select name="tipo"
                                    class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecciona un tipo</option>
                                @foreach($pilasCatalogo as $pilaCat)
                                    <option value="{{ $pilaCat->codigo }}"
                                        {{ old('tipo') == $pilaCat->codigo ? 'selected' : '' }}>
                                        {{ $pilaCat->codigo }} – {{ $pilaCat->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Diámetro y profundidad de proyecto --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Diámetro de proyecto (m)
                                </label>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="diametro_proyecto"
                                       value="{{ old('diametro_proyecto') }}"
                                       class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                                @error('diametro_proyecto')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Profundidad de proyecto (m)
                                </label>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="profundidad_proyecto"
                                       value="{{ old('profundidad_proyecto') }}"
                                       class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                                @error('profundidad_proyecto')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Ubicación --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Ubicación (ej. eje, alineamiento, etc.)
                            </label>
                            <input type="text"
                                   name="ubicacion"
                                   value="{{ old('ubicacion') }}"
                                   class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            @error('ubicacion')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Notas --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Notas
                            </label>
                            <textarea name="notas"
                                      rows="3"
                                      class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">{{ old('notas') }}</textarea>
                            @error('notas')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 bg-teal-600 text-white text-sm rounded-xl hover:bg-teal-700">
                            Asignar pila
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    
@endif

{{--  TAB RELACIONAR  FACTURAS --}}
@if ($tab === 'relacionar')
<div x-data="relacionFacturasModal()">

    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Facturas relacionadas</h2>

            <button
                @click="openModal()"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                + Relacionar facturas
            </button>
        </div>

        {{-- Tabla de facturas ya relacionadas --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">UUID</th>
                        <th class="py-2">Fecha</th>
                        <th class="py-2">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($obra->cfdis as $cfdi)
                        <tr class="border-t">
                            <td class="py-2">{{ $cfdi->uuid }}</td>
                            <td class="py-2">{{ $cfdi->fecha_emision }}</td>
                            <td class="py-2">${{ number_format($cfdi->total,2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-400">
                                Sin facturas relacionadas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
{{-- MODAL --}}
<div x-show="open"
     x-cloak
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white w-full max-w-2xl rounded-xl p-6">

        <h3 class="text-lg font-semibold mb-4">Relacionar facturas</h3>

      {{-- BUSCADORES --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
  <input type="date"
       id="filtro_fecha_cfdi"
       name="fecha"
       x-model="filters.fecha"
       @input="applyFilters()"
       class="rounded-lg border-gray-300 text-sm"
       placeholder="Fecha">

    <input type="text"
           x-model="filters.uuid"
           @input="applyFilters()"
           class="rounded-lg border-gray-300 text-sm"
           placeholder="Buscar UUID">

    <input type="text"
           x-model="filters.monto"
           @input="applyFilters()"
           class="rounded-lg border-gray-300 text-sm"
           placeholder="Buscar monto">
</div>

{{-- TABLA --}}
<div class="max-h-96 overflow-y-auto border rounded-lg">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 sticky top-0">
            <tr>
                <th class="px-3 py-2 text-left">Fecha</th>
                <th class="px-3 py-2 text-left">UUID</th>
                <th class="px-3 py-2 text-right">Monto</th>
                <th class="px-3 py-2 text-center">Seleccionar</th>
            </tr>
        </thead>

        <tbody>
            
                <template x-for="cfdi in paginatedCfdis" :key="cfdi.id">
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-3 py-2" x-text="cfdi.fecha_emision"></td>

                    <td class="px-3 py-2">
                        <span class="font-mono text-xs" x-text="cfdi.uuid"></span>
                    </td>

                    <td class="px-3 py-2 text-right font-medium">
                        $<span x-text="Number(cfdi.total).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                    </td>

                    <td class="px-3 py-2 text-center">
                        <input type="checkbox"
                               :value="cfdi.id"
                               x-model="selected"
                               class="rounded border-gray-300">
                    </td>
                </tr>
            </template>

            <tr x-show="filteredCfdis.length === 0">
                <td colspan="4" class="px-3 py-6 text-center text-gray-400">
                    No se encontraron CFDIs
                </td>
            </tr>
        </tbody>
    </table>
    <div class="mt-4 flex items-center justify-between text-sm">
    <div class="text-gray-500">
        Mostrando
        <span x-text="paginatedCfdis.length"></span>
        de
        <span x-text="filteredCfdis.length"></span>
        CFDIs
    </div>

    <div class="flex items-center gap-2">
        <button type="button"
                @click="prevPage()"
                :disabled="currentPage === 1"
                class="rounded-lg border px-3 py-1 disabled:opacity-40">
            Anterior
        </button>

        <span class="text-gray-600">
            Página <span x-text="currentPage"></span> de <span x-text="totalPages"></span>
        </span>

        <button type="button"
                @click="nextPage()"
                :disabled="currentPage === totalPages"
                class="rounded-lg border px-3 py-1 disabled:opacity-40">
            Siguiente
        </button>
    </div>
</div>
</div>

        {{-- ACCIONES --}}
        <div class="flex justify-end gap-2 mt-4">
            <button @click="open=false" class="px-4 py-2 border rounded">
                Cancelar
            </button>

            <button @click="relacionar()"
                    class="bg-green-600 text-white px-4 py-2 rounded">
                Relacionar seleccionadas
            </button>
        </div>

    </div>
</div>
</div>
   @php
    $cfdisDisponiblesJson = $cfdisDisponibles->map(function ($cfdi) {
        return [
            'id' => $cfdi->id,
            'uuid' => $cfdi->uuid,
            'fecha_emision' => optional($cfdi->fecha_emision)->format('Y-m-d'),
            'total' => (float) $cfdi->total,
        ];
    })->values();
@endphp
<script>
function relacionFacturasModal() {
    return {
        open: false,

        cfdis: @json($cfdisDisponiblesJson),
        filteredCfdis: [],
        selected: [],

        filters: {
            fecha: '',
            uuid: '',
            monto: '',
        },

        currentPage: 1,
        perPage: 10,

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredCfdis.length / this.perPage));
        },

        get paginatedCfdis() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredCfdis.slice(start, start + this.perPage);
        },

        openModal() {
            this.open = true;
            this.filteredCfdis = this.cfdis;
            this.currentPage = 1;

             console.log('Total CFDIs cargados en modal:', this.cfdis.length);
    console.log('CFDIs del 2026-03-17:', this.cfdis.filter(c => c.fecha_emision === '2026-03-17'));
        },

        applyFilters() {
            const fecha = this.filters.fecha;
            const uuid = this.filters.uuid.toLowerCase().trim();
            const monto = this.filters.monto.replace(',', '').trim();
    console.log('Filtro fecha:', fecha);

            this.filteredCfdis = this.cfdis.filter(cfdi => {
                const matchFecha = !fecha || String(cfdi.fecha_emision ?? '').includes(fecha);
                const matchUuid = !uuid || String(cfdi.uuid ?? '').toLowerCase().includes(uuid);
                const matchMonto = !monto || String(cfdi.total ?? '').includes(monto);

                return matchFecha && matchUuid && matchMonto;
            });

            this.currentPage = 1;
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },

        closeModal() {
            this.open = false;
            this.filters = { fecha: '', uuid: '', monto: '' };
            this.selected = [];
            this.filteredCfdis = this.cfdis;
            this.currentPage = 1;
        },

       async relacionar() {
    console.log('IDs enviados:', this.selected);

    if (this.selected.length === 0) {
        alert('Selecciona al menos una factura.');
        return;
    }

    const res = await fetch(`{{ route('obras.relacionarCfdis', $obra->id) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            cfdis: this.selected
        })
    });

    const text = await res.text();

    console.log('Status:', res.status);
    console.log('Respuesta:', text);

    if (!res.ok) {
        alert('Error al relacionar. Revisa consola.');
        return;
    }

    location.reload();
}
    }
}
</script>
@endif
{{--  TERMINA TAB RELACIONAR  FACTURAS --}}
{{-- TAB: FACTURACIÓN --}}
@if ($tab === 'facturacion')
    <h2 class="text-lg font-semibold mb-4">Facturación de la obra</h2>

    <div class="bg-white border rounded-xl p-4 shadow-sm space-y-6" id="facturacion-tab">

        {{-- Encabezado y botón --}}
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700">
                Facturas registradas
            </h3>

            <button type="button"
                    id="btn-toggle-factura-form"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg
                           bg-[#0B265A] text-white shadow-sm hover:bg-[#0d3278] transition">
                + Registrar nueva factura
            </button>
        </div>
        @php
        $fmtTotalFacturado = '$' . number_format($totalFacturado, 2);
        $fmtTotalPagado    = '$' . number_format($totalPagado, 2);
        $fmtPendiente      = '$' . number_format($totalPendiente, 2);
    @endphp

<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
            Total facturado
        </p>
        <p class="mt-1 text-sm font-semibold text-slate-800">
            {{ $fmtTotalFacturado }}
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
            Total pagado
        </p>
        <p class="mt-1 text-sm font-semibold text-emerald-700">
            {{ $fmtTotalPagado }}
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
            Pendiente de pago
        </p>
        <p class="mt-1 text-sm font-semibold {{ $totalPendiente > 0 ? 'text-amber-700' : 'text-slate-700' }}">
            {{ $fmtPendiente }}
        </p>
    </div>
</div>


       {{-- Tabla de facturas --}}
@if($facturas->isEmpty())
    <div class="border border-dashed border-slate-200 rounded-lg p-4 text-sm text-slate-500">
        No hay facturas registradas para esta obra.
    </div>
@else
    <div class="border border-slate-200 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-600">
                <tr>
                    <th class="px-3 py-2 text-left">Fecha factura</th>
                    <th class="px-3 py-2 text-left">Fecha pago</th>
                    <th class="px-3 py-2 text-right">Monto</th>
                    <th class="px-3 py-2 text-right">Estado</th>
                    <th class="px-3 py-2 text-center">PDF</th>
                    <th class="px-3 py-2 text-center">Acciones</th>
                </tr>
            </thead>
           <tbody class="divide-y divide-slate-100">
                @foreach($facturas as $factura)
                    <tr>
                        <td class="px-3 py-2">
                            {{ optional($factura->fecha_factura)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $factura->fecha_pago ? $factura->fecha_pago->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            $ {{ number_format($factura->monto, 2) }}
                        </td>

                        {{-- NUEVO: Estado --}}
                       <td class="px-3 py-2 text-center">
                            @if($factura->estado === 'pagada')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                            bg-emerald-100 text-emerald-800">
                                    Pagada
                                </span>
                            @elseif($factura->estado === 'pendiente')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                            bg-amber-100 text-amber-800">
                                    Pendiente
                                </span>
                            @elseif($factura->estado === 'cancelada')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                            bg-rose-100 text-rose-800">
                                    Cancelada
                                </span>
                            @endif
                        </td>


                        <td class="px-3 py-2 text-center">
                            @if($factura->pdf_path)
                                <a href="{{ asset('storage/'.$factura->pdf_path) }}"
                                target="_blank"
                                class="text-xs text-[#0B265A] font-semibold hover:underline">
                                    Ver PDF
                                </a>
                            @else
                                <span class="text-xs text-slate-400">Sin archivo</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-2">

                                @if($factura->estado === 'pendiente')
                                    {{-- Marcar como pagada --}}
                                    <form method="POST"
                                        action="{{ route('obras.facturas.pagada', ['obra' => $obra, 'factura' => $factura]) }}">
                                        @csrf
                                        @method('PATCH')
                                        {{-- Si deseas capturar fecha_pago, puedes añadir un input type="date" aquí --}}
                                        <button type="submit"
                                                class="text-[11px] text-emerald-700 hover:underline">
                                            Marcar pagada
                                        </button>
                                    </form>

                                    {{-- Marcar como cancelada --}}
                                    <form method="POST"
                                        action="{{ route('obras.facturas.cancelada', ['obra' => $obra, 'factura' => $factura]) }}"
                                        onsubmit="return confirm('¿Marcar esta factura como cancelada?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-[11px] text-rose-700 hover:underline">
                                            Cancelar
                                        </button>
                                    </form>
                                @endif

                                {{-- Eliminar (opcional, solo para admin) --}}
                                <form method="POST"
                                    action="{{ route('obras.facturas.destroy', ['obra' => $obra, 'factura' => $factura]) }}"
                                    onsubmit="return confirm('¿Eliminar definitivamente esta factura?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] text-red-600 hover:underline">
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
@endif

{{-- FORM NUEVA FACTURA --}}
<div id="factura-form-container" class="hidden">
    <div class="mt-4 pt-4 border-t border-slate-100">
        <h4 class="text-xs font-semibold text-slate-600 mb-3">
            Registrar nueva factura
        </h4>

        <form method="POST"
              action="{{ route('obras.facturas.store', $obra) }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Fecha de generación de factura
                    </label>
                    <input type="date" name="fecha_factura"
                           value="{{ old('fecha_factura') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                    @error('fecha_factura')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Fecha de pago
                    </label>
                    <input type="date" name="fecha_pago"
                           value="{{ old('fecha_pago') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                    @error('fecha_pago')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Monto de la factura
                    </label>
                    <input type="number" step="0.01" name="monto"
                           value="{{ old('monto') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           placeholder="$ 0.00">
                    @error('monto')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        PDF de la factura
                    </label>
                    <input type="file" name="pdf"
                           class="mt-1 block w-full text-xs text-slate-600
                                  file:mr-3 file:py-1.5 file:px-3
                                  file:rounded-lg file:border-0
                                  file:bg-[#0B265A] file:text-white
                                  file:text-xs file:font-semibold">
                    @error('pdf')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Notas (opcional)
                </label>
                <textarea name="notas" rows="2"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('notas') }}</textarea>
                @error('notas')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <button type="button"
                        id="btn-cancelar-factura"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#0B265A] text-white shadow-sm">
                    Guardar factura
                </button>
            </div>
        </form>
    </div>
</div>

                <p class="mt-2 text-xs text-slate-500">
                    Nota: este formulario aún no guarda en la base de datos. En el siguiente paso crearemos
                    la tabla <code>obras_facturas</code> y conectaremos este tab con el backend.
                </p>
            </div>
        </div>

    </div>
    



    {{-- Script sencillo para mostrar/ocultar el formulario --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnToggle = document.getElementById('btn-toggle-factura-form');
            const formContainer = document.getElementById('factura-form-container');
            const btnCancelar = document.getElementById('btn-cancelar-factura');

            if (!btnToggle || !formContainer) return;

            const closeForm = () => {
                formContainer.classList.add('hidden');
                btnToggle.textContent = '+ Registrar nueva factura';
            };

            const openForm = () => {
                formContainer.classList.remove('hidden');
                btnToggle.textContent = 'Cerrar formulario';
            };

            btnToggle.addEventListener('click', function () {
                if (formContainer.classList.contains('hidden')) {
                    openForm();
                } else {
                    closeForm();
                }
            });

            if (btnCancelar) {
                btnCancelar.addEventListener('click', function () {
                    closeForm();
                });
            }
        });
    </script>
@endif


    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    // Lista de empleados desde PHP → JS
    const empleados = {!! $empleadosAsignables->toJson(JSON_UNESCAPED_UNICODE) !!};
// console.log('Total empleados asignables:', empleados.length);


    const inputBuscar = document.getElementById('buscador-empleado');
    const inputId     = document.getElementById('empleado_id');
    const contenedor  = document.getElementById('resultados-empleado');

     if (!inputBuscar || !inputId || !contenedor) {
        // console.warquiero n('Buscador de empleados: elementos no encontrados. Ignorando script en este tab.');
        return;
    }


    function limpiarResultados() {
        contenedor.innerHTML = '';
        contenedor.classList.add('hidden');
    }

    function mostrarResultados(lista) {
        contenedor.innerHTML = '';
        if (!lista.length) {
            const item = document.createElement('div');
            item.className = 'px-3 py-2 text-slate-500';
            item.textContent = 'No existe un empleado con ese nombre';
            contenedor.appendChild(item);
            contenedor.classList.remove('hidden');
            return;
        }

        lista.forEach(function (emp) {
            const apellidos = emp.Apellidos ?? '';
            const nombre    = emp.Nombre ?? '';
            const puesto    = emp.Puesto ?? '';
            const obraAsignada = emp.obra_asignada ?? null;
            const obraTexto = obraAsignada
                ? ((obraAsignada.clave_obra ? obraAsignada.clave_obra + ' - ' : '') + (obraAsignada.nombre ?? '')).trim()
                : '';

            const nombreCompleto = (apellidos + ' ' + nombre).trim();
            const texto = nombreCompleto + (puesto ? ' (' + puesto + ')' : '');

            const item = document.createElement('button');
            item.type = 'button';
            item.className = emp.asignado
                ? 'w-full text-left px-3 py-2 bg-amber-50 text-slate-500 cursor-not-allowed border-b border-amber-100 last:border-b-0'
                : 'w-full text-left px-3 py-2 hover:bg-slate-100 border-b border-slate-100 last:border-b-0';
            item.disabled = !!emp.asignado;

            const titulo = document.createElement('div');
            titulo.className = 'font-medium';
            titulo.textContent = texto;
            item.appendChild(titulo);

            if (emp.asignado) {
                const detalle = document.createElement('div');
                detalle.className = 'mt-0.5 text-[11px] text-amber-700';
                detalle.textContent = emp.asignado_en_esta_obra
                    ? 'Ya está asignado en esta obra'
                    : 'Ya está asignado en otra obra' + (obraTexto ? ': ' + obraTexto : '');
                item.appendChild(detalle);
            }

            item.addEventListener('click', function () {
                if (emp.asignado) {
                    return;
                }

                inputBuscar.value = nombreCompleto;
                inputId.value = emp.id_Empleado;
                limpiarResultados();
            });

            contenedor.appendChild(item);
        });

        contenedor.classList.remove('hidden');
    }

    inputBuscar.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();
        inputId.value = ''; // si cambia el texto, limpiamos la selección

        if (termino.length < 2) {
            limpiarResultados();
            return;
        }

        const filtrados = empleados.filter(function (emp) {
            const apellidos = (emp.Apellidos ?? '').toLowerCase();
            const nombre    = (emp.Nombre ?? '').toLowerCase();
            const puesto    = (emp.Puesto ?? '').toLowerCase();

            const texto = apellidos + ' ' + nombre + ' ' + puesto;
            return texto.includes(termino);
        }).slice(0, 15); // máximo 15 resultados

        mostrarResultados(filtrados);
    });

    // Cerrar lista si haces click fuera
    document.addEventListener('click', function (e) {
        if (!contenedor.contains(e.target) && e.target !== inputBuscar) {
            limpiarResultados();
        }
    });
});
</script>

@endsection
{{-- COLOCAR ESTO AL FINAL DEL ARCHIVO, FUERA DE LOS @if($tab) --}}

<div id="modalPresupuestos" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/75 transition-opacity" onclick="closeModalPresupuestos()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200">
            <div class="bg-white">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Vincular Presupuestos Maestros</h3>
                        <p class="text-xs text-slate-500">Selecciona los presupuestos disponibles para esta obra.</p>
                    </div>
                    <button type="button" onclick="closeModalPresupuestos()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto bg-white">
                    <form id="formVincularPresupuesto" action="{{ route('obras.vincularPresupuesto', $obra->id) }}" method="POST">
                        @csrf
                        <table class="w-full text-sm">
                            <thead class="text-slate-500 uppercase text-[10px] tracking-wider border-b sticky top-0 bg-white">
                                <tr>
                                    <th class="py-3 px-2 text-left">Selección</th>
                                    <th class="py-3 px-2 text-left">Folio</th>
                                    <th class="py-3 px-2 text-left">Cliente / Proyecto</th>
                                    <th class="py-3 px-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($presupuestosDisponibles as $pre)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="py-3 px-2">
                                            <input type="checkbox" name="presupuestos[]" value="{{ $pre->id }}" 
                                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        </td>
                                        <td class="py-3 px-2 font-mono text-xs font-bold text-blue-700">
                                            {{ $pre->codigo_proyecto }}
                                        </td>
                                        <td class="py-3 px-2 text-slate-700">
                                            {{ $pre->nombre_cliente }}
                                        </td>
                                        <td class="py-3 px-2 text-right font-bold text-slate-900">
                                            ${{ number_format($pre->total_presupuesto, 2) }}
                                        </td>
                                    </tr>
                               @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                {{-- Icono con tamaño controlado h-12 w-12 --}}
                                                <div class="bg-slate-50 p-4 rounded-full mb-3">
                                                    <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <p class="text-slate-500 font-medium">Todos los presupuestos están vinculados</p>
                                                <p class="text-slate-400 text-xs mt-1">No hay presupuestos maestros disponibles para este cliente.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </form>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModalPresupuestos()" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" form="formVincularPresupuesto" 
                            class="px-6 py-2 bg-[#0B265A] text-white rounded-xl text-sm font-bold shadow-lg hover:bg-[#1a3a7a] transition-all transform hover:scale-[1.02]">
                        Vincular Seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openModalPresupuestos() {
        const modal = document.getElementById('modalPresupuestos');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Evita el scroll de fondo
    }
    function closeModalPresupuestos() {
        const modal = document.getElementById('modalPresupuestos');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Devuelve el scroll
    }

//     function calcularFila(idCampo) {
//     const inputs = document.querySelectorAll(`.input-semana-${idCampo}`);
//     const displayTotal = document.getElementById(`total_prog_${idCampo}`);
//     const displayDiff = document.getElementById(`diff_${idCampo}`);
    
//     let totalProgramado = 0;
//     let tope = 0;

//     inputs.forEach(input => {
//         totalProgramado += parseFloat(input.value) || 0;
//         tope = parseFloat(input.dataset.tope); // El tope lo sacamos del primer input
//     });

//     const diferencia = tope - totalProgramado;

//     // Actualizar textos
//     displayTotal.innerText = '$' + totalProgramado.toLocaleString('en-US', {minimumFractionDigits: 2});
//     displayDiff.innerText = '$' + diferencia.toLocaleString('en-US', {minimumFractionDigits: 2});

//     // Color de alerta si se pasa
//     if (diferencia < 0) {
//         displayDiff.classList.remove('text-green-600', 'text-slate-600');
//         displayDiff.classList.add('text-red-600');
//     } else {
//         displayDiff.classList.remove('text-red-600');
//         displayDiff.classList.add('text-green-600');
//     }
// }

// Ejecutar una vez al cargar para llenar los totales iniciales
document.addEventListener('DOMContentLoaded', function() {
    const uniqueIds = [...new Set([...document.querySelectorAll('input[data-id]')].map(i => i.dataset.id))];
    uniqueIds.forEach(id => calcularFila(id));
});
// Quita comas para que el usuario pueda editar el número fácilmente
function limpiarFormato(input) {
    let valor = input.value.replace(/,/g, '');
    input.type = 'number'; // Cambiamos temporalmente a number para teclado numérico en móvil
    input.value = valor;
}

// Pone comas y decimales cuando el usuario termina de editar
function aplicarFormato(input) {
    let valor = parseFloat(input.value) || 0;
    input.type = 'text';
    input.dataset.valor = valor; // Guardamos el valor crudo para cálculos
    input.value = valor.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Ajusta tu función calcularFila para que use dataset.valor
// Cache de referencias al cargar la página
const filaCache = {};

function buildCache() {
    document.querySelectorAll('[data-id]').forEach(input => {
        const id = input.dataset.id;
        if (!filaCache[id]) {
            filaCache[id] = {
                inputs: [],
                total: document.getElementById(`total_prog_${id}`),
                diff:  document.getElementById(`diff_${id}`),
                tope:  parseFloat(input.dataset.tope) || 0,
            };
        }
        filaCache[id].inputs.push(input);
    });
}

// Ajusta tu función calcularFila para que use dataset.valor
function calcularFila(idCampo) {
    const inputs = document.querySelectorAll(`.input-semana-${idCampo}`);
    const displayTotal = document.getElementById(`total_prog_${idCampo}`);
    const displayDiff = document.getElementById(`diff_${idCampo}`);
    
    let totalProgramado = 0;
    let tope = 0;

    inputs.forEach(input => {
        // Usamos el valor crudo del dataset o limpiamos el value si no existe
        let valorLimpio = input.dataset.valor || input.value.replace(/,/g, '');
        totalProgramado += parseFloat(valorLimpio) || 0;
        tope = parseFloat(input.dataset.tope);
    });

    const diferencia = tope - totalProgramado;
    const porcentajeProgramado = tope > 0 ? (totalProgramado / tope) * 100 : 0;
    const porcentajeLimitado = Math.min(Math.max(porcentajeProgramado, 0), 100);
    const barraProgramado = document.getElementById(`prog_bar_${idCampo}`);
    const labelProgramado = document.getElementById(`prog_label_${idCampo}`);

    displayTotal.innerText = '$' + totalProgramado.toLocaleString('en-US', {minimumFractionDigits: 2});
    displayDiff.innerText = '$' + diferencia.toLocaleString('en-US', {minimumFractionDigits: 2});

    if (barraProgramado && labelProgramado) {
        const colorBarra = porcentajeProgramado >= 90
            ? 'bg-red-300/80'
            : (porcentajeProgramado >= 50 ? 'bg-amber-300/80' : 'bg-emerald-300/80');
        const colorTexto = porcentajeProgramado >= 90
            ? 'text-red-700'
            : (porcentajeProgramado >= 50 ? 'text-amber-700' : 'text-emerald-700');

        barraProgramado.className = `absolute inset-y-0 left-0 ${colorBarra} transition-all duration-300`;
        barraProgramado.style.width = `${porcentajeLimitado}%`;
        labelProgramado.className = `text-[10px] font-bold ${colorTexto}`;
        labelProgramado.innerText = `Programado: ${porcentajeProgramado.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}%`;
    }

    // Colores de alerta
    if (diferencia < 0) {
        displayDiff.className = 'p-3 border text-right font-bold text-red-600';
    } else {
        displayDiff.className = 'p-3 border text-right font-bold text-green-600';
    }
}

// Deshabilitar inputs vacíos antes de enviar para que no viajen en el Request
// document.querySelector('form').addEventListener('submit', function() {
//     this.querySelectorAll('.input-semana').forEach(input => {
//         if (input.value === "" || input.value === null) {
//             input.disabled = true; // No se enviará al servidor
//         }
//     });
// });
</script>
