<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaquinaFoto extends Model
{
    protected $table = 'maquina_fotos';

    protected $fillable = [
        'maquina_id',
        'ruta',
        'descripcion',
        'uploaded_by',
    ];

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
