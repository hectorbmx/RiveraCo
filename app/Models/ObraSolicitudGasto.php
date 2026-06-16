<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraSolicitudGasto extends Model
{
    use HasFactory;

    protected $table = 'obra_solicitud_gastos';

    protected $fillable = [
        'obra_id',
        'semana',
        'estatus',
        'total',
        'solicitado_por',
        'solicitado_at',
        'autorizado_por',
        'autorizado_at',
        'pagado_por',
        'pagado_at',
        'observaciones',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'solicitado_at' => 'datetime',
        'autorizado_at' => 'datetime',
        'pagado_at' => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function detalles()
    {
        return $this->hasMany(ObraSolicitudGastoDetalle::class);
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }
}
