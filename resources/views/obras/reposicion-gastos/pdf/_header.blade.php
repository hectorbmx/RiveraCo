<table class="header-table">
    <tr>
        <td class="logo-box">
            <div class="logo-title">RIVERA</div>
            <div class="logo-subtitle">CONSTRUCCIONES</div>
        </td>
        <td class="folio-box">
            <div><strong>Folio:</strong> REP-{{ str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div><strong>Fecha impresion:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            <div><strong>Estatus:</strong> {{ strtoupper(str_replace('_', ' ', $reposicion->estatus)) }}</div>
        </td>
    </tr>
</table>

<div class="title">{{ $tituloPdf }}</div>
<div class="blue-line"></div>

<table class="info-table">
    <tr>
        <td width="50%">
            <div class="label">Obra</div>
            <div class="value">{{ $obra->nombre ?? '-' }}</div>
        </td>
        <td width="25%">
            <div class="label">Clave</div>
            <div class="value">{{ $obra->clave_obra ?? '-' }}</div>
        </td>
        <td width="25%">
            <div class="label">Semana</div>
            <div class="value">{{ $reposicion->semana ?? '-' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Solicito / Residente</div>
            <div class="value">{{ $reposicion->solicitadoPor->name ?? '-' }}</div>
        </td>
        <td>
            <div class="label">Fecha solicitud</div>
            <div class="value">{{ optional($reposicion->solicitado_at)->format('d/m/Y H:i') ?? '-' }}</div>
        </td>
        <td>
            <div class="label">Total</div>
            <div class="value">${{ number_format($totalEncabezado ?? $reposicion->total, 2) }}</div>
        </td>
    </tr>
</table>