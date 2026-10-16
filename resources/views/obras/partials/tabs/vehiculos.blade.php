{{-- TAB: VEHICULOS --}}
@if($tab === 'vehiculos')
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Vehiculos asignados a la obra</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Vehiculos de empleados activos en esta obra y registros de kilometraje/gasolina capturados desde la app movil.
                </p>
            </div>
            <div class="text-sm text-slate-500">
                {{ $vehiculoKmLogs->count() }} lecturas encontradas
            </div>
        </div>


        @can('obras.vehiculos.combustible.create.access')
            <div id="combustible-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/50 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="combustible-modal-title">
                <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 id="combustible-modal-title" class="text-lg font-semibold text-slate-900">Agregar registro de kilometraje y gasolina</h3>
                            <p class="mt-1 text-sm text-slate-500">El vehículo ya está definido por la tarjeta seleccionada.</p>
                        </div>
                        <button type="button" data-close-combustible-modal aria-label="Cerrar" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800">&times;</button>
                    </div>

                    <form id="captura-combustible-form" method="POST" action="" enctype="multipart/form-data" class="space-y-4 px-6 py-5">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <span class="block text-sm font-medium text-slate-700">Vehículo asignado</span>
                                <div id="combustible-vehiculo-label" class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">-</div>
                                <p class="mt-1 text-xs text-slate-500">La obra y la asignación se toman del vehículo seleccionado.</p>
                            </div>
                            <div>
                                <label for="combustible_fecha" class="block text-sm font-medium text-slate-700">Fecha y hora</label>
                                <input id="combustible_fecha" name="fecha" type="datetime-local" value="{{ now()->format('Y-m-d\\TH:i') }}" required
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="combustible_km" class="block text-sm font-medium text-slate-700">Lectura actual (km)</label>
                                <input id="combustible_km" name="km" type="number" min="0" step="1" required
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Lectura actual">
                                <p id="combustible-km-hint" class="mt-1 text-xs text-slate-500"></p>
                                <p id="combustible-km-alert" class="mt-1 hidden rounded-md px-2 py-1 text-xs font-medium" role="alert"></p>
                            </div>
                            <div>
                                <label for="combustible_monto" class="block text-sm font-medium text-slate-700">Monto gasolina</label>
                                <input id="combustible_monto" name="monto_gasolina" type="number" min="0.01" step="0.01" required
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Captura el monto">
                            </div>
                            <div>
                                <label for="combustible_foto" class="block text-sm font-medium text-slate-700">Foto del odómetro</label>
                                <input id="combustible_foto" name="foto" type="file" accept="image/jpeg,image/png" required
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="combustible_ticket" class="block text-sm font-medium text-slate-700">Ticket de gasolina</label>
                                <input id="combustible_ticket" name="foto_ticket_gasolina" type="file" accept="image/jpeg,image/png"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                            </div>
                            <div>
                                <label for="combustible_notas" class="block text-sm font-medium text-slate-700">Notas</label>
                                <input id="combustible_notas" name="notas" type="text" maxlength="500"
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Observaciones de la carga">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" data-close-combustible-modal class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                            <button id="combustible-submit" type="submit" disabled
                                    class="inline-flex items-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white opacity-50 cursor-not-allowed">
                                Guardar captura
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                (() => {
                    const modal = document.getElementById('combustible-modal');
                    const form = document.getElementById('captura-combustible-form');
                    const submit = document.getElementById('combustible-submit');
                    const kmInput = document.getElementById('combustible_km');
                    const vehicleLabel = document.getElementById('combustible-vehiculo-label');
                    const kmHint = document.getElementById('combustible-km-hint');
                    const kmAlert = document.getElementById('combustible-km-alert');

                    if (!modal || !form || !submit || !kmInput) return;

                    const closeModal = () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    };

                    const validateKm = () => {
                        const currentKm = Number(kmInput.dataset.minimum || 0);
                        const capturedKm = Number(kmInput.value);
                        const invalid = Number.isFinite(capturedKm) && capturedKm <= currentKm;

                        kmAlert.classList.toggle('hidden', !invalid);
                        kmAlert.classList.toggle('bg-red-50', invalid);
                        kmAlert.classList.toggle('text-red-700', invalid);
                        kmAlert.textContent = invalid
                            ? `La lectura debe ser mayor a ${currentKm.toLocaleString('es-MX')} km.`
                            : '';

                        const canSubmit = Boolean(form.action) && !invalid && kmInput.value !== '';
                        submit.disabled = !canSubmit;
                        submit.classList.toggle('opacity-50', !canSubmit);
                        submit.classList.toggle('cursor-not-allowed', !canSubmit);
                        return !invalid;
                    };

                    const openModal = (button) => {
                        form.action = button.dataset.action || '';
                        vehicleLabel.textContent = button.dataset.vehicle || '-';
                        const currentKm = button.dataset.currentKm || '';
                        kmInput.value = currentKm;
                        kmInput.dataset.minimum = currentKm || '0';
                        kmInput.min = currentKm ? String(Number(currentKm) + 1) : '0';
                        kmHint.textContent = currentKm
                            ? `Máximo registrado para el vehículo: ${Number(currentKm).toLocaleString('es-MX')} km. Captura una lectura mayor.`
                            : 'No hay lectura previa; captura el kilometraje actual.';
                        kmAlert.classList.add('hidden');
                        submit.disabled = true;
                        submit.classList.add('opacity-50', 'cursor-not-allowed');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        kmInput.focus();
                    };

                    // Las tarjetas se renderizan despues de este bloque; la delegacion evita perder sus botones.
                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-open-combustible-modal]');
                        if (button) openModal(button);
                    });

                    modal.querySelectorAll('[data-close-combustible-modal]').forEach((button) => {
                        button.addEventListener('click', closeModal);
                    });

                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) closeModal();
                    });

                    kmInput.addEventListener('blur', validateKm);
                    kmInput.addEventListener('input', validateKm);
                    form.addEventListener('submit', (event) => {
                        if (!validateKm()) event.preventDefault();
                    });
                })();
            </script>
        @endcan

        @can('obras.vehiculos.combustible.edit.access')
            <div id="editar-combustible-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/50 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="editar-combustible-title">
                <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 id="editar-combustible-title" class="text-lg font-semibold text-slate-900">Editar registro de combustible</h3>
                            <p id="editar-combustible-vehiculo" class="mt-1 text-sm text-slate-500">-</p>
                        </div>
                        <button type="button" data-close-editar-combustible aria-label="Cerrar" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800">&times;</button>
                    </div>
                    <form id="editar-combustible-form" method="POST" action="" enctype="multipart/form-data" class="space-y-4 px-6 py-5">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="editar_combustible_fecha" class="block text-sm font-medium text-slate-700">Fecha y hora</label>
                                <input id="editar_combustible_fecha" name="fecha" type="datetime-local" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="editar_combustible_km" class="block text-sm font-medium text-slate-700">Kilometraje</label>
                                <input id="editar_combustible_km" name="km" type="number" min="0" step="1" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="editar_combustible_monto" class="block text-sm font-medium text-slate-700">Monto gasolina</label>
                                <input id="editar_combustible_monto" name="monto_gasolina" type="number" min="0.01" step="0.01" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="editar_combustible_notas" class="block text-sm font-medium text-slate-700">Notas</label>
                                <input id="editar_combustible_notas" name="notas" type="text" maxlength="500" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="editar_combustible_foto" class="block text-sm font-medium text-slate-700">Reemplazar odómetro</label>
                                <input id="editar_combustible_foto" name="foto" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                            </div>
                            <div>
                                <label for="editar_combustible_ticket" class="block text-sm font-medium text-slate-700">Reemplazar ticket</label>
                                <input id="editar_combustible_ticket" name="foto_ticket_gasolina" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" data-close-editar-combustible class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                            <button type="submit" class="inline-flex items-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#091e49]">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
                (() => {
                    const modal = document.getElementById('editar-combustible-modal');
                    const form = document.getElementById('editar-combustible-form');
                    if (!modal || !form) return;
                    const closeModal = () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    };
                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-open-editar-combustible]');
                        if (!button) return;
                        form.action = button.dataset.action || '';
                        document.getElementById('editar-combustible-vehiculo').textContent = button.dataset.vehicle || '-';
                        document.getElementById('editar_combustible_fecha').value = button.dataset.fecha || '';
                        document.getElementById('editar_combustible_km').value = button.dataset.km || '';
                        document.getElementById('editar_combustible_monto').value = button.dataset.monto || '';
                        document.getElementById('editar_combustible_notas').value = button.dataset.notas || '';
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    });
                    modal.querySelectorAll('[data-close-editar-combustible]').forEach((button) => {
                        button.addEventListener('click', closeModal);
                    });
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) closeModal();
                    });
                })();
            </script>
        @endcan
        @if($vehiculosObra->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                Todavia no hay vehiculos ligados a empleados activos de esta obra ni registros moviles de vehiculo.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($vehiculoKmResumen as $resumen)
                    @php
                        $asig = $resumen->asignacion;
                        $vehiculo = $asig->vehiculo;
                        $maxKmVehiculo = $vehiculoKmLogs
                            ->filter(fn ($log) => (int) ($log->asignacion?->vehiculo_id ?? 0) === (int) $asig->vehiculo_id)
                            ->max('km');
                    @endphp
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-slate-900">
                                    {{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Placas: {{ $vehiculo->placas ?? 'Sin placas' }}
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                {{ $resumen->lecturas }} {{ $resumen->lecturas === 1 ? 'log' : 'logs' }}
                            </span>
                        </div>

                        @if($asignacionesVehiculoActivas->contains('id', $asig->id))
                            <button type="button"
                                    data-open-combustible-modal
                                    data-action="{{ route('obras.vehiculos.combustible.store', ['obra' => $obra->id, 'asignacion' => $asig->id]) }}"
                                    data-vehicle="{{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }} - {{ $vehiculo->placas ?? 'Sin placas' }}"
                                    data-current-km="{{ $maxKmPorVehiculo->get($asig->vehiculo_id) ?? $maxKmVehiculo ?? $resumen->km_actual ?? '' }}"
                                    class="mt-3 inline-flex items-center rounded-lg border border-[#0B265A] px-3 py-2 text-xs font-semibold text-[#0B265A] hover:bg-blue-50">
                                + Agregar registro
                            </button>
                        @endif

                        <dl class="grid grid-cols-2 gap-3 mt-4 text-xs md:grid-cols-4">
                            <div>
                                <dt class="text-slate-500">Km inicio</dt>
                                <dd class="font-semibold text-slate-900">{{ $resumen->km_inicio !== null ? number_format($resumen->km_inicio) : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Km actual</dt>
                                <dd class="font-semibold text-slate-900">{{ $resumen->km_actual !== null ? number_format($resumen->km_actual) : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Avance</dt>
                                <dd class="font-semibold text-emerald-700">{{ $resumen->km_avanzados !== null ? number_format($resumen->km_avanzados) . ' km' : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Gasolina</dt>
                                <dd class="font-semibold text-slate-900">{{ $resumen->monto_gasolina > 0 ? '$' . number_format($resumen->monto_gasolina, 2) : '-' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 text-xs text-slate-500">
                            {{ $asig->fecha_asignacion?->format('d/m/Y') }} - {{ $asig->fecha_fin?->format('d/m/Y') ?? 'Actual' }}
                            @if($asig->empleado)
                                <br>Responsable: {{ $asig->empleado->Nombre }} {{ $asig->empleado->Apellidos }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-[#0B265A] text-white">
                        <tr class="border-b">
                            <th class="py-2 px-3 text-left">Fecha</th>
                            <th class="py-2 px-3 text-left">Vehiculo</th>
                            <th class="py-2 px-3 text-left">Empleado</th>
                            <th class="py-2 px-3 text-right">Km</th>
                            <th class="py-2 px-3 text-right">Km recorridos</th>
                            <th class="py-2 px-3 text-right">Gasolina</th>
                            <th class="py-2 px-3 text-left">Notas</th>
                            <th class="py-2 px-3 text-left">Odometro</th>
                            <th class="py-2 px-3 text-left">Ticket</th>
                            <th class="py-2 px-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculoKmLogs as $log)
                            @php
                                $vehiculo = $log->asignacion?->vehiculo;
                                $empleado = $log->asignacion?->empleado;
                            @endphp
                            <tr class="border-b hover:bg-slate-50 align-top">
                                <td class="py-2 px-3 whitespace-nowrap">
                                    {{ $log->fecha?->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-2 px-3">
                                    <div class="font-medium text-slate-800">
                                        {{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }}
                                    </div>
                                    <div class="text-xs text-slate-400">{{ $vehiculo->placas ?? 'Sin placas' }}</div>
                                </td>
                                <td class="py-2 px-3">
                                    @if($empleado)
                                        {{ $empleado->Nombre }} {{ $empleado->Apellidos }}
                                    @else
                                        <span class="text-slate-400">Sin empleado</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    {{ number_format((int) $log->km) }} km
                                </td>
                                <td class="py-2 px-3 text-right font-semibold text-emerald-700 whitespace-nowrap">
                                    {{ $log->km_recorridos !== null ? number_format((int) $log->km_recorridos) . ' km' : '-' }}
                                </td>
                                <td class="py-2 px-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $log->monto_gasolina !== null ? '$' . number_format((float) $log->monto_gasolina, 2) : '-' }}
                                </td>
                                <td class="py-2 px-3 max-w-xs text-slate-600">
                                    {{ $log->notas ?: '-' }}
                                </td>
                                <td class="py-2 px-3">
                                    @if($log->foto_url)
                                        <a href="{{ $log->foto_url }}" target="_blank" class="inline-block rounded-lg border border-slate-200 overflow-hidden hover:ring-2 hover:ring-blue-300">
                                            <img src="{{ $log->foto_url }}" alt="Lectura km {{ $log->km }}" class="h-16 w-20 object-cover bg-slate-100">
                                        </a>
                                    @else
                                        <span class="text-slate-400">Sin imagen</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    @if($log->foto_ticket_gasolina_url)
                                        <a href="{{ $log->foto_ticket_gasolina_url }}" target="_blank" class="inline-block rounded-lg border border-slate-200 overflow-hidden hover:ring-2 hover:ring-blue-300">
                                            <img src="{{ $log->foto_ticket_gasolina_url }}" alt="Ticket gasolina" class="h-16 w-20 object-cover bg-slate-100">
                                        </a>
                                    @else
                                        <span class="text-slate-400">Sin ticket</span>
                                    @endif
                                </td>                                <td class="py-2 px-3">
                                    <div class="flex items-center justify-center gap-2">
                                        @can('obras.vehiculos.combustible.edit.access')
                                            <button type="button" data-open-editar-combustible
                                                    data-action="{{ route('obras.vehiculos.combustible.update', ['obra' => $obra->id, 'log' => $log->id]) }}"
                                                    data-vehicle="{{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }} - {{ $vehiculo->placas ?? 'Sin placas' }}"
                                                    data-fecha="{{ $log->fecha?->format('Y-m-d\\TH:i') }}"
                                                    data-km="{{ $log->km }}"
                                                    data-monto="{{ $log->monto_gasolina }}"
                                                    data-notas="{{ $log->notas }}"
                                                    title="Editar registro"
                                                    aria-label="Editar registro"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                                </svg>
                                            </button>
                                        @endcan
                                        @can('obras.vehiculos.combustible.delete.access')
                                            <form method="POST" action="{{ route('obras.vehiculos.combustible.destroy', ['obra' => $obra->id, 'log' => $log->id]) }}" onsubmit="return confirm('¿Borrar este registro de combustible? La evidencia se conservará como eliminado.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Borrar registro" aria-label="Borrar registro" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-6 text-center text-slate-500">
                                    Todavia no hay lecturas moviles ligadas a esta obra.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
