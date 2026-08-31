<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReposicionCajaChicaRelacion extends Model
{
    use HasFactory;

    protected $table = 'reposicion_caja_chica_relaciones';

    protected $fillable = [
        'folio',
        'semana_anio',
        'semana_numero',
        'fecha_inicio',
        'fecha_fin',
        'responsable_user_id',
        'area_codigo',
        'almacen_id',
        'estado',
        'fecha_generacion',
        'total_registrado',
        'total_autorizado',
        'total_rechazado',
        'total_pendiente',
        'monto_reposicion',
        'programacion_pago_id',
        'pagado_at',
        'referencia_pago',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_generacion' => 'datetime',
        'total_registrado' => 'decimal:2',
        'total_autorizado' => 'decimal:2',
        'total_rechazado' => 'decimal:2',
        'total_pendiente' => 'decimal:2',
        'monto_reposicion' => 'decimal:2',
        'pagado_at' => 'datetime',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(ReposicionCajaChicaGasto::class, 'relacion_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
