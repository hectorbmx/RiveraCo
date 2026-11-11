<?php

namespace App\Services\Obras;

use App\Models\Obra;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObrasConMaterialEnUsoService
{
    /** Las entregas y devoluciones se interpretan con las mismas reglas del finiquito. */
    public function consultar(): Collection
    {
        // Acota las obras candidatas antes de consultar sus saldos y conserva orígenes ambiguos para revisión.
        $generales = DB::table('inventario_movimientos as m')
            ->join('inventario_documentos as d', 'd.id', '=', 'm.documento_id')
            ->whereNotNull('m.obra_id')->whereColumn('d.obra_id', 'm.obra_id')
            ->whereColumn('d.almacen_id', 'm.almacen_id')->where('m.tipo_movimiento', 'out')
            ->where('d.tipo', 'salida')->where('d.estado', 'aplicado')->whereNull('d.deleted_at')
            ->distinct()->pluck('m.obra_id');
        $huentitan = DB::table('huentitan_salidas as s')
            ->join('huentitan_salida_detalles as p', 'p.huentitan_salida_id', '=', 's.id')
            ->whereNotNull('s.obra_id')->where('s.estado', 'aplicada')
            ->whereNotNull('p.producto_id')->where('p.cantidad_salida', '>', 0)
            ->distinct()->pluck('s.obra_id');

        $candidatas = Obra::query()->whereIn('id', $generales->concat($huentitan)->unique())
            ->orderBy('nombre')->get(['id', 'nombre', 'clave_obra']);
        $finiquito = app(FiniquitoMaterialConsultaService::class);

        return $candidatas->filter(function (Obra $obra) use ($finiquito) {
            $resultado = $finiquito->consultar($obra);
            if (count($resultado['origenes_faltantes']) || count($resultado['materiales']['origenes_ambiguos']) || count($resultado['materiales']['inconsistencias'])) {
                return true;
            }

            return $resultado['entregas']->contains(fn ($fila) => $fila['requiere_revision']
                || $fila['entrega']->cantidad_pendiente_evaluacion > 0
                || (float) $fila['entrega']->cantidad - $fila['entrega']->cantidad_devuelta_confirmada > 0.0005);
        })->values();
    }
}
