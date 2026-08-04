<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoEppEntrega extends Model
{
    protected $table = 'empleado_epp_entregas';

    protected $fillable = [
        'empleado_id',
        'articulo',
        'cantidad',
        'talla',
        'fecha_entrega',
        'condicion',
        'obra_area',
        'entregado_por',
        'observaciones',
        'confirmado_por_empleado',
        'fecha_confirmacion',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'fecha_entrega' => 'date',
        'confirmado_por_empleado' => 'boolean',
        'fecha_confirmacion' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function entregadoPor()
    {
        return $this->belongsTo(User::class, 'entregado_por');
    }
}
