<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaReciboComision extends Model
{
    protected $table = 'nomina_recibo_comisiones';

    protected $fillable = [
        'recibo_id',
        'corrida_id',
        'comision_id',
        'comision_personal_id',
        'empleado_id',
        'obra_id',
        'fecha_comision',
        'importe_comision',
        'tiempo_extra',
        'rol',
    ];

    protected $casts = [
        'fecha_comision' => 'date',
        'importe_comision' => 'decimal:2',
        'tiempo_extra' => 'decimal:2',
    ];

    public function recibo()
    {
        return $this->belongsTo(NominaRecibo::class, 'recibo_id');
    }

    public function corrida()
    {
        return $this->belongsTo(NominaCorrida::class, 'corrida_id');
    }

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }

    public function comisionPersonal()
    {
        return $this->belongsTo(ComisionPersonal::class, 'comision_personal_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }
}