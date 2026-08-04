<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horas extra Giralda</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { color: #0B265A; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #e2e8f0; }
        .right { text-align: right; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 36px; margin-top: 54px; }
        .line { border-top: 1px solid #111827; padding-top: 6px; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimir</button>
    <h1>Formato de horas extra Giralda</h1>
    <div>Periodo: {{ $desde ?: '-' }} a {{ $hasta ?: '-' }}</div>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Hora inicial</th>
                <th>Hora final</th>
                <th class="right">Total</th>
                <th>Motivo</th>
                <th>Solicita</th>
                <th>Autoriza</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $registro)
                <tr>
                    <td>{{ $registro->empleado?->nombre_completo }}</td>
                    <td>{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                    <td>{{ substr($registro->hora_inicio, 0, 5) }}</td>
                    <td>{{ substr($registro->hora_fin, 0, 5) }}</td>
                    <td class="right">{{ number_format((float)$registro->total_horas, 2) }}</td>
                    <td>{{ $registro->motivo }}</td>
                    <td>{{ $registro->responsable_solicita }}</td>
                    <td>{{ $registro->responsable_autoriza }}</td>
                    <td>{{ $registro->observaciones }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="line">Solicita</div>
        <div class="line">Autoriza</div>
        <div class="line">Recibe administracion</div>
    </div>
</body>
</html>
