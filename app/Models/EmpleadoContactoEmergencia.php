<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoContactoEmergencia extends Model
{
    protected $table = 'empleado_contactos_emergencia';

    protected $fillable = [
        'empleado_id',
        'nombre',
        'parentesco',
        'telefono',
        'celular',
        'es_principal',
        'notas',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }
}
