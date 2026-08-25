<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DocumentoFirmante extends Model
{
    public const DOCUMENTO_ORDEN_COMPRA = 'orden_compra';

    /** @deprecated Usar CAMPO_VOBO_1 */
    public const CAMPO_VOBO     = 'vobo';

    public const CAMPO_VOBO_1   = 'vobo_1';
    public const CAMPO_VOBO_2   = 'vobo_2';
    public const CAMPO_ENTERADO = 'enterado';
    protected $table = 'documento_firmantes';
    protected $fillable = [
        'documento',
        'campo',
        'user_id',
        'activo',
    ];
    protected $casts = [
        'activo' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
