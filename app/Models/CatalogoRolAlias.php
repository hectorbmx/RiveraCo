<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoRolAlias extends Model
{
    protected $table = 'catalogo_roles_alias';

    protected $fillable = [
        'rol_id',
        'alias',
    ];

    public $timestamps = true;

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function rol()
    {
        return $this->belongsTo(CatalogoRol::class, 'rol_id');
    }
}
