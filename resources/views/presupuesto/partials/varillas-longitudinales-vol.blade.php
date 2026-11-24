@php
    $tramosVarillas = ['l0', 'l1'];
    $calibrePredeterminado = \App\Models\PresupuestoVolPila::CALIBRE_PREDETERMINADO;
@endphp

<fieldset class="rounded-xl border border-slate-200 bg-white px-4 pb-4">
    <legend class="rounded-md px-3 py-1 text-sm font-semibold text-slate-950" style="background-color: {{ $coloresVol['Varillas longitudinales'] }}">Varillas longitudinales</legend>
    <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($tramosVarillas as $tramo)
            @php
                $zona = strtoupper($tramo);
                $cantidad = "varillas_{$tramo}";
                $calibre = "calibre_varilla_{$tramo}";
                $calibreGuardado = $editarVol?->{$calibre};
                $usaPredeterminado = $editarVol !== null && $calibreGuardado === null;
                $seleccionado = (string) old($calibre, $editarVol ? $calibreGuardado : $calibrePredeterminado);
            @endphp
            <div class="min-w-0">
                <label for="vol-{{ $cantidad }}" class="block text-sm font-medium text-slate-700">Cantidad de varillas {{ $zona }}</label>
                <input id="vol-{{ $cantidad }}" name="{{ $cantidad }}" type="number" min="0" max="4294967295" step="1" placeholder="0"
                       value="{{ old($cantidad, $editarVol?->{$cantidad}) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                       @if($errors->pilaVol->has($cantidad)) aria-invalid="true" aria-describedby="vol-error-{{ $cantidad }}" @endif>
                @error($cantidad, 'pilaVol')
                    <p id="vol-error-{{ $cantidad }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
                <label for="vol-{{ $calibre }}" class="mt-2 block text-xs font-medium text-slate-600">Calibre {{ $zona }}</label>
                <select id="vol-{{ $calibre }}" name="{{ $calibre }}" @required(!$usaPredeterminado)
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                        @if($errors->pilaVol->has($calibre)) aria-invalid="true" aria-describedby="vol-error-{{ $calibre }}" @endif>
                    @if($usaPredeterminado)
                        <option value="" @selected($seleccionado === '')>#{{ $calibrePredeterminado }} (predet.)</option>
                    @endif
                    @foreach(\App\Models\PresupuestoVolPila::PESOS_VARILLA as $numero => $peso)
                        <option value="{{ $numero }}" @selected($seleccionado === (string) $numero)>#{{ $numero }} · {{ number_format($peso, 3) }} kg/m</option>
                    @endforeach
                </select>
                @error($calibre, 'pilaVol')
                    <p id="vol-error-{{ $calibre }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
        @foreach($tramosVarillas as $tramo)
            <dl class="self-start rounded-lg bg-slate-50 px-3 py-2 text-sm">
                <dt class="font-medium text-slate-700">Zona {{ strtoupper($tramo) }} (kg)</dt>
                <dd class="mt-1 font-semibold tabular-nums text-slate-900" x-text="mostrar('peso_varillas_{{ $tramo }}', 3)">—</dd>
            </dl>
        @endforeach
        <dl class="self-start rounded-lg bg-slate-50 px-3 py-2 text-sm">
            <dt class="font-medium text-slate-700">Traslape (kg)</dt>
            <dd class="mt-1 font-semibold tabular-nums text-slate-900" x-text="mostrar('traslape_varillas', 3)">—</dd>
        </dl>
    </div>
</fieldset>
