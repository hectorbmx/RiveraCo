<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaquinaDocumento extends Model
{
    protected $table = 'maquina_documentos';

    protected $fillable = [
        'maquina_id',
        'nombre',
        'tipo',
        'descripcion',
        'nombre_original',
        'archivo_path',
        'mime_type',
        'tamano',
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
