<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoFirmaDefinicion extends Model
{
    protected $table = 'documento_firma_definiciones';

    protected $fillable = [
        'documento',
        'documento_label',
        'ambito',
        'ambito_label',
        'campo',
        'campo_label',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query
            ->orderBy('documento_label')
            ->orderBy('ambito_label')
            ->orderBy('orden')
            ->orderBy('campo_label');
    }
}