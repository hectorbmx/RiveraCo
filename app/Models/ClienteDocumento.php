<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'cliente_documentos';

    protected $fillable = [
        'cliente_id',
        'documento_tipo_id',
        'tipo_documento',
        'nombre_documento',
        'archivo_path',
        'archivo_nombre_original',
        'mime_type',
        'extension',
        'tamano_bytes',
        'fecha_documento',
        'fecha_vencimiento',
        'vigente',
        'estatus_validacion',
        'validado_por',
        'validado_en',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'fecha_vencimiento' => 'date',
        'vigente' => 'boolean',
        'validado_en' => 'datetime',
        'tamano_bytes' => 'integer',
    ];

    public const ESTATUS_VALIDACION = [
        'pendiente',
        'validado',
        'rechazado',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function documentoTipo()
    {
        return $this->belongsTo(EmpresaDocumentoTipo::class, 'documento_tipo_id');
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVigentes($query)
    {
        return $query->where('vigente', true);
    }

    public function getNombreMostrarAttribute()
    {
        return $this->nombre_documento ?: str_replace('_', ' ', $this->tipo_documento);
    }
}