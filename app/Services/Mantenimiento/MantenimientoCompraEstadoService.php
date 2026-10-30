<?php

namespace App\Services\Mantenimiento;

use App\Models\MantenimientoAbastecimiento;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class MantenimientoCompraEstadoService
{
    public function sincronizar(OrdenCompra $orden): void
    {
        if (! $orden->mantenimiento_abastecimiento_id) {
            return;
        }

        DB::transaction(function () use ($orden) {
            $solicitud = MantenimientoAbastecimiento::query()
                ->whereKey($orden->mantenimiento_abastecimiento_id)
                ->where('tipo', MantenimientoAbastecimiento::TIPO_COMPRA)
                ->lockForUpdate()->first();

            // La autorización no debe retroceder una recepción o un surtido.
            if (! $solicitud || ! in_array($solicitud->estado, ['pendiente', 'parcial', 'en_compra'], true)) {
                return;
            }

            $autorizada = OrdenCompra::query()
                ->where('mantenimiento_abastecimiento_id', $solicitud->id)
                ->whereIn('estado', ['AUTORIZADA', 'APROBADA', 'VERIFICADA'])->exists();

            $solicitud->update(['estado' => $autorizada ? 'en_compra'
                : ($solicitud->estado === 'parcial' ? 'parcial' : 'pendiente')]);
        });
    }
}
