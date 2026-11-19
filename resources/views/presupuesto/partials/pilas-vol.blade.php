@php
    ['filas' => $pilasVol, 'totales' => $totalesVol, 'porDiametro' => $volPorDiametro] = $volumetria;
    $camposVol = [
        'Geometría y rendimiento' => [
            'tipo' => ['Tipo de pila', 'text', null, null, null, 'Po1', true],
            'diametro' => ['Diámetro (m)', 'number', '0.15', '99999.999', '0.001', '0.80', true],
            'piezas' => ['Piezas', 'number', '1', '4294967295', '1', '2', true],
            'avance_metros_por_jornal' => ['Avance (m por jornal)', 'number', '0.00001', '999999999.99999', '0.00001', '60', true],
        ],
        'Profundidad' => [
            'longitud_l0' => ['Longitud L0 (m)', 'number', '0.001', '99999.999', '0.001', '13', true],
            'longitud_l1' => ['Longitud L1 (m)', 'number', '0', '99999.999', '0.001', '0', false],
            'longitud_l2' => ['Longitud L2 (m)', 'number', '0', '99999.999', '0.001', '0', false],
        ],
        'Zunchos' => [
            'intervalo_l0' => ['Intervalo de zuncho L0 (m)', 'number', '0', '99999.999', '0.001', '0', false],
            'intervalo_l1' => ['Intervalo de zuncho L1 (m)', 'number', '0', '99999.999', '0.001', '0', false],
            'intervalo_l2' => ['Intervalo de zuncho L2 (m)', 'number', '0', '99999.999', '0.001', '0', false],
        ],
        'Varillas longitudinales' => [
            'varillas_l0' => ['Varillas L0', 'number', '0', '4294967295', '1', '0', false],
            'varillas_l1' => ['Varillas L1', 'number', '0', '4294967295', '1', '0', false],
        ],
        'Volúmenes y peso' => [
            'acero_kg_por_pila' => ['Acero por pieza (kg)', 'number', '0', '9999999.999', '0.001', 'Captura manual', false],
        ],
    ];
    $coloresVol = [
        'Geometría y rendimiento' => '#5B9BD5',
        'Profundidad' => '#A5A5A5',
        'Zunchos' => '#70AD47',
        'Varillas longitudinales' => '#FFC000',
        'Volúmenes y peso' => '#ED7D31',
    ];
    $columnasVol = [
        'Geometría y rendimiento' => [
            'pila.diametro' => ['Ø (m)', 3],
            'vol.area' => ['Área (m²)', 3],
            'vol.perimetro' => ['Perímetro (m)', 3],
            'pila.piezas' => ['Pzas', 0],
            'vol.pilas_por_jornal' => ['Pilas / jornal', 3],
            'vol.jornales' => ['Jornales', 3],
        ],
        'Profundidad' => [
            'vol.longitud_l0' => ['L0 (m)', 3],
            'pila.longitud_l1' => ['L1 (m)', 3],
            'pila.longitud_l2' => ['L2 (m)', 3],
            'vol.longitud' => ['Long. total (m)', 3],
        ],
        'Zunchos' => [
            'pila.intervalo_l0' => ['Intervalo L0 (m)', 3],
            'pila.intervalo_l1' => ['Intervalo L1 (m)', 3],
            'pila.intervalo_l2' => ['Intervalo L2 (m)', 3],
            'vol.zunchos_l0' => ['L0 (kg)', 3],
            'vol.zunchos_l1' => ['L1 (kg)', 3],
            'vol.zunchos_l2' => ['L2 (kg)', 3],
            'vol.traslape_zunchos' => ['Traslape (kg)', 3],
        ],
        'Varillas longitudinales' => [
            'pila.varillas_l0' => ['Varillas L0', 0],
            'pila.varillas_l1' => ['Varillas L1', 0],
            'vol.peso_varillas_l0' => ['L0 (kg)', 3],
            'vol.peso_varillas_l1' => ['L1 (kg)', 3],
            'vol.traslape_varillas' => ['Traslape (kg)', 3],
        ],
        'Volúmenes y peso' => [
            'vol.perforacion' => ['Perforación (m³)', 3],
            'vol.metros_lineales' => ['Perforación (ML)', 3],
            'vol.concreto' => ['Concreto (m³)', 3],
            'vol.bentonita' => ['Bentonita (m³)', 3],
            'vol.retiro' => ['Retiro (m³)', 3],
            'pila.acero_kg_por_pila' => ['Acero por pieza (kg)', 3],
            'vol.acero' => ['Acero total (kg)', 3],
        ],
    ];
    $resultadosPorSeccion = collect($columnasVol)->map(fn ($columnas) => collect($columnas)
        ->filter(fn ($etiqueta, $campo) => str_starts_with($campo, 'vol.'))
        ->mapWithKeys(fn ($etiqueta, $campo) => [substr($campo, 4) => $etiqueta]));
    $resumenVol = [
        'piezas' => ['Pilas', 0],
        'jornales' => ['Jornales', 3],
        'longitud_l0_promedio' => ['Promedio L0 (m)', 3],
        'metros_lineales' => ['Perforación (ML)', 3],
        'perforacion' => ['Perforación (m³)', 3],
        'concreto' => ['Concreto (m³)', 3],
        'bentonita' => ['Bentonita (m³)', 3],
        'retiro' => ['Retiro (m³)', 3],
        'comision_ml' => ['Comisión (ML)', 3],
        'acero' => ['Acero total (kg)', 3],
    ];
    $urlVol = route('presupuesto.show', [$presupuesto, 'tab' => 'pilas']);
    $avanceVol = $editarVol?->avance_metros_por_jornal
        ?? ($editarVol?->longitud_l0 > 0 && $editarVol?->pilas_por_jornal > 0
            ? max(0.00001, round($editarVol->longitud_l0 * $editarVol->pilas_por_jornal, 5))
            : 60);
    $avanceVol = rtrim(rtrim(number_format($avanceVol, 5, '.', ''), '0'), '.');
