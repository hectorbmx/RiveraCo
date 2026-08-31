<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoFirmante extends Model
{
    public const DOCUMENTO_ORDEN_COMPRA = 'orden_compra';
    public const DOCUMENTO_REPOSICION_CAJA_CHICA = 'reposicion_caja_chica';

    public const AMBITO_GENERAL = 'general';
    public const AMBITO_REPOSICION_GASTOS_ALMACEN = 'reposicion_gastos_almacen';
    public const AMBITO_GIRALDA = 'giralda';

    public const CAMPO_ELABORO = 'elaboro';
    public const CAMPO_VOBO = 'vobo';
    public const CAMPO_AUTORIZO = 'autorizo';
    public const CAMPO_VOBO_1 = 'vobo_1';
    public const CAMPO_VOBO_2 = 'vobo_2';
    public const CAMPO_ENTERADO = 'enterado';

    protected $table = 'documento_firmantes';

    protected $fillable = [
        'documento',
        'ambito',
        'campo',
        'user_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
