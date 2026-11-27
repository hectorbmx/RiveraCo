<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoSubpartida extends Model
{
    protected $table = 'presupuesto_subpartidas';

    protected $fillable = ['concepto', 'unidad', 'cantidad', 'precio_unitario', 'orden'];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(PresupuestoPartida::class, 'presupuesto_partida_id');
    }
}
