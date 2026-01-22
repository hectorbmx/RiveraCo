<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoObra extends Model
{
    protected $table = 'vehiculo_obra';

    protected $fillable = [
        'vehiculo_id',
        'obra_id',
        'empleado_id',
        'fecha_inicio',
        'km_inicio',
        'fecha_fin',
        'km_fin',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }
}
