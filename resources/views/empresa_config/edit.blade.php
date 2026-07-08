@extends('layouts.admin')

@section('title', 'ConfiguraciÃ³n de la Empresa')

@section('content')
    <div class="max-w-8xl mx-auto px-4 py-6"
         x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || 'general',
            setTab(t){
                this.tab = t;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', t);
                window.history.replaceState({}, '', url);
            }
         }">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">ConfiguraciÃ³n de la Empresa</h1>
                <p class="text-sm text-gray-600">ParÃ¡metros globales que impactan vehÃ­culos, maquinaria, costos y comisiones.</p>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm">
                <div class="font-semibold mb-1">Revisa lo siguiente:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            {{-- Tabs --}}
            <div class="border-b border-gray-100">
                <div class="px-4 sm:px-6">
                    <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                        @php
                            $tabs = [
                                'general'   => ['label' => 'General', 'desc' => 'Datos base del sistema'],
                                
                                'cuentas' => ['label' => 'Cuentas banco', 'desc' => 'Cuentas para pagos y aprovisionamiento'],
                                'vehiculos' => ['label' => 'VehÃ­culos', 'desc' => 'Mantenimientos y alertas'],
                                'maquinaria'=> ['label' => 'Maquinaria', 'desc' => 'Servicios por horas y tiempos'],
                                'rrhh'      => ['label' => 'Puestos', 'desc' => 'Horas y horas extra'],
                                'documentos' => ['label' => 'Documentos','desc'  => 'Documentos requeridos para empleados'],
                                'equipos_computo' => ['label' => 'Equipo de computo', 'desc' => 'Inventario y responsables'],
                                'centros_costo' => ['label' => 'Centros de costo', 'desc' => 'Gastos fuera de obra'],
                                'iva' => ['label' => 'IVA', 'desc' => 'Tipos de IVA utilizables'],
                                'comisiones'=> ['label' => 'Comisiones', 'desc' => 'Reglas por tipo de trabajo'],
                                'reglas'    => ['label' => 'Reglas', 'desc' => 'PolÃ­ticas y flujos'],
                                'alertas'   => ['label' => 'Alertas', 'desc' => 'Notificaciones y avisos'],
                                'areas'   => ['label' => 'Areas', 'desc' => 'Areas de la empresa'],
                                'folios'   => ['label' => 'Folios', 'desc' => 'Consecutivos de obras'],
                                'listas_raya' => ['label' => 'Listas de raya', 'desc' => 'Agrupadores de nomina'],
                            ];
                             if (auth()->check() && auth()->user()->hasAnyRole(['admin','super-admin'])) {
                                $tabs['roles']    = ['label' => 'Roles', 'desc' => 'Perfiles de acceso'];
                                $tabs['permisos'] = ['label' => 'Permisos', 'desc' => 'Acciones del sistema'];
                            }
                        @endphp

                        @foreach ($tabs as $key => $t)
                            <button type="button"
                                    @click="setTab('{{ $key }}')"
                                    class="whitespace-nowrap py-4 text-sm font-medium border-b-2 transition
                                           "
                                    :class="tab === '{{ $key }}'
                                        ? 'border-gray-900 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                {{ $t['label'] }}
                            </button>
                        @endforeach
                    </nav>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-4 sm:p-6">

                {{-- ======================
                     GENERAL
                ======================= --}}
                <div x-show="tab === 'general'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">General</h2>
                        <p class="text-sm text-gray-600">Valores por defecto para el sistema.</p>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos generales --}}
        <div class="bg-white rounded shadow p-5 space-y-4">
            <h2 class="font-semibold text-gray-700">Datos generales</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">RazÃ³n social</label>
                    <input type="text" name="razon_social" class="w-full border rounded px-3 py-2"
                           value="{{ old('razon_social', $config->razon_social) }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Nombre comercial</label>
                    <input type="text" name="nombre_comercial" class="w-full border rounded px-3 py-2"
                           value="{{ old('nombre_comercial', $config->nombre_comercial) }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">RFC</label>
                    <input type="text" name="rfc" class="w-full border rounded px-3 py-2"
                           value="{{ old('rfc', $config->rfc) }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">TelÃ©fono</label>
                    <input type="text" name="telefono" class="w-full border rounded px-3 py-2"
                           value="{{ old('telefono', $config->telefono) }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2"
                           value="{{ old('email', $config->email) }}">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Domicilio fiscal</label>
                    <textarea name="domicilio_fiscal" rows="2"
                              class="w-full border rounded px-3 py-2">{{ old('domicilio_fiscal', $config->domicilio_fiscal) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ConfiguraciÃ³n financiera --}}
        <div class="bg-white rounded shadow p-5 space-y-4">
            <h2 class="font-semibold text-gray-700">ConfiguraciÃ³n financiera</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm mb-1">Moneda base</label>
                    <select name="moneda_base" class="w-full border rounded px-3 py-2">
                        @foreach(['MXN','USD','EUR'] as $m)
                            <option value="{{ $m }}" @selected($config->moneda_base === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1">IVA por defecto (%)</label>
                    <input type="number" step="0.01" name="iva_por_defecto"
                           class="w-full border rounded px-3 py-2"
                           value="{{ old('iva_por_defecto', $config->iva_por_defecto) }}">
                </div>

                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" name="activa" value="1"
                           {{ $config->activa ? 'checked' : '' }}>
                    <span class="text-sm">Empresa activa</span>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">
                Guardar configuraciÃ³n
            </button>
        </div>
    </form>
                </div>

    {{-- ======================    CUENTAS BANCO ======================= --}}
<div x-show="tab === 'cuentas'" x-cloak class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Cuentas bancarias de la empresa
            </h2>
            <p class="text-sm text-gray-600">
                Cuentas disponibles para aprovisionar y ejecutar pagos.
            </p>
        </div>
    </div>

    {{-- FORM NUEVA CUENTA --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
        <h3 class="text-sm font-bold text-gray-800 mb-4">
            Nueva cuenta bancaria
        </h3>

        <form method="POST" action="{{ route('empresa_config.cuentas.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Nombre interno
                </label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Cuenta principal BBVA"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Banco
                </label>
                <input
                    type="text"
                    name="banco"
                    required
                    placeholder="BBVA, Banorte, Santander..."
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Titular
                </label>
                <input
                    type="text"
                    name="titular"
                    placeholder="Rivera Construcciones"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Moneda
                </label>
                <select
                    name="moneda"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
                    <option value="MXN">MXN</option>
                    <option value="USD">USD</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    NÃºmero de cuenta
                </label>
                <input
                    type="text"
                    name="numero_cuenta"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    CLABE
                </label>
                <input
                    type="text"
                    name="clabe"
                    maxlength="30"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                >
            </div>

            <div class="flex items-center gap-3 pt-7">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="activa"
                        value="1"
                        checked
                        class="rounded border-gray-300"
                    >
                    Activa
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="principal"
                        value="1"
                        class="rounded border-gray-300"
                    >
                    Principal
                </label>
            </div>

            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700">
                    Observaciones
                </label>
                <textarea
                    name="observaciones"
                    rows="2"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20"
                ></textarea>
            </div>

            <div class="flex justify-end md:col-span-4">
                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                    Guardar cuenta
                </button>
            </div>
        </form>
    </div>

    {{-- LISTADO --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-sm font-bold text-gray-800">
                Cuentas registradas
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 border text-left">Nombre</th>
                        <th class="p-3 border text-left">Banco</th>
                        <th class="p-3 border text-left">Titular</th>
                        <th class="p-3 border text-left">Cuenta</th>
                        <th class="p-3 border text-left">CLABE</th>
                        <th class="p-3 border text-center">Moneda</th>
                        <th class="p-3 border text-center">Estado</th>
                        <th class="p-3 border text-center">Principal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cuentasBancoEmpresa ?? [] as $cuenta)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 border font-semibold text-gray-800">
                                {{ $cuenta->nombre ?? '-' }}
                            </td>

                            <td class="p-3 border">
                                {{ $cuenta->banco }}
                            </td>

                            <td class="p-3 border">
                                {{ $cuenta->titular ?? '-' }}
                            </td>

                            <td class="p-3 border">
                                {{ $cuenta->numero_cuenta ?? '-' }}
                            </td>

                            <td class="p-3 border">
                                {{ $cuenta->clabe ?? '-' }}
                            </td>

                            <td class="p-3 border text-center">
                                {{ $cuenta->moneda }}
                            </td>

                            <td class="p-3 border text-center">
                                @if($cuenta->activa)
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        Activa
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 border text-center">
                                @if($cuenta->principal)
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        Principal
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">
                                No hay cuentas bancarias registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
{{-- ======================
     TERMINA CUENTAS BANCO
======================= --}}
                                

                {{-- ======================
                     VEHICULOS
                ======================= --}}
                <div x-show="tab === 'vehiculos'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">VehÃ­culos</h2>
                        <p class="text-sm text-gray-600">Frecuencias de servicio y alertas globales.</p>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update') }}"  class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="vehiculos">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio cada (km)</label>
                            <input type="number" name="servicio_km" value="5000"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio cada (meses)</label>
                            <input type="number" name="servicio_meses" value="6"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alerta antes (dÃ­as)</label>
                            <input type="number" name="alerta_dias" value="10"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-3">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar VehÃ­culos
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ======================
                     MAQUINARIA
                ======================= --}}
                <div x-show="tab === 'maquinaria'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Maquinaria</h2>
                        <p class="text-sm text-gray-600">Servicios por horas de uso y por tiempo.</p>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update')}}"
                          class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="maquinaria">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio cada (horas)</label>
                            <input type="number" name="maquinaria_servicio_horas" value="{{ old('maquinaria_servicio_horas', $config->maquinaria_servicio_horas ?? 250) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio cada (meses)</label>
                            <input type="number" name="maquinaria_servicio_meses" value="{{ old('maquinaria_servicio_meses', $config->maquinaria_servicio_meses ?? 6) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alerta antes (horas)</label>
                            <input type="number" name="maquinaria_alerta_horas" value="{{ old('maquinaria_alerta_horas', $config->maquinaria_alerta_horas ?? 20) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-3">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar Maquinaria
                            </button>
                        </div>
                    </form>
                    <div class="text-xs text-gray-500">
                        {{-- ======================
   CatÃ¡logo de MÃ¡quinas
====================== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900">CatÃ¡logo de MÃ¡quinas</h3>
            <p class="text-sm text-gray-600">Lista corporativa (no se permiten mÃ¡quinas temporales).</p>
        </div>

        {{-- Por ahora solo el botÃ³n (en el siguiente paso lo hacemos funcional) --}}
        <a href="{{ route('empresa_config.maquinas.create') }}"
           class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
            + Nueva mÃ¡quina
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">CÃ³digo</th>
                    <th class="text-left px-4 py-3">Serie</th>
                    <th class="text-left px-4 py-3">AÃ±o</th>
                    <th class="text-left px-4 py-3">Placas</th>
                    <th class="text-left px-4 py-3">Color</th>
                    <th class="text-left px-4 py-3">HorÃ³metro base</th>
                    <th class="text-left px-4 py-3">Servicio preventivo</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="text-left px-4 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($maquinas as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $m->nombre }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->codigo ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->numero_serie ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->modelo ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->placas ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->color ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->horometro_base ?? 'â€”' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            @include('maquinas.partials._preventivo_badge', ['preventivo' => $preventivosMaquinaria[$m->id] ?? null])
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            @if(isset($m->estado))
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700">
                                    {{ $m->estado }}
                                </span>
                            @else
                                <span class="text-gray-400">â€”</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                          <a href="{{ route('empresa_config.maquinas.edit', $m->id) }}"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                                    border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                            title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 3.487a2.1 2.1 0 113.0 3.0L7.5 18.862 3 21l2.138-4.5L16.862 3.487z"/>
                                </svg>
                            </a>

                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            No hay mÃ¡quinas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    MÃ¡quinas cargadas: {{ isset($maquinas) ? $maquinas->count() : 'NO VAR' }}
</div>
                </div>

              {{-- ======================
                            RRHH
                    ======================= --}}
<div x-show="tab === 'rrhh'" x-cloak class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Puestos</h2>
            <p class="text-sm text-gray-600">
                CatÃ¡logo de puestos disponibles en la empresa.
            </p>
        </div>

        <a href="{{ route('empresa_config.catalogo_roles.create') }}"
           class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
            Nuevo puesto
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">ROL_KEY</th>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Comisionable</th>
                    
                    <th class="text-right px-4 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($catalogoRoles as $rol)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">
                            {{ $rol->rol_key }}
                        </td>

                        <td class="px-4 py-3 text-gray-900">
                            {{ $rol->nombre }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-lg
                                {{ $rol->comisionable ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $rol->comisionable ? 'SÃ­' : 'No' }}
                            </span>
                        </td>

                      

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('empresa_config.catalogo_roles.edit', $rol->id) }}"
                               class="text-xs text-gray-700 hover:underline mr-3">
                                Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">
                            No hay puestos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
{{-- ====================== DOCUMENTOS EMPLEADO ======================= --}}
<div x-show="tab === 'documentos'" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">
                Documentos de empleados
            </h2>

            <p class="text-sm text-slate-500 mt-1">
                Configura los documentos requeridos para expedientes de empleados.
            </p>
        </div>

        {{-- FORM NUEVO --}}
        <div class="p-6 border-b border-slate-200 bg-slate-50">
            <form
                method="POST"
                action="{{ route('empresa_config.documentos.store') }}"
                class="grid grid-cols-1 md:grid-cols-6 gap-4"
            >
                @csrf

                {{-- NOMBRE --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        required
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500"
                        placeholder="Ej. INE"
                    >
                </div>

                {{-- DESCRIPCION --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        DescripciÃ³n
                    </label>

                    <input
                        type="text"
                        name="descripcion"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500"
                        placeholder="Opcional"
                    >
                </div>

                {{-- CHECKS --}}
                <div class="flex flex-col justify-center gap-2 pt-6">
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="obligatorio"
                            value="1"
                            class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                        >

                        <span class="text-sm text-slate-700">
                            Obligatorio
                        </span>
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="requiere_vencimiento"
                            value="1"
                            class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                        >

                        <span class="text-sm text-slate-700">
                            Vencimiento
                        </span>
                    </label>
                </div>

                {{-- BOTON --}}
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 transition"
                    >
                        Agregar
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Documento
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            ConfiguraciÃ³n
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse($documentosEmpleadoTipos as $documento)

                        <tr class="hover:bg-slate-50 transition">

                            {{-- NOMBRE --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">

                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $documento->nombre }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $documento->codigo }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- CONFIG --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">

                                    @if($documento->obligatorio)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                            Obligatorio
                                        </span>
                                    @endif

                                    @if($documento->requiere_vencimiento)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            Requiere vencimiento
                                        </span>
                                    @endif

                                    @if($documento->activo)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            Inactivo
                                        </span>
                                    @endif

                                </div>
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">

                                    {{-- TOGGLE --}}
                                    <form
                                        method="POST"
                                        action="{{ route('empresa_config.documentos.toggle-activo', $documento) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100"
                                        >
                                            {{ $documento->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>

                                    {{-- DELETE --}}
                                    <form
                                        method="POST"
                                        action="{{ route('empresa_config.documentos.destroy', $documento) }}"
                                        onsubmit="return confirm('Â¿Eliminar documento?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100"
                                        >
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-slate-500">
                                No hay documentos configurados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>

@include('empresa_config.partials._equipos_computo')
@include('empresa_config.partials._centros_costo')
@include('empresa_config.partials._tipos_iva')

                {{-- ======================
     COMISIONES
======================= --}}
<div x-show="tab === 'comisiones'" x-cloak class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Comisiones</h2>
            <p class="text-sm text-gray-600">Tarifarios y reglas vigentes para cÃ¡lculo de comisiones.</p>
        </div>

        <a href="{{ route('empresa_config.comisiones.tarifarios.create') }}"
           class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
            Nuevo tarifario
        </a>
    </div>

    {{-- Tabla tarifarios --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="text-left px-4 py-3">Vigencia</th>
                    <th class="text-left px-4 py-3">Publicado</th>
                    <th class="text-right px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tarifarios as $t)
                    @php
                        $isVigente = $tarifarioVigente && $tarifarioVigente->id === $t->id;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $t->nombre }}
                            @if($isVigente)
                                <span class="ml-2 text-xs px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700">
                                    Vigente
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $t->estado }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ optional($t->vigente_desde)->format('Y-m-d') ?? 'â€”' }}
                            <span class="text-gray-400">â†’</span>
                            {{ optional($t->vigente_hasta)->format('Y-m-d') ?? 'â€”' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ optional($t->published_at)->format('Y-m-d') ?? 'â€”' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('empresa_config.comisiones.tarifarios.show', $t->id) }}"
                               class="text-xs text-gray-700 hover:underline">
                                Ver detalles
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                            No hay tarifarios aÃºn.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Detalles del vigente --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Detalles del tarifario vigente</div>
                <div class="text-xs text-gray-500">
                    Estos importes son los que se usarÃ¡n al generar comisiones.
                </div>
            </div>

            @if($tarifarioVigente)
                <a href="{{ route('empresa_config.comisiones.detalles.create', $tarifarioVigente->id) }}"
                   class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                    Agregar Nuevo Concepto
                </a>
            @endif
        </div>

        @if(!$tarifarioVigente)
            <div class="text-sm text-gray-600">
                No hay tarifario vigente. Crea uno para comenzar.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-3 py-2">Rol</th>
                            <!-- <th class="text-left px-3 py-2">Trabajo</th> -->
                            <th class="text-left px-3 py-2">Concepto</th>
                            <!-- <th class="text-left px-3 py-2">Trabajo</th> -->
                            <th class="text-left px-3 py-2">UOM</th>
                            <th class="text-right px-3 py-2">Tarifa</th>
                            <th class="text-center px-3 py-2">Activo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($tarifarioDetalles as $d)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    {{ $d->rol?->nombre ?? ('Rol #' . $d->rol_id) }}
                                </td>
                                <!-- <td class="px-3 py-2">
                                    {{ $d->trabajo_id }}
                                </td> -->
                                <!-- <td class="px-3 py-2">{{ $d->concepto }}</td> -->
                                <td class="px-3 py-2">{{ $d->variable_origen }}</td>
                                <td class="px-3 py-2">  {{ $d->uom?->nombre ?? 'â€”' }}</td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ number_format((float)$d->tarifa, 2) }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="text-xs px-2 py-1 rounded-lg {{ $d->activo ? 'bg-sky-50 text-sky-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $d->activo ? 'SÃ­' : 'No' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-gray-400">
                                    AÃºn no hay detalles en el tarifario vigente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Tus settings globales (los que ya tenÃ­as) --}}
    <form method="POST" action="{{ route('empresa_config.update') }}"
          class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="section" value="comisiones">

        <div>
            <label class="block text-sm font-medium text-gray-700">ComisiÃ³n default (%)</label>
            <input type="number" step="0.01" name="comision_default_pct" value="0"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ComisiÃ³n por metro (default)</label>
            <input type="number" step="0.01" name="comision_por_metro" value="0"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ComisiÃ³n por hora (default)</label>
            <input type="number" step="0.01" name="comision_por_hora" value="0"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
        </div>

        <div class="flex items-end justify-end md:col-span-3">
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                Guardar Comisiones
            </button>
        </div>
    </form>
</div>

                {{-- ======================
                     REGLAS
                ======================= --}}
                <div x-show="tab === 'reglas'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Reglas de Negocio</h2>
                        <p class="text-sm text-gray-600">Activa o desactiva comportamientos del sistema.</p>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update')}}"
                          class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="reglas">

                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="oc_requiere_autorizacion" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-800">Ã“rdenes de compra requieren autorizaciÃ³n</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="comision_solo_factura_pagada" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-800">ComisiÃ³n solo si la factura estÃ¡ pagada</span>
                        </label>

                        <div class="flex items-end justify-end">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar Reglas
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ======================
                     ALERTAS
                ======================= --}}
                <div x-show="tab === 'alertas'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Alertas</h2>
                        <p class="text-sm text-gray-600">Notificaciones del sistema (internas o futuras integraciones).</p>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update') }}"
                          class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="alertas">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">RevisiÃ³n de alertas</label>
                            <select name="alertas_frecuencia" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                                <option value="daily">Diaria</option>
                                <option value="weekly">Semanal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">AnticipaciÃ³n (dÃ­as)</label>
                            <input type="number" name="alertas_anticipacion_dias" value="7"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-2">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar Alertas
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
         {{-- ======================
     FOLIOS DE OBRA
======================= --}}
<div x-show="tab === 'folios'" x-cloak class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Folios de obra</h2>
            <p class="text-sm text-gray-600">Controla el siguiente consecutivo para Pilas y Pozos.</p>
        </div>

        <form method="GET" action="{{ route('empresa_config.edit') }}" class="flex items-end gap-2">
            <input type="hidden" name="tab" value="folios">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">AÃ±o</label>
                <input type="number"
                       name="folio_anio"
                       min="2020"
                       max="2100"
                       value="{{ $anioFoliosObra }}"
                       class="w-28 rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
            </div>
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                Consultar
            </button>
        </form>
    </div>

    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="px-4 py-3 border-b bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-900">Tipos de obra y Ã¡reas</h3>
            <p class="text-xs text-slate-500">Define quÃ© Ã¡rea corresponde a cada tipo de obra.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">Tipo</th>
                        <th class="text-left font-semibold px-4 py-3">Prefijo</th>
                        <th class="text-left font-semibold px-4 py-3">Ãrea asignada</th>
                        <th class="text-left font-semibold px-4 py-3">Activo</th>
                        <th class="text-right font-semibold px-4 py-3">AcciÃ³n</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($tiposObraConfiguraciones as $tipo)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $tipo->label }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">
                                {{ $tipo->prefijo }}
                            </td>
                            <td class="px-4 py-3">
                                <form id="tipo-obra-{{ $tipo->id }}"
                                      method="POST"
                                      action="{{ route('empresa_config.tipos-obra.update', $tipo) }}"
                                      class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="area_id"
                                            class="min-w-56 rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                                        <option value="">Sin Ã¡rea</option>
                                        @foreach($areas as $area)
                                            <option value="{{ $area->id }}" @selected(old('area_id', $tipo->area_id) == $area->id)>
                                                {{ $area->codigo ? $area->codigo . ' - ' : '' }}{{ $area->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                            </td>
                            <td class="px-4 py-3">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $tipo->activo))
                                               class="rounded border-slate-300">
                                        Activo
                                    </label>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="submit"
                                        form="tipo-obra-{{ $tipo->id }}"
                                        class="px-3 py-1.5 rounded-lg text-xs bg-slate-900 text-white hover:bg-slate-800">
                                    Guardar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                No hay tipos de obra configurados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">Tipo</th>
                        <th class="text-left font-semibold px-4 py-3">Prefijo</th>
                        <th class="text-left font-semibold px-4 py-3">Ãšltimo usado</th>
                        <th class="text-left font-semibold px-4 py-3">Siguiente folio</th>
                        <th class="text-left font-semibold px-4 py-3">MÃ­nimo permitido</th>
                        <th class="text-right font-semibold px-4 py-3">AcciÃ³n</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($foliosObra as $folio)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ ucfirst(strtolower($folio->tipo_obra)) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">
                            {{ $folio->prefijo }}-{{ $folio->anio }}
                        </td>
                        <td class="px-4 py-3">
                            <form id="folio-obra-{{ $folio->id }}"
                                  method="POST"
                                  action="{{ route('empresa_config.folios-obra.update', $folio) }}"
                                  class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="number"
                                       name="ultimo_consecutivo"
                                       min="{{ $folio->minimo_consecutivo }}"
                                       max="999999"
                                       value="{{ old('ultimo_consecutivo', $folio->ultimo_consecutivo) }}"
                                       class="w-28 rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            </form>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-900">
                            {{ $folio->siguiente_folio }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $folio->minimo_consecutivo }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="submit"
                                    form="folio-obra-{{ $folio->id }}"
                                    class="px-3 py-1.5 rounded-lg text-xs bg-slate-900 text-white hover:bg-slate-800">
                                Guardar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                            No hay folios configurados para este aÃ±o.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        El valor editable es el Ãºltimo consecutivo usado. El siguiente folio se genera sumando uno.
    </div>
