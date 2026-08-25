<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraAsistenciaSemanalReporte extends Model
{
    public const ESTATUS_BORRADOR = 'borrador';
    public const ESTATUS_GENERADO = 'generado';
    public const ESTATUS_REVISADO = 'revisado';
    public const ESTATUS_AUTORIZADO = 'autorizado';
    public const ESTATUS_PAGADO = 'pagado';

    protected $table = 'obra_asistencia_semanal_reportes';

    protected $fillable = [
        'obra_id',
        'semana_inicio',
        'semana_fin',
        'generado_por_user_id',
        'estatus',
        'generado_at',
        'revisado_at',
        'autorizado_at',
        'pagado_at',
        'meta',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
        'generado_at' => 'datetime',
        'revisado_at' => 'datetime',
        'autorizado_at' => 'datetime',
        'pagado_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function estatusLabels(): array
    {
        return [
            self::ESTATUS_BORRADOR => 'Borrador',
            self::ESTATUS_GENERADO => 'Generado',
            self::ESTATUS_REVISADO => 'Revisado',
            self::ESTATUS_AUTORIZADO => 'Autorizado',
            self::ESTATUS_PAGADO => 'Pagado',
        ];
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function detalles()
    {
        return $this->hasMany(ObraAsistenciaSemanalDetalle::class, 'reporte_id');
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por_user_id');
    }
}
