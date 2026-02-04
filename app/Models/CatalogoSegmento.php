<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoSegmento extends Model
{
    protected $table = 'catalogo_segmentos';

    protected $fillable = [
        'nombre',
        'activo',
    ];
}
