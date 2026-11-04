@php
    $idsSeleccionados = old('perforacion.personal_presente')
        ? collect(old('perforacion.personal_ids', []))->map(fn ($id) => (int) $id)
        : $personalDisponibleAlta->pluck('id');
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Personal y fotografías de Perforación">
    <div class="bg-[#0B265A] px-5 py-4 text-white">
        <h2 class="text-base font-semibold">Personal y fotografías</h2>
        <p class="mt-1 text-sm text-blue-100">Estos participantes y evidencias corresponden únicamente a la perforación inicial.</p>
    </div>
    <div class="p-5 sm:p-6">
        <fieldset>
            <legend class="text-sm font-semibold text-[#0B265A]">Personal involucrado</legend>
            <input type="hidden" name="perforacion[personal_presente]" value="1">
            <p class="mt-1 mb-3 text-xs text-slate-500">Se propone el personal activo de la obra. Desmarca a quienes no participaron. El residente figura como responsable en Datos generales.</p>
            @if($personalDisponibleAlta->isEmpty())
                <p class="text-sm text-slate-500">No hay personal con asignación activa disponible para seleccionar.</p>
            @else
                <label for="alta-buscar-personal" class="block text-sm font-medium text-slate-700">Buscar por nombre o rol</label>
                <input id="alta-buscar-personal" type="search" x-model="buscarPersonal" class="mt-1 mb-3 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Filtrar personal…">
                <p class="mb-3 text-xs text-slate-500">El filtro conserva las selecciones aunque queden ocultas.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($personalDisponibleAlta as $asignacion)
                        <label x-show="coincidePersonal($el.textContent)" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                            <input type="checkbox" name="perforacion[personal_ids][]" value="{{ $asignacion->id }}"
                                   @checked($idsSeleccionados->contains($asignacion->id)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">{{ $asignacion->empleado->nombre_completo }}</span>
                                <span class="block text-xs text-slate-500">{{ $asignacion->rol?->nombre ?? 'Sin rol' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>
        <fieldset class="mt-5">
            <legend class="text-sm font-semibold text-[#0B265A]">Fotografías de Perforación</legend>
            @include('obras.comisiones.partials.fotos-nuevas')
            <p class="mt-2 text-xs text-slate-500">Si recargas o abandonas la página, tendrás que volver a seleccionar los archivos.</p>
        </fieldset>
    </div>
</section>
