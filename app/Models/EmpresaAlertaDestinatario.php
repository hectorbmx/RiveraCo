<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmpresaAlertaDestinatario extends Model
{
    protected $table = 'empresa_alerta_destinatarios';

    protected $fillable = [
        'empresa_config_id',
        'modulo',
        'user_id',
        'nombre',
        'email',
        'notificar_correo',
        'notificar_sistema',
        'activo',
    ];

    protected $casts = [
        'notificar_correo' => 'boolean',
        'notificar_sistema' => 'boolean',
        'activo' => 'boolean',
    ];

    public function empresaConfig()
    {
        return $this->belongsTo(EmpresaConfig::class, 'empresa_config_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeModulo(Builder $query, string $modulo): Builder
    {
        return $query->where('modulo', $modulo);
    }
}
