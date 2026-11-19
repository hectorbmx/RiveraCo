@php
    $coloresDuracion = ['realista' => '#FFF2CC', 'optimista' => '#E2EFDA', 'pesimista' => '#FFC7CE'];
    $camposDuracion = ['jornales' => ['Jornales de trabajo', '0.01'], 'dias' => ['Días calendario', '1'], 'semanas' => ['Semanas', '1']];
@endphp

<form method="POST" action="{{ route('presupuesto.duracion-apupi.update', $presupuesto) }}"
      x-data="{ editando: @js($errors->duracionApupi->any()) }" class="mt-4">
    @csrf
    @method('PATCH')
    <input type="hidden" name="tab" value="{{ $tabInicial }}" :value="tab">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-slate-800">Duración estimada de la obra</h2>
        <button type="button" class="text-sm font-semibold text-[#0B265A] hover:underline" x-show="!editando"
                @click="editando = true; $nextTick(() => $refs.primerPlazo.focus())">Editar duración</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <caption class="sr-only">Duración de obra por escenario</caption>
            <thead>
                <tr>
                    <th scope="col" class="p-2 text-left text-slate-600">Plazo</th>
                    @foreach(\App\Models\Presupuesto::ESCENARIOS_APUPI as $escenario)
                        <th scope="col" class="px-3 py-2 text-center text-slate-900" style="background-color: {{ $coloresDuracion[$escenario] }}">{{ ucfirst($escenario) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-white">
                @foreach($camposDuracion as $campo => [$etiqueta, $paso])
                    <tr>
                        <th scope="row" class="px-2 py-1.5 text-left font-medium text-slate-700">{{ $etiqueta }}</th>
                        @foreach(\App\Models\Presupuesto::ESCENARIOS_APUPI as $escenario)
                            @php
                                $clave = "duracion.$escenario.$campo";
                                $valor = $duracionApupi[$escenario][$campo];
                                $manual = $presupuesto->duracion_apupi[$escenario][$campo] ?? null;
                            @endphp
                            <td class="px-2 py-1.5 text-center font-medium tabular-nums" style="background-color: {{ $coloresDuracion[$escenario] }}">
                                <span x-show="!editando">{{ $valor === null ? '—' : number_format($valor, $paso === '1' ? 0 : 2) }}</span>
                                <input type="number" min="0" max="999999" step="{{ $paso }}" name="duracion[{{ $escenario }}][{{ $campo }}]"
                                       value="{{ $errors->duracionApupi->any() ? old($clave) : $manual }}"
                                       placeholder="{{ $manual === null ? $valor ?? '—' : '—' }}"
                                       aria-label="{{ $etiqueta }} · {{ ucfirst($escenario) }}"
                                       @if($campo === 'jornales' && $escenario === 'realista') x-ref="primerPlazo" @endif
                                       x-show="editando" x-cloak
                                       class="w-full rounded-md border-slate-300 px-1 py-1 text-center text-sm">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($errors->duracionApupi->any())
        <ul role="alert" class="mt-2 text-sm text-red-700">
            @foreach($errors->duracionApupi->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <div x-show="editando" x-cloak class="mt-2 space-y-2">
        <p class="text-xs text-slate-600">Deja en blanco las celdas que quieras calcular automáticamente.</p>
        <button type="submit" class="rounded-lg bg-[#0B265A] px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-900">Guardar duración</button>
        <button type="reset" @click="editando = false" class="ml-2 text-sm font-medium text-slate-600 hover:underline">Cancelar</button>
    </div>
</form>
