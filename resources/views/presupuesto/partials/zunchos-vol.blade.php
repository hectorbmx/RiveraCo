@php
    $armadoPorCalibre = !$editarVol || $editarVol->calibre_varilla_l0 !== null || $editarVol->calibre_varilla_l1 !== null;
    $predeterminados = \App\Models\PresupuestoVolPila::calibresZunchoPredeterminados($armadoPorCalibre);
    $calibres = array_intersect_key(\App\Models\PresupuestoVolPila::PESOS_VARILLA, \App\Models\PresupuestoVolPila::TRASLAPES_METROS);
@endphp

<fieldset class="rounded-xl border border-slate-200 bg-white px-4 pb-4">
    <legend class="rounded-md px-3 py-1 text-sm font-semibold text-slate-950" style="background-color: {{ $coloresVol['Zunchos'] }}">Zunchos</legend>
    <div class="mt-2 grid gap-3 sm:grid-cols-3 xl:grid-cols-7">
        @foreach($predeterminados as $tramo => $predeterminado)
            @php
                $zona = strtoupper($tramo);
                $intervalo = "intervalo_{$tramo}";
                $calibre = "calibre_zuncho_{$tramo}";
                $seleccionado = (string) old($calibre, $editarVol?->{$calibre});
            @endphp
            <div class="min-w-0" x-data="{ calibre: @js($seleccionado) }">
                <label for="vol-{{ $intervalo }}" class="block text-sm font-medium text-slate-700">Intervalo {{ $zona }} (m)</label>
                <input id="vol-{{ $intervalo }}" name="{{ $intervalo }}" type="number" min="0" max="99999.999" step="0.001" placeholder="0"
                       value="{{ old($intervalo, $editarVol?->{$intervalo}) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                       @if($errors->pilaVol->has($intervalo)) aria-invalid="true" aria-describedby="vol-error-{{ $intervalo }}" @endif>
                @error($intervalo, 'pilaVol')
                    <p id="vol-error-{{ $intervalo }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
                <label for="vol-{{ $calibre }}" class="mt-2 block text-xs font-medium text-slate-600">Calibre {{ $zona }}</label>
                <select id="vol-{{ $calibre }}" name="{{ $calibre }}" x-model="calibre" class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                        @if($errors->pilaVol->has($calibre)) aria-invalid="true" aria-describedby="vol-error-{{ $calibre }}" @endif>
                    <option value="" @selected($seleccionado === '')>#{{ $predeterminado['peso'] }} (predet.)</option>
                    @foreach($calibres as $numero => $peso)
                        <option value="{{ $numero }}" @selected($seleccionado === (string) $numero)>#{{ $numero }} · {{ number_format($peso, 3) }} kg/m</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-600" x-cloak
                   x-show="calibre === '' && (resultados.calibres_zuncho_predeterminados?.{{ $tramo }}.traslape ?? {{ $predeterminado['traslape'] }}) !== {{ $predeterminado['peso'] }}">
                    Traslape predet. #<span x-text="resultados.calibres_zuncho_predeterminados?.{{ $tramo }}.traslape ?? {{ $predeterminado['traslape'] }}">{{ $predeterminado['traslape'] }}</span>
                </p>
                @error($calibre, 'pilaVol')
                    <p id="vol-error-{{ $calibre }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
        @foreach(array_keys($predeterminados) as $tramo)
            <dl class="self-start rounded-lg bg-slate-50 px-3 py-2 text-sm">
                <dt class="font-medium text-slate-700">Zona {{ strtoupper($tramo) }} (kg)</dt>
                <dd class="mt-1 font-semibold tabular-nums text-slate-900" x-text="mostrar('zunchos_{{ $tramo }}', 3)">—</dd>
                <dt class="mt-2 text-xs text-slate-600">Traslape (kg)</dt>
                <dd class="text-xs tabular-nums text-slate-700" x-text="mostrar('traslape_zunchos_{{ $tramo }}', 3)">—</dd>
            </dl>
        @endforeach
        <dl class="self-start rounded-lg bg-slate-50 px-3 py-2 text-sm">
            <dt class="font-medium text-slate-700">Traslape total (kg)</dt>
            <dd class="mt-1 font-semibold tabular-nums text-slate-900" x-text="mostrar('traslape_zunchos', 3)">—</dd>
        </dl>
    </div>
</fieldset>
