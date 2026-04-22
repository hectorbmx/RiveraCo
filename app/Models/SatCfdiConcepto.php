<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatCfdiConcepto extends Model
{
    protected $table = 'sat_cfdi_conceptos';

    protected $fillable = [
        'sat_cfdi_id',
        'clave_prod_serv',
        'no_identificacion',
        'cantidad',
        'clave_unidad',
        'unidad',
        'descripcion',
        'valor_unitario',
        'importe',
        'descuento',
        'objeto_impuesto',
        'informacion_aduanera_json',
        'cuenta_predial_json',
        'parte_json',
        'complemento_concepto_json',
        'meta_json',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'valor_unitario' => 'decimal:6',
        'importe' => 'decimal:6',
        'descuento' => 'decimal:6',
        'informacion_aduanera_json' => 'array',
        'cuenta_predial_json' => 'array',
        'parte_json' => 'array',
        'complemento_concepto_json' => 'array',
        'meta_json' => 'array',
    ];

    public function cfdi(): BelongsTo
    {
        return $this->belongsTo(SatCfdi::class, 'sat_cfdi_id');
    }
}