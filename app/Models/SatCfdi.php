<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SatCfdiConcepto;
use App\Models\SatCfdiPago;

class SatCfdi extends Model
{
    protected $table = 'sat_cfdis';

    protected $fillable = [
        'sat_download_request_id',
        'uuid',
        'version',
        'serie',
        'folio',
        'rfc_emisor',
        'rfc_receptor',
        'emisor_rfc',
        'emisor_nombre',
        'emisor_regimen_fiscal',
        'receptor_rfc',
        'receptor_nombre',
        'receptor_domicilio_fiscal',
        'receptor_regimen_fiscal',
        'receptor_uso_cfdi',
        'fecha_emision',
        'tipo_comprobante',
        'subtotal',
        'descuento',
        'total',
        'moneda',
        'tipo_cambio',
        'forma_pago',
        'metodo_pago',
        'lugar_expedicion',
        'exportacion',
        'no_certificado',
        'certificado',
        'sello',
        'xml_path',
        'package_id',
        'obra_id',
        'orden_compra_id',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:6',
        'descuento' => 'decimal:6',
        'total' => 'decimal:6',
        'tipo_cambio' => 'decimal:6',
    ];

    public function downloadRequest()
    {
        return $this->belongsTo(SatDownloadRequest::class, 'sat_download_request_id');
    }
    public function conceptos(): HasMany
    {
        return $this->hasMany(SatCfdiConcepto::class, 'sat_cfdi_id');
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function pagos()
    {
        return $this->hasMany(SatCfdiPago::class, 'sat_cfdi_id');
    }
    public function totalPagado()
{
    return $this->pagos()
        ->where('estatus', 'activo')
        ->sum('monto');
}

public function saldoPendiente()
{
    return round((float) $this->total - (float) $this->totalPagado(), 2);
}

public function estaPagada()
{
    return $this->saldoPendiente() <= 0;
}

public function estadoPago()
{
    $totalPagado = (float) $this->totalPagado();
    $saldo = (float) $this->saldoPendiente();

    if ($saldo <= 0) {
        return 'pagada';
    }

    if ($totalPagado > 0) {
        return 'parcial';
    }

    return 'sin_pago';
}

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }
    public function programaciones()
    {
        return $this->hasMany(SatCfdiProgramacion::class, 'sat_cfdi_id');
    }
}