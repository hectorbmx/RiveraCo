@extends('layouts.admin')

@section('content')
    <div class="max-w-[95%] mx-auto py-4">
        @php
            $esMaquina = !empty($mantenimiento->maquina_id);
            $servicioCerrado = in_array($mantenimiento->estatus, ['completado', 'cancelado'], true);
            $serviciosPreventivos = $esMaquina ? ($serviciosPreventivosMaquinaria ?? collect()) : ($serviciosPreventivosVehiculos ?? collect());
            $servicioPreventivoId = old('servicio_preventivo_tipo_id', $mantenimiento->servicio_preventivo_tipo_id);
            $servicioPreventivoSeleccionado = $serviciosPreventivos->firstWhere('id', $servicioPreventivoId);
            $categoriaSugerida = $servicioPreventivoSeleccionado
                ? $servicioPreventivoSeleccionado->nombre . ' · ' . number_format($servicioPreventivoSeleccionado->intervalo_valor) . ' ' . ($servicioPreventivoSeleccionado->unidad === 'horas' ? 'horas' : 'km')
                : '';
            $categoriaActual = old('categoria_mantenimiento', $mantenimiento->categoria_mantenimiento);
            if ($mantenimiento->tipo === 'programado' && $categoriaSugerida && in_array(trim((string) $categoriaActual), ['', 'Servicio', 'Mantenimiento'], true)) {
                $categoriaActual = $categoriaSugerida;
            }
            $hayFaltantes = $materialesAbastecimiento->contains(fn ($material) => (float) $material['faltante'] > 0);
            $hayDisponibles = $materialesAbastecimiento->contains(fn ($material) => (float) $material['surtible'] > 0);
            $hayPorComprar = $materialesAbastecimiento->contains(fn ($material) => (float) $material['por_comprar'] > 0.0005);
            $ordenGenerada = !empty($mantenimiento->orden_servicio_folio)
                && $mantenimiento->estatus !== 'borrador';
            $solicitudAlmacenActiva = $abastecimientos->first(fn ($solicitud) => $solicitud->tipo === 'almacen' && !in_array($solicitud->estado, ['surtida', 'cancelada'], true));
            $solicitudCompraActiva = $abastecimientos->first(fn ($solicitud) => $solicitud->tipo === 'compra' && !in_array($solicitud->estado, ['surtida', 'cancelada'], true));
            $cancelUrl = $esMaquina
                ? route('maquinas.show', ['maquina' => $mantenimiento->maquina_id, 'tab' => 'servicios'])
                : route('mantenimiento.mantenimientos.index');
            $cancelUrl = $returnTo ?? $cancelUrl;
        @endphp

        <!-- Encabezado Compacto -->
        <div class="flex items-center justify-between mb-4 bg-white px-5 py-3 rounded-xl shadow-sm border border-slate-100">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-600 to-[#0B265A] flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-slate-900">{{ $servicioCerrado ? 'Consultar mantenimiento' : 'Editar Mantenimiento' }}</h1>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">#{{ $mantenimiento->id }}</span>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        @if($esMaquina)
                            <span class="text-blue-600 font-semibold">Máquina:</span> {{ $mantenimiento->maquina->nombre ?? ('Máquina #' . $mantenimiento->maquina_id) }}
                        @else
                            <span class="text-blue-600 font-semibold">Vehículo:</span> {{ $mantenimiento->vehiculo->marca ?? '' }} {{ $mantenimiento->vehiculo->modelo ?? '' }}
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ $cancelUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-lg transition border border-slate-200">
                &larr; Volver
            </a>
        </div>

        @if($servicioCerrado)
            <div role="status" class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-[#0B265A]">
                <strong>Servicio {{ $mantenimiento->estatus === 'completado' ? 'completado' : 'cancelado' }}.</strong> Los datos se muestran en modo consulta.
            </div>
            <style>
                .campos-servicio-cerrado :is(input:not([type="hidden"]), select, textarea) { background-color: #f1f5f9; color: #64748b; border-color: #cbd5e1; cursor: not-allowed; opacity: 1; }
            </style>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl shadow-sm">
                <span class="font-bold">Por favor corrige los siguientes errores:</span>
                <ul class="list-disc list-inside mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
            <div class="md:col-span-4 -mx-4 -mt-4 rounded-t-2xl bg-[#0B265A] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white">Contexto de la orden de servicio</div>
            <div><div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $ordenServicioDatos['es_maquina'] ? 'Máquina' : 'Vehículo' }}</div><div class="text-sm font-semibold text-slate-800">{{ $ordenServicioDatos['equipo'] ?: '—' }}</div></div>
            <div><div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Código / placas</div><div class="text-sm font-semibold text-slate-800">{{ $ordenServicioDatos['codigo'] ?: '—' }}</div></div>
            <div><div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ubicación</div><div class="text-sm font-semibold text-slate-800">{{ $ordenServicioDatos['ubicacion'] ?: '—' }}</div></div>
            <div><div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $ordenServicioDatos['lectura_label'] }}</div><div class="text-sm font-semibold text-slate-800">{{ $ordenServicioDatos['lectura_actual'] !== null ? number_format((float) $ordenServicioDatos['lectura_actual'], 2) : '—' }}</div></div>
            <div class="md:col-span-4 border-t border-slate-100 pt-2 text-xs text-slate-600"><strong>Último servicio equivalente:</strong> {{ $ordenServicioDatos['ultimo_servicio']?->fecha_fin?->format('d/m/Y H:i') ?? $ordenServicioDatos['ultimo_servicio']?->fecha_programada?->format('d/m/Y H:i') ?? 'Sin registro' }}</div>
        </div>

        <!-- Layout principal a 3 columnas para ajuste de pantalla completa -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- Formulario principal (50% del ancho) -->
            <div class="lg:col-span-6 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                <form method="POST" action="{{ route('mantenimiento.mantenimientos.update', $mantenimiento) }}" id="main-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @if($returnTo)
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <fieldset @disabled($servicioCerrado) class="min-w-0 border-0 p-0 m-0 {{ $servicioCerrado ? 'campos-servicio-cerrado' : '' }}" aria-label="Datos del mantenimiento">

                    <div class="mb-4 overflow-hidden rounded-xl border border-slate-200">
                        <div class="bg-[#0B265A] px-3 py-2 text-xs font-bold uppercase tracking-wider text-white">Datos del servicio</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        @if($esMaquina)
                            <input type="hidden" name="maquina_id" value="{{ old('maquina_id', $mantenimiento->maquina_id) }}">
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Máquina Asignada</label>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 flex items-center justify-between">
                                    <span>{{ $mantenimiento->maquina->nombre ?? ('Máquina #' . $mantenimiento->maquina_id) }}</span>
                                    <span class="text-[10px] px-2 py-0.5 bg-white rounded border border-slate-200 text-slate-400">Bloqueado</span>
                                </div>
                            </div>
                        @else
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                                <select name="vehiculo_id" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3" required>
                                    @foreach($vehiculos as $vehiculo)
                                        <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id', $mantenimiento->vehiculo_id) == $vehiculo->id)>
                                            {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->placas }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                            <select name="tipo" id="tipo-mantenimiento" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                                <option value="programado" @selected(old('tipo', $mantenimiento->tipo) === 'programado')>Programado</option>
                                <option value="emergencia" @selected(old('tipo', $mantenimiento->tipo) === 'emergencia')>Emergencia</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                            <select name="estatus" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                                <option value="borrador" @selected(old('estatus', $mantenimiento->estatus) === 'borrador')>Borrador</option>
                                <option value="orden_generada" @selected(old('estatus', $mantenimiento->estatus) === 'orden_generada')>Orden de servicio generada</option>
                                <option value="pendiente" @selected(old('estatus', $mantenimiento->estatus) === 'pendiente')>Pendiente</option>
                                <option value="en_proceso" @selected(old('estatus', $mantenimiento->estatus) === 'en_proceso')>En proceso</option>
                                <option value="completado" @selected(old('estatus', $mantenimiento->estatus) === 'completado')>Completado</option>
                                <option value="cancelado" @selected(old('estatus', $mantenimiento->estatus) === 'cancelado')>Cancelado</option>
                            </select>
                        </div>

                        <div class="md:col-span-2 overflow-hidden rounded-xl border border-slate-200">
                            <div class="bg-[#0B265A] px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-white">Configuración del servicio</div>
                            <div class="grid grid-cols-1 gap-3 p-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Tipo de servicio preventivo</label>
                            <select name="servicio_preventivo_tipo_id" id="servicio_preventivo_tipo_id" @required(old('tipo', $mantenimiento->tipo) === 'programado') class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                                <option value="">Sin tipo específico</option>
                                @foreach($serviciosPreventivos as $servicioPreventivo)
                                    <option value="{{ $servicioPreventivo->id }}"
                                            data-categoria="{{ $servicioPreventivo->nombre . ' · ' . number_format($servicioPreventivo->intervalo_valor) . ' ' . ($servicioPreventivo->unidad === 'horas' ? 'horas' : 'km') }}"
                                            @selected($servicioPreventivoId == $servicioPreventivo->id)>
                                        {{ $servicioPreventivo->nombre }} - cada {{ number_format($servicioPreventivo->intervalo_valor) }} {{ $servicioPreventivo->unidad === 'horas' ? 'h' : 'km' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Categoría</label>
                            <input type="text" name="categoria_mantenimiento" id="categoria_mantenimiento" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3" value="{{ $categoriaActual }}" placeholder="Ej. Cambio de aceite">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Fecha del servicio</label>
                            <input type="datetime-local" name="fecha_programada" value="{{ old('fecha_programada', optional($mantenimiento->fecha_programada)->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Inicio de ejecución</label>
                            <input type="datetime-local" name="fecha_inicio" value="{{ old('fecha_inicio', optional($mantenimiento->fecha_inicio)->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Fin de ejecución</label>
                            <input type="datetime-local" name="fecha_fin" value="{{ old('fecha_fin', optional($mantenimiento->fecha_fin)->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                            <p class="mt-1 text-[10px] text-slate-500">Se calcula el tiempo trabajado al completar el servicio.</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Mecánico asignado</label>
                            <select name="mecanico_id" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                                <option value="">Sin asignar</option>
                                @foreach($mecanicos as $mecanico)
                                    <option value="{{ $mecanico->id_Empleado }}" @selected(old('mecanico_id', $mantenimiento->mecanico_id) == $mecanico->id_Empleado)>
                                        {{ trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')) ?: ('Empleado '.$mecanico->id_Empleado) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($esMaquina)
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Horómetro actual</label>
                                <input type="number" step="0.1" name="horometro" value="{{ old('horometro', $mantenimiento->horometro) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                            </div>
                        @else
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Km actuales</label>
                                <input type="number" name="km_actuales" value="{{ old('km_actuales', $mantenimiento->km_actuales) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 py-2 px-3">
                            </div>
                        @endif

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Descripción</label>
                            <textarea name="descripcion" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs font-medium focus:border-blue-600 focus:ring-blue-600 p-2.5" placeholder="Detalles adicionales...">{{ old('descripcion', $mantenimiento->descripcion) }}</textarea>
                        </div>

                        @if($mantenimiento->tipo === 'emergencia')
                            <div class="md:col-span-2 overflow-hidden rounded-xl border border-amber-200">
                                <div class="bg-amber-50 px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-amber-900">Detalle manual de emergencia</div>
                                <div class="overflow-x-auto p-3">
                                    <table class="w-full text-xs">
                                        <thead class="text-left text-[10px] uppercase tracking-wider text-slate-500">
                                            <tr><th class="pb-2">Tipo</th><th class="pb-2">Concepto</th><th class="pb-2">Cantidad</th><th class="pb-2">Costo unitario</th></tr>
                                        </thead>
                                        <tbody id="detalles-emergencia">
                                            @foreach(old('detalles', $detalles->toArray()) as $indice => $detalle)
                                                <tr class="border-t border-slate-100">
                                                    <td class="py-2 pr-2"><select name="detalles[{{ $indice }}][tipo]" class="w-full rounded-lg border-slate-200 py-1.5"><option value="refaccion" @selected(($detalle['tipo'] ?? '') === 'refaccion')>Refacción</option><option value="consumible" @selected(($detalle['tipo'] ?? '') === 'consumible')>Consumible</option><option value="mano_obra" @selected(($detalle['tipo'] ?? '') === 'mano_obra')>Mano de obra</option><option value="servicio_externo" @selected(($detalle['tipo'] ?? '') === 'servicio_externo')>Servicio externo</option></select></td>
                                                    <td class="py-2 pr-2"><input required name="detalles[{{ $indice }}][concepto]" value="{{ $detalle['concepto'] ?? '' }}" class="w-full rounded-lg border-slate-200 py-1.5"></td>
                                                    <td class="py-2 pr-2"><input required type="number" min="0.01" step="0.01" name="detalles[{{ $indice }}][cantidad]" value="{{ $detalle['cantidad'] ?? 1 }}" class="w-full rounded-lg border-slate-200 py-1.5"></td>
                                                    <td class="py-2"><input type="number" min="0" step="0.01" name="detalles[{{ $indice }}][costo_unitario]" value="{{ $detalle['costo_unitario'] ?? 0 }}" class="w-full rounded-lg border-slate-200 py-1.5"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <p class="mt-2 text-[10px] text-amber-800">Los servicios de emergencia usan esta captura manual y no cargan una plantilla preventiva.</p>
                                </div>
                            </div>
                        @endif

                        <div class="md:col-span-2 overflow-hidden rounded-xl border border-slate-200">
                            <div class="bg-[#0B265A] px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-white">Evidencia del servicio</div>
                            <div class="p-3">
                                <p class="text-[11px] text-slate-500">{{ $servicioCerrado ? 'Fotografías registradas del servicio. La captura está bloqueada después del cierre.' : 'Agrega hasta 8 fotografías del trabajo realizado. Cada imagen se optimizará a WebP antes de almacenarse y puede llevar su propia nota.' }}</p>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Fotografías</label>
                                        <input type="file" name="fotos[]" id="fotos-input" accept="image/jpeg,image/png,image/webp" multiple data-existing-count="{{ $mantenimiento->fotos->count() }}" class="w-full rounded-xl border-slate-200 bg-slate-50/50 text-xs py-2 px-3">
                                        <p id="fotos-ayuda" class="mt-1 text-[10px] text-slate-500">{{ $mantenimiento->fotos->count() }}/8 fotografías registradas.</p>
                                        <p id="fotos-error" class="mt-1 hidden text-xs text-red-600"></p>
                                        @error('fotos')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('fotos.*')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Vista previa y nota por fotografía</label>
                                        <div id="fotos-preview" class="grid grid-cols-2 gap-2"></div>
                                    </div>
                                </div>
                                @if($mantenimiento->fotos->isNotEmpty())
                                    <div class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-4">
                                        @foreach($mantenimiento->fotos as $foto)
                                            <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($foto->ruta) }}" alt="Evidencia del servicio" class="h-20 w-full object-cover">
                                                @if($foto->descripcion)
                                                    <div class="px-2 py-1 text-[10px] text-slate-600">{{ $foto->descripcion }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                    </fieldset>
                </form>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <a href="{{ $cancelUrl }}" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition">{{ $servicioCerrado ? 'Volver' : 'Cancelar' }}</a>
                    @if(($mantenimiento->estatus ?? null) === 'borrador')
                        <form method="POST" action="{{ route('mantenimiento.mantenimientos.generar-orden-servicio', $mantenimiento) }}">
                            @csrf
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition">
                                Crear orden de servicio
                            </button>
                        </form>
                    @elseif(($mantenimiento->estatus ?? null) === 'completado')
                        <a href="{{ route('mantenimiento.mantenimientos.print', $mantenimiento) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl bg-[#0B265A] text-white shadow-md hover:opacity-90 transition">
                            Imprimir reporte
                        </a>
                    @elseif($mantenimiento->estatus === 'cancelado')
                        @if($mantenimiento->orden_servicio_folio)
                            <a href="{{ route('mantenimiento.mantenimientos.orden-servicio.print', $mantenimiento) }}" target="_blank" rel="noopener" class="px-4 py-2 text-xs font-semibold rounded-xl border border-blue-200 text-blue-700 hover:bg-blue-50">Imprimir orden</a>
                        @endif
                    @else
                        @if($mantenimiento->orden_servicio_folio)
                            <a href="{{ route('mantenimiento.mantenimientos.orden-servicio.print', $mantenimiento) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl border border-blue-200 text-blue-700 hover:bg-blue-50 transition">
                                Imprimir orden
                            </a>
                        @endif
                        <button type="submit" form="main-form" id="servicio-ejecutado-button" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition">Servicio ejecutado</button>
                    @endif
                </div>
            </div>

            <!-- Columna Derecha: Materiales y Solicitudes (50% del ancho) -->
            <div class="lg:col-span-6 flex flex-col gap-4">
                @if($materialesOrden->isNotEmpty())
                        <form method="POST" action="{{ route('mantenimiento.mantenimientos.materiales-orden.update', $mantenimiento) }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                            @csrf
                            <div class="-mx-4 -mt-4 mb-3 flex items-center justify-between bg-[#0B265A] px-3 py-2.5">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Materiales de la orden</h3>
                                <span class="text-[10px] text-blue-100">Ajuste exclusivo de este servicio</span>
                            </div>
                            <div class="space-y-2">
                                @foreach($materialesOrden as $materialOrden)
                                    <div class="rounded-xl border border-slate-200 p-2.5 text-xs {{ $materialOrden->incluido ? 'bg-slate-50' : 'bg-amber-50' }}">
                                        <label class="flex items-start gap-2">
                                            <input type="checkbox" name="incluidos[]" value="{{ $materialOrden->id }}" @checked($materialOrden->incluido) @disabled($servicioCerrado || $ordenGenerada === false) class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                            <span class="flex-1">
                                                <span class="block font-semibold text-slate-800">{{ $materialOrden->producto_nombre_snapshot }}</span>
                                                <span class="block text-[11px] text-slate-500">{{ $materialOrden->producto_sku_snapshot ?: 'Sin SKU' }} · {{ number_format((float) $materialOrden->cantidad_requerida, 2) }} {{ $materialOrden->unidad ?: '' }}</span>
                                            </span>
                                        </label>
                                        <input type="text" name="motivos[{{ $materialOrden->id }}]" value="{{ $materialOrden->motivo_exclusion }}" placeholder="Motivo obligatorio si no se aplica en este servicio" maxlength="255" @disabled($servicioCerrado || $ordenGenerada === false) class="mt-2 w-full rounded-lg border-slate-200 px-2 py-1.5 text-[11px] disabled:bg-slate-100">
                                    </div>
                                @endforeach
                            </div>
                            @if(!$servicioCerrado && $ordenGenerada && $abastecimientos->isEmpty())
                                <button type="submit" class="mt-3 rounded-xl bg-[#0B265A] px-3 py-2 text-xs font-bold text-white hover:bg-[#12367d]">Guardar materiales</button>
                            @else
                                <p class="mt-3 text-[11px] font-semibold text-slate-500">{{ $servicioCerrado ? 'El servicio está cerrado. Los materiales son de solo consulta.' : 'Los materiales quedan bloqueados después de generar una solicitud de abastecimiento.' }}</p>
                            @endif
                        </form>
                @endif

                @if($materialesAbastecimiento->isNotEmpty())
                    <!-- Materiales compactos -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col max-h-[300px]">
                        <div class="-mx-4 -mt-4 mb-3 flex items-center justify-between bg-[#0B265A] px-3 py-2.5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white">Materiales del Servicio</h3>
                            <span class="rounded-full bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 text-[10px] font-bold">
                                {{ $materialesAbastecimiento->where('faltante', 0)->count() }}/{{ $materialesAbastecimiento->count() }} completos
                            </span>
                        </div>
                        <div class="space-y-2 overflow-y-auto pr-1 flex-1">
                            @foreach($materialesAbastecimiento as $material)
                                <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 text-xs">
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-slate-800">{{ $material['producto'] }}</span>
                                            @if($material['estado'] === 'surtido')
                                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded">Material surtido</span>
                                        @elseif($material['estado'] === 'disponible')
                                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded">Disponible</span>
                                        @elseif($material['estado'] === 'parcial')
                                            <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded">Parcial</span>
                                        @else
                                            <span class="px-1.5 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold rounded">Sin stock</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap justify-between gap-2 mt-1 text-[11px] text-slate-500">
                                        <span>Req: <strong class="text-slate-700">{{ number_format($material['requerido'], 2) }}</strong></span>
                                        <span>Surtido: <strong class="text-emerald-700">{{ number_format($material['surtido'], 2) }}</strong></span>
                                        <span>Surtible: <strong class="text-slate-700">{{ number_format($material['surtible'], 2) }}</strong></span>
                                        <span>Faltante: <strong class="{{ $material['faltante'] > 0 ? 'text-red-600' : 'text-slate-700' }}">{{ number_format($material['faltante'], 2) }}</strong></span>
                                        <span>Por comprar: <strong class="{{ $material['por_comprar'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($material['por_comprar'], 2) }}</strong></span>
                                    </div>
                                    @if(!empty($material['folios_salida']))
                                        <div class="mt-1 text-[10px] text-slate-500">
                                            Salidas:
                                            @foreach($material['folios_salida'] as $folio)
                                                <span class="font-semibold text-[#0B265A]">{{ $folio }}</span>@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Abastecimientos y Botones de Acción -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <h3 class="-mx-4 -mt-4 mb-3 bg-[#0B265A] px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-white">Abastecimiento y Folios</h3>
                        
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <form method="POST" action="{{ route('mantenimiento.mantenimientos.abastecimientos.store', $mantenimiento) }}">
                                @csrf
                                <input type="hidden" name="tipo" value="almacen">
                                <button type="submit" @disabled($servicioCerrado || !$ordenGenerada || $solicitudAlmacenActiva || !$hayFaltantes) class="w-full rounded-xl bg-[#0B265A] p-2.5 text-left text-white hover:bg-[#12367d] disabled:opacity-50 transition">
                                    <span class="block text-xs font-bold">Almacén</span>
                                    <span class="block text-[10px] text-blue-200">Surtido parcial/total</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('mantenimiento.mantenimientos.abastecimientos.store', $mantenimiento) }}">
                                @csrf
                                <input type="hidden" name="tipo" value="compra">
                                <button type="submit" @disabled($servicioCerrado || !$ordenGenerada || $solicitudCompraActiva || !$hayPorComprar) class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-left text-slate-800 hover:bg-slate-50 disabled:opacity-50 transition">
                                    <span class="block text-xs font-bold">Compras</span>
                                    <span class="block text-[10px] text-slate-500">Adquisición externa</span>
                                </button>
                            </form>
                        </div>

                        @if($abastecimientos->isNotEmpty())
                            <div class="space-y-1.5 max-h-[140px] overflow-y-auto pr-1">
                                @foreach($abastecimientos as $solicitud)
                                    <div class="p-2 rounded-lg border border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-bold text-slate-800">#{{ $solicitud->folio }}</span>
                                            <span class="text-[10px] text-slate-500 ml-1">({{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }})</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px]">
                                            <a target="_blank"
                                               href="{{ route('mantenimiento.mantenimientos.abastecimientos.print', $solicitud) }}"
                                               title="Imprimir solicitud"
                                               aria-label="Imprimir solicitud"
                                               class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-200 hover:text-[#0B265A]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!$ordenGenerada)
                            <p class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-[11px] font-semibold text-blue-700">
                                Genera la orden de servicio para habilitar las solicitudes de material.
                            </p>
                        @elseif(!$hayFaltantes)
                            <p class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700">
                                Materiales surtidos. No se pueden generar nuevas solicitudes para este servicio.
                            </p>
                        @endif
                    </div>
                @elseif($mantenimiento->tipo === 'programado')
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <div class="font-bold">Sin plantilla de materiales</div>
                        @if(!$mantenimiento->servicio_preventivo_tipo_id)
                            <p class="mt-1 text-xs text-amber-800">
                                Este servicio programado todavía no tiene un tipo de servicio preventivo asignado.
                                Selecciona uno en la sección «Configuración del servicio» para cargar sus materiales y habilitar la solicitud de abastecimiento.
                            </p>
                        @else
                            <p class="mt-1 text-xs text-amber-800">
                                El tipo de servicio está asignado, pero no tiene materiales configurados para esta máquina.
                            </p>
                        @endif
                    </div>
                @elseif($mantenimiento->tipo === 'emergencia')
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
                        <div class="font-bold">Servicio de emergencia</div>
                        <p class="mt-1 text-xs text-slate-500">
                            Este tipo de servicio usa captura manual y no requiere una plantilla preventiva de materiales.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mainForm = document.getElementById('main-form');
            const servicioEjecutadoButton = document.getElementById('servicio-ejecutado-button');
            const estatusInput = mainForm?.querySelector('[name="estatus"]');

            servicioEjecutadoButton?.addEventListener('click', function () {
                if (estatusInput) {
                    estatusInput.value = 'completado';
                }
            });

            const fotosInput = document.getElementById('fotos-input');
            const fotosPreview = document.getElementById('fotos-preview');
            const fotosAyuda = document.getElementById('fotos-ayuda');
            const fotosError = document.getElementById('fotos-error');

            if (fotosInput && fotosPreview) {
                const maxFotos = 8;
                const existentes = Number(fotosInput.dataset.existingCount || 0);

                fotosInput.addEventListener('change', function () {
                    const archivos = Array.from(fotosInput.files || []);
                    const disponibles = Math.max(0, maxFotos - existentes);

                    fotosError.classList.add('hidden');
                    fotosError.textContent = '';

                    if (archivos.length > disponibles) {
                        fotosInput.value = '';
                        fotosPreview.innerHTML = '';
                        fotosError.textContent = disponibles === 0
                            ? 'Este servicio ya tiene el máximo de 8 fotografías.'
                            : `Solo puedes seleccionar ${disponibles} fotografía(s) más.`;
                        fotosError.classList.remove('hidden');
                        return;
                    }

                    fotosPreview.innerHTML = '';
                    archivos.forEach((archivo, indice) => {
                        const tarjeta = document.createElement('div');
                        tarjeta.className = 'overflow-hidden rounded-lg border border-slate-200 bg-white';
                        tarjeta.innerHTML = `
                            <img alt="Vista previa de evidencia" class="fotos-preview-image h-24 w-full object-cover bg-slate-100">
                            <div class="p-2">
                                <p class="truncate text-[10px] font-semibold text-slate-600" title="${archivo.name}">${archivo.name}</p>
                                <input type="text" name="foto_descripciones[]" maxlength="200" placeholder="Nota de esta foto..." class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-[10px] px-2 py-1.5 focus:border-blue-600 focus:ring-blue-600">
                            </div>
                        `;
                        const previewImage = tarjeta.querySelector('.fotos-preview-image');
                        fotosPreview.appendChild(tarjeta);

                        const reader = new FileReader();
                        reader.onload = function (evento) {
                            previewImage.src = evento.target.result;
                        };
                        reader.readAsDataURL(archivo);
                    });

                    fotosAyuda.textContent = `${existentes + archivos.length}/8 fotografías registradas después de guardar.`;
                });
            }

            const tipoServicio = document.getElementById('servicio_preventivo_tipo_id');
            const tipoMantenimiento = document.getElementById('tipo-mantenimiento');
            const categoria = document.getElementById('categoria_mantenimiento');

            const actualizarRequeridoTipoServicio = () => {
                if (tipoServicio && tipoMantenimiento) {
                    tipoServicio.required = tipoMantenimiento.value === 'programado';
                }
            };

            actualizarRequeridoTipoServicio();
            tipoMantenimiento?.addEventListener('change', actualizarRequeridoTipoServicio);

            if (!tipoServicio || !categoria) return;

            let ultimaCategoriaAutomatica = @json($categoriaSugerida);

            tipoServicio.addEventListener('change', function () {
                const opcion = tipoServicio.options[tipoServicio.selectedIndex];
                const nuevaCategoria = opcion?.dataset.categoria || '';
                const categoriaActual = categoria.value.trim();

                if (!categoriaActual || categoriaActual === ultimaCategoriaAutomatica || ['Servicio', 'Mantenimiento'].includes(categoriaActual)) {
                    categoria.value = nuevaCategoria;
                }

                ultimaCategoriaAutomatica = nuevaCategoria;
            });
        });
    </script>
@endsection
