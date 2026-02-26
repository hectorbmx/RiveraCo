<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionTarifario extends Model
{
    protected $table = 'comision_tarifarios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'vigente_desde',
        'vigente_hasta',
        'created_by',
        'published_by',
        'published_at',
    ];

    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PUBLICADO = 'publicado';
    const ESTADO_ARCHIVADO = 'archivado';

    public static function estados()
    {
        return [
            self::ESTADO_BORRADOR,
            self::ESTADO_PUBLICADO,
            self::ESTADO_ARCHIVADO,
        ];
    }

    protected $casts = [
        'vigente_desde' => 'datetime',
        'vigente_hasta' => 'datetime',
        'published_at'  => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(ComisionTarifarioDetalle::class, 'comision_tarifario_id');
    }
}
