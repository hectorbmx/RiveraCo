@php
    $pilaElegida = (string) old('pila_id', $pilas->count() === 1 ? $pilas->first()->id : '');
    $maquinaElegida = (string) old('obra_maquina_id', $maquinaInicialId ?? '');
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Datos generales del trabajo">
    <div class="bg-[#0B265A] px-5 py-4 text-white">
        <h2 class="text-base font-semibold">Datos generales</h2>
        <p class="mt-1 text-sm text-blue-100">Selecciona la pila y la máquina de esta obra.</p>
    </div>
    <div class="p-5 sm:p-6">
        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label for="alta-fecha" class="block text-sm font-medium text-slate-700 mb-1">Fecha <span aria-hidden="true">*</span></label>
                <input id="alta-fecha" type="date" name="fecha" required value="{{ old('fecha', now()->toDateString()) }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="alta-pila" class="block text-sm font-medium text-slate-700 mb-1">Pila de la obra <span aria-hidden="true">*</span></label>
                <select id="alta-pila" name="pila_id" required x-ref="altaPila" @change="cargarPila(true)" @disabled($pilas->isEmpty()) class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Selecciona una pila</option>
                    @foreach($pilas as $pila)
                        <option value="{{ $pila->id }}" @selected($pilaElegida === (string) $pila->id)
                                data-diametro="{{ $pila->diametro_proyecto }}" data-profundidad="{{ $pila->profundidad_proyecto }}">
                            {{ $pila->tipo ?: ('Pila #' . $pila->id) }}{{ $pila->numero_pila ? ' · ' . $pila->numero_pila : '' }}
                        </option>
                    @endforeach
                </select>
                @if($pilas->isEmpty())<p class="mt-1 text-xs text-red-700">La obra no tiene pilas activas. Registra una antes de iniciar el trabajo.</p>@endif
            </div>
            <div>
                <label for="alta-maquina" class="block text-sm font-medium text-slate-700 mb-1">Máquina asignada <span aria-hidden="true">*</span></label>
                <select id="alta-maquina" name="obra_maquina_id" required @disabled($asignacionesMaquinas->isEmpty()) class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Selecciona una máquina</option>
                    @foreach($asignacionesMaquinas as $asignacion)
                        <option value="{{ $asignacion->id }}" @selected($maquinaElegida === (string) $asignacion->id)>
                            {{ trim(($asignacion->maquina->codigo ? $asignacion->maquina->codigo . ' · ' : '') . ($asignacion->maquina->nombre ?: 'Máquina #' . $asignacion->maquina_id)) }}
                        </option>
                    @endforeach
                </select>
                @if($asignacionesMaquinas->isEmpty())<p class="mt-1 text-xs text-red-700">La obra no tiene máquinas con asignación activa disponible.</p>@endif
            </div>
            <div>
                <label for="alta-residente" class="block text-sm font-medium text-slate-700 mb-1">Residente de la obra</label>
                <input id="alta-residente" type="text" readonly value="{{ $residenteAsignado?->empleado?->nombre_completo ?? 'Sin residente activo asignado' }}" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-600">
                @if(! $residenteAsignado)<p class="mt-1 text-xs text-red-700">Asigna un residente activo a la obra antes de iniciar el trabajo.</p>@endif
            </div>
            <div>
                <label for="alta-numero-formato" class="block text-sm font-medium text-slate-700 mb-1">Número de formato</label>
                <input id="alta-numero-formato" type="text" name="numero_formato" maxlength="50" value="{{ old('numero_formato') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Opcional">
            </div>
            <div>
                <label for="alta-cliente" class="block text-sm font-medium text-slate-700 mb-1">Cliente en el formato</label>
                <input id="alta-cliente" type="text" name="cliente_nombre" maxlength="255" value="{{ old('cliente_nombre', $obra->cliente?->nombre_comercial ?? '') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
        <div class="mt-5">
            <label for="alta-observaciones" class="block text-sm font-medium text-slate-700 mb-1">Observaciones generales</label>
            <textarea id="alta-observaciones" name="observaciones" rows="3" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Información general del trabajo…">{{ old('observaciones') }}</textarea>
        </div>
    </div>
</section>
