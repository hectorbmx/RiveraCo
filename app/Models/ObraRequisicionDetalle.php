<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraRequisicionDetalle extends Model
{
    use HasFactory;

    protected $table = 'obra_requisicion_detalles';

    protected $fillable = [
        'obra_requisicion_id',
        'item_type',
        'item_id',
        'descripcion',
        'descripcion_snapshot',
        'unidad',
        'cantidad_solicitada',
        'cantidad_autorizada',
        'cantidad_surtida',
        'estado',
        'observaciones',
        'equipo_tipo',
        'vehiculo_id',
        'maquina_id',
        'equipo_nombre_snapshot',
        'equipo_identificador_snapshot',
        'surtido_origen_type',
        'surtido_origen_id',
        'inventario_documento_id',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:3',
        'cantidad_autorizada' => 'decimal:3',
        'cantidad_surtida' => 'decimal:3',
    ];

    public function requisicion()
    {
        return $this->belongsTo(ObraRequisicion::class, 'obra_requisicion_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
}