@endphp

<div class="space-y-4 p-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Pilas / VOL</h2>
        <p class="mt-1 text-sm text-slate-500">Cada renglón es un tipo de pila, como Po1 o Po1.1. Piezas indica cuántas pilas comparten esa geometría y armado.</p>
    </div>

    @if(session('estadoVol'))
        <p role="status" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estadoVol') }}</p>
    @endif

    <details id="captura-vol" class="rounded-xl border border-slate-200 bg-slate-50" @if($editarVol || $errors->pilaVol->any()) open @endif>
        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-[#0B265A]">{{ $editarVol ? 'Editar tipo de pila' : 'Agregar tipo de pila' }}</summary>
        <form action="{{ $editarVol ? route('presupuesto.pilas-vol.update', [$presupuesto, $editarVol]) : route('presupuesto.pilas-vol.store', $presupuesto) }}"
              method="POST" class="space-y-4 border-t border-slate-200 p-4"
              data-calcular-url="{{ route('presupuesto.pilas-vol.calcular', [$presupuesto, 'pila_vol' => $editarVol?->id]) }}"
              x-data="{
                  resultados: {},
                  solicitud: null,
                  error: '',
                  async calcular(formulario) {
                      this.solicitud?.abort();
                      const solicitud = new AbortController();
                      this.solicitud = solicitud;
                      try {
                          const datosFormulario = new URLSearchParams(new FormData(formulario));
                          datosFormulario.delete('_method');
                          const respuesta = await fetch(formulario.dataset.calcularUrl, {
                              method: 'POST',
                              body: datosFormulario,
                              headers: { Accept: 'application/json' },
                              signal: solicitud.signal
                          });
                          if (!respuesta.ok) throw new Error(respuesta.status);
                          const datos = await respuesta.json();
                          if (!solicitud.signal.aborted) {
                              this.resultados = datos;
                              this.error = '';
                          }
                      } catch (error) {
                          if (solicitud.signal.aborted) return;
                          this.resultados = {};
                          this.error = error.message === '422' ? 'Revisa los valores de captura para actualizar el cálculo.' : 'No se pudo actualizar el cálculo.';
                      }
                  },
                  mostrar(campo, decimales) {
                      const valor = this.resultados[campo];
                      return typeof valor === 'number' ? valor.toLocaleString('en-MX', { minimumFractionDigits: decimales, maximumFractionDigits: decimales }) : '—';
                  }
              }"
              @input.debounce.180ms="calcular($el)"
              @if($editarVol || $errors->pilaVol->any()) x-init="calcular($el)" @endif>
            @csrf
            @if($editarVol)
                @method('PATCH')
            @endif
            @foreach($camposVol as $seccion => $campos)
                <fieldset class="rounded-xl border border-slate-200 bg-white px-4 pb-4">
                    <legend class="rounded-md px-3 py-1 text-sm font-semibold text-slate-950" style="background-color: {{ $coloresVol[$seccion] }}">{{ $seccion }}</legend>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($campos as $nombre => [$etiqueta, $tipo, $minimo, $maximo, $paso, $ejemplo, $requerido])
                            <div>
                                <label for="vol-{{ $nombre }}" class="block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
                                <input id="vol-{{ $nombre }}" name="{{ $nombre }}" type="{{ $tipo }}" value="{{ old($nombre, $nombre === 'avance_metros_por_jornal' ? $avanceVol : $editarVol?->{$nombre}) }}"
                                       placeholder="{{ $ejemplo }}"
                                       @if($tipo === 'number') min="{{ $minimo }}" max="{{ $maximo }}" step="{{ $paso }}" @else maxlength="80" @endif
                                       @required($requerido)
                                       @if($errors->pilaVol->has($nombre)) aria-invalid="true" aria-describedby="vol-error-{{ $nombre }}" @endif
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                @error($nombre, 'pilaVol')
                                    <p id="vol-error-{{ $nombre }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                    <dl class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-3 text-sm">
                        @foreach($resultadosPorSeccion[$seccion] as $campo => [$etiqueta, $decimales])
                            <div class="rounded-md bg-slate-50 px-3 py-2">
                                <dt class="text-slate-600">{{ $etiqueta }}</dt>
                                <dd class="font-semibold text-slate-900" x-text="mostrar('{{ $campo }}', {{ $decimales }})">—</dd>
                            </div>
                        @endforeach
                    </dl>
                </fieldset>
            @endforeach
            <p class="text-sm text-red-700" role="status" x-show="error" x-text="error" x-cloak></p>
            <p class="text-sm text-slate-600">Usa cero para un intervalo sin zunchos. El acero total usa los kg por pieza capturados, como en el machote.</p>
            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">Guardar tipo de pila</button>
                @if($editarVol)
                    <a href="{{ $urlVol }}" class="text-sm font-medium text-[#0B265A] hover:underline">Cancelar</a>
                @endif
            </div>
        </form>
    </details>

    <details class="rounded-xl border border-slate-200 bg-slate-50" @if($errors->traslapeVol->any()) open @endif>
        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-[#0B265A]">Tablas de varillas y traslapes</summary>
        <div class="grid gap-4 border-t border-slate-200 p-4 sm:grid-cols-2">
            <table class="w-full text-sm">
                <caption class="mb-2 text-left font-semibold text-slate-800">Tabla de varillas</caption>
                <thead class="bg-slate-100">
                    <tr><th scope="col" class="p-2 text-left">N.º</th><th scope="col" class="p-2 text-right">kg/m</th><th scope="col" class="p-2 text-left">Uso en VOL</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach(\App\Models\PresupuestoVolPila::PESOS_VARILLA as $numero => $peso)
                        <tr><th scope="row" class="p-2 text-left">{{ $numero }}</th><td class="p-2 text-right">{{ number_format($peso, 3) }}</td><td class="p-2">{{ [3 => 'Zunchos L0 y traslapes de zunchos', 4 => 'Zunchos L1 y L2', 6 => 'Varillas longitudinales'][$numero] ?? '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <table class="w-full self-start text-sm">
                <caption class="mb-2 text-left font-semibold text-slate-800">Tabla de traslapes</caption>
                <thead class="bg-slate-100">
                    <tr><th scope="col" class="p-2 text-left">N.º</th><th scope="col" class="p-2 text-right">Longitud (m)</th><th scope="col" class="p-2 text-left">Uso en VOL</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach(\App\Models\PresupuestoVolPila::TRASLAPES_METROS as $numero => $longitud)
                        <tr><th scope="row" class="p-2 text-left">{{ $numero }}</th><td class="p-2 text-right">{{ number_format($longitud, 5) }}</td><td class="p-2">{{ $numero === 3 ? 'Traslapes de zunchos' : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form action="{{ route('presupuesto.pilas-vol.traslape', $presupuesto) }}" method="POST" class="border-t border-slate-200 p-4">
            @csrf
            @method('PATCH')
            <label for="vol-traslape-varilla" class="block text-sm font-medium text-slate-700">Longitud de traslape (m)</label>
            <input id="vol-traslape-varilla" name="vol_traslape_varilla" type="number" required min="0" max="99999.99999" step="0.00001"
                   value="{{ old('vol_traslape_varilla', $presupuesto->vol_traslape_varilla) }}"
                   class="mt-1 rounded-lg border-slate-300 text-sm">
            <button type="submit" class="ml-2 rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Guardar traslape</button>
            @error('vol_traslape_varilla', 'traslapeVol')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-sm text-slate-600">Aplica a todos los tipos de pila del presupuesto. Equivale a U32 en VOL.</p>
        </form>
    </details>
</div>

@if($pilasVol->isEmpty())
    <p class="border-t border-slate-100 px-5 py-6 text-sm text-slate-500">Todavía no se han capturado tipos de pila para calcular la volumetría.</p>
@else
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabla de volumetría">
        <table class="w-full whitespace-nowrap text-sm">
            <caption class="sr-only">Tipos de pila y volumetría calculada</caption>
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th rowspan="2" class="sticky left-0 px-4 py-3 text-left text-slate-950" style="background-color: {{ $coloresVol['Geometría y rendimiento'] }}" scope="col">Tipo</th>
                    @foreach($columnasVol as $seccion => $columnas)
                        <th colspan="{{ count($columnas) }}" class="border-l border-white/20 px-4 py-2 text-center text-slate-950" style="background-color: {{ $coloresVol[$seccion] }}" scope="colgroup">{{ $seccion }}</th>
                    @endforeach
                    <th rowspan="2" class="px-4 py-3 text-center" scope="col">Acciones</th>
                </tr>
                <tr>
                    @foreach($columnasVol as $seccion => $columnas)
                        @foreach($columnas as [$etiqueta, $decimales])
                            <th class="border-t border-white/20 px-4 py-2 text-right text-slate-950" style="background-color: {{ $coloresVol[$seccion] }}" scope="col">{{ $etiqueta }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach($pilasVol as $fila)
                    <tr>
                        <th class="sticky left-0 bg-white px-4 py-3 text-left font-semibold" scope="row">{{ $fila['pila']->tipo }}</th>
                        @foreach($columnasVol as $columnas)
                            @foreach($columnas as $clave => [$etiqueta, $decimales])
                                @php($valor = data_get($fila, $clave))
                                <td class="px-4 py-3 text-right">{{ $valor === null ? '—' : number_format($valor, $decimales) }}</td>
                            @endforeach
                        @endforeach
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'pilas', 'editar_pila' => $fila['pila']->id]) }}#captura-vol"
                                   aria-label="Editar tipo {{ $fila['pila']->tipo }}" class="font-medium text-[#0B265A] hover:underline">Editar</a>
                                <form action="{{ route('presupuesto.pilas-vol.destroy', [$presupuesto, $fila['pila']]) }}" method="POST"
                                      @submit="if (!window.confirm('¿Eliminar este tipo de pila de VOL?')) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Eliminar tipo {{ $fila['pila']->tipo }}" class="font-medium text-red-700 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<section class="border-t border-slate-200 p-5">
    <h3 class="text-base font-semibold text-slate-800">Resumen ML y volúmenes</h3>
    <p class="mt-1 text-sm text-slate-500">Totales de los tipos de pila guardados.</p>
    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <table class="w-full text-sm">
            <caption class="mb-2 text-left font-semibold text-slate-800">Totales del presupuesto</caption>
            <thead class="bg-[#0B265A] text-white">
                <tr><th class="px-3 py-2 text-left" scope="col">Concepto</th><th class="px-3 py-2 text-right" scope="col">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($resumenVol as $clave => [$etiqueta, $decimales])
                    <tr>
                        <th class="px-3 py-2 text-left font-medium" scope="row">{{ $etiqueta }}</th>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $totalesVol[$clave] === null ? '—' : number_format($totalesVol[$clave], $decimales) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="max-h-96 overflow-auto" tabindex="0" role="region" aria-label="Resumen de perforación por diámetro">
            <table class="w-full text-sm">
                <caption class="mb-2 text-left font-semibold text-slate-800">Perforación por diámetro</caption>
                <thead class="sticky top-0 bg-[#0B265A] text-white">
                    <tr><th class="px-3 py-2 text-left" scope="col">Diámetro (m)</th><th class="px-3 py-2 text-right" scope="col">Piezas</th><th class="px-3 py-2 text-right" scope="col">ML</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($volPorDiametro as $diametro => $resumen)
                        <tr>
                            <th class="px-3 py-2 text-left font-medium" scope="row">{{ number_format($diametro, 3) }}</th>
                            <td class="px-3 py-2 text-right">{{ number_format($resumen['piezas']) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($resumen['metros_lineales'], 3) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

@if($presupuesto->pilas->isNotEmpty())
    <details class="border-t border-slate-200">
        <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-[#0B265A]">Perforación de la cotización importada</summary>
        @include('presupuesto.partials.tabla-escenarios', ['filas' => $presupuesto->pilas, 'esPila' => true])
    </details>
@endif

