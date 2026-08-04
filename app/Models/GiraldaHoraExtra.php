<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiraldaHoraExtra extends Model
{
    protected $table = 'giralda_horas_extras';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas',
        'motivo',
        'responsable_solicita',
        'responsable_autoriza',
        'autorizado_por',
        'fecha_autorizacion',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_horas' => 'decimal:2',
        'fecha_autorizacion' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
