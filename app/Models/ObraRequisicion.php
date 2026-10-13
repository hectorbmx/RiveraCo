<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraRequisicion extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    public const ESTADO_AUTORIZADA = 'autorizada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_EN_SURTIDO = 'en_surtido';
    public const ESTADO_SURTIDA_PARCIAL = 'surtida_parcial';
    public const ESTADO_SURTIDA = 'surtida';
    public const ESTADO_RECIBIDA = 'recibida';
    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'obra_requisiciones';

    protected $fillable = [
        'obra_id',
        'empresa_requisicion_tipo_id',
        'obra_requisicion_folio_id',
        'folio',
        'anio',
        'numero_catalogo',
        'consecutivo',
        'estado',
        'fecha_solicitud',
        'fecha_requerida',
        'solicitado_por',
        'solicitado_at',
        'autorizado_por',
        'autorizado_at',
        'rechazado_por',
        'rechazado_at',
        'surtido_por',
        'surtido_at',
        'recibido_por',
        'recibido_at',
        'observaciones',
        'motivo_rechazo',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'anio' => 'integer',
        'consecutivo' => 'integer',
        'fecha_solicitud' => 'date',
        'fecha_requerida' => 'date',
        'solicitado_at' => 'datetime',
        'autorizado_at' => 'datetime',
        'rechazado_at' => 'datetime',
        'surtido_at' => 'datetime',
        'recibido_at' => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function tipo()
    {
        return $this->belongsTo(EmpresaRequisicionTipo::class, 'empresa_requisicion_tipo_id');
    }

    public function folioControl()
    {
        return $this->belongsTo(ObraRequisicionFolio::class, 'obra_requisicion_folio_id');
    }

    public function detalles()
    {
        return $this->hasMany(ObraRequisicionDetalle::class, 'obra_requisicion_id');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function rechazadoPor()
    {
        return $this->belongsTo(User::class, 'rechazado_por');
    }

    public function surtidoPor()
    {
        return $this->belongsTo(User::class, 'surtido_por');
    }

    public function recibidoPor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }
}
