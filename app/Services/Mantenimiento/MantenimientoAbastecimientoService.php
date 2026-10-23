<?php

namespace App\Services\Mantenimiento;

use App\Models\Almacen;
use App\Models\EmpresaServicioPreventivoMaterial;
use App\Models\InventarioStock;
use App\Models\Mantenimiento;
use App\Models\MantenimientoAbastecimiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MantenimientoAbastecimientoService
{
    public function calcular(Mantenimiento $mantenimiento): Collection
    {
        if (!$mantenimiento->maquina_id) {
            return collect();
        }

        $tieneSnapshot = $mantenimiento->materialesOrden()->exists();

        if ($tieneSnapshot) {
            $materialesOrden = $mantenimiento->materialesOrden()
                ->where('incluido', true)
                ->get()
                ->map(fn ($material) => [
                    'material_preventivo_id' => $material->material_preventivo_id,
                    'producto_id' => $material->producto_id,
                    'producto' => $material->producto_nombre_snapshot,
                    'sku' => $material->producto_sku_snapshot,
                    'unidad' => $material->unidad,
                    'requerido' => (float) $material->cantidad_requerida,
                ]);
        } else {
            if (!$mantenimiento->servicio_preventivo_tipo_id) {
                return collect();
            }

            $materiales = EmpresaServicioPreventivoMaterial::query()
                ->where('servicio_preventivo_tipo_id', $mantenimiento->servicio_preventivo_tipo_id)
                ->where('activo', true)
                ->where(function ($query) use ($mantenimiento) {
                    $query->whereNull('maquina_id')
                        ->orWhere('maquina_id', $mantenimiento->maquina_id);
                })
                ->with('producto:id,nombre,sku,unidad')
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            $especificos = $materiales->where('maquina_id', $mantenimiento->maquina_id);
            $materialesEfectivos = $especificos->isNotEmpty()
                ? $especificos
                : $materiales->whereNull('maquina_id');

            $materialesOrden = $materialesEfectivos->values()->map(fn ($material) => [
                'material_preventivo_id' => $material->id,
                'producto_id' => $material->producto_id,
                'producto' => $material->producto?->nombre ?? 'Producto no disponible',
                'sku' => $material->producto?->sku,
                'unidad' => $material->unidad ?: $material->producto?->unidad,
                'requerido' => (float) $material->cantidad_requerida,
            ]);
        }

        $abastecimientos = $mantenimiento->abastecimientos()
            ->with([
                'detalles:id,abastecimiento_id,producto_id,cantidad_atendida',
                'inventarioDocumentos' => fn ($query) => $query
                    ->where('tipo', 'salida')
                    ->where('estado', 'aplicado')
                    ->select('id', 'folio', 'mantenimiento_abastecimiento_id'),
            ])
            ->get();

        $surtidoPorProducto = $abastecimientos
            ->flatMap(fn ($abastecimiento) => $abastecimiento->detalles)
            ->groupBy('producto_id')
            ->map(fn ($detalles) => (float) $detalles->sum('cantidad_atendida'));

        $foliosSalida = $abastecimientos
            ->flatMap(fn ($abastecimiento) => $abastecimiento->inventarioDocumentos)
            ->pluck('folio')
            ->filter()
            ->unique()
            ->values();

        $almacen = Almacen::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(codigo) = ?', ['gl'])
                    ->orWhereRaw('LOWER(nombre) like ?', ['%giralda%']);
            })
            ->orderByRaw("CASE WHEN LOWER(codigo) = 'gl' THEN 0 ELSE 1 END")
            ->first();

        $stock = $almacen
            ? InventarioStock::query()
                ->where('almacen_id', $almacen->id)
                ->get()
                ->keyBy('producto_id')
            : collect();

        return $materialesOrden->map(function (array $material) use ($stock, $surtidoPorProducto, $foliosSalida) {
            $stockRow = $stock->get($material['producto_id']);
            $requerido = $material['requerido'];
            $disponible = max(0, (float) ($stockRow?->stock_actual ?? 0) - (float) ($stockRow?->stock_reservado ?? 0));
            $surtible = min($requerido, $disponible);
            $surtido = min($requerido, (float) $surtidoPorProducto->get($material['producto_id'], 0));
            $faltante = max(0, $requerido - $surtido);

            $estado = $faltante <= 0
                ? 'surtido'
                : ($surtible >= $faltante ? 'disponible' : ($surtible > 0 ? 'parcial' : 'sin_existencia'));

            return [
                'material_preventivo_id' => $material['material_preventivo_id'],
                'producto_id' => $material['producto_id'],
                'producto' => $material['producto'],
                'sku' => $material['sku'],
                'unidad' => $material['unidad'],
                'requerido' => $requerido,
                'surtido' => $surtido,
                'disponible' => $disponible,
                'surtible' => $surtible,
                'faltante' => $faltante,
                'folios_salida' => $foliosSalida->all(),
                'estado' => $estado,
            ];
        });
    }

    public function crearSolicitud(Mantenimiento $mantenimiento, string $tipo, ?int $usuarioId = null): MantenimientoAbastecimiento
    {
        if (!$mantenimiento->orden_servicio_folio || $mantenimiento->estatus === 'borrador') {
            throw new \RuntimeException('Primero debes generar la orden de servicio antes de solicitar material.');
        }

        if ($mantenimiento->estatus === 'completado') {
            throw new \RuntimeException('El servicio ya fue ejecutado y no admite nuevas solicitudes de material.');
        }

        $tipo = $tipo === MantenimientoAbastecimiento::TIPO_COMPRA
            ? MantenimientoAbastecimiento::TIPO_COMPRA
            : MantenimientoAbastecimiento::TIPO_ALMACEN;

        $materiales = $this->calcular($mantenimiento);
        if ($materiales->isNotEmpty() && $materiales->every(fn (array $material) => (float) $material['faltante'] <= 0.0005)) {
            throw new \RuntimeException('La necesidad de materiales de este servicio ya fue surtida. No se puede generar otra solicitud.');
        }

        $existente = $mantenimiento->abastecimientos()
            ->where('tipo', $tipo)
            ->whereNotIn('estado', ['surtida', 'cancelada'])
            ->latest('id')
            ->first();

        if ($existente) {
            return $existente;
        }

        if ($materiales->isEmpty()) {
            throw new \RuntimeException('El servicio no tiene materiales configurados para generar una solicitud.');
        }

        $materialesPendientes = $materiales
            ->filter(fn (array $material) => (float) $material['faltante'] > 0.0005)
            ->values();

        if ($materialesPendientes->isEmpty()) {
            throw new \RuntimeException('La necesidad de materiales de este servicio ya fue surtida. No se puede generar otra solicitud.');
        }

        return DB::transaction(function () use ($mantenimiento, $tipo, $usuarioId, $materialesPendientes) {
            $solicitud = $mantenimiento->abastecimientos()->create([
                'tipo' => $tipo,
                'estado' => 'pendiente',
                'created_by' => $usuarioId,
            ]);

            $solicitud->detalles()->createMany($materialesPendientes->map(fn (array $material) => [
                'producto_id' => $material['producto_id'],
                'material_preventivo_id' => $material['material_preventivo_id'],
                'producto_nombre_snapshot' => $material['producto'],
                'producto_sku_snapshot' => $material['sku'],
                'unidad' => $material['unidad'],
                // Una solicitud posterior representa solamente el faltante.
                'cantidad_requerida' => $material['faltante'],
                'cantidad_solicitada' => $material['faltante'],
                'cantidad_atendida' => 0,
                'estado' => 'pendiente',
            ])->all());

            $mantenimiento->update([
                'abastecimiento_tipo' => $tipo === MantenimientoAbastecimiento::TIPO_COMPRA ? Mantenimiento::ABASTECIMIENTO_COMPRA : Mantenimiento::ABASTECIMIENTO_SURTIDO_DIRECTO,
                'abastecimiento_estado' => $tipo === MantenimientoAbastecimiento::TIPO_COMPRA ? 'solicitud_compra' : 'solicitud_almacen',
                'estatus' => 'pendiente',
            ]);

            return $solicitud->fresh(['detalles', 'mantenimiento']);
        });
    }
}