</div>
{{-- ======================
     LISTAS DE RAYA
======================= --}}
<div x-show="tab === 'listas_raya'" x-cloak class="space-y-6" x-data="listasRayaTab()">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Listas de raya</h2>
            <p class="text-sm text-gray-600">Agrupadores de nomina. Las listas de obra se generan automaticamente con las obras vivas.</p>
        </div>
        <button type="button" @click="openCreate()" class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">+ Agregar lista</button>
    </div>

    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">Nombre</th>
                        <th class="text-left font-semibold px-4 py-3">Tipo</th>
                        <th class="text-left font-semibold px-4 py-3">Origen</th>
                        <th class="text-left font-semibold px-4 py-3">Estatus</th>
                        <th class="text-right font-semibold px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($listasRaya as $lista)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><div class="font-medium text-slate-900">{{ $lista->nombre }}</div>@if($lista->es_automatica)<div class="text-[11px] text-slate-500">Automatica</div>@endif</td>
                        <td class="px-4 py-3 text-slate-700">{{ \App\Models\NominaListaRaya::TIPOS[$lista->tipo] ?? ucfirst($lista->tipo) }}</td>
                        <td class="px-4 py-3 text-slate-600">@if($lista->obra){{ $lista->obra->clave_obra }} - {{ $lista->obra->nombre }}@elseif($lista->area)Area: {{ $lista->area->nombre }}@elseif($lista->almacen)Almacen: {{ $lista->almacen->nombre }}@else-@endif</td>
                        <td class="px-4 py-3">@if($lista->activo)<span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activa</span>@else<span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-200 text-slate-700">Inactiva</span>@endif</td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2">
                            <button type="button" @click="openEdit(@js($lista))" @disabled($lista->es_automatica) class="px-3 py-1.5 rounded-lg text-xs {{ $lista->es_automatica ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' }}">Editar</button>
                            <form method="POST" action="{{ route('empresa-config.listas-raya.toggle', $lista->id) }}" onsubmit="return confirm('Cambiar estatus de la lista de raya?')">@csrf @method('PATCH')<button type="submit" @disabled($lista->es_automatica) class="px-3 py-1.5 rounded-lg text-xs {{ $lista->es_automatica ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : ($lista->activo ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-green-100 text-green-800 hover:bg-green-200') }}">{{ $lista->activo ? 'Desactivar' : 'Activar' }}</button></form>
                            <form method="POST" action="{{ route('empresa-config.listas-raya.destroy', $lista->id) }}" onsubmit="return confirm('Eliminar esta lista de raya?')">@csrf @method('DELETE')<button type="submit" @disabled($lista->es_automatica) class="px-3 py-1.5 rounded-lg text-xs {{ $lista->es_automatica ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">Eliminar</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No hay listas de raya registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border">
            <div class="p-5 border-b flex items-center justify-between"><div><div class="text-base font-semibold text-slate-900" x-text="isEdit ? 'Editar lista de raya' : 'Agregar lista de raya'"></div><div class="text-xs text-slate-500">Configura el agrupador principal de nomina.</div></div><button type="button" @click="close()" class="p-2 rounded-lg hover:bg-slate-100">x</button></div>
            <form :action="formAction" method="POST" class="p-5 space-y-4">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PATCH"></template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-xs text-slate-600 mb-1">Nombre</label><input type="text" name="nombre" x-model="form.nombre" class="w-full rounded-xl border-slate-300"></div>
                    <div><label class="block text-xs text-slate-600 mb-1">Tipo</label><select name="tipo" x-model="form.tipo" class="w-full rounded-xl border-slate-300">@foreach(\App\Models\NominaListaRaya::TIPOS as $tipo => $label)<option value="{{ $tipo }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="block text-xs text-slate-600 mb-1">Area relacionada</label><select name="area_id" x-model="form.area_id" class="w-full rounded-xl border-slate-300"><option value="">Sin area</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->nombre }}</option>@endforeach</select></div>
                    <div><label class="block text-xs text-slate-600 mb-1">Almacen relacionado</label><select name="almacen_id" x-model="form.almacen_id" class="w-full rounded-xl border-slate-300"><option value="">Sin almacen</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->id }}">{{ $almacen->nombre }}</option>@endforeach</select></div>
                    <div><label class="block text-xs text-slate-600 mb-1">Orden</label><input type="number" name="orden" min="0" x-model="form.orden" class="w-full rounded-xl border-slate-300"></div>
                </div>
                <div class="flex items-center justify-between"><label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="activo" value="1" x-model="form.activo" class="rounded border-slate-300">Activa</label><div class="flex gap-2"><button type="button" @click="close()" class="px-4 py-2 rounded-xl text-sm bg-slate-100 text-slate-800">Cancelar</button><button type="submit" class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white">Guardar</button></div></div>
            </form>
        </div>
    </div>
