<h2 class="text-lg font-semibold mb-4">Historial de nómina</h2>

@if($empleado->nominaRecibos->isEmpty())
    <p class="text-slate-500 text-sm">
        Este empleado aún no tiene recibos de nómina registrados en el sistema.
        <br>
        Los recibos históricos se importan desde el sistema anterior y
        los nuevos se generarán desde el módulo de nómina por obra.
    </p>
@else
    <div class="mb-3 flex justify-between items-center text-xs text-slate-500">
        <span>
            Total de recibos registrados: {{ $empleado->nominaRecibos->count() }}
        </span>
        <span>
            Mostrando los más recientes primero
        </span>
    </div>

    <div class="border rounded-2xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-2 px-3">Periodo</th>
                    <th class="py-2 px-3">Fechas</th>
                    <th class="py-2 px-3">Obra</th>
                    <th class="py-2 px-3">Tipo</th> {{-- 👈 nueva --}}
                    <th class="py-2 px-3 text-right">Percepciones</th>
                    <th class="py-2 px-3 text-right">Deducciones</th>
                    <th class="py-2 px-3 text-right">Neto pagado</th>
                    <th class="py-2 px-3 text-center">Estado</th>
                    <th class="py-2 px-3 text-right">Detalles</th>
                </tr>
            </thead>
            <tbody>
                @foreach($empleado->nominaRecibos as $recibo)
                    <tr class="border-b last:border-b-0 hover:bg-slate-50 align-top">
                        {{-- Periodo --}}
                        <td class="py-2 px-3">
                            <div class="font-medium text-slate-800">
                                {{ $recibo->periodo_label ?? 'Periodo sin etiqueta' }}
                            </div>
                            @if($recibo->folio)
                                <div class="text-[11px] text-slate-400">
                                    Folio: {{ $recibo->folio }}
                                </div>
                            @endif
                        </td>

                        {{-- Fechas --}}
                        <td class="py-2 px-3 text-[13px]">
                            @if($recibo->fecha_inicio || $recibo->fecha_fin)
                                <div>
                                    Del
                                    {{ $recibo->fecha_inicio?->format('d/m/Y') ?? '¿?' }}
                                    al
                                    {{ $recibo->fecha_fin?->format('d/m/Y') ?? '¿?' }}
                                </div>
                            @endif
                            @if($recibo->fecha_pago)
                                <div class="text-[11px] text-slate-400 mt-1">
                                    Pago: {{ $recibo->fecha_pago->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>

                        {{-- Obra --}}
                        <td class="py-2 px-3 text-[13px]">
                            @if($recibo->obra)
                                <div class="font-medium text-slate-700">
                                    {{ $recibo->obra->nombre ?? $recibo->obra->folio ?? 'Obra #'.$recibo->obra->id }}
                                </div>
                                @if($recibo->obra->cliente ?? false)
                                    <div class="text-[11px] text-slate-400">
                                        {{ $recibo->obra->cliente->nombre_comercial ?? '' }}
                                    </div>
                                @endif
                            @elseif($recibo->obra_legacy)
                                <span class="text-[12px] text-slate-500">
                                    {{ $recibo->obra_legacy }} (legacy)
                                </span>
                            @else
                                <span class="text-[12px] text-slate-400">
                                    No asociada a obra
                                </span>
                            @endif
                        </td>
                        {{-- Tipo de pago --}}
                            <td class="py-2 px-3 align-top text-[12px]">
                                @php
                                    $tipo    = $recibo->tipo_pago ?? 'nomina';
                                    $subtipo = $recibo->subtipo ?? 'nomina_normal';

                                    // Texto amigable
                                    $label = 'Nómina';
                                    if ($tipo === 'extra') {
                                        $map = [
                                            'aguinaldo'        => 'Aguinaldo',
                                            'prima_vacacional' => 'Prima vacacional',
                                            'ptu'              => 'PTU',
                                            'bono'             => 'Bono',
                                            'otro'             => 'Extra',
                                        ];
                                        $label = $map[$subtipo] ?? ucfirst(str_replace('_', ' ', $subtipo));
                                    }
                                @endphp

                                @if($tipo === 'nomina')
                                    <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-slate-100 text-slate-700">
                                        {{ $label }}
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-purple-100 text-purple-700">
                                        {{ $label }}
                                    </span>
                                @endif
                            </td>


                        {{-- Percepciones --}}
                        <td class="py-2 px-3 text-right">
                            ${{ number_format($recibo->total_percepciones, 2) }}
                        </td>

                        {{-- Deducciones --}}
                        <td class="py-2 px-3 text-right">
                            ${{ number_format($recibo->total_deducciones, 2) }}
                        </td>

                        {{-- Neto --}}
                        <td class="py-2 px-3 text-right font-semibold text-slate-800">
                            ${{ number_format($recibo->sueldo_neto, 2) }}
                        </td>

                        {{-- Estado --}}
                        <td class="py-2 px-3 text-center">
                            @php $status = strtolower($recibo->status); @endphp

                            @if($status === 'pagado')
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-green-100 text-green-700">
                                    Pagado
                                </span>
                            @elseif($status === 'pendiente')
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-yellow-100 text-yellow-700">
                                    Pendiente
                                </span>
                            @elseif($status === 'cancelado')
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-red-100 text-red-700">
                                    Cancelado
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-slate-100 text-slate-600">
                                    {{ ucfirst($recibo->status) }}
                                </span>
                            @endif
                        </td>

                        {{-- Detalle legacy --}}
                        <td class="py-2 px-3 text-right text-[12px]">
                            <details>
                                <summary class="cursor-pointer text-blue-600 hover:text-blue-800">
                                    Ver
                                </summary>
                                <div class="mt-2 text-left text-[11px] text-slate-600 space-y-1">
                                    @if(!is_null($recibo->faltas))
                                        <div>Faltas: {{ $recibo->faltas }}</div>
                                    @endif
                                    @if(!is_null($recibo->horas_extra))
                                        <div>Horas extra: {{ $recibo->horas_extra }}</div>
                                    @endif
                                    @if(!is_null($recibo->metros_lin_monto))
                                        <div>Metros lineales (monto): ${{ number_format($recibo->metros_lin_monto, 2) }}</div>
                                    @endif
                                    @if(!is_null($recibo->comisiones_monto))
                                        <div>Comisiones: ${{ number_format($recibo->comisiones_monto, 2) }}</div>
                                    @endif
                                    @if(!is_null($recibo->factura_monto))
                                        <div>Factura por extras: ${{ number_format($recibo->factura_monto, 2) }}</div>
                                    @endif
                                    @if(!is_null($recibo->descuentos_legacy) || !is_null($recibo->infonavit_legacy))
                                        <div>Descuentos legacy:
                                            ${{ number_format(($recibo->descuentos_legacy ?? 0) + ($recibo->infonavit_legacy ?? 0), 2) }}
                                            @if(!is_null($recibo->infonavit_legacy))
                                                <span>(INFONAVIT: ${{ number_format($recibo->infonavit_legacy, 2) }})</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($recibo->notas_legacy)
                                        <div>Notas: {{ $recibo->notas_legacy }}</div>
                                    @endif
                                </div>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
