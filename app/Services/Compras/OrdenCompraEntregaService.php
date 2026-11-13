<?php

namespace App\Services\Compras;

use App\Models\Almacen;
use App\Models\Obra;
use App\Models\OrdenCompra;
use Illuminate\Validation\ValidationException;

class OrdenCompraEntregaService
{
    public function asignar(OrdenCompra $orden, array $data): void
    {
        $modalidad = $data['modalidad_entrega'] ?? $orden->modalidad_entrega ?? OrdenCompra::ENTREGA_ALMACEN;
        $almacenId = array_key_exists('almacen_destino_id', $data)
            ? ($data['almacen_destino_id'] ?: null)
            : $orden->almacen_destino_id;

        if (! in_array($modalidad, [OrdenCompra::ENTREGA_ALMACEN, OrdenCompra::ENTREGA_OBRA_DIRECTA], true)) {
            throw ValidationException::withMessages(['modalidad_entrega' => 'Selecciona una modalidad de entrega válida.']);
        }

        // Clientes anteriores conservan el flujo normal y el valor predeterminado de la base.
        if ($modalidad === OrdenCompra::ENTREGA_ALMACEN
            && ! array_key_exists('modalidad_entrega', $data)
            && ! array_key_exists('almacen_destino_id', $data)) {
            return;
        }

        if ($modalidad === OrdenCompra::ENTREGA_OBRA_DIRECTA) {
            $obraActiva = $orden->obra_id && Obra::query()->whereKey($orden->obra_id)
                ->whereIn('estatus_nuevo', [Obra::ESTATUS_PLANEACION, Obra::ESTATUS_EJECUCION])->exists();
            if (! $obraActiva) {
                throw ValidationException::withMessages(['obra_id' => 'La entrega directa requiere una obra activa como destino de la orden.']);
            }
            if (! $almacenId) {
                throw ValidationException::withMessages(['almacen_destino_id' => 'Selecciona el almacén responsable de la entrega directa.']);
            }
        }

        if ($almacenId) {
            $almacen = Almacen::query()->whereKey($almacenId)->where('activo', true)->first();
            if (! $almacen || ! app(AreaAlmacenCompraService::class)->permite($orden->area_id ? (int) $orden->area_id : null, $almacen->area_id ? (int) $almacen->area_id : null)) {
                throw ValidationException::withMessages(['almacen_destino_id' => 'El almacén debe estar activo y corresponder al área de la orden.']);
            }
        }

        $orden->modalidad_entrega = $modalidad;
        $orden->almacen_destino_id = $almacenId;
    }
}
