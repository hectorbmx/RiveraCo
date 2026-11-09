<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraRecepcionDirectaArchivo extends Model
{
    protected $table = 'compra_recepcion_directa_archivos';
    protected $fillable = ['recepcion_id', 'disk', 'path', 'nombre_original', 'mime_type', 'size_bytes', 'hash_sha256', 'uploaded_by'];
    protected $casts = ['size_bytes' => 'integer'];

    public function recepcion(): BelongsTo { return $this->belongsTo(CompraRecepcionDirecta::class, 'recepcion_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
