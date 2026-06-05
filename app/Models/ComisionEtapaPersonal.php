<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionEtapaPersonal extends Model
{
    use HasFactory;

    protected $table = 'comision_etapa_personal';

    protected $fillable = [
        'comision_etapa_id',
        'obra_empleado_id',
        'empleado_id',
        'rol_id',
        'actividad_id',
        'comisiona',
        'importe_comision',
        'notas',
    ];

    protected $casts = [
        'comisiona' => 'boolean',
        'importe_comision' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function etapa()
    {
        return $this->belongsTo(ComisionEtapa::class, 'comision_etapa_id');
    }

    public function asignacionEmpleado()
    {
        return $this->belongsTo(ObraEmpleado::class, 'obra_empleado_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function rol()
    {
        return $this->belongsTo(CatalogoRol::class, 'rol_id');
    }

    public function actividad()
    {
        return $this->belongsTo(CatalogoActividadComision::class, 'actividad_id');
    }
}
