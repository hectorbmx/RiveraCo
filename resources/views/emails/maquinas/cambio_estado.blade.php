<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #eee; padding: 20px; border-radius: 8px; }
        .header { background: #f8f9fa; padding: 10px; border-bottom: 3px solid #007bff; }
        .badge { padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; }
        .bg-danger { background-color: #dc3545; }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #212529; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Notificación de Cambio de Estad de Maquina</h2>
        </div>
        
        <p>Se ha registrado un cambio en el estado de una maquinaria:</p>
        
        <ul>
            <li><strong>Máquina:</strong> {{ $maquina->nombre }} ({{ $maquina->codigo_interno }})</li>
            <li><strong>Estado Anterior:</strong> {{ strtoupper($anterior) }}</li>
            <li><strong>Estado Nuevo:</strong> <span class="badge {{ $nuevo == 'operativa' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($nuevo) }}</span></li>
            <li><strong>Fecha/Hora:</strong> {{ now()->format('d/m/Y H:i') }}</li>
        </ul>

        @if($motivo)
            <div style="background: #fff3cd; padding: 15px; border-left: 5px solid #ffeeba;">
                <strong>Motivo del cambio:</strong><br>
                {{ $motivo }}
            </div>
        @endif
          @if($notas)
            <div style="background: #fff3cd; padding: 15px; border-left: 5px solid #ffeeba;">
                <strong>Notas adicionales del cambio:</strong><br>
                {{ $notas }}
            </div>
        @endif


        <p style="font-size: 12px; color: #777; margin-top: 30px;">
            Este es un correo automático generado por el Sistema de Gestión Rivera.
        </p>
    </div>
</body>
</html>