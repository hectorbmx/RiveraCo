<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $table = 'uoms';

    protected $fillable = [
        'clave',
        'nombre',
        'simbolo',
        'tipo',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];
}
