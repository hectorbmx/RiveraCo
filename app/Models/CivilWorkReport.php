<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilWorkReport extends Model
{
    use HasFactory;

    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_EN_REVISION = 'en_revision';
    public const STATUS_APROBADO = 'aprobado';
    public const STATUS_RECHAZADO = 'rechazado';
    public const STATUS_CONVERTIDO_A_ESTIMACION = 'convertido_a_estimacion';

    protected $fillable = [
        'obra_id',
        'user_id',
        'empleado_id',
        'status',
        'notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected $casts = [
        'empleado_id' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(CivilWorkReportItem::class, 'civil_work_report_id');
    }
}
