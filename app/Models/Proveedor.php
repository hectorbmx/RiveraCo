<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    // La tabla legacy ya tiene created_at/updated_at (aunque sean NULL a veces)
    public $timestamps = true;

    protected $fillable = [
        'legacy_id',
        'nombre',
        'descripcion',
        'rfc',
        'domicilio',
        'telefono',
        'email',
        'banco',
        'clabe',
        'cuenta',
        'activo',
        'fecha_registro',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_registro' => 'date',
        'legacy_id' => 'integer',
    ];

    /**
     * Aliases (para que tu código “nuevo” siga funcionando sin cambiar BD)
     */
    protected $appends = [
        'nombre_comercial',
        'direccion',
        'codigo',
        'razon_social',
        'contacto_nombre',
        'dias_credito',
        'moneda_preferida',
        'notas',
    ];
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_proveedor', 'proveedor_id', 'producto_id')
            ->withPivot([
                'precio_lista',
                'moneda',
                'tiempo_entrega_dias',
                'activo',
                'notas',
            ])
            ->withTimestamps();
    }

    // nombre_comercial <-> nombre
    public function getNombreComercialAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNombreComercialAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }

    // direccion <-> domicilio
    public function getDireccionAttribute(): ?string
    {
        return $this->attributes['domicilio'] ?? null;
    }

    public function setDireccionAttribute($value): void
    {
        $this->attributes['domicilio'] = $value;
    }

    /**
     * codigo:
     * - Si en legacy no existe, lo exponemos como virtual.
     * - Puedes decidir mapearlo a legacy_id o a otra lógica.
     */
    public function getCodigoAttribute(): ?string
    {
        // ejemplo: usa legacy_id como código visual
        $legacyId = $this->attributes['legacy_id'] ?? null;
        return $legacyId ? ('LEG-' . $legacyId) : null;
    }

    public function setCodigoAttribute($value): void
    {
        // No hacemos nada porque no hay columna 'codigo' en legacy.
        // Si quieres mapearlo a legacy_id, aquí lo podemos parsear.
    }

    /**
     * Campos “nuevos” que tu BD legacy no tiene:
     * Los exponemos como NULL o default para no reventar vistas/forms.
     * Más adelante puedes migrarlos si decides.
     */
    public function getRazonSocialAttribute(): ?string { return null; }
    public function setRazonSocialAttribute($value): void { /* noop */ }

    public function getContactoNombreAttribute(): ?string { return null; }
    public function setContactoNombreAttribute($value): void { /* noop */ }

    public function getDiasCreditoAttribute(): int { return 0; }
    public function setDiasCreditoAttribute($value): void { /* noop */ }

    public function getMonedaPreferidaAttribute(): string { return 'MXN'; }
    public function setMonedaPreferidaAttribute($value): void { /* noop */ }

    public function getNotasAttribute(): ?string { return null; }
    public function setNotasAttribute($value): void { /* noop */ }
}
