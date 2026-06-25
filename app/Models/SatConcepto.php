<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatConcepto extends Model
{
    protected $table = 'sat_conceptos';

    protected $fillable = [

        'codigo',

        'clave_producto_servicio',
        'clave_unidad',

        'descripcion',
        'unidad',

        'objeto_impuesto',

        'iva_tasa',
        'incluye_iva',

        'precio_unitario',

        'activo',

        'observaciones',
    ];

    protected $casts = [
        'incluye_iva' => 'boolean',
        'activo' => 'boolean',

        'iva_tasa' => 'decimal:6',
        'precio_unitario' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getIvaPorcentajeAttribute(): float
    {
        return (float) $this->iva_tasa * 100;
    }
    public function facturaBorradores()
    {
        return $this->hasMany(ObraFacturaBorrador::class, 'sat_concepto_id');
    }
}
