<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionEtapa extends Model
{
    use HasFactory;

    public const ETAPA_PERFORACION = 'perforacion';
    public const ETAPA_BENTONITA = 'bentonita';
    public const ETAPA_ADEME = 'ademe';
    public const ETAPA_ACERO = 'acero';
    public const ETAPA_COLADO = 'colado';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_NO_APLICA = 'no_aplica';

    protected $table = 'comision_etapas';

    protected $fillable = [
        'comision_id',
        'obra_id',
        'pila_id',
        'etapa',
        'estado',
        'orden',
        'hora_inicio',
        'hora_fin',
        'observaciones',
        'requiere_foto',
        'completada_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requiere_foto' => 'boolean',
        'completada_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function pila()
    {
        return $this->belongsTo(ObraPila::class, 'pila_id');
    }

    public function personal()
    {
        return $this->hasMany(ComisionEtapaPersonal::class, 'comision_etapa_id');
    }

    public function fotos()
    {
        return $this->hasMany(ComisionEtapaFoto::class, 'comision_etapa_id');
    }
}
