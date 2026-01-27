<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraAsistencia extends Model
{
    protected $table = 'obras_asistencias';

    protected $fillable = [
        'obra_id',
        'empleado_id',
        'registrado_por_user_id',
        'tipo',
        'checked_at',
        'checked_date',
        'photo_path',
        'lat',
        'lng',
        'ubicacion_texto',
        'meta',
        'deleted_by_user_id',
        'delete_reason',
    ];

    protected $casts = [
        'checked_at'   => 'datetime',
        'checked_date' => 'date',
        'meta'         => 'array',
    ];
    public function empleado()
    {
        // Tu PK legacy en empleados es id_Empleado
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}

