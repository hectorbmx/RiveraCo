<?php

namespace App\Services\Compras;

use App\Models\Area;

class AreaAlmacenCompraService
{
    public function permite(?int $areaOrdenId, ?int $areaAlmacenId): bool
    {
        if (! $areaAlmacenId || $areaOrdenId === $areaAlmacenId) {
            return true;
        }
        if (! $areaOrdenId) {
            return false;
        }

        $codigos = Area::query()->whereIn('id', [$areaOrdenId, $areaAlmacenId])->pluck('codigo', 'id');
        $origen = strtoupper(trim((string) ($codigos[$areaOrdenId] ?? '')));
        $destino = strtoupper(trim((string) ($codigos[$areaAlmacenId] ?? '')));

        return in_array([$origen, $destino], [['PZ', 'HT'], ['PL', 'GL']], true);
    }
}
