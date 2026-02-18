<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NominaCorrida extends Model
{
    use HasFactory;

    protected $table = 'nomina_corridas';

    protected $fillable = [
        'tipo_pago',
        'subtipo',
        'periodo_label',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'status',
        'notas',
        'created_by',
        'closed_by',
        'closed_at',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'fecha_pago'   => 'date',
        'closed_at'    => 'datetime',
        'paid_at'      => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function recibos()
    {
        return $this->hasMany(NominaRecibo::class, 'corrida_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cerrador()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de estado
    |--------------------------------------------------------------------------
    */

    public function estaAbierta(): bool
    {
        return $this->status === 'abierta';
    }

    public function estaCerrada(): bool
    {
        return $this->status === 'cerrada';
    }

    public function estaPagada(): bool
    {
        return $this->status === 'pagada';
    }
}
