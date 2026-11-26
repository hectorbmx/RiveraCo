<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\ObraEmpleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGerencialController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Proyectos activos (mismo criterio que acordamos)
        $obrasActivas = (int) DB::table('obras')
            ->whereIn('estatus_nuevo', [1, 2])
            ->count();

        $empleadosActivosEnObra = $this->asignacionesActivas()->count();

        // ✅ Maquinaria en uso (usa tu tabla obra_maquina + scope "activas" equivalente)
        // Ajusta condiciones: aquí asumo "fecha_fin is null" como activa
        $maquinariaEnUso = (int) DB::table('obra_maquina')
            ->where('estado', 'activa')
            ->whereNull('fecha_fin')
            ->count();

        // ✅ Inventario: si ya tienes inventario_stock, puedes calcular "stock_pct"
        // Como no tenemos regla exacta, lo dejo conservador:
        // - si tienes mínimos: pct = productos con stock>0 / total productos en stock
        // Ajusta a tu regla real.
        $inventarioTotal = (int) DB::table('inventario_stock')->count();
        $inventarioConStock = (int) DB::table('inventario_stock')
            ->where('stock_actual', '>', 0) // ajusta si tu columna es distinta
            ->count();

        $inventarioStockPct = $inventarioTotal > 0
            ? (int) round(($inventarioConStock / $inventarioTotal) * 100)
            : 0;

        return response()->json([
            'ok' => true,
            'data' => [
                'proyectos_activos' => $obrasActivas,
                'obras_activas' => $obrasActivas,
                'empleados_presentes' => $empleadosActivosEnObra, // renómbralo si quieres precisión
                'maquinaria_en_uso' => $maquinariaEnUso,
                'inventario_stock_pct' => $inventarioStockPct,
            ],
        ]);
    }

    public function empleadosEnObra()
    {
        $obras = $this->asignacionesActivas()
            ->with([
                'obra' => fn ($obra) => $obra->withTrashed()->select('id', 'nombre'),
                'empleado:id_Empleado,Nombre,Apellidos',
            ])
            ->get(['id', 'obra_id', 'empleado_id'])
            ->groupBy('obra_id')
            ->map(fn ($asignaciones, $obraId) => [
                'id' => $obraId,
                'nombre' => $asignaciones->first()->obra->nombre,
                'empleados' => $asignaciones->sortBy('empleado.nombre_completo', SORT_NATURAL | SORT_FLAG_CASE)
                    ->map(fn (ObraEmpleado $asignacion) => [
                        'id' => $asignacion->id,
                        'nombre' => $asignacion->empleado->nombre_completo,
                    ])->values(),
            ])
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['ok' => true, 'data' => $obras]);
    }

    private function asignacionesActivas(): Builder
    {
        return ObraEmpleado::query()->where('activo', 1)->whereNull('fecha_baja');
    }
}
