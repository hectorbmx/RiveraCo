<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoCategoria extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function planos()
    {
        return $this->hasMany(ObraPlano::class);
    }
}
