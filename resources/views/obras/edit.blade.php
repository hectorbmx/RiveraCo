@extends('layouts.admin')

@section('title', 'Editar Obra')

@section('content')

<div class="max-w-6xl mx-auto">

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
            'pilas'        => 'Pilas',
            'empleados'    => 'Empleados',
            'maquinaria'   => 'Maquinaria',
            'horas-maquina'=> 'Horas maquina',
            'comisiones'   => 'Comisiones',
            'facturacion'  => 'Facturacion',
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
        @if($tab === 'general')
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
@if($tab === 'presupuestos')
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
@endif


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

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

        {{-- LISTA DE ASIGNACIONES ACTIVAS --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Empleados actualmente en la obra</h3>

            <div class="border rounded-xl overflow-hidden">
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
                <div class="border rounded-xl overflow-hidden max-h-64 overflow-y-auto">
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

        {{-- FORM PARA ASIGNAR NUEVO EMPLEADO --}}
<div>
    <h3 class="text-sm font-semibold text-slate-700 mb-3">Asignar empleado a esta obra</h3>

    @if($empleadosAsignables->isEmpty())
        <p class="text-sm text-slate-500">
            No hay empleados disponibles sin asignación activa.
        </p>
    @else
        <form id="form-asignar-empleado"
              method="POST"
              action="{{ route('obras.empleados.store', $obra) }}">
            @csrf

            <div class="grid md:grid-cols-3 gap-3">
                {{-- Buscador de empleado --}}
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Buscar empleado
                    </label>

                    {{-- Input visible donde el usuario escribe el nombre/apellido --}}
                    <input type="text"
                           id="buscador-empleado"
                           autocomplete="off"
                           placeholder="Escribe apellido o nombre"
                           class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">

                    {{-- Input oculto que realmente se envía en el form --}}
                    <input type="hidden" name="empleado_id" id="empleado_id" value="{{ old('empleado_id') }}">

                    {{-- Contenedor para los resultados --}}
                    <div id="resultados-empleado"
                         class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow text-sm max-h-60 overflow-y-auto hidden">
                        {{-- Aquí se insertan los resultados por JS --}}
                    </div>

                    @error('empleado_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Puesto en la obra --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Puesto en la obra
                    </label>
                    {{-- Rol en la obra (CANÓNICO) --}}
                            <div>
                                <!-- <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Rol en la obra
                                </label> -->

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

                    <!-- <input type="text"
                           name="puesto_en_obra"
                           value="{{ old('puesto_en_obra') }}"
                           class="w-full rounded-xl border-slate-200 text-sm px-3 py-2"> -->
                    @error('puesto_en_obra')
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
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-teal-600 text-white text-sm rounded-xl hover:bg-teal-700">
                    Asignar empleado
                </button>
            </div>
        </form>
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
                        <th class="py-2 px-3 text-left">Maquina</th>
                        <th class="py-2 px-3 text-left">Pilas Hechas</th>
                        <th class="py-2 px-3 text-left">Residente</th>
                        <th class="py-2 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comisiones as $comision)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="py-2 px-3">
                                {{ $comision->fecha?->format('d/m/Y') }}
                            </td>
                            {{-- Máquina usada en la comisión --}}
                            <td class="py-2 px-3">
                                @php
                                    // Tomamos el primer detalle (todos tienen la misma obra_maquina_id)
                                    $detallePrimario = $comision->detalles->first();
                                    $maquina = $detallePrimario?->asignacionMaquina?->maquina;
                                @endphp

                                {{ $maquina ? $maquina->nombre : '—' }}
                            </td>

                            {{-- Total de pilas hechas en esta comisión (suma de cantidades de todos los tipos) --}}
                            <td class="py-2 px-3">
                                {{ (int) ($comision->total_pilas ?? 0) }}
                            </td>
                            <td class="py-2 px-3">
                                @php $res = $comision->residente ?? null; @endphp
                                {{ $res ? $res->Nombre . ' ' . $res->Apellidos : '—' }}
                            </td>
                            <td class="py-2 px-3 text-right text-xs">
                                <a href="{{ route('obras.comisiones.show', [$obra, $comision]) }}"
                                   class="text-sky-600 hover:underline mr-3">
                                    Ver
                                </a>
                                <a href="{{ route('obras.comisiones.print', [$obra, $comision]) }}"target ="_blank"
                                   class="text-slate-600 hover:underline mr-3">
                                    Imprimir
                                </a>
                                <a href="{{ route('obras.comisiones.edit', [$obra, $comision]) }}"
                                   class="text-amber-600 hover:underline mr-3">
                                    Editar
                                </a>
                      
                               
                            {{-- Eliminar --}}
                                    <form action="{{ route('obras.comisiones.destroy', [$obra, $comision]) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Eliminar definitivamente esta comisión?');"
                                        class="inline">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="text-red-600 hover:underline mr-3">
                                            Eliminar
                                        </button>
                                    </form>
                                                                    
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
                                        Activa
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
console.log('Total empleados asignables:', empleados.length);


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
            limpiarResultados();
            return;
        }

        lista.forEach(function (emp) {
            const apellidos = emp.Apellidos ?? '';
            const nombre    = emp.Nombre ?? '';
            const puesto    = emp.Puesto ?? '';

            const nombreCompleto = (apellidos + ' ' + nombre).trim();
            const texto = nombreCompleto + (puesto ? ' (' + puesto + ')' : '');

            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'w-full text-left px-3 py-2 hover:bg-slate-100';
            item.textContent = texto;

            item.addEventListener('click', function () {
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
