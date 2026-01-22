<!-- @extends('layouts.admin')

@section('title', 'Configuración de la Empresa')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6"
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
                <h1 class="text-2xl font-semibold text-gray-900">Configuración de la Empresa</h1>
                <p class="text-sm text-gray-600">Parámetros globales que impactan vehículos, maquinaria, costos y comisiones.</p>
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
                                'vehiculos' => ['label' => 'Vehículos', 'desc' => 'Mantenimientos y alertas'],
                                'maquinaria'=> ['label' => 'Maquinaria', 'desc' => 'Servicios por horas y tiempos'],
                                'rrhh'      => ['label' => 'Puestos & Costos', 'desc' => 'Horas y horas extra'],
                                'comisiones'=> ['label' => 'Comisiones', 'desc' => 'Reglas por tipo de trabajo'],
                                'reglas'    => ['label' => 'Reglas', 'desc' => 'Políticas y flujos'],
                                'alertas'   => ['label' => 'Alertas', 'desc' => 'Notificaciones y avisos'],
                            ];
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
                    <label class="block text-sm mb-1">Razón social</label>
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
                    <label class="block text-sm mb-1">Teléfono</label>
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

        {{-- Configuración financiera --}}
        <div class="bg-white rounded shadow p-5 space-y-4">
            <h2 class="font-semibold text-gray-700">Configuración financiera</h2>

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
                Guardar configuración
            </button>
        </div>
    </form>
                </div>

                {{-- ======================
                     VEHICULOS
                ======================= --}}
                <div x-show="tab === 'vehiculos'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Vehículos</h2>
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
                            <label class="block text-sm font-medium text-gray-700">Alerta antes (días)</label>
                            <input type="number" name="alerta_dias" value="10"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-3">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar Vehículos
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
                            <input type="number" name="servicio_horas" value="250"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio cada (meses)</label>
                            <input type="number" name="servicio_meses" value="6"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alerta antes (horas)</label>
                            <input type="number" name="alerta_horas" value="20"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-3">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar Config
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ======================
   Catálogo de Máquinas
====================== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Catálogo de Máquinas</h3>
            <p class="text-sm text-gray-600">Administra el catálogo corporativo (no hay máquinas temporales).</p>
        </div>

        <button
            type="button"
            class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800"
            @click="openMaquinaCreate = true">
            + Nueva máquina
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Código</th>
                    <th class="text-left px-4 py-3">Serie</th>
                    <th class="text-left px-4 py-3">Placas</th>
                    <th class="text-left px-4 py-3">Color</th>
                    <th class="text-left px-4 py-3">Horómetro base</th>
                    <th class="text-right px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($maquinas as $m)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $m->nombre }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->codigo ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->numero_serie ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->placas ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->color ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->horometro_base ?? '—' }}</td>

                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                                    @click="openEditMaquina(@js($m))">
                                    Editar
                                </button>

                                <form method="POST" action="{{ route('empresa_config.maquinas.destroy', $m->id) }}"
                                      onsubmit="return confirm('¿Eliminar máquina?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 rounded-lg border border-red-300 text-red-700 hover:bg-red-50">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            No hay máquinas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL CREAR --}}
<div x-show="openMaquinaCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" @click="openMaquinaCreate = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Nueva máquina</h3>
            <button class="text-gray-500 hover:text-gray-800" @click="openMaquinaCreate = false">✕</button>
        </div>

        <form method="POST" action="{{ route('empresa_config.maquinas.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                <input name="nombre" required class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Código</label>
                <input name="codigo" class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Número de serie</label>
                <input name="numero_serie" class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Placas</label>
                <input name="placas" class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Color</label>
                <input name="color" class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Horómetro base</label>
                <input name="horometro_base" type="number" step="0.01" class="mt-1 w-full rounded-lg border-gray-300">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="activo" value="1" checked class="rounded border-gray-300">
                <span class="text-sm text-gray-700">Activa</span>
            </div>

            <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                <button type="button" class="px-4 py-2 rounded-lg border" @click="openMaquinaCreate = false">Cancelar</button>
                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white">Guardar</button>
            </div>
        </form>
    </div>
</div>
{{-- MODAL EDITAR --}}
<div x-show="openMaquinaEdit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" @click="openMaquinaEdit = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Editar máquina</h3>
            <button class="text-gray-500 hover:text-gray-800" @click="openMaquinaEdit = false">✕</button>
        </div>

        <template x-if="editMaquina">
            <form method="POST"
                  :action="`/empresa-config/maquinas/${editMaquina.id}`"
                  class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PUT')

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input name="nombre" required class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.nombre">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Código</label>
                    <input name="codigo" class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.codigo">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de serie</label>
                    <input name="numero_serie" class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.numero_serie">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Placas</label>
                    <input name="placas" class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.placas">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <input name="color" class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.color">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Horómetro base</label>
                    <input name="horometro_base" type="number" step="0.01"
                           class="mt-1 w-full rounded-lg border-gray-300"
                           x-model="editMaquina.horometro_base">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="activo" value="1" class="rounded border-gray-300"
                           :checked="!!editMaquina.activo">
                    <span class="text-sm text-gray-700">Activa</span>
                </div>

                <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                    <button type="button" class="px-4 py-2 rounded-lg border" @click="openMaquinaEdit = false">Cancelar</button>
                    <button class="px-4 py-2 rounded-lg bg-gray-900 text-white">Guardar cambios</button>
                </div>
            </form>
        </template>
    </div>
</div>


                {{-- ======================
                     RRHH
                ======================= --}}
                <div x-show="tab === 'rrhh'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Puestos & Costos</h2>
                        <p class="text-sm text-gray-600">Costos por hora y reglas de hora extra por puesto.</p>
                    </div>

                    {{-- Placeholder: aquí normalmente pondrías una tabla editable por rol/puesto --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                        Sugerencia: aquí va una tabla “Puesto / Costo hora / Multiplicador extra / Tope”.
                        Si quieres, la armamos con inputs por fila y “Guardar cambios”.
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update') }}"
                          class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="rrhh">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Multiplicador hora extra (default)</label>
                            <input type="number" step="0.01" name="hora_extra_mult" value="1.5"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tope hora extra (horas/semana)</label>
                            <input type="number" name="hora_extra_tope" value="10"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div class="flex items-end justify-end md:col-span-2">
                            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                                Guardar RRHH
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ======================
                     COMISIONES
                ======================= --}}
                <div x-show="tab === 'comisiones'" x-cloak class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Comisiones</h2>
                        <p class="text-sm text-gray-600">Reglas globales por tipo de trabajo.</p>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                        Recomendación: manejar una tabla por “tipo_trabajo” y “modo” (por metro/hora/pieza/%).
                        Si ya tienes catálogo de tipos, lo conectamos aquí.
                    </div>

                    <form method="POST" action="{{ route('empresa_config.update') }}"
                          class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="comisiones">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Comisión default (%)</label>
                            <input type="number" step="0.01" name="comision_default_pct" value="0"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Comisión por metro (default)</label>
                            <input type="number" step="0.01" name="comision_por_metro" value="0"
                                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Comisión por hora (default)</label>
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
                            <span class="text-sm text-gray-800">Órdenes de compra requieren autorización</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="comision_solo_factura_pagada" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-800">Comisión solo si la factura está pagada</span>
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
                            <label class="block text-sm font-medium text-gray-700">Revisión de alertas</label>
                            <select name="alertas_frecuencia" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                                <option value="daily">Diaria</option>
                                <option value="weekly">Semanal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Anticipación (días)</label>
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

    </div>
@endsection -->