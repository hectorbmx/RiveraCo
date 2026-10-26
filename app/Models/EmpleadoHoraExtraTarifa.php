<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoHoraExtraTarifa extends Model
{
    public const MODALIDAD_AUTOMATICA = 'automatica';
    public const MODALIDAD_CUOTA_FIJA = 'cuota_fija';

    protected $table = 'empleado_hora_extra_tarifas';

    protected $fillable = [
        'empleado_id',
        'modalidad',
        'costo_hora_extra',
        'vigente_desde',
        'vigente_hasta',
        'motivo',
        'registrado_por',
    ];

    protected $casts = [
        'costo_hora_extra' => 'decimal:4',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
