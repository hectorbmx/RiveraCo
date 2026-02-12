<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGerencialController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Proyectos activos (mismo criterio que acordamos)
        $proyectosActivos = (int) DB::table('obras')
            ->whereIn('estatus_nuevo', [2, 3])
            ->count();

        // ✅ Empleados presentes (AJUSTA el criterio según tu negocio)
        // Si NO tienes "presentes", por ahora puedes devolver "activos en obra"
        $empleadosActivosEnObra = (int) DB::table('obra_empleado')
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->count();

        // ✅ Maquinaria en uso (usa tu tabla obra_maquina + scope "activas" equivalente)
        // Ajusta condiciones: aquí asumo "fecha_fin is null" como activa
        $maquinariaEnUso = (int) DB::table('obra_maquina')
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
                'proyectos_activos' => $proyectosActivos,
                'empleados_presentes' => $empleadosActivosEnObra, // renómbralo si quieres precisión
                'maquinaria_en_uso' => $maquinariaEnUso,
                'inventario_stock_pct' => $inventarioStockPct,
            ],
        ]);
    }
}
