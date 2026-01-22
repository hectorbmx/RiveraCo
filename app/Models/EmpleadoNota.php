<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoNota extends Model
{
    protected $table = 'empleado_notas';

    protected $fillable = [
        'empleado_id',
        'user_id',
        'tipo',
        'titulo',
        'descripcion',
        'monto',
        'fecha_evento',
    ];

    protected $casts = [
        'monto'        => 'decimal:2',
        'fecha_evento' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
