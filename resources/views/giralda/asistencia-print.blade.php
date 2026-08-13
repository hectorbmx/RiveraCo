<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia semanal Giralda</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 11px; }
        h1 { color: #0B265A; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .employee { min-width: 145px; }
        .day { min-width: 54px; }
        .check { font-size: 16px; font-weight: bold; color: #166534; line-height: 1; }
        .absent { color: #991b1b; font-weight: bold; }
        .he { display: block; margin-top: 3px; color: #0B265A; font-size: 10px; font-weight: bold; }
        .summary { margin-top: 6px; color: #475569; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 36px; margin-top: 48px; }
        .line { border-top: 1px solid #111827; padding-top: 6px; text-align: center; }
        @media print { .no-print { display: none; } body { font-size: 10px; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimir</button>
    <h1>Asistencia semanal Giralda</h1>
    <div>Periodo: {{ $semanaData['titulo'] }}</div>
    <div class="summary">Estatus: {{ ucfirst($estatus) }} | Area: {{ $areaGiralda?->nombre ?? 'Giralda' }}</div>

    <table>
        <thead>
            <tr>
                <th class="employee">Empleado</th>
                <th>Puesto</th>
                @foreach($weekDays as $day)
                    <th class="center day">
                        <div>{{ strtoupper($day->locale('es')->translatedFormat('D')) }}</div>
                        <div class="muted">{{ $day->format('d/m') }}</div>
                    </th>
                @endforeach
                <th class="right">Total HE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($empleados as $empleado)
                <tr>
                    <td>
                        <strong>{{ $empleado->nombre_completo }}</strong>
                        <div class="muted">ID {{ $empleado->id_Empleado }}</div>
                    </td>
                    <td>{{ $empleado->Puesto ?? '-' }}</td>
                    @foreach($weekDays as $day)
                        @php
                            $date = $day->toDateString();
                            $asistencia = $asistencias->get($empleado->id_Empleado . '|' . $date);
                            $horasExtra = (float) ($horasExtrasPorDia->get($empleado->id_Empleado . '|' . $date)?->total_horas ?? 0);
                        @endphp
                        <td class="center">
                            @if($asistencia?->estado === 'presente')
                                <span class="check">âœ“</span>
                            @elseif($asistencia?->estado === 'ausente')
                                <span class="absent">-</span>
                            @else
                                <span class="muted">-</span>
                            @endif

                            @if($horasExtra > 0)
                                <span class="he">HE {{ number_format($horasExtra, 2) }}</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="right"><strong>{{ number_format((float) ($totalesHorasExtras->get($empleado->id_Empleado) ?? 0), 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center muted">No hay empleados asignados a Giralda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div class="line">Captura asistencia</div>
        <div class="line">Revisa horas extras</div>
        <div class="line">Recibe administracion</div>
    </div>
</body>
</html>
