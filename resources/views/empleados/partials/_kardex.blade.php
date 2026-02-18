<h2 class="text-lg font-semibold mb-4">Kardex del empleado</h2>

@if(($kardex ?? collect())->isEmpty())
  <p class="text-slate-500 text-sm">
    Aún no hay movimientos en el kardex para este empleado.
  </p>
@else

  <div class="mb-3 flex justify-between items-center text-xs text-slate-500">
    <span>Total de movimientos: {{ $kardex->count() }}</span>
    <span>Ordenado del más reciente al más antiguo</span>
  </div>

  <div class="space-y-3">
    @foreach($kardex as $k)
      @php
        $isExtra = ($k['tipo'] ?? '') === 'extra';
        $fecha = $k['fecha'] ? \Carbon\Carbon::parse($k['fecha']) : null;

        $status = strtolower($k['status'] ?? '');
        $statusClass = 'bg-slate-100 text-slate-700';
        if ($status === 'pagado') $statusClass = 'bg-green-100 text-green-700';
        elseif ($status === 'pendiente') $statusClass = 'bg-yellow-100 text-yellow-700';
        elseif ($status === 'cancelado') $statusClass = 'bg-red-100 text-red-700';
      @endphp

      <div class="border rounded-2xl p-4 hover:bg-slate-50">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="inline-flex px-2 py-1 rounded-full text-[11px]
                {{ $isExtra ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' }}">
                {{ $k['titulo'] ?? 'Movimiento' }}
              </span>

              @if(!empty($k['status']))
                <span class="inline-flex px-2 py-1 rounded-full text-[11px] {{ $statusClass }}">
                  {{ ucfirst($k['status']) }}
                </span>
              @endif

              @if(!empty($k['subtitulo']))
                <span class="text-[12px] text-slate-500 truncate">
                  {{ $k['subtitulo'] }}
                </span>
              @endif
            </div>

            <div class="mt-1 text-[12px] text-slate-500">
              @if($fecha)
                <span class="font-medium text-slate-700">
                  {{ $fecha->format('d/m/Y') }}
                </span>
                <span class="text-slate-400">·</span>
              @endif

              @if(!empty($k['meta']['periodo']))
                <span>Periodo: {{ $k['meta']['periodo'] }}</span>
                <span class="text-slate-400">·</span>
              @endif

              @if(!empty($k['meta']['folio']))
                <span>Folio: {{ $k['meta']['folio'] }}</span>
              @endif
            </div>

            {{-- Detalle resumido --}}
            <div class="mt-2 text-[12px] text-slate-600">
              <span class="mr-3">Percepciones: <b>${{ number_format($k['percepciones'] ?? 0, 2) }}</b></span>
              <span class="mr-3">Deducciones: <b>${{ number_format($k['deducciones'] ?? 0, 2) }}</b></span>
              <span>Neto: <b class="text-slate-800">${{ number_format($k['monto'] ?? 0, 2) }}</b></span>
            </div>

            {{-- Detalles expandibles --}}
            <div class="mt-2">
              <details>
                <summary class="cursor-pointer text-blue-600 hover:text-blue-800 text-[12px]">
                  Ver detalles
                </summary>

                <div class="mt-2 text-[11px] text-slate-600 space-y-1">
                  @php $d = $k['detalle'] ?? []; @endphp

                  @if(!empty($k['meta']['rango']))
                    <div>Rango: {{ $k['meta']['rango'] }}</div>
                  @endif
                  @if(!empty($k['meta']['pago']))
                    <div>Pago: {{ $k['meta']['pago'] }}</div>
                  @endif

                  @if(!is_null($d['faltas'] ?? null))
                    <div>Faltas: {{ $d['faltas'] }}</div>
                  @endif
                  @if(!is_null($d['horas_extra'] ?? null))
                    <div>Horas extra: {{ $d['horas_extra'] }}</div>
                  @endif
                  @if(!is_null($d['metros_lin_monto'] ?? null))
                    <div>Metros lineales (monto): ${{ number_format($d['metros_lin_monto'], 2) }}</div>
                  @endif
                  @if(!is_null($d['comisiones_monto'] ?? null))
                    <div>Comisiones: ${{ number_format($d['comisiones_monto'], 2) }}</div>
                  @endif
                  @if(!is_null($d['factura_monto'] ?? null))
                    <div>Factura por extras: ${{ number_format($d['factura_monto'], 2) }}</div>
                  @endif

                  @php
                    $descLegacy = ($d['descuentos_legacy'] ?? null);
                    $infonavit  = ($d['infonavit_legacy'] ?? null);
                  @endphp
                  @if(!is_null($descLegacy) || !is_null($infonavit))
                    <div>
                      Descuentos legacy:
                      ${{ number_format(($descLegacy ?? 0) + ($infonavit ?? 0), 2) }}
                      @if(!is_null($infonavit))
                        <span>(INFONAVIT: ${{ number_format($infonavit, 2) }})</span>
                      @endif
                    </div>
                  @endif

                  @if(!empty($d['notas_legacy'] ?? null))
                    <div>Notas: {{ $d['notas_legacy'] }}</div>
                  @endif

                  @if(!empty($d['notas'] ?? null))
                    <div>Notas: {{ $d['notas'] }}</div>
                  @endif
                </div>
              </details>
            </div>

          </div>

          <div class="shrink-0 text-right">
            <div class="text-[11px] text-slate-400">
              {{ $fecha ? $fecha->diffForHumans() : '' }}
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

@endif