</div>

<script>
function listasRayaTab() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: @js(route('empresa-config.listas-raya.store')),
        form: { id: null, nombre: '', tipo: 'operativa', area_id: '', almacen_id: '', orden: 100, activo: true },
        openCreate() { this.isEdit = false; this.formAction = @js(route('empresa-config.listas-raya.store')); this.form = { id: null, nombre: '', tipo: 'operativa', area_id: '', almacen_id: '', orden: 100, activo: true }; this.modalOpen = true; },
        openEdit(lista) { if (lista.es_automatica) return; this.isEdit = true; this.formAction = @js(url('/empresa-config/listas-raya')) + '/' + lista.id; this.form = { id: lista.id, nombre: lista.nombre ?? '', tipo: lista.tipo ?? 'operativa', area_id: lista.area_id ? String(lista.area_id) : '', almacen_id: lista.almacen_id ? String(lista.almacen_id) : '', orden: lista.orden ?? 100, activo: !!lista.activo }; this.modalOpen = true; },
        close() { this.modalOpen = false; }
    }
}
</script>

         {{-- ======================
     AREAS
======================= --}}
<div x-show="tab === 'areas'" x-cloak class="space-y-6"
     x-data="areasTab()">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Ãreas</h2>
            <p class="text-sm text-gray-600">Ãreas del sistema</p>
        </div>

        <button type="button"
                @click="openCreate()"
                class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
            + Agregar Ã¡rea
        </button>
    </div>

    {{-- Tabla --}}
    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">CÃ³digo</th>
                        <th class="text-left font-semibold px-4 py-3">Nombre</th>
                        <th class="text-left font-semibold px-4 py-3">DescripciÃ³n</th>
                        <th class="text-left font-semibold px-4 py-3">Estatus</th>
                        <th class="text-right font-semibold px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($areas as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $a->codigo }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $a->nombre }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $a->descripcion ?: 'â€”' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($a->activo)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activa</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-200 text-slate-700">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="openEdit(@js($a))"
                                        class="px-3 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-800 hover:bg-slate-200">
                                    Editar
                                </button>

                                <form method="POST"
                                      action="{{ route('empresa-config.areas.toggle', $a->id) }}"
                                      onsubmit="return confirm('Â¿Cambiar estatus del Ã¡rea?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs {{ $a->activo ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                        {{ $a->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('empresa-config.areas.destroy', $a->id) }}"
                                      onsubmit="return confirm('Â¿Eliminar esta Ã¡rea?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs bg-red-100 text-red-700 hover:bg-red-200">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                            No hay Ã¡reas registradas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl border">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <div class="text-base font-semibold text-slate-900" x-text="isEdit ? 'Editar Ã¡rea' : 'Agregar Ã¡rea'"></div>
                    <div class="text-xs text-slate-500">Configura cÃ³digo, nombre, descripciÃ³n y estatus.</div>
                </div>
                <button type="button" @click="close()"
                        class="p-2 rounded-lg hover:bg-slate-100">
                    âœ•
                </button>
            </div>

            <form :action="formAction" method="POST" class="p-5 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">CÃ³digo</label>
                        <input type="text" name="codigo" x-model="form.codigo"
                               class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                               placeholder="EJ: ADM, OBR, RH">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Nombre</label>
                        <input type="text" name="nombre" x-model="form.nombre"
                               class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                               placeholder="Ej: AdministraciÃ³n">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-slate-600 mb-1">DescripciÃ³n</label>
                    <textarea name="descripcion" x-model="form.descripcion" rows="3"
                              class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                              placeholder="Opcional"></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="activo" value="1" x-model="form.activo"
                               class="rounded border-slate-300">
                        Activa
                    </label>

                    <div class="flex gap-2">
                        <button type="button" @click="close()"
                                class="px-4 py-2 rounded-xl text-sm bg-slate-100 text-slate-800 hover:bg-slate-200">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
                            Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function areasTab() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: @js(route('empresa-config.areas.store')),
        form: { id: null, codigo: '', nombre: '', descripcion: '', activo: true },

        openCreate() {
            this.isEdit = false;
            this.formAction = @js(route('empresa-config.areas.store'));
            this.form = { id: null, codigo: '', nombre: '', descripcion: '', activo: true };
            this.modalOpen = true;
        },

        openEdit(area) {
            this.isEdit = true;
            this.formAction = @js(url('/empresa-config/areas')) + '/' + area.id;
            this.form = {
                id: area.id,
                codigo: area.codigo ?? '',
                nombre: area.nombre ?? '',
                descripcion: area.descripcion ?? '',
                activo: !!area.activo,
            };
            this.modalOpen = true;
        },

        close() {
            this.modalOpen = false;
        }
    }
}
</script>

