<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seguro extends Model
{
    use SoftDeletes;

    protected $table = 'seguros';

    protected $fillable = [
        'asegurable_type',
        'asegurable_id',
        'aseguradora',
        'poliza_numero',
        'tipo_seguro',
        'metodo_pago',
        'frecuencia_pago',
        'costo',
        'moneda',
        'fecha_compra',
        'vigencia_desde',
        'vigencia_hasta',
        'suma_asegurada',
        'deducible',
        'cobertura',
        'estatus',
        'alerta_vencimiento_activa',
        'dias_preaviso',
        'ultima_alerta_enviada_at',
        'documento_path',
        'comprobante_path',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
        'ultima_alerta_enviada_at' => 'datetime',
        'costo' => 'decimal:2',
        'suma_asegurada' => 'decimal:2',
        'deducible' => 'decimal:2',
        'alerta_vencimiento_activa' => 'boolean',
        'dias_preaviso' => 'integer',
    ];

    public function asegurable()
    {
        return $this->morphTo();
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}