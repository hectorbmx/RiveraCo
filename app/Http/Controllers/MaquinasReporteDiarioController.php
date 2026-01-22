<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Maquina;
use App\Models\MaquinaReporteDiario;
use App\Models\MaquinariaReporteSnapshot;
use App\Models\MaquinariaReporteSnapshotItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class MaquinasReporteDiarioController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->input('fecha')
            ? Carbon::parse($request->input('fecha'))->toDateString()
            : now()->toDateString();

           
        $rolResidenteId = DB::table('catalogo_roles')
        ->where('rol_key', 'RESIDENTE')
        ->value('id');

        $pilasProgramadasPorObra = DB::table('obras_pilas')
        ->select('obra_id', DB::raw('SUM(cantidad_programada) as pilas_programadas'))
        ->groupBy('obra_id');

        $pilasEjecutadasPorObra = DB::table('comisiones as c')
                ->join('comision_detalles as d', 'd.comision_id', '=', 'c.id')
                ->whereDate('c.fecha', '<=', $fecha)
                ->select(
                    'c.obra_id',
                    DB::raw('SUM(COALESCE(d.cantidad,0)) as pilas_ejecutadas'),
                    DB::raw("
                        GROUP_CONCAT(
                            DISTINCT NULLIF(TRIM(c.observaciones), '')
                            ORDER BY c.fecha ASC, c.id ASC
                            SEPARATOR ' | '
                        ) as observaciones
                    ")
                )
                ->groupBy('c.obra_id');



        $cobradoPorObra = DB::table('obras_facturas')
                ->where('estado', 'pagada') // ajusta si tu valor real es "pagada"/"PAGADA"/"pagado"
                ->select('obra_id', DB::raw('SUM(COALESCE(monto,0)) as monto_cobrado'))
                ->groupBy('obra_id');

        $asignaciones = DB::table('maquinas as m')
            ->leftJoin('obra_maquina as om', function ($join) {
                $join->on('om.maquina_id', '=', 'm.id')
                    ->where('om.estado', '=', 'activa');
            })
            ->leftJoin('obras as o', 'o.id', '=', 'om.obra_id')
            ->leftJoinSub($cobradoPorObra, 'co', function ($join) {
                    $join->on('co.obra_id', '=', 'o.id');
                })

            ->leftJoin('clientes as c', 'c.id', '=', 'o.cliente_id')
            ->leftJoinSub($pilasProgramadasPorObra, 'pp', function ($join) {
                    $join->on('pp.obra_id', '=', 'o.id');
                })
                ->leftJoinSub($pilasEjecutadasPorObra, 'pe', function ($join) {
                    $join->on('pe.obra_id', '=', 'o.id');
                })

            ->leftJoin('obras_pilas as op', 'op.obra_id', '=', 'o.id')
            //agregar la columna de la hora inicial

            ->leftJoin('obra_empleado as oe', function ($join) use ($rolResidenteId) {
                $join->on('oe.obra_id', '=', 'o.id')
                    ->where('oe.activo', '=', 1)
                    ->whereNull('oe.fecha_baja')
                    ->where('oe.rol_id', '=', $rolResidenteId);
            })

            ->leftJoin('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
            ->select([
                'om.id as obra_maquina_id',
                'om.obra_id',
                'm.id as maquina_id',
                'om.fecha_inicio',
                'om.horometro_inicio',
                'om.horometro_fin',
                
                DB::raw("MAX(COALESCE(pe.observaciones, '')) as observaciones"),

                

                // Si no hay obra: “LA GIRALDA”
                DB::raw('COALESCE(o.monto_contratado,0) as total_obra'),
                DB::raw('COALESCE(co.monto_cobrado,0) as monto_cobrado'),
                DB::raw("
                    CASE
                        WHEN COALESCE(o.monto_contratado,0) = 0 THEN 0
                        ELSE ROUND((COALESCE(co.monto_cobrado,0) / o.monto_contratado) * 100, 0)
                    END as pago_pct
                "),

                DB::raw("COALESCE(c.nombre_comercial, '—') as cliente_nombre"),
                DB::raw('COALESCE(pp.pilas_programadas, 0) as pilas_programadas'),
                DB::raw('COALESCE(pe.pilas_ejecutadas, 0) as pilas_ejecutadas'),
                DB::raw("
                    CASE
                        WHEN COALESCE(pp.pilas_programadas, 0) = 0 THEN 0
                        ELSE ROUND((COALESCE(pe.pilas_ejecutadas, 0) / pp.pilas_programadas) * 100, 0)
                    END as avance_global_pct
                "),

                DB::raw("COALESCE(o.nombre, 'LA GIRALDA') as obra_nombre"),
                'o.cliente_id',
                DB::raw("COALESCE(CONCAT(e.nombre,' ',IFNULL(e.Apellidos,'')), '') as residente_nombre"),
                // DB::raw('COALESCE(SUM(op.cantidad_programada), 0) as pilas_programadas'),

                        'm.nombre as maquina_nombre',
                        'm.codigo as maquina_codigo',
                        'm.placas',
                        'm.color',
                        'm.horometro_base',
                        'm.estado as maquina_estado',
            ])
   
            ->selectSub(function ($q) {
                    $q->from('obra_maquina_registros as omr')
                    ->selectRaw('COALESCE(SUM(omr.horas),0)')
                    ->whereColumn('omr.obra_maquina_id', 'om.id');
                }, 'horas_trabajadas')
                ->selectSub(function ($q) {
                    $q->from('obra_maquina_registros as omr')
                    ->select('omr.horometro_fin')
                    ->whereColumn('omr.obra_maquina_id', 'om.id')
                    ->orderByDesc('omr.fin')
                    ->orderByDesc('omr.id')
                    ->limit(1);
                }, 'horometro_actual')


                    
            // Orden: primero las que sí tienen obra, luego las de almacén
            ->orderByRaw('CASE WHEN o.nombre IS NULL THEN 1 ELSE 0 END')
            ->orderBy('o.nombre')
            ->orderBy('m.nombre')
            ->groupBy([
                'om.id',
                'om.obra_id',
                'm.id',
                'om.fecha_inicio',
                'om.horometro_inicio',
                'om.horometro_fin',
                'o.nombre',
                'o.cliente_id',
                'e.nombre',
                'e.Apellidos',
                'm.nombre',
                'm.codigo',
                'm.placas',
                'm.color',
                'm.horometro_base',
                'm.estado',
                'pilas_programadas',
                'pilas_ejecutadas',
                'c.nombre_comercial',
                'o.monto_contratado',
                'co.monto_cobrado',
                
            ])

            ->get();

            $obraIds = $asignaciones
                ->pluck('obra_id')
                ->filter()     // quita null (almacén)
                ->unique()
                ->values();

            $equipoPorObra = collect();

            if ($obraIds->isNotEmpty()) {
                $equipoPorObra = DB::table('obra_empleado as oe')
                    ->join('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
                    ->whereIn('oe.obra_id', $obraIds->all())
                    ->where('oe.activo', 1)
                    ->whereNull('oe.fecha_baja')
                    ->where('oe.rol_id', '!=', $rolResidenteId) // excluir residente
                    ->orderBy('e.nombre')
                    ->get([
                        'oe.obra_id',
                        DB::raw("CONCAT(e.nombre,' ',e.Apellidos,' ',IFNULL(e.Apellidos,'')) as nombre"),
                        'oe.rol_id',
                    ])
                    ->groupBy('obra_id');
            }

        /**
         * Traer observaciones del día (si existen) en batch.
         */
        $mrdHoy = MaquinaReporteDiario::query()
            ->where('fecha', $fecha)
            ->get()
            ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

        /**
         * Traer últimas observaciones previas (fallback) por (obra, maquina).
         * Estrategia simple y eficiente:
         * 1) tomar llaves únicas de asignaciones
         * 2) consultar el último registro < fecha para esas llaves
         */
        $keys = $asignaciones
            ->map(fn($a) => $a->obra_id.'-'.$a->maquina_id)
            ->unique()
            ->values();

        // Si no hay asignaciones, retornamos vacío
        if ($keys->isEmpty()) {
            return view('reportes.maquinaria.reporte_diario', [
                'fecha' => $fecha,
                'rows'  => collect(),
            ]);
        }
// dd($asignaciones->take(5)->map(fn($a) => [
//     'obra' => $a->obra_nombre,
//     'programadas' => $a->pilas_programadas,
//     'ejecutadas' => $a->pilas_ejecutadas,
//     'pct' => $a->avance_global_pct,
// ]));


        // Query de últimos registros previos por obra+maquina (en SQL)
        $prev = MaquinaReporteDiario::query()
            ->select(['obra_id', 'maquina_id', 'fecha', 'observaciones'])
            ->where('fecha', '<', $fecha)
            ->whereIn(DB::raw("CONCAT(obra_id,'-',maquina_id)"), $keys->all())
            ->orderBy('fecha', 'desc')
            ->get()
            ->unique(fn($r) => $r->obra_id.'-'.$r->maquina_id)
            ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

        /**
         * Armar filas para la vista.
         */
        $rows = $asignaciones->map(function ($a) use ($fecha, $mrdHoy, $prev, $equipoPorObra) {
            $k = $a->obra_id.'-'.$a->maquina_id;

            $hoy = $mrdHoy->get($k);
            $last = $prev->get($k);

            return [
                'fecha' => $fecha,
                'residente_nombre' => $a->residente_nombre ?: null,
                'observaciones' => $a->observaciones ?: null,

                'obra_id' => $a->obra_id,
                'obra_nombre' => $a->obra_nombre,
                'cliente_id' => $a->cliente_id,
                'maquina_id' => $a->maquina_id,
                'maquina_nombre' => $a->maquina_nombre,
                'maquina_codigo' => $a->maquina_codigo,
                'horometro_inicio' => $a->horometro_inicio,
                'horas_trabajadas'      => (float) ($a->horas_trabajadas ?? 0),
                'placas' => $a->placas,
                'color' => $a->color,

                'horometro_actual' => $a->horometro_actual ?? null,
                'horas_trabajadas' => (float) ($a->horas_trabajadas ?? 0),

                
                'pilas_programadas'  => (int) ($a->pilas_programadas ?? 0),
                'pilas_ejecutadas'   => (int) ($a->pilas_ejecutadas ?? 0),
                'avance_global_pct'  => (int) ($a->avance_global_pct ?? 0),
                // 'avance_global_pct' => min((int) ($r['avance_global_pct'] ?? 0), 100),

                'cliente_nombre' => $a->cliente_nombre ?: null,
                'total_obra'    => (float) ($a->total_obra ?? 0),
                'monto_cobrado' => (float) ($a->monto_cobrado ?? 0),
                'pago_pct'      => (int)   ($a->pago_pct ?? 0),


                // Horómetros base / inicio obra (para cálculo posterior en la vista)
                'horometro_base' => $a->horometro_base,
                'horometro_inicio_obra' => $a->horometro_inicio,
                'horometro_fin_obra' => $a->horometro_fin,

                // Observaciones: si hoy existe, usarlo; si no, precargar el último
                
                'equipo' => $a->obra_id ? ($equipoPorObra[$a->obra_id] ?? collect()) : collect(),

                // Estado para pintar UI
                'ya_guardado_hoy' => (bool) $hoy,
            ];
        });
//    que

// dd($asignaciones->take(10)->pluck('obra_id', 'obra_nombre'), $asignaciones->take(10)->pluck('observaciones'));
$view = $request->boolean('embed')
    ? 'reportes.maquinaria.reporte_diario_embed'
    : 'reportes.maquinaria.reporte_diario';

        return view($view, [
    'fecha' => $fecha,
    'rows'  => $rows,
]);
    }

    //guarda snapshot del reporte diario cargado en la vista index
//     public function storeSnapshot(Request $request)
// {
//     $fecha = $request->input('fecha')
//         ? Carbon::parse($request->input('fecha'))->toDateString()
//         : now()->toDateString();

//     DB::beginTransaction();

//     try {
//         // 1) Reusar el armado "en vivo" del index para esa fecha.
//         //    Para no duplicar lógica, crea buildReporteRows($fecha) (paso 4.3).
//         $rows = $this->buildReporteRows($fecha);

//         // 2) Cabecera snapshot (1 por fecha)
//         $snapshot = MaquinariaReporteSnapshot::query()->firstOrCreate(
//             ['fecha' => $fecha],
//             [
//                 'estado' => 'abierto',
//                 'total_maquinas' => 0,
//                 'created_by' => Auth::id(),
//                 'updated_by' => Auth::id(),
//             ]
//         );

//         // Si ya existía
//         $snapshot->updated_by = Auth::id();
//         $snapshot->save();

//         // 3) Items: upsert por (snapshot_id, maquina_id)
//         $now = now();

//         $itemsPayload = $rows->map(function ($r) use ($snapshot, $now) {

//             // equipo: la vista trae Collection de objetos con ->nombre
//             $equipoArr = [];
//             if (!empty($r['equipo']) && is_iterable($r['equipo'])) {
//                 foreach ($r['equipo'] as $p) {
//                     if (isset($p->nombre)) $equipoArr[] = $p->nombre;
//                 }
//             }

//             return [
//                 'snapshot_id' => $snapshot->id,
//                 'maquina_id'  => $r['maquina_id'],

//                 'obra_id'         => $r['obra_id'],
//                 'obra_maquina_id' => $r['obra_maquina_id'] ?? null,
//                 'cliente_id'      => $r['cliente_id'],

//                 'obra_nombre'      => $r['obra_nombre'] ?? null,
//                 'cliente_nombre'   => $r['cliente_nombre'] ?? null,
//                 'residente_nombre' => $r['residente_nombre'] ?? null,

//                 'pilas_programadas' => (int) ($r['pilas_programadas'] ?? 0),
//                 'pilas_ejecutadas'  => (int) ($r['pilas_ejecutadas'] ?? 0),
//                 'avance_global_pct' => (int) ($r['avance_global_pct'] ?? 0),

//                 'horometro_inicio_obra' => $r['horometro_inicio_obra'] ?? null,
//                 'horometro_actual'      => $r['horometro_actual'] ?? null,
//                 'horas_trabajadas'      => (float) ($r['horas_trabajadas'] ?? 0),

//                 'total_obra'    => (float) ($r['total_obra'] ?? 0),
//                 'monto_cobrado' => (float) ($r['monto_cobrado'] ?? 0),
//                 'pago_pct'      => (int)   ($r['pago_pct'] ?? 0),

//                 'maquina_nombre' => $r['maquina_nombre'] ?? null,
//                 'maquina_codigo' => $r['maquina_codigo'] ?? null,
//                 'placas'         => $r['placas'] ?? null,
//                 'color'          => $r['color'] ?? null,
//                 'horometro_base' => $r['horometro_base'] ?? null,
//                 'maquina_estado' => $r['maquina_estado'] ?? null,

//                 'equipo' => $equipoArr, // cast array en el modelo

//                 // En tu view esto se llama 'observaciones'
//                 'observaciones_comisiones' => $r['observaciones'] ?? null,

//                 // No seteamos observaciones_snapshot aquí (regla: no pisar)
//                 'created_by' => Auth::id(),
//                 'updated_by' => Auth::id(),
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ];
//         })->values()->all();

//         MaquinariaReporteSnapshotItem::query()->upsert(
//             $itemsPayload,
//             ['snapshot_id', 'maquina_id'],
//             [
//                 'obra_id','obra_maquina_id','cliente_id',
//                 'obra_nombre','cliente_nombre','residente_nombre',
//                 'pilas_programadas','pilas_ejecutadas','avance_global_pct',
//                 'horometro_inicio_obra','horometro_actual','horas_trabajadas',
//                 'total_obra','monto_cobrado','pago_pct',
//                 'maquina_nombre','maquina_codigo','placas','color','horometro_base','maquina_estado',
//                 'equipo',
//                 'observaciones_comisiones',
//                 'updated_by','updated_at'
//             ]
//         );

//         // 4) Precargar observaciones_snapshot solo si está NULL o ''
//         MaquinariaReporteSnapshotItem::query()
//             ->where('snapshot_id', $snapshot->id)
//             ->where(function ($q) {
//                 $q->whereNull('observaciones_snapshot')
//                   ->orWhere('observaciones_snapshot', '=', '');
//             })
//             ->update([
//                 'observaciones_snapshot' => DB::raw('observaciones_comisiones'),
//                 'updated_by' => Auth::id(),
//                 'updated_at' => $now,
//             ]);

//         // 5) Totales cabecera
//         $snapshot->total_maquinas = $rows->count();
//         $snapshot->save();

//         DB::commit();

//         return redirect()
//             ->back()
//             ->with('success', 'Snapshot guardado correctamente para la fecha '.$fecha.'.');

//     } catch (\Throwable $e) {
//         DB::rollBack();
//         report($e);

//         return redirect()
//             ->back()
//             ->withErrors(['snapshot' => 'No se pudo guardar el snapshot. Revisa el log.']);
//     }
// }
public function storeSnapshot(Request $request)
{

   $fecha = Carbon::parse(
            $request->input('fecha', now())
        )->toDateString();


    DB::beginTransaction();

    try {

        // =========================
        // BLOQUE COPIADO DE index()
        // (armado de $rows "en vivo")
        // =========================

        $rolResidenteId = DB::table('catalogo_roles')
            ->where('rol_key', 'RESIDENTE')
            ->value('id');

        $pilasProgramadasPorObra = DB::table('obras_pilas')
            ->select('obra_id', DB::raw('SUM(cantidad_programada) as pilas_programadas'))
            ->groupBy('obra_id');

        $pilasEjecutadasPorObra = DB::table('comisiones as c')
            ->join('comision_detalles as d', 'd.comision_id', '=', 'c.id')
            ->whereDate('c.fecha', '<=', $fecha)
            ->select(
                'c.obra_id',
                DB::raw('SUM(COALESCE(d.cantidad,0)) as pilas_ejecutadas'),
                DB::raw("
                    GROUP_CONCAT(
                        DISTINCT NULLIF(TRIM(c.observaciones), '')
                        ORDER BY c.fecha ASC, c.id ASC
                        SEPARATOR ' | '
                    ) as observaciones
                ")
            )
            ->groupBy('c.obra_id');

        $cobradoPorObra = DB::table('obras_facturas')
            ->where('estado', 'pagada')
            ->select('obra_id', DB::raw('SUM(COALESCE(monto,0)) as monto_cobrado'))
            ->groupBy('obra_id');

        $asignaciones = DB::table('maquinas as m')
            ->leftJoin('obra_maquina as om', function ($join) {
                $join->on('om.maquina_id', '=', 'm.id')
                    ->where('om.estado', '=', 'activa');
            })
            ->leftJoin('obras as o', 'o.id', '=', 'om.obra_id')
            ->leftJoinSub($cobradoPorObra, 'co', function ($join) {
                $join->on('co.obra_id', '=', 'o.id');
            })
            ->leftJoin('clientes as c', 'c.id', '=', 'o.cliente_id')
            ->leftJoinSub($pilasProgramadasPorObra, 'pp', function ($join) {
                $join->on('pp.obra_id', '=', 'o.id');
            })
            ->leftJoinSub($pilasEjecutadasPorObra, 'pe', function ($join) {
                $join->on('pe.obra_id', '=', 'o.id');
            })
            ->leftJoin('obras_pilas as op', 'op.obra_id', '=', 'o.id')
            ->leftJoin('obra_empleado as oe', function ($join) use ($rolResidenteId) {
                $join->on('oe.obra_id', '=', 'o.id')
                    ->where('oe.activo', '=', 1)
                    ->whereNull('oe.fecha_baja')
                    ->where('oe.rol_id', '=', $rolResidenteId);
            })
            ->leftJoin('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
            ->select([
                'om.id as obra_maquina_id',
                'om.obra_id',
                'm.id as maquina_id',
                'om.fecha_inicio',
                'om.horometro_inicio',
                'om.horometro_fin',

                DB::raw("MAX(COALESCE(pe.observaciones, '')) as observaciones"),

                DB::raw('COALESCE(o.monto_contratado,0) as total_obra'),
                DB::raw('COALESCE(co.monto_cobrado,0) as monto_cobrado'),
                DB::raw("
                    CASE
                        WHEN COALESCE(o.monto_contratado,0) = 0 THEN 0
                        ELSE ROUND((COALESCE(co.monto_cobrado,0) / o.monto_contratado) * 100, 0)
                    END as pago_pct
                "),

                DB::raw("COALESCE(c.nombre_comercial, '—') as cliente_nombre"),
                DB::raw('COALESCE(pp.pilas_programadas, 0) as pilas_programadas'),
                DB::raw('COALESCE(pe.pilas_ejecutadas, 0) as pilas_ejecutadas'),
                DB::raw("
                    CASE
                        WHEN COALESCE(pp.pilas_programadas, 0) = 0 THEN 0
                        ELSE ROUND((COALESCE(pe.pilas_ejecutadas, 0) / pp.pilas_programadas) * 100, 0)
                    END as avance_global_pct
                "),

                DB::raw("COALESCE(o.nombre, 'LA GIRALDA') as obra_nombre"),
                'o.cliente_id',
                DB::raw("COALESCE(CONCAT(e.nombre,' ',IFNULL(e.Apellidos,'')), '') as residente_nombre"),

                'm.nombre as maquina_nombre',
                'm.codigo as maquina_codigo',
                'm.placas',
                'm.color',
                'm.horometro_base',
                'm.estado as maquina_estado',
            ])
            ->selectSub(function ($q) {
                $q->from('obra_maquina_registros as omr')
                    ->selectRaw('COALESCE(SUM(omr.horas),0)')
                    ->whereColumn('omr.obra_maquina_id', 'om.id');
            }, 'horas_trabajadas')
            ->selectSub(function ($q) {
                $q->from('obra_maquina_registros as omr')
                    ->select('omr.horometro_fin')
                    ->whereColumn('omr.obra_maquina_id', 'om.id')
                    ->orderByDesc('omr.fin')
                    ->orderByDesc('omr.id')
                    ->limit(1);
            }, 'horometro_actual')
            ->orderByRaw('CASE WHEN o.nombre IS NULL THEN 1 ELSE 0 END')
            ->orderBy('o.nombre')
            ->orderBy('m.nombre')
            ->groupBy([
                'om.id',
                'om.obra_id',
                'm.id',
                'om.fecha_inicio',
                'om.horometro_inicio',
                'om.horometro_fin',
                'o.nombre',
                'o.cliente_id',
                'e.nombre',
                'e.Apellidos',
                'm.nombre',
                'm.codigo',
                'm.placas',
                'm.color',
                'm.horometro_base',
                'm.estado',
                'pilas_programadas',
                'pilas_ejecutadas',
                'c.nombre_comercial',
                'o.monto_contratado',
                'co.monto_cobrado',
            ])
            ->get();
\Log::info('SNAPSHOT: asignaciones obtenidas', [
    'count' => $asignaciones->count(),
]);

        $obraIds = $asignaciones
            ->pluck('obra_id')
            ->filter()
            ->unique()
            ->values();

        $equipoPorObra = collect();

        if ($obraIds->isNotEmpty()) {
            $equipoPorObra = DB::table('obra_empleado as oe')
                ->join('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
                ->whereIn('oe.obra_id', $obraIds->all())
                ->where('oe.activo', 1)
                ->whereNull('oe.fecha_baja')
                ->where('oe.rol_id', '!=', $rolResidenteId)
                ->orderBy('e.nombre')
                ->get([
                    'oe.obra_id',
                    DB::raw("CONCAT(e.nombre,' ',e.Apellidos,' ',IFNULL(e.Apellidos,'')) as nombre"),
                    'oe.rol_id',
                ])
                ->groupBy('obra_id');
        }

        $mrdHoy = MaquinaReporteDiario::query()
            ->where('fecha', $fecha)
            ->get()
            ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

        $keys = $asignaciones
            ->map(fn($a) => $a->obra_id.'-'.$a->maquina_id)
            ->unique()
            ->values();

        // Si no hay asignaciones, no guardamos snapshot
        if ($keys->isEmpty()) {
            DB::rollBack();
            return redirect()->back()->withErrors(['snapshot' => 'No hay máquinas activas para guardar snapshot en '.$fecha.'.']);
        }

        $prev = MaquinaReporteDiario::query()
            ->select(['obra_id', 'maquina_id', 'fecha', 'observaciones'])
            ->where('fecha', '<', $fecha)
            ->whereIn(DB::raw("CONCAT(obra_id,'-',maquina_id)"), $keys->all())
            ->orderBy('fecha', 'desc')
            ->get()
            ->unique(fn($r) => $r->obra_id.'-'.$r->maquina_id)
            ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

        $rows = $asignaciones->map(function ($a) use ($fecha, $mrdHoy, $prev, $equipoPorObra) {
            $k = $a->obra_id.'-'.$a->maquina_id;

            $hoy = $mrdHoy->get($k);

            return [
                'fecha' => $fecha,
                'residente_nombre' => $a->residente_nombre ?: null,

                // OJO: esto es lo rojo de tu vista (observaciones de comisiones)
                'observaciones' => $a->observaciones ?: null,

                'obra_id' => $a->obra_id,
                'obra_maquina_id' => $a->obra_maquina_id, // <-- CLAVE PARA SNAPSHOT
                'obra_nombre' => $a->obra_nombre,
                'cliente_id' => $a->cliente_id,

                'maquina_id' => $a->maquina_id,
                'maquina_nombre' => $a->maquina_nombre,
                'maquina_codigo' => $a->maquina_codigo,

                'horometro_inicio' => $a->horometro_inicio,
                'horometro_actual' => $a->horometro_actual ?? null,
                'horas_trabajadas' => (float) ($a->horas_trabajadas ?? 0),

                'placas' => $a->placas,
                'color' => $a->color,

                'pilas_programadas'  => (int) ($a->pilas_programadas ?? 0),
                'pilas_ejecutadas'   => (int) ($a->pilas_ejecutadas ?? 0),
                'avance_global_pct'  => (int) ($a->avance_global_pct ?? 0),

                'cliente_nombre' => $a->cliente_nombre ?: null,
                'total_obra'    => (float) ($a->total_obra ?? 0),
                'monto_cobrado' => (float) ($a->monto_cobrado ?? 0),
                'pago_pct'      => (int)   ($a->pago_pct ?? 0),

                'horometro_base' => $a->horometro_base,
                'horometro_inicio_obra' => $a->horometro_inicio,
                'horometro_fin_obra' => $a->horometro_fin,

                'maquina_estado' => $a->maquina_estado ?? null,

                'equipo' => $a->obra_id ? ($equipoPorObra[$a->obra_id] ?? collect()) : collect(),

                'ya_guardado_hoy' => (bool) $hoy,
            ];
        });

        // =========================
        // FIN BLOQUE "EN VIVO"
        // =========================

        // 2) Cabecera snapshot por fecha
        $snapshot = MaquinariaReporteSnapshot::query()->firstOrCreate(
            ['fecha' => $fecha],
            [
                'estado' => 'abierto',
                'total_maquinas' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        $snapshot->updated_by = Auth::id();
        $snapshot->save();
        \Log::info('SNAPSHOT: cabecera OK', [
    'snapshot_id' => $snapshot->id,
]);


        // 3) Upsert items
        $now = now();

        $itemsPayload = $rows->map(function ($r) use ($snapshot, $now) {

            // equipo: convertir Collection de objetos a array de nombres
            $equipoArr = [];
            $equipo = $r['equipo'] ?? null;
            if ($equipo && is_iterable($equipo)) {
                foreach ($equipo as $p) {
                    if (isset($p->nombre) && $p->nombre !== '') {
                        $equipoArr[] = $p->nombre;
                    }
                }
            }
                $equipoJson = json_encode($equipoArr, JSON_UNESCAPED_UNICODE);
                if ($equipoJson === false) {
                    $equipoJson = '[]';
                }

            return [
                'snapshot_id' => $snapshot->id,
                'maquina_id'  => $r['maquina_id'],

                'obra_id'         => $r['obra_id'],
                'obra_maquina_id' => $r['obra_maquina_id'] ?? null,
                'cliente_id'      => $r['cliente_id'],

                'obra_nombre'      => $r['obra_nombre'] ?? null,
                'cliente_nombre'   => $r['cliente_nombre'] ?? null,
                'residente_nombre' => $r['residente_nombre'] ?? null,

                'pilas_programadas' => (int) ($r['pilas_programadas'] ?? 0),
                'pilas_ejecutadas'  => (int) ($r['pilas_ejecutadas'] ?? 0),
                'avance_global_pct' => (int) ($r['avance_global_pct'] ?? 0),

                'horometro_inicio_obra' => $r['horometro_inicio_obra'] ?? null,
                'horometro_actual'      => $r['horometro_actual'] ?? null,
                'horas_trabajadas'      => (float) ($r['horas_trabajadas'] ?? 0),

                'total_obra'    => (float) ($r['total_obra'] ?? 0),
                'monto_cobrado' => (float) ($r['monto_cobrado'] ?? 0),
                'pago_pct'      => (int) ($r['pago_pct'] ?? 0),

                'maquina_nombre' => $r['maquina_nombre'] ?? null,
                'maquina_codigo' => $r['maquina_codigo'] ?? null,
                'placas'         => $r['placas'] ?? null,
                'color'          => $r['color'] ?? null,
                'horometro_base' => $r['horometro_base'] ?? null,
                'maquina_estado' => $r['maquina_estado'] ?? null,

                // 'equipo' => $equipoArr,
                'equipo' => $equipoJson,


                // En tu vista es 'observaciones'
                'observaciones_comisiones' => $r['observaciones'] ?? null,

                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();
        MaquinariaReporteSnapshotItem::query()->upsert(
            $itemsPayload,
            ['snapshot_id', 'maquina_id'],
            [
                'obra_id','obra_maquina_id','cliente_id',
                'obra_nombre','cliente_nombre','residente_nombre',
                'pilas_programadas','pilas_ejecutadas','avance_global_pct',
                'horometro_inicio_obra','horometro_actual','horas_trabajadas',
                'total_obra','monto_cobrado','pago_pct',
                'maquina_nombre','maquina_codigo','placas','color','horometro_base','maquina_estado',
                'equipo',
                'observaciones_comisiones',
                'updated_by','updated_at'
            ]
        );


        // 4) Precargar observaciones_snapshot SOLO si está vacío
        MaquinariaReporteSnapshotItem::query()
            ->where('snapshot_id', $snapshot->id)
            ->where(function ($q) {
                $q->whereNull('observaciones_snapshot')
                  ->orWhere('observaciones_snapshot', '=', '');
            })
            ->update([
                'observaciones_snapshot' => DB::raw('observaciones_comisiones'),
                'updated_by' => Auth::id(),
                'updated_at' => $now,
            ]);


        // 5) Totales cabecera
        $snapshot->total_maquinas = $rows->count();
        $snapshot->save();

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Snapshot guardado correctamente para la fecha '.$fecha.'.');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
  
        return redirect()
            ->back()
            ->withErrors(['snapshot' => $e->getMessage()]);
    }
}


public function historial(Request $request)
{
    $fecha = $request->input('fecha')
        ? Carbon::parse($request->input('fecha'))->toDateString()
        : now()->toDateString();

    $snapshot = MaquinariaReporteSnapshot::query()
        ->whereDate('fecha', $fecha)
        ->first();

    if (!$snapshot) {
        // crea snapshot + items (sin redirect)
        $snapshot = $this->generarSnapshotParaFecha($fecha);
    }

    $items = MaquinariaReporteSnapshotItem::query()
        ->where('snapshot_id', $snapshot->id)
        ->orderBy('obra_nombre')
        ->orderBy('maquina_nombre')
        ->get();

    // armamos $rows en formato compatible con tu tabla actual
    $rows = $items->map(function ($it) use ($fecha) {
        return [
            'fecha' => $fecha,

            'obra_id' => $it->obra_id,
            'obra_nombre' => $it->obra_nombre,
            'cliente_id' => $it->cliente_id,
            'cliente_nombre' => $it->cliente_nombre,
            'residente_nombre' => $it->residente_nombre,

            'maquina_id' => $it->maquina_id,
            'maquina_nombre' => $it->maquina_nombre,
            'maquina_codigo' => $it->maquina_codigo,
            'placas' => $it->placas,
            'color' => $it->color,

            'horometro_inicio' => $it->horometro_inicio_obra,
            'horas_trabajadas' => (float) ($it->horas_trabajadas ?? 0),

            'pilas_programadas' => (int) ($it->pilas_programadas ?? 0),
            'pilas_ejecutadas'  => (int) ($it->pilas_ejecutadas ?? 0),
            'avance_global_pct' => (int) ($it->avance_global_pct ?? 0),

            'total_obra'    => (float) ($it->total_obra ?? 0),
            'monto_cobrado' => (float) ($it->monto_cobrado ?? 0),
            'pago_pct'      => (int)   ($it->pago_pct ?? 0),

            'observaciones' => $it->observaciones_snapshot ?? $it->observaciones_comisiones,

            'equipo' => collect($it->equipo ?? []),

            'ya_guardado_hoy' => true,
        ];
    });

    return view('reportes.maquinaria.historial', [
        'fecha' => $fecha,
        'rows' => $rows,
        'snapshot' => $snapshot,
    ]);
}


private function generarSnapshotParaFecha(string $fecha): MaquinariaReporteSnapshot
{
    DB::beginTransaction();

    try {
        // IMPORTANTE:
        // Aquí necesitamos $rows "en vivo" para esa fecha.
        // En el siguiente paso vamos a crear buildRowsParaSnapshot($fecha)
        // copiando la lógica de tu index.
        $rows = $this->buildRowsParaSnapshot($fecha);

        $snapshot = MaquinariaReporteSnapshot::query()->firstOrCreate(
            ['fecha' => $fecha],
            [
                'estado' => 'abierto',
                'total_maquinas' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        $snapshot->updated_by = Auth::id();
        $snapshot->save();

        $now = now();

        $itemsPayload = $rows->map(function ($r) use ($snapshot, $now) {
            return [
                'snapshot_id' => $snapshot->id,
                'maquina_id'  => $r['maquina_id'],

                'obra_id'         => $r['obra_id'],
                'obra_maquina_id' => $r['obra_maquina_id'] ?? null,
                'cliente_id'      => $r['cliente_id'],

                'obra_nombre'      => $r['obra_nombre'] ?? null,
                'cliente_nombre'   => $r['cliente_nombre'] ?? null,
                'residente_nombre' => $r['residente_nombre'] ?? null,

                'pilas_programadas' => (int) ($r['pilas_programadas'] ?? 0),
                'pilas_ejecutadas'  => (int) ($r['pilas_ejecutadas'] ?? 0),
                'avance_global_pct' => (int) ($r['avance_global_pct'] ?? 0),

                'horometro_inicio_obra' => $r['horometro_inicio_obra'] ?? null,
                'horometro_actual'      => $r['horometro_actual'] ?? null,
                'horas_trabajadas'      => (float) ($r['horas_trabajadas'] ?? 0),

                'total_obra'    => (float) ($r['total_obra'] ?? 0),
                'monto_cobrado' => (float) ($r['monto_cobrado'] ?? 0),
                'pago_pct'      => (int)   ($r['pago_pct'] ?? 0),

                'maquina_nombre' => $r['maquina_nombre'] ?? null,
                'maquina_codigo' => $r['maquina_codigo'] ?? null,
                'placas'         => $r['placas'] ?? null,
                'color'          => $r['color'] ?? null,
                'horometro_base' => $r['horometro_base'] ?? null,
                'maquina_estado' => $r['maquina_estado'] ?? null,

                // IMPORTANTÍSIMO: equipo debe ser string JSON (no array/collection)
                'equipo' => isset($r['equipo']) ? json_encode($r['equipo']) : null,

                'observaciones_comisiones' => $r['observaciones'] ?? null,

                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('maquinaria_reporte_snapshot_items')->upsert(
            $itemsPayload,
            ['snapshot_id', 'maquina_id'],
            [
                'obra_id','obra_maquina_id','cliente_id',
                'obra_nombre','cliente_nombre','residente_nombre',
                'pilas_programadas','pilas_ejecutadas','avance_global_pct',
                'horometro_inicio_obra','horometro_actual','horas_trabajadas',
                'total_obra','monto_cobrado','pago_pct',
                'maquina_nombre','maquina_codigo','placas','color','horometro_base','maquina_estado',
                'equipo',
                'observaciones_comisiones',
                'updated_by','updated_at'
            ]
        );

        DB::table('maquinaria_reporte_snapshot_items')
            ->where('snapshot_id', $snapshot->id)
            ->where(function ($q) {
                $q->whereNull('observaciones_snapshot')
                  ->orWhere('observaciones_snapshot', '=', '');
            })
            ->update([
                'observaciones_snapshot' => DB::raw('observaciones_comisiones'),
                'updated_by' => Auth::id(),
                'updated_at' => $now,
            ]);

        $snapshot->total_maquinas = $rows->count();
        $snapshot->save();

        DB::commit();

        return $snapshot;

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('HISTORIAL: generarSnapshotParaFecha ERROR', [
            'fecha' => $fecha,
            'message' => $e->getMessage(),
        ]);
        throw $e;
    }
}
private function buildRowsParaSnapshot(string $fecha)
{
    $rolResidenteId = DB::table('catalogo_roles')
        ->where('rol_key', 'RESIDENTE')
        ->value('id');

    $pilasProgramadasPorObra = DB::table('obras_pilas')
        ->select('obra_id', DB::raw('SUM(cantidad_programada) as pilas_programadas'))
        ->groupBy('obra_id');

    $pilasEjecutadasPorObra = DB::table('comisiones as c')
        ->join('comision_detalles as d', 'd.comision_id', '=', 'c.id')
        ->whereDate('c.fecha', '<=', $fecha)
        ->select(
            'c.obra_id',
            DB::raw('SUM(COALESCE(d.cantidad,0)) as pilas_ejecutadas'),
            DB::raw("
                GROUP_CONCAT(
                    DISTINCT NULLIF(TRIM(c.observaciones), '')
                    ORDER BY c.fecha ASC, c.id ASC
                    SEPARATOR ' | '
                ) as observaciones
            ")
        )
        ->groupBy('c.obra_id');

    $cobradoPorObra = DB::table('obras_facturas')
        ->where('estado', 'pagada')
        ->select('obra_id', DB::raw('SUM(COALESCE(monto,0)) as monto_cobrado'))
        ->groupBy('obra_id');

    $asignaciones = DB::table('maquinas as m')
        ->leftJoin('obra_maquina as om', function ($join) {
            $join->on('om.maquina_id', '=', 'm.id')
                 ->where('om.estado', '=', 'activa');
        })
        ->leftJoin('obras as o', 'o.id', '=', 'om.obra_id')
        ->leftJoinSub($cobradoPorObra, 'co', function ($join) {
            $join->on('co.obra_id', '=', 'o.id');
        })
        ->leftJoin('clientes as c', 'c.id', '=', 'o.cliente_id')
        ->leftJoinSub($pilasProgramadasPorObra, 'pp', function ($join) {
            $join->on('pp.obra_id', '=', 'o.id');
        })
        ->leftJoinSub($pilasEjecutadasPorObra, 'pe', function ($join) {
            $join->on('pe.obra_id', '=', 'o.id');
        })
        ->leftJoin('obras_pilas as op', 'op.obra_id', '=', 'o.id')
        ->leftJoin('obra_empleado as oe', function ($join) use ($rolResidenteId) {
            $join->on('oe.obra_id', '=', 'o.id')
                ->where('oe.activo', '=', 1)
                ->whereNull('oe.fecha_baja')
                ->where('oe.rol_id', '=', $rolResidenteId);
        })
        ->leftJoin('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
        ->select([
            'om.id as obra_maquina_id',
            'om.obra_id',
            'm.id as maquina_id',
            'om.fecha_inicio',
            'om.horometro_inicio',
            'om.horometro_fin',

            DB::raw("MAX(COALESCE(pe.observaciones, '')) as observaciones"),

            DB::raw('COALESCE(o.monto_contratado,0) as total_obra'),
            DB::raw('COALESCE(co.monto_cobrado,0) as monto_cobrado'),
            DB::raw("
                CASE
                    WHEN COALESCE(o.monto_contratado,0) = 0 THEN 0
                    ELSE ROUND((COALESCE(co.monto_cobrado,0) / o.monto_contratado) * 100, 0)
                END as pago_pct
            "),

            DB::raw("COALESCE(c.nombre_comercial, '—') as cliente_nombre"),
            DB::raw('COALESCE(pp.pilas_programadas, 0) as pilas_programadas'),
            DB::raw('COALESCE(pe.pilas_ejecutadas, 0) as pilas_ejecutadas'),
            DB::raw("
                CASE
                    WHEN COALESCE(pp.pilas_programadas, 0) = 0 THEN 0
                    ELSE ROUND((COALESCE(pe.pilas_ejecutadas, 0) / pp.pilas_programadas) * 100, 0)
                END as avance_global_pct
            "),

            DB::raw("COALESCE(o.nombre, 'LA GIRALDA') as obra_nombre"),
            'o.cliente_id',
            DB::raw("COALESCE(CONCAT(e.nombre,' ',IFNULL(e.Apellidos,'')), '') as residente_nombre"),

            'm.nombre as maquina_nombre',
            'm.codigo as maquina_codigo',
            'm.placas',
            'm.color',
            'm.horometro_base',
            'm.estado as maquina_estado',
        ])
        ->selectSub(function ($q) {
            $q->from('obra_maquina_registros as omr')
              ->selectRaw('COALESCE(SUM(omr.horas),0)')
              ->whereColumn('omr.obra_maquina_id', 'om.id');
        }, 'horas_trabajadas')
        ->selectSub(function ($q) {
            $q->from('obra_maquina_registros as omr')
              ->select('omr.horometro_fin')
              ->whereColumn('omr.obra_maquina_id', 'om.id')
              ->orderByDesc('omr.fin')
              ->orderByDesc('omr.id')
              ->limit(1);
        }, 'horometro_actual')
        ->orderByRaw('CASE WHEN o.nombre IS NULL THEN 1 ELSE 0 END')
        ->orderBy('o.nombre')
        ->orderBy('m.nombre')
        ->groupBy([
            'om.id',
            'om.obra_id',
            'm.id',
            'om.fecha_inicio',
            'om.horometro_inicio',
            'om.horometro_fin',
            'o.nombre',
            'o.cliente_id',
            'e.nombre',
            'e.Apellidos',
            'm.nombre',
            'm.codigo',
            'm.placas',
            'm.color',
            'm.horometro_base',
            'm.estado',
            'pilas_programadas',
            'pilas_ejecutadas',
            'c.nombre_comercial',
            'o.monto_contratado',
            'co.monto_cobrado',
        ])
        ->get();

    $obraIds = $asignaciones
        ->pluck('obra_id')
        ->filter()
        ->unique()
        ->values();

    $equipoPorObra = collect();

    if ($obraIds->isNotEmpty()) {
        $equipoPorObra = DB::table('obra_empleado as oe')
            ->join('empleados as e', 'e.id_Empleado', '=', 'oe.empleado_id')
            ->whereIn('oe.obra_id', $obraIds->all())
            ->where('oe.activo', 1)
            ->whereNull('oe.fecha_baja')
            ->where('oe.rol_id', '!=', $rolResidenteId)
            ->orderBy('e.nombre')
            ->get([
                'oe.obra_id',
                DB::raw("CONCAT(e.nombre,' ',e.Apellidos,' ',IFNULL(e.Apellidos,'')) as nombre"),
                'oe.rol_id',
            ])
            ->groupBy('obra_id');
    }

    // Observaciones del día (si existen)
    $mrdHoy = MaquinaReporteDiario::query()
        ->where('fecha', $fecha)
        ->get()
        ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

    $keys = $asignaciones
        ->map(fn($a) => $a->obra_id.'-'.$a->maquina_id)
        ->unique()
        ->values();

    if ($keys->isEmpty()) {
        return collect();
    }

    $prev = MaquinaReporteDiario::query()
        ->select(['obra_id', 'maquina_id', 'fecha', 'observaciones'])
        ->where('fecha', '<', $fecha)
        ->whereIn(DB::raw("CONCAT(obra_id,'-',maquina_id)"), $keys->all())
        ->orderBy('fecha', 'desc')
        ->get()
        ->unique(fn($r) => $r->obra_id.'-'.$r->maquina_id)
        ->keyBy(fn($r) => $r->obra_id.'-'.$r->maquina_id);

    $rows = $asignaciones->map(function ($a) use ($fecha, $mrdHoy, $prev, $equipoPorObra) {
        $k = $a->obra_id.'-'.$a->maquina_id;

        $hoy  = $mrdHoy->get($k);
        $last = $prev->get($k);

        return [
            'fecha' => $fecha,

            'obra_maquina_id' => $a->obra_maquina_id ?? null,

            'obra_id' => $a->obra_id,
            'obra_nombre' => $a->obra_nombre,
            'cliente_id' => $a->cliente_id,
            'cliente_nombre' => $a->cliente_nombre ?: null,

            'residente_nombre' => $a->residente_nombre ?: null,

            'maquina_id' => $a->maquina_id,
            'maquina_nombre' => $a->maquina_nombre,
            'maquina_codigo' => $a->maquina_codigo,
            'placas' => $a->placas,
            'color' => $a->color,
            'horometro_base' => $a->horometro_base,
            'maquina_estado' => $a->maquina_estado,

            'horometro_inicio' => $a->horometro_inicio,
            'horometro_inicio_obra' => $a->horometro_inicio,
            'horometro_fin_obra' => $a->horometro_fin,
            'horometro_actual' => $a->horometro_actual ?? null,

            'horas_trabajadas' => (float) ($a->horas_trabajadas ?? 0),

            'pilas_programadas' => (int) ($a->pilas_programadas ?? 0),
            'pilas_ejecutadas'  => (int) ($a->pilas_ejecutadas ?? 0),
            'avance_global_pct' => (int) ($a->avance_global_pct ?? 0),

            'total_obra'    => (float) ($a->total_obra ?? 0),
            'monto_cobrado' => (float) ($a->monto_cobrado ?? 0),
            'pago_pct'      => (int)   ($a->pago_pct ?? 0),

            // Observación “de comisiones” (lo que hoy arma tu query)
            'observaciones' => $a->observaciones ?: null,

            // Equipo
            'equipo' => $a->obra_id ? ($equipoPorObra[$a->obra_id] ?? collect()) : collect(),

            // Estado UI (en snapshot no importa, pero lo dejamos)
            'ya_guardado_hoy' => (bool) $hoy,
        ];
    });

    return $rows;
}
public function snapshotsIndex(Request $request)
{
    $from = $request->input('from');
    $to   = $request->input('to');

    // defaults: últimos 30 días
    $toDate = $to ? Carbon::parse($to)->toDateString() : now()->toDateString();
    $fromDate = $from ? Carbon::parse($from)->toDateString() : now()->subDays(30)->toDateString();

    $snapshots = MaquinariaReporteSnapshot::query()
        ->whereBetween('fecha', [$fromDate, $toDate])
        ->orderBy('fecha', 'desc')
        ->get(['id','fecha','estado','total_maquinas','created_at','created_by']);

    return view('reportes.maquinaria.snapshots_index', [
        'from' => $fromDate,
        'to' => $toDate,
        'snapshots' => $snapshots,
    ]);
}
public function updateObservacionSnapshot(Request $request)
{
    $data = $request->validate([
        'snapshot_id' => ['required','integer','exists:maquinaria_reporte_snapshots,id'],
        'maquina_id'  => ['required','integer'],
        'observaciones_snapshot' => ['nullable','string','max:5000'],
    ]);

    $item = MaquinariaReporteSnapshotItem::query()
        ->where('snapshot_id', $data['snapshot_id'])
        ->where('maquina_id', $data['maquina_id'])
        ->firstOrFail();

    $item->observaciones_snapshot = $data['observaciones_snapshot'];
    $item->updated_by = auth()->id();
    $item->save();

    return response()->json([
        'ok' => true,
        'message' => 'Observación guardada.',
        'updated_at' => $item->updated_at?->toDateTimeString(),
    ]);
}


}
