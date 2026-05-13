<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraReposicionGasto extends Model
{
    use HasFactory;

    protected $table = 'obra_reposicion_gastos';

    protected $fillable = [
        'obra_id',
        'tipo_reposicion',
        'partida_id',
        'semana',
        'estatus',
        'observaciones',
        'total',

        'solicitado_por',
        'solicitado_at',

        'revisado_por',
        'revisado_at',

        'aprobado_por',
        'aprobado_at',
        'comentarios_autorizacion',

        'pagado_por',
        'pagado_at',

        'fecha_programada_pago',
        'comentarios_revision',

        'cuenta_banco_empresa_id',
        'metodo_pago_empresa_id',
        'fecha_salida_programada',
        'comentarios_aprovisionamiento',
        'aprovisionado_por',
        'aprovisionado_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'solicitado_at' => 'datetime',
        'revisado_at' => 'datetime',
        'aprobado_at' => 'datetime',
        'pagado_at' => 'datetime',
        'fecha_programada_pago' => 'date',
        'fecha_salida_programada' => 'date',
        'aprovisionado_at' => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function detalles()
    {
        return $this->hasMany(ObraReposicionGastoDetalle::class, 'obra_reposicion_gasto_id');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }
 public function partida()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'partida_id');
    }
    public function aprovisionadoPor()
    {
        return $this->belongsTo(User::class, 'aprovisionado_por');
    }

    public function cuentaBancoEmpresa()
    {
        return $this->belongsTo(CuentaBancoEmpresa::class);
    }

    public function metodoPagoEmpresa()
    {
        return $this->belongsTo(MetodoPagoEmpresa::class);
    }
}