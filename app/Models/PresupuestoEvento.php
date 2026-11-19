<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoEvento extends Model
{
    public const UPDATED_AT = null;
    public const GUARDADO = 'guardado';
    public const ACEPTADO = 'aceptado';
    public const TIPOS_SEGUIMIENTO = [
        'enviado' => 'Enviado',
        'cambios_solicitados' => 'Cambios solicitados',
        self::ACEPTADO => 'Aceptado',
        'rechazado' => 'Rechazado',
    ];

    protected $table = 'presupuesto_eventos';

    protected $fillable = ['registrado_por', 'tipo', 'nota'];

    public function etiqueta(): string
    {
        return self::TIPOS_SEGUIMIENTO[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PresupuestoVersion::class, 'presupuesto_version_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
