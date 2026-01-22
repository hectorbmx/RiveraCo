<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoRol extends Model
{
    protected $table = 'catalogo_roles';

    protected $fillable = [
        'nombre',
        'rol_key',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Empleados asignados a obra con este rol
    public function obrasEmpleados()
    {
        return $this->hasMany(ObraEmpleado::class, 'rol_id');
    }

    // Personal en comisiones con este rol
    public function comisionesPersonal()
    {
        return $this->hasMany(ComisionPersonal::class, 'rol_id');
    }

    // Alias históricos (texto legacy)
    public function alias()
    {
        return $this->hasMany(CatalogoRolAlias::class, 'rol_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes útiles
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
