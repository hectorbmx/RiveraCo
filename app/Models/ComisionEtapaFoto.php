<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionEtapaFoto extends Model
{
    use HasFactory;

    protected $table = 'comision_etapa_fotos';

    protected $fillable = [
        'comision_etapa_id',
        'disk',
        'path',
        'mime_type',
        'size',
        'comentario',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function etapa()
    {
        return $this->belongsTo(ComisionEtapa::class, 'comision_etapa_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
