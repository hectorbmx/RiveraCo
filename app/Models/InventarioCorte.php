<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioCorte extends Model
{
    protected $table = 'inventario_cortes';

    protected $fillable = [
        'almacen_id',
        'fecha_desde',
        'fecha_hasta',
        'titulo',
        'archivo_original',
        'hoja_importada',
        'estado',
        'total_productos',
        'total_con_existencia',
        'valor_total_actual',
        'documento_id',
        'importado_por',
        'aplicado_por',
        'aplicado_at',
        'notas',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'valor_total_actual' => 'decimal:4',
        'aplicado_at' => 'datetime',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioCorteDetalle::class, 'corte_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(InventarioDocumento::class, 'documento_id');
    }

    public function importador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }

    public function aplicador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }
}