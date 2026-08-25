<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista semanal de asistencia</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        .toolbar { margin-bottom: 16px; }
        .btn { background: #0B265A; color: #fff; border: 0; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #f1f5f9; }
        .center { text-align: center; }
        .employee { width: 210px; }
        .checkmark { font-size: 18px; font-weight: 700; color: #111827; }
        .emptymark { color: #cbd5e1; }
        .signatures { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-top: 36px; }
        .signature { border-top: 1px solid #111827; padding-top: 6px; text-align: center; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            .toolbar { display: none; }
            body { font-size: 10px; }
        }
    </style>
</head>
<body>
    @php
        $dayNames = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ];
    @endphp
    <div class="toolbar">
        <button class="btn" onclick="window.print()">Imprimir / guardar PDF</button>
    </div>

    <h1>Lista semanal de asistencia</h1>

    <div class="meta">
        <div class="box">
            <div class="label">Obra</div>
            <div>{{ $obra->nombre }}</div>
        </div>
        <div class="box">
            <div class="label">Periodo</div>
            <div>{{ $desde->format('d/m/Y') }} - {{ $hasta->format('d/m/Y') }}</div>
        </div>
        <div class="box">
            <div class="label">Estatus</div>
            <div>{{ \App\Models\ObraAsistenciaSemanalReporte::estatusLabels()[$reporte->estatus] ?? $reporte->estatus }}</div>
        </div>
        <div class="box">
            <div class="label">Generado por</div>
            <div>{{ $generadoPor ?: '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="employee">Empleado</th>
                @foreach($weekDays as $day)
                    @php($dayNumber = \Carbon\Carbon::parse($day['date'])->dayOfWeekIso)
                    <th class="center">{{ $dayNames[$dayNumber] }}<br>{{ $day['label'] }}</th>
                @endforeach
                <th class="center">Total asistencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="employee">
                        <strong>{{ $row->empleado->Nombre }} {{ $row->empleado->Apellidos }}</strong><br>
                        {{ $row->asignacion->puesto_en_obra ?: ($row->empleado->Puesto ?? '') }}
                    </td>
                    @foreach($weekDays as $day)
                        @php($cell = $row->dias[$day['date']])
                        <td class="center">
                            @if($cell['planeado'])
                                <span class="checkmark">&#10003;</span>
                            @else
                                <span class="emptymark">-</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="center">{{ $row->totales['planeados'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature">Residente</div>
        <div class="signature">Aux. pilas / Revision</div>
        <div class="signature">Gerente construccion</div>
        <div class="signature">Administracion</div>
    </div>
</body>
</html>



