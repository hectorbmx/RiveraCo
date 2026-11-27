<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoPartida extends Model
{
    protected $table = 'presupuesto_partidas';

    protected $fillable = ['nombre', 'orden'];

    protected $casts = ['orden' => 'integer'];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function subpartidas(): HasMany
    {
        return $this->hasMany(PresupuestoSubpartida::class)->orderBy('orden')->orderBy('id');
    }
}
