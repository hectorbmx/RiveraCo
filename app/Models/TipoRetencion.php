<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model representing a retention type (Tipo Retención).
 *
 * This model follows SOLID principles:
 *  - Single Responsibility: only represents the "tipo_retencion" table.
 *  - Open/Closed: can be extended via traits or services without modifying core.
 *  - Liskov Substitution: extends base Model, behaves like any other Eloquent model.
 *  - Interface Segregation: consumers can depend on the specific methods they need.
 *  - Dependency Inversion: higher‑level modules should depend on abstractions (e.g., a RetencionService) rather than this concrete model.
 */
class TipoRetencion extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tipos_retencion';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'nombre',        // string, description of the retention type
        'porcentaje',    // decimal(5,2), percentage to apply
        'activo',        // boolean, whether the retention is active
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo'     => 'boolean',
    ];
}
