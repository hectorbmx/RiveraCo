<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiraldaAsistencia extends Model
{
    protected $table = 'giralda_asistencias';

    protected $fillable = [
        'empleado_id',
        'area_id',
        'fecha',
        'estado',
        'origen',
        'entrada_at',
        'salida_at',
        'attendance_device_id',
        'attendance_enroll_id',
        'registrado_por',
        'actualizado_por',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'entrada_at' => 'datetime',
        'salida_at' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function attendanceDevice()
    {
        return $this->belongsTo(AttendanceDevice::class, 'attendance_device_id');
    }
}
