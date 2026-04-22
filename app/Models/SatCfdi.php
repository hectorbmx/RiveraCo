<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SatCfdiConcepto;

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
}