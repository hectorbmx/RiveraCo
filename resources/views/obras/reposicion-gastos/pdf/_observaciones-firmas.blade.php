<div class="observaciones">
    <div class="observaciones-title">Observaciones:</div>
    <div class="observaciones-body">{{ $reposicion->observaciones ?? '' }}</div>
</div>

<table class="firmas">
    <tr>
        <td>
            <div class="firma-linea">{{ $reposicion->solicitadoPor->name ?? 'Solicitante' }}</div>
            <div class="firma-cargo">Realizo</div>
        </td>
        <td>
            <div class="firma-linea">{{ $reposicion->revisadoPor->name ?? 'Vo. Bo.' }}</div>
            <div class="firma-cargo">Vo. Bo.</div>
        </td>
        <td>
            <div class="firma-linea">{{ $reposicion->aprobadoPor->name ?? 'Reviso / Autorizo' }}</div>
            <div class="firma-cargo">Reviso</div>
        </td>
    </tr>
</table>

<div class="footer">SIRICO - Rivera Construcciones - Documento generado automaticamente</div>