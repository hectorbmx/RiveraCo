<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatDocumentRequest extends Model
{
    public const TYPE_CSF = 'csf';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';

    protected $table = 'sat_document_requests';

   protected $fillable = [
    'sat_empresa_id',
    'type',
    'status',
    'file_path',
    'file_name',
    'mime_type',
    'file_size',
    'captcha_path',
    'captcha_answer',
    'captcha_token',
    'captcha_requested_at',
    'error_message',
    'requested_by',
    'processed_at',
];

   protected $casts = [
    'processed_at' => 'datetime',
    'captcha_requested_at' => 'datetime',
];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(SatEmpresa::class, 'sat_empresa_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}