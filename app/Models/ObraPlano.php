<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraPlano extends Model
{
    protected $fillable = [
        'obra_id',
        'plano_categoria_id',
        'nombre',
        'version',
        'archivo_path',
        'notas',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function categoria()
    {
        return $this->belongsTo(PlanoCategoria::class, 'plano_categoria_id');
    }
}
