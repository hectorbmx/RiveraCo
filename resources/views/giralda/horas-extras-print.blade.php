<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horas extra Giralda</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 12px; font-size: 10.5px; }
        .no-print { margin-bottom: 14px; text-align: right; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 5px; padding: 7px 11px; font-weight: 700; cursor: pointer; }
        .brand { min-height: 58px; display: inline-flex; align-items: center; gap: 10px; color: #0B265A; }
        .brand img { display: block; width: 86px; max-height: 54px; object-fit: contain; }
        .brand-text { display: flex; flex-direction: column; line-height: .86; letter-spacing: 1px; }
        .brand-text .rivera { font-size: 32px; font-weight: 900; }
        .brand-text .construcciones { margin-top: 4px; font-size: 14px; font-weight: 900; letter-spacing: 1.4px; }
        .title { color: #0B265A; text-align: center; font-size: 24px; font-weight: 800; margin: 10px 0 8px; letter-spacing: 1px; }
        .rule { border-top: 7px solid #d9d8f0; border-bottom: 4px double #0B265A; height: 12px; margin-bottom: 10px; }
        .period { margin-bottom: 12px; font-size: 16px; font-weight: 800; color: #0B265A; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #c8d8f2 !important; text-align: center; color: #111827; text-transform: uppercase; font-size: 10px; }
        td { text-align: center; }
        .left { text-align: left; }
        .right { text-align: right; }
        .employee-name { text-align: left; font-weight: 600; }
        .total-row { font-weight: 700; background: #f8fafc; }
        .employee-total { text-align: center; vertical-align: middle; }
        .employee-total-hours { font-size: 21px; font-weight: 900; line-height: 1.05; color: #0B265A; }
        .employee-total-amount { margin-top: 4px; font-size: 10.5px; font-weight: 700; color: #166534; white-space: nowrap; }
        .signatures { display: grid; grid-template-columns: repeat(4, 1fr); gap: 26px; margin-top: 54px; }
        .signature { text-align: center; }
        .line { border-top: 1px solid #111827; min-height: 18px; padding-top: 6px; text-align: center; font-weight: 700; }
        .signature-label { margin-top: 4px; font-size: 11px; }
        @media print {
            @page { size: letter portrait; margin: 6mm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $solicitaNombre = $firmasImpresas->get('reporte_horas_extra|solicita|realiza')?->user?->name
            ?? collect($registros)->pluck('responsable_solicita')->filter()->unique()->first()
            ?? 'Solicitante';

        $voboNombre = $firmasImpresas->get('reporte_horas_extra|giralda|vobo')?->user?->name
            ?? 'VoBo';

        $autorizaNombre = $firmasImpresas->get('reporte_horas_extra|giralda|autoriza')?->user?->name
            ?? 'Autoriza';

        $recibeNombre = $firmasImpresas->get('reporte_horas_extra|recibe|recibe')?->user?->name
            ?? $firmasImpresas->get('reporte_horas_extra|giralda|recibe_administracion')?->user?->name
            ?? $firmasImpresas->get('reporte_horas_extra|giralda|autoriza')?->user?->name
            ?? 'Recibe administracion';
    @endphp

    <div class="no-print">
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    <div class="brand">
        <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
        <div class="brand-text">
            <span class="rivera">RIVERA</span>
            <span class="construcciones">CONSTRUCCIONES</span>
        </div>
    </div>
    <div class="title">FORMATO DE HORAS EXTRA GIRALDA</div>
    <div class="rule"></div>
    <div class="period">Periodo: {{ $desde ?: '-' }} a {{ $hasta ?: '-' }}</div>

    <table>
        <thead>
            <tr>
                <th class="left" style="min-width: 180px;">Empleado</th>
                <th class="left" style="min-width: 150px;">Puesto</th>
                @foreach($dias as $dia)
                    <th style="min-width: 90px;">
                        <div>{{ $dia['label'] }}</div>
                        <small>{{ $dia['weekday'] }}</small>
                    </th>
                @endforeach
                <th style="min-width: 90px;">TOTAL H.EXTRA</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                @php
                    $empleadoNombre = $fila['empleado'];
                    $empleadoPuesto = $fila['puesto'] ?? null;
                @endphp
                <tr>
                    <td class="employee-name left">{{ $empleadoNombre }}</td>
                    <td class="left">{{ $empleadoPuesto ? mb_strtoupper($empleadoPuesto, 'UTF-8') : '-' }}</td>
                    @foreach($dias as $dia)
                        @php
                            $valorDia = (float) ($fila['dias'][$dia['date']] ?? 0);
                            $motivosDia = $fila['motivos'][$dia['date']] ?? [];
                            $horariosDia = $fila['horarios'][$dia['date']] ?? [];
                        @endphp
                        <td class="right">
                            <div>{{ number_format($valorDia, 2) }}</div>
                            @if(!empty($horariosDia) || !empty($motivosDia))
                                <div style="border-top: 1px solid rgba(148, 163, 184, 0.7); margin-top: 5px; padding-top: 5px;">
                                    @if(!empty($horariosDia))
                                        <div style="font-size: 10px; color: #334155; line-height: 1.35; text-align: left;">
                                            @foreach($horariosDia as $horario)
                                                <div>{{ $horario }}</div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(!empty($motivosDia))
                                        <div style="font-size: 10px; color: #475569; line-height: 1.3; text-align: left; margin-top: 3px;">
                                            @foreach($motivosDia as $motivo)
                                                <div>{{ $motivo }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    @endforeach
                    <td class="employee-total">
                        <div class="employee-total-hours">{{ number_format((float) $fila['total'], 2) }}</div>
                        <div class="employee-total-amount">
                            @if($fila['total_pesos_horas_extra'] !== null)
                                $ {{ number_format((float) $fila['total_pesos_horas_extra'], 2) }}
                            @else
                                -
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dias) + 3 }}" class="left">No hay horas extra para este periodo.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="left">Total</td>
                <td class="left">-</td>
                @foreach($dias as $dia)
                    <td class="right">{{ number_format((float)($totalesPorDia[$dia['date']] ?? 0), 2) }}</td>
                @endforeach
                <td class="right">{{ number_format((float) $totalPeriodo, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="signature">
            <div class="line">{{ $solicitaNombre }}</div>
            <div class="signature-label">Elabora</div>
        </div>
        <div class="signature">
            <div class="line">{{ $autorizaNombre }}</div>
            <div class="signature-label">Autoriza</div>
        </div>
        <div class="signature">
            <div class="line">{{ $voboNombre }}</div>
            <div class="signature-label">VoBo</div>
        </div>
        <div class="signature">
            <div class="line">{{ $recibeNombre }}</div>
            <div class="signature-label">Recibe administracion</div>
        </div>
    </div>
</body>
</html>