{{--  TERMINA :AREAS --}}

        
        
{{-- TAB: ROLES --}}
<div x-show="tab === 'roles'" x-cloak class="p-4 sm:p-6">
    @if(session('ok'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-900 text-sm">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-900 text-sm">{{ session('err') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Crear Rol --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="font-semibold text-sm mb-3">Crear rol</h3>

            <form method="POST" action="{{ route('empresa_config.roles.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="block text-xs text-gray-600 mb-1">Nombre</label>
                    <input name="name" value="{{ old('name') }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           placeholder="ej: admin, residente, captura">
                    @error('name')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <input type="hidden" name="guard_name" value="web">

                <button class="w-full px-3 py-2 rounded-lg bg-gray-900 text-white text-sm">
                    Guardar
                </button>
            </form>
        </div>

        {{-- SelecciÃ³n de Rol + Renombrar/Eliminar --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:col-span-2">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h3 class="font-semibold text-sm">Roles</h3>

                {{-- Selector por query: tab=roles&role=ID --}}
                <form method="GET" action="{{ route('empresa_config.edit') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="roles">
                    <select name="role" class="border rounded-lg px-3 py-2 text-sm"
                            onchange="this.form.submit()">
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ optional($selectedRole)->id === $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if(!$selectedRole)
                <div class="text-sm text-gray-500">
                    No hay roles disponibles. Crea uno para asignarle permisos.
                </div>
            @else
                {{-- Renombrar Rol --}}
                <div class="border rounded-xl p-3 mb-4">
                    <div class="text-xs text-gray-500 mb-2">Editar rol seleccionado</div>
                    <form method="POST" action="{{ route('empresa_config.roles.update', $selectedRole) }}" class="flex gap-2">
                        @csrf
                        @method('PUT')

                        <input name="name" value="{{ old('name', $selectedRole->name) }}"
                               class="flex-1 border rounded-lg px-3 py-2 text-sm">

                        <button class="px-3 py-2 rounded-lg border text-sm">
                            Renombrar
                        </button>
                    </form>

                    {{-- Eliminar Rol --}}
                    <form method="POST" action="{{ route('empresa_config.roles.destroy', $selectedRole) }}"
                          class="mt-2"
                          onsubmit="return confirm('Â¿Eliminar rol? (solo si no estÃ¡ asignado a usuarios)')">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm text-red-600 hover:underline">
                            Eliminar rol
                        </button>
                    </form>
                </div>

                {{-- Asignar Permisos al Rol --}}
                <div class="border rounded-xl p-3">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <div class="font-semibold text-sm">Permisos del rol</div>
                            <div class="text-xs text-gray-500">
                                Marca/desmarca y guarda. (Guard: {{ $selectedRole->guard_name }})
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('empresa_config.roles.permissions.sync', $selectedRole) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($permissions as $p)
                                <label class="flex items-center gap-2 p-2 border rounded-lg">
                                    <input type="checkbox" name="permissions[]" value="{{ $p->id }}"
                                           {{ in_array($p->id, $selectedRolePermissionIds ?? []) ? 'checked' : '' }}>
                                    <span class="text-sm">{{ $p->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm">
                                Guardar permisos
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
{{-- TAB: PERMISOS --}}
<div x-show="tab === 'permisos'" x-cloak class="p-4 sm:p-6">
    @if(session('ok'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-900 text-sm">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-900 text-sm">{{ session('err') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Crear Permiso (modulo.access) --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="font-semibold text-sm mb-3">Crear permiso (mÃ³dulo)</h3>

            <form method="POST" action="{{ route('empresa_config.permissions.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="block text-xs text-gray-600 mb-1">MÃ³dulo</label>
                    <input name="module" value="{{ old('module') }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           placeholder="ej: clientes, obras, ordenes_compra">
                    @error('module')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Creamos name final como module.access desde frontend --}}
                <input type="hidden" name="guard_name" value="web">
                <input type="hidden" name="name" id="perm_name_final">

                <button type="submit"
                        onclick="
                          const m = (this.form.module.value || '').trim().toLowerCase();
                          document.getElementById('perm_name_final').value = m ? (m + '.access') : '';
                        "
                        class="w-full px-3 py-2 rounded-lg bg-gray-900 text-white text-sm">
                    Guardar
                </button>
            </form>

            {{-- Generar base (opcional) --}}
            @if(Route::has('empresa_config.permissions.seed_modules'))
                <form method="POST" action="{{ route('empresa_config.permissions.seed_modules') }}" class="mt-3"
                      onsubmit="return confirm('Â¿Generar permisos base de mÃ³dulos? (si ya existen, no duplica)')">
                    @csrf
                    <button class="w-full px-3 py-2 rounded-lg border text-sm">
                        Generar permisos base
                    </button>
                </form>
            @endif

            <div class="mt-3 text-xs text-gray-500">
                Se crea como <span class="font-mono">modulo.access</span> (guard web).
            </div>
        </div>

        {{-- Listado de permisos --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Permisos existentes</h3>
                <div class="text-xs text-gray-500">Guard: web</div>
            </div>

            @php
                // Filtramos solo *.access y dashboard.view (por el esquema acordado)
                $modulePerms = ($permissions ?? collect())
                    ->filter(fn($p) => str_ends_with($p->name, '.access') || $p->name === 'dashboard.view')
                    ->values();
            @endphp

            @if($modulePerms->isEmpty())
                <div class="text-sm text-gray-500">
                    No hay permisos de mÃ³dulo todavÃ­a. Crea uno o usa â€œGenerar permisos baseâ€.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($modulePerms as $p)
                        <div class="flex items-center justify-between gap-3 p-2 border rounded-lg">
                            <div class="text-sm">
                                <div class="font-medium">{{ $p->name }}</div>
                                <div class="text-xs text-gray-500">id: {{ $p->id }}</div>
                            </div>

                            <form method="POST" action="{{ route('empresa_config.permissions.destroy', $p) }}"
                                  onsubmit="return confirm('Â¿Eliminar permiso? (solo si no estÃ¡ asignado)')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm text-red-600 hover:underline">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

    </div>
    
    
@endsection





