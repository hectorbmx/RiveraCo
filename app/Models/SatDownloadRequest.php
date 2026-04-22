<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;

class SatDownloadRequest extends Model
{
    protected $table = 'sat_download_requests';

    protected $fillable = [
        'user_id',
        'rfc_solicitante',
        'fecha_inicio',
        'fecha_fin',
        'sat_empresa_id',
        'tipo_descarga',
        'request_id_sat',
        'packages_ids',
        'total_xml',
        'estado',
        'error_message',
    ];

    protected $casts = [
        'packages_ids' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
    public function cfdis()
    {
        return $this->hasMany(SatCfdi::class, 'sat_download_request_id');
    }
    public function empresa()
    {
        return $this->belongsTo(\App\Models\SatEmpresa::class, 'sat_empresa_id');
    }
}