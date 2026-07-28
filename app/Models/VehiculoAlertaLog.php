<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoAlertaLog extends Model
{
    protected $table = 'vehiculo_alerta_logs';

    protected $fillable = [
        'vehiculo_id',
        'tipo_alerta',
        'estado',
        'km_actual',
        'km_proximo_servicio',
        'km_restantes',
        'hash_contexto',
        'correos_enviados',
        'notificaciones_enviadas',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
