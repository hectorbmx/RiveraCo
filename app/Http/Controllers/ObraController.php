<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\SatCfdi;
use App\Models\User;
use App\Models\Empleado;
use App\Models\ObraEmpleado;
use App\Models\ObraPlaneacionGasto;
use App\Models\ObraReposicionGasto;
use App\Models\gastosPlaneados;
use App\Models\Comision;
use App\Models\ComisionDetalle;
use App\Models\ObraMaquina;
use App\Models\Maquina;
use App\Models\Pila;
use App\Models\Presupuesto;
use App\Models\ObraPila;
use App\Models\CatalogoPila;
use Illuminate\Support\Facades\DB;
use App\Models\ObraMaquinaRegistro;
use App\Models\CatalogoActividadComision;
use App\Models\ObraAsistencia;
use Carbon\Carbon;
use App\Models\OrdenCompra;





class ObraController extends Controller
{
    public function index()
    {
        $obras = Obra::with(['cliente', 'responsable'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('obras.index', compact('obras'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre_comercial')->get();
        $responsables = User::orderBy('name')->get(); // luego podemos filtrar por rol "jefe-obra"

        return view('obras.create', compact('clientes', 'responsables'));
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'cliente_id'               => ['required', 'exists:clientes,id'],
        'nombre'                   => ['required', 'string', 'max:255'],
        'clave_obra'               => ['required', 'string', 'max:100', 'unique:obras,clave_obra'],
        'descripcion'              => ['nullable', 'string'],
        'tipo_obra'                => ['nullable', 'string', 'max:100'],
        'estatus_nuevo'            => ['required', 'numeric', 'in:1,2,3,4,5'],
        'fecha_inicio_programada'  => ['nullable', 'date'],
        'fecha_inicio_real'        => ['nullable', 'date'],
        'fecha_fin_programada'     => ['nullable', 'date'],
        'fecha_fin_real'           => ['nullable', 'date'],
        'monto_contratado'         => ['nullable', 'numeric'],
        'monto_modificado'         => ['nullable', 'numeric'],
        'responsable_id'           => ['nullable', 'exists:users,id'],
        'ubicacion'                => ['nullable', 'string', 'max:255'],
        'profundidad_total'        => ['nullable', 'numeric', 'min:0'],
        'kg_acero_total'           => ['nullable', 'numeric', 'min:0'],
        'bentonita_total'          => ['nullable', 'numeric', 'min:0'],
        'concreto_total'           => ['nullable', 'numeric', 'min:0'],
    ]);

    $obra = Obra::create($data);

    return redirect()->route('obras.edit', $obra)
        ->with('success', 'Obra creada correctamente.');
}


public function edit(Request $request, Obra $obra)
{

    $roles = \DB::table('catalogo_roles')->orderBy('nombre')->get();
    $clientes     = Cliente::orderBy('nombre_comercial')->get();
    $responsables = User::orderBy('name')->get();

    $desde =$request->query('asist_desde');
    $hasta =$request->query('asist_hasta');
    $semanas = $obra->semanas_totales;
    // Por esto:
// $presupuestoIds = $obra->presupuestos_vinculados->pluck('id');
$presupuestoIds = $obra->presupuestos_vinculados()->pluck('presupuestos.id');

$registrosPlaneacion = \App\Models\ObraPlaneacionGasto::query()
    ->where(function ($q) use ($obra, $presupuestoIds) {
        $q->where('obra_id', $obra->id)
          ->orWhereIn('presupuesto_id', $presupuestoIds);
    })
    ->get();

$gastosBase = $registrosPlaneacion
    ->where('numero_semana', 0)
    ->values();

    $cfdisDisponibles = SatCfdi::whereNull('obra_id')
    ->orderByDesc('fecha_emision')
    ->limit(300)
    ->get();

$planeacion = \App\Models\ObraPlaneacionSemanal::query()
    ->whereIn('planeacion_gasto_id', $gastosBase->pluck('id'))
    ->get()
    ->groupBy('planeacion_gasto_id')
    ->map(function ($group) {
        return $group->keyBy('numero_semana');
    });

    if (!$desde && !$hasta) {
    $start = Carbon::now('America/Mexico_City')->startOfWeek(Carbon::MONDAY);
    $end   = Carbon::now('America/Mexico_City')->endOfWeek(Carbon::SUNDAY);

    $desde = $start->toDateString(); // YYYY-MM-DD
    $hasta = $end->toDateString();
}



    // Puestos BASE que se pueden asignar a una obra
    // (estos son los grupos normalizados en la columna puesto_base)
    $puestosBaseAsignables = [
        'AYUDANTE PERFORADOR',
        'AYUDANTE GENERAL',
        'RESIDENTE',
        'INGENIERO',
        'COLADOR',
        'ARQUITECTO',
        'OPERADOR',
        'OFICIAL',
        'AYUDANTE',
        'TUBERO',
        'SOLDADOR',
        'PERFORADOR',
        'MECANICO',
        'CHOFER',
        'SUPERVISOR',
        'SUPERVISOR_OBRA',
        'OPERADOR GRUA',
        'AXILAR'
    ];

    // Cargar relaciones principales de la obra
    $obra->load([
        'cliente',
        'gastosPlaneados',
        'contratos',
        'planos',
        'presupuestos',
        'empleadosAsignados.empleado',
        'maquinasAsignadas.maquina',
        'presupuestos',
        'presupuestos_vinculados.resumenes',
        'presupuestos_vinculados.pilas'
    ]);

    $tab = $request->query('tab', 'general');

   // Cambiamos $id por $obra->id para evitar el error de variable indefinida
$presupuestosDisponibles = Presupuesto::whereDoesntHave('obras', function($query) use ($obra) {
    $query->where('obras.id', $obra->id);
})
// Usamos el nombre del cliente de la relación de la obra
// ->where('nombre_cliente', $obra->cliente->nombre) 
->get();

    // Asignaciones activas e histórico (de esta obra)
    $asignaciones           = $obra->empleadosAsignados;
    $asignacionesActivas    = $asignaciones->where('activo', true);
    $asignacionesHistoricas = $asignaciones->where('activo', false);

     $statuses = [
        1 => 'planeacion',
        2 => 'ejecucion',
        3 => 'detenida',
        4 => 'terminada',
        5 => 'cancelada',
    ];
    $asistencias = collect();
    $weekDays = collect();
    $asistenciasSemana = collect();
    $gastosOC = collect();
    $gastosPorPartida = collect();
    $daysCount = 0;
if ($tab === 'gastos') {
    $gastosOC = OrdenCompra::where('obra_id', $obra->id)
        ->whereNotNull('planeacion_gasto_id')
        ->where('estado', 'AUTORIZADA')
        // ->with(['partida', 'proveedor'])
            ->with(['proveedor', 'planeacionGasto'])

        ->orderBy('fecha')
        ->get();
}
$gastosPorPartida = $gastosOC
    ->groupBy('planeacion_gasto_id')
    ->map(function ($ocs) {
        $gastoBase = $ocs->first()->planeacionGasto;

        $tope = (float) ($gastoBase->monto_programado ?? 0);
        $gastado = (float) $ocs->sum('total');
        $disponible = $tope - $gastado;

        return [
            'gasto_base' => $gastoBase,
            'tope' => $tope,
            'gastado' => $gastado,
            'disponible' => $disponible,
            'ordenes' => $ocs,
        ];
    });
if ($tab === 'asistencias') {

    $rawQuery = ObraAsistencia::query()
        ->where('obra_id', $obra->id)
        ->with('empleado');

    // Filtro por rango
    if ($desde && $hasta) {
        $request->validate([
            'asist_desde' => ['date'],
            'asist_hasta' => ['date', 'after_or_equal:asist_desde'],
        ]);

        $rawQuery->whereBetween('checked_date', [$desde, $hasta]);

    } elseif ($desde || $hasta) {
        $d = $desde ?: $hasta;

        $request->validate([
            $desde ? 'asist_desde' : 'asist_hasta' => ['date'],
        ]);

        $rawQuery->whereDate('checked_date', $d);

    } else {
        $rawQuery->whereDate('checked_date', now()->toDateString());
    }

    // 👉 AQUÍ se ejecuta el query
    $raw = $rawQuery
        ->orderByDesc('checked_date')
        ->orderBy('checked_at')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | TABLA GENERAL (ya la tenías)
    |--------------------------------------------------------------------------
    */
    $asistencias = $raw
        ->groupBy(fn ($a) => $a->empleado_id . '|' . $a->checked_date)
        ->map(function (Collection $items) {
            $entrada = $items->firstWhere('tipo', 'entrada');
            $salida  = $items->firstWhere('tipo', 'salida');

            return (object) [
                    'empleado'      => $items->first()->empleado,
                    'checked_date'  => $items->first()->checked_date,

                    'entrada_hora'  => $entrada?->checked_at?->timezone('America/Mexico_City')->format('H:i'),
                    'salida_hora'   => $salida?->checked_at?->timezone('America/Mexico_City')->format('H:i'),

                    // ✅ estas 2 líneas son las que te faltan
                    'entrada_foto'  => $entrada?->photo_path,
                    'salida_foto'   => $salida?->photo_path,

                    'entrada_id'    => $entrada?->id,
                    'salida_id'     => $salida?->id,
                ];
        })
        ->values();

    /*
    |--------------------------------------------------------------------------
    | TABLA SEMANAL (NUEVA)
    |--------------------------------------------------------------------------
    */
    $start = Carbon::parse($desde, 'America/Mexico_City')->startOfDay();
    $end   = Carbon::parse($hasta, 'America/Mexico_City')->startOfDay();

    $daysCount = $start->diffInDays($end) + 1;

    $weekDays = collect();
    if ($daysCount === 7) {
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $weekDays->push([
                'date'  => $d->toDateString(),
                'label' => $d->format('d/m'),
                'dow'   => mb_strtoupper($d->isoFormat('ddd')),
            ]);
        }
    }

    $getEmpId = function ($r) {
    // prioridad: si la relación empleado existe, toma su PK real
    return $r->empleado?->id_Empleado ?? $r->empleado_id;
};

    // $index = $raw->groupBy(fn ($r) => $r->empleado_id . '|' . $r->checked_date);
    // $index = $raw->groupBy(fn ($r) => $getEmpId($r) . '|' . $r->checked_date);
    $index = $raw->groupBy(fn ($r) => $getEmpId($r) . '|' . Carbon::parse($r->checked_date)->toDateString());


    $asistenciasSemana = collect();

    if ($daysCount === 7) {
        // $empleados = $raw->pluck('empleado')->filter()->unique('id_Empleado');
        $empleados = $raw->map(fn($r) => $r->empleado)->filter()
        ->unique(fn($e) => $e->id_Empleado)
        ->values();


        $asistenciasSemana = $empleados->map(function ($emp) use ($weekDays, $index) {
            $dias = [];

            foreach ($weekDays as $wd) {
                $key = $emp->id_Empleado . '|' . $wd['date'];
                $items = $index->get($key, collect());

                $entrada = $items->firstWhere('tipo', 'entrada');
                $salida  = $items->firstWhere('tipo', 'salida');

                $dias[$wd['date']] = [
                    'entrada' => $entrada?->checked_at?->timezone('America/Mexico_City')->format('H:i'),
                    'salida'  => $salida?->checked_at?->timezone('America/Mexico_City')->format('H:i'),
                ];
            }

            return (object)[
                'empleado' => $emp,
                'dias'     => $dias,
            ];
        })->values();
    }
}

$reposicionesGastos = ObraReposicionGasto::with([
             'partida',
        'detalles',
        'solicitadoPor',
        'revisadoPor',
        'aprobadoPor',
        'pagadoPor',
        ])
    ->where('obra_id', $obra->id)
    ->latest()
    ->get();
$reposicionesStats = [
    'total' => $reposicionesGastos->count(),

    'solicitadas' => $reposicionesGastos
        ->where('estatus', 'solicitado')
        ->count(),

    'en_revision' => $reposicionesGastos
        ->whereIn('estatus', [
            'en_revision_area',
            'programado_area',
            'en_revision_admin',
            'pendiente_autorizacion',
        ])
        ->count(),

    'autorizadas' => $reposicionesGastos
        ->whereIn('estatus', [
            'autorizado',
            'pagado',
            'cerrado',
        ])
        ->count(),
];
$reposicionesMontos = [

    'solicitado' => $reposicionesGastos
        ->whereIn('estatus', [
            'solicitado',
            'en_revision_area',
            'programado_area',
            'en_revision_admin',
            'pendiente_autorizacion',
        ])
        ->sum('total'),

    'autorizado' => $reposicionesGastos
        ->whereIn('estatus', [
            'autorizado',
            'pagado',
            'cerrado',
        ])
        ->sum('total'),

    'pagado' => $reposicionesGastos
        ->whereIn('estatus', [
            'pagado',
            'cerrado',
        ])
        ->sum('total'),
];
$gastadoReposicionPorPartida = \App\Models\ObraReposicionGastoDetalle::query()
    ->whereHas('reposicion', function ($query) use ($obra) {
        $query->where('obra_id', $obra->id)
            ->whereNotIn('estatus', ['rechazado', 'cancelado']);
    })
    ->selectRaw('partida_id, SUM(monto) as total_gastado')
    ->whereNotNull('partida_id')
    ->groupBy('partida_id')
    ->pluck('total_gastado', 'partida_id');

    $currentStatus = $obra->estatus_nuevo;
    if (!is_null($currentStatus) && !is_numeric($currentStatus)) {
            $reverse = array_flip($statuses); // ['planeacion' => 1, ...]
            $currentStatus = $reverse[$currentStatus] ?? null;
        }

    // Empleados activos para el buscador, incluyendo su asignacion actual si existe.
    $empleadosAsignables = Empleado::query()
        ->with([
            'asignacionActiva.obra:id,nombre,clave_obra',
        ])
        ->where('Estatus', 1)
        ->whereIn('puesto_base', $puestosBaseAsignables)
        ->orderBy('Apellidos')
        ->orderBy('Nombre')
        ->get([
            'id_Empleado',
            'Nombre',
            'Apellidos',
            'Puesto',
            'puesto_base',
        ])
        ->map(function ($empleado) use ($obra) {
            $asignacionActiva = $empleado->asignacionActiva;
            $obraActiva = $asignacionActiva?->obra;

            return [
                'id_Empleado' => $empleado->id_Empleado,
                'Nombre' => $empleado->Nombre,
                'Apellidos' => $empleado->Apellidos,
                'Puesto' => $empleado->Puesto,
                'puesto_base' => $empleado->puesto_base,
                'asignado' => (bool) $asignacionActiva,
                'asignado_en_esta_obra' => $asignacionActiva
                    ? (int) $asignacionActiva->obra_id === (int) $obra->id
                    : false,
                'obra_asignada' => $obraActiva ? [
                    'id' => $obraActiva->id,
                    'nombre' => $obraActiva->nombre,
                    'clave_obra' => $obraActiva->clave_obra,
                ] : null,
            ];
        })
        ->values();

    // Inicializamos vacíos por si no estamos en ese tab
    $maquinasAsignadasActivas    = collect();
    $maquinasAsignadasHistoricas = collect();
    $maquinasDisponibles         = collect();

    $registrosHorasMaquina = collect();

    $pilasAsignadasActivas       = collect();
    $pilasAsignadasHistoricas    = collect();
    $pilasCatalogo               = collect();

        if ($tab === 'pilas' || $tab === 'comisiones') {
        // Pilas de esta obra
        $pilasObra = $obra->pilas()
            ->withSum('detallesComision as cantidad_ejecutada', 'cantidad')
            ->orderBy('numero_pila')
            ->get();

        $cantPilasObra = $obra->pilas()
            ->orderBy('cantidad_programada')
            ->get();

        $pilasAsignadasActivas    = $pilasObra->where('activo', true);
        $pilasAsignadasHistoricas = $pilasObra->where('activo', false);

        // Catálogo de pilas (para el <select> de asignación)
        $pilasCatalogo = CatalogoPila::where('activa', true)
            ->orderBy('diametro_cm')
            ->orderBy('codigo')
            ->get();
    }

//tab maquionas
    //    if ($tab === 'maquinaria' || $tab === 'comisiones') {
    if (in_array($tab, ['maquinaria', 'horas-maquina', 'comisiones'], true)) {

        // Asignaciones de maquinaria de esta obra
      $asignacionesMaquina = ObraMaquina::query()
                ->where('obra_id', $obra->id)
                ->with('maquina')
                ->select('obra_maquina.*')
                ->selectSub(function ($q) {
                    $q->from('obra_maquina_registros')
                        ->selectRaw('COALESCE(SUM(GREATEST(0, horometro_fin - horometro_inicio)),0)')
                        ->whereColumn('obra_maquina_registros.obra_maquina_id', 'obra_maquina.id');
                }, 'total_horas')
                ->selectSub(function ($q) {
                    $q->from('obra_maquina_registros')
                        ->select('horometro_fin')
                        ->whereColumn('obra_maquina_registros.obra_maquina_id', 'obra_maquina.id')
                        ->orderByDesc('fin')
                        ->orderByDesc('id')
                        ->limit(1);
                }, 'horometro_actual')
                ->orderBy('fecha_inicio')
                ->get();

        $maquinasAsignadasActivas    = $asignacionesMaquina->where('estado', 'activa');
        $maquinasAsignadasHistoricas = $asignacionesMaquina->where('estado', 'finalizada');

        // IDs de máquinas actualmente activas en cualquier obra
        $maquinasOcupadasIds = ObraMaquina::where('estado', 'activa')
            ->whereNull('fecha_fin')
            ->pluck('maquina_id');

        // Máquinas operativas y no ocupadas
        $maquinasDisponibles = Maquina::query()
            ->where('estado', 'operativa')
            ->whereNotIn('id', $maquinasOcupadasIds)
            ->orderBy('nombre')
            ->get();
    }
if ($tab === 'horas-maquina') {
    $registrosHorasMaquina = ObraMaquinaRegistro::query()
        ->where('obra_id', $obra->id)
        ->with([
            'asignacion.maquina', // para mostrar la máquina en cada registro
        ])
        ->orderByDesc('inicio')
        ->orderByDesc('id')
        ->get();
}

    $comisiones =collect();
    $comisionesAgrupadas = collect();
    $fechasDisponibles =collect();
    $selectedFecha = null;

    if ($tab === 'comisiones') {

    // 1) Fecha seleccionada en el filtro (puede venir vacía)
    $selectedFecha = $request->query('fecha');

    // 2) Todas las fechas distintas de comisiones de esta obra (para el <select>)
    $fechasDisponibles = Comision::where('obra_id', $obra->id)
        ->select('fecha')
        ->distinct()
        ->orderByDesc('fecha')
        ->pluck('fecha');

    // 3) Query base de comisiones de la obra
    $query = Comision::where('obra_id', $obra->id)
        ->with([
            'pila',
            // para poder mostrar la máquina sin N+1
            'detalles.asignacionMaquina.maquina',
        ])
        // suma de cantidad de TODAS las filas de detalle de esta comisión
        ->withSum('detalles as total_pilas', 'cantidad')
        ->orderByDesc('fecha')
        ->orderByDesc('id');

    // 4) Si hay filtro de fecha, lo aplicamos
    if ($selectedFecha) {
        $query->whereDate('fecha', $selectedFecha);
    }

    // 5) Obtenemos el histórico (sin paginar porque estás dentro de la misma vista)
    $comisiones = $query->get();
    $comisionesAgrupadas = $comisiones
    ->groupBy(function ($comision) {
        return $comision->fecha?->format('Y-m-d');
    })
    ->map(function ($items, $fecha) {
        return (object) [
            'fecha' => \Carbon\Carbon::parse($fecha),
            'total_pilas' => $items->sum('total_pilas'),
            'comisiones' => $items,
        ];
    })
    ->values();
}
  $tab = $request->query('tab', 'general');
  
  $avanceObra = [
        'profundidad' => 0.0,
        'kg_acero'    => 0.0,
        'bentonita'   => 0.0,
        'concreto'    => 0.0,
    ];

     if ($tab === 'general') {
        // Hacemos una sola consulta con SUMs agregados
        $totales = ComisionDetalle::selectRaw('
                COALESCE(SUM(profundidad), 0)    as total_profundidad,
                COALESCE(SUM(kg_acero), 0)       as total_kg_acero,
                COALESCE(SUM(vol_bentonita), 0)  as total_vol_bentonita,
                COALESCE(SUM(vol_concreto), 0)   as total_vol_concreto
            ')
            // Solo detalles de comisiones de ESTA obra
            ->whereHas('comision', function ($q) use ($obra) {
                $q->where('obra_id', $obra->id);
            })
            ->first();

        $avanceObra['profundidad'] = (float) $totales->total_profundidad;
        $avanceObra['kg_acero']    = (float) $totales->total_kg_acero;
        $avanceObra['bentonita']   = (float) $totales->total_vol_bentonita;
        $avanceObra['concreto']    = (float) $totales->total_vol_concreto;
    }
        // Facturas de la obra (para el tab de facturación)
        $facturas = $obra->facturas()
            ->orderByDesc('fecha_factura')
            ->orderByDesc('id')
            ->get();
        $totalFacturado = (float) $obra->facturas()
            ->where('estado', '!=', 'cancelada')
            ->sum('monto');
        $totalPagado = (float) $obra->facturas()
            ->where('estado', 'pagada')
            ->sum('monto');
        $totalPendiente = max(0, $totalFacturado - $totalPagado);

// Totales de facturación (para resumen y barras)
// $totalFacturado = (float) $obra->facturas()->sum('monto');                         // todas las facturas emitidas
// $totalPagado    = (float) $obra->facturas()->whereNotNull('fecha_pago')->sum('monto'); // solo las pagadas
// $totalPendiente = max(0, $totalFacturado - $totalPagado);
$actividades = CatalogoActividadComision::where('activa', 1)
    ->orderBy('orden')
    ->get();

// Avance cobrado que usamos en el tab "Información general"
$avanceCobrado = 0;
if ($tab === 'general') {
    // reutilizamos el total pagado
    $avanceCobrado = $totalPagado;
}

return view('obras.edit', [
    'obra'                        => $obra,
    'reposicionesGastos'          => $reposicionesGastos,
    'reposicionesStats'           => $reposicionesStats,
    'reposicionesMontos'          => $reposicionesMontos,
    'gastadoReposicionPorPartida' => $gastadoReposicionPorPartida,
    
    'clientes'                    => $clientes,
    'responsables'                => $responsables,
    'tab'                         => $tab,
    'asignaciones'                => $asignaciones,
    'asignacionesActivas'         => $asignacionesActivas,
    'asignacionesHistoricas'      => $asignacionesHistoricas,
    'empleadosAsignables'         => $empleadosAsignables,
    'actividades'                 => $actividades,

    'maquinasAsignadasActivas'    => $maquinasAsignadasActivas,
    'maquinasAsignadasHistoricas' => $maquinasAsignadasHistoricas,
    'maquinasDisponibles'         => $maquinasDisponibles,
    'registrosHorasMaquina'       => $registrosHorasMaquina,
    

    
    'pilasAsignadasActivas'       => $pilasAsignadasActivas,
    'pilasAsignadasHistoricas'    => $pilasAsignadasHistoricas,
    'pilasCatalogo'               => $pilasCatalogo,

    'comisiones'                  => $comisiones,
    'comisionesAgrupadas'         => $comisionesAgrupadas,
    'fechasDisponibles'           => $fechasDisponibles,
    'selectedFecha'               => $selectedFecha,

    'statuses'                    => $statuses,
    'currentStatus'               => $currentStatus,

    'avanceObra'                  => $avanceObra,

    'facturas'                    => $facturas,

    // NUEVO: totales para el resumen de facturación
    'totalFacturado'              => $totalFacturado,
    'totalPagado'                 => $totalPagado,
    'totalPendiente'              => $totalPendiente,

    // NUEVO: lo usamos en la barra de avance de cobro en Información general
    'avanceCobrado'                => $avanceCobrado,
    'roles'                        =>$roles,
    'asistencias'                  =>$asistencias,
    'asist_desde'                  => $desde,
    'asist_hasta'                  => $hasta,
    'weekDays' => $weekDays,
    'asistenciasSemana' => $asistenciasSemana,
    'daysCount' => $daysCount,
    'presupuestosDisponibles'     => $presupuestosDisponibles,


    'gastosBase' => $gastosBase,
    'planeacion' => $planeacion,
    'semanas'    => $semanas,
    'gastosOC'                    => $gastosOC,
    'gastosPorPartida' => $gastosPorPartida,

    'cfdisDisponibles' => $cfdisDisponibles,

    ]);
}


public function reporteAsistencias(Request $request, Obra $obra)
{
    $desde = $request->query('asist_desde');
    $hasta = $request->query('asist_hasta');

    if (!$desde && !$hasta) {
        $start = Carbon::now('America/Mexico_City')->startOfWeek(Carbon::MONDAY);
        $end = Carbon::now('America/Mexico_City')->endOfWeek(Carbon::SUNDAY);
        $desde = $start->toDateString();
        $hasta = $end->toDateString();
    }

    $request->validate([
        'asist_desde' => ['nullable', 'date'],
        'asist_hasta' => ['nullable', 'date', 'after_or_equal:asist_desde'],
    ]);

    $desde = $desde ?: $hasta;
    $hasta = $hasta ?: $desde;

    $start = Carbon::parse($desde, 'America/Mexico_City')->startOfDay();
    $end = Carbon::parse($hasta, 'America/Mexico_City')->startOfDay();
    $daysCount = $start->diffInDays($end) + 1;

    if ($daysCount !== 7) {
        return redirect()
            ->route('obras.edit', [
                'obra' => $obra->id,
                'tab' => 'asistencias',
                'asist_desde' => $desde,
                'asist_hasta' => $hasta,
            ])
            ->withErrors(['asist_desde' => 'El reporte de asistencia se genera con un rango semanal de 7 dias.']);
    }

    $obra->load(['cliente', 'responsable']);

    $weekDays = collect();
    for ($i = 0; $i < 7; $i++) {
        $day = $start->copy()->addDays($i);
        $weekDays->push([
            'date' => $day->toDateString(),
            'day' => $day->format('d'),
            'dow' => mb_strtoupper($day->isoFormat('dd')),
        ]);
    }

    $raw = ObraAsistencia::query()
        ->where('obra_id', $obra->id)
        ->whereBetween('checked_date', [$desde, $hasta])
        ->with('empleado')
        ->orderBy('checked_date')
        ->orderBy('checked_at')
        ->get();

    $index = $raw->groupBy(function ($row) {
        $empleadoId = $row->empleado?->id_Empleado ?? $row->empleado_id;

        return $empleadoId . '|' . Carbon::parse($row->checked_date)->toDateString();
    });

    $empleados = $raw
        ->map(fn ($row) => $row->empleado)
        ->filter()
        ->unique(fn ($empleado) => $empleado->id_Empleado)
        ->sortBy(fn ($empleado) => trim(($empleado->Apellidos ?? '') . ' ' . ($empleado->Nombre ?? '')))
        ->values();

    $rows = $empleados->map(function ($empleado) use ($weekDays, $index) {
        $dias = [];
        $totalDias = 0;

        foreach ($weekDays as $day) {
            $items = $index->get($empleado->id_Empleado . '|' . $day['date'], collect());
            $entrada = $items->firstWhere('tipo', 'entrada');
            $salida = $items->firstWhere('tipo', 'salida');
            $presente = (bool) ($entrada || $salida);

            if ($presente) {
                $totalDias++;
            }

            $dias[$day['date']] = [
                'presente' => $presente,
                'entrada' => $entrada?->checked_at?->timezone('America/Mexico_City')->format('H:i'),
                'salida' => $salida?->checked_at?->timezone('America/Mexico_City')->format('H:i'),
            ];
        }

        $sueldoSemanal = (float) ($empleado->Sueldo ?: $empleado->Sueldo_real ?: 0);
        $sueldoDiario = $sueldoSemanal > 0 ? round($sueldoSemanal / 7, 2) : 0;
        $descuentoInfonavit = (float) ($empleado->infonavit ?? 0);
        $sueldoPeriodo = round($sueldoDiario * $totalDias, 2);

        return (object) [
            'empleado' => $empleado,
            'dias' => $dias,
            'total_dias' => $totalDias,
            'sueldo_semanal' => $sueldoSemanal,
            'sueldo_diario' => $sueldoDiario,
            'sueldo_periodo' => $sueldoPeriodo,
            'descuento_infonavit' => $descuentoInfonavit,
            'total_pagar' => max(0, $sueldoPeriodo - $descuentoInfonavit),
        ];
    });

    return view('obras.asistencias.reporte', [
        'obra' => $obra,
        'weekDays' => $weekDays,
        'rows' => $rows,
        'desde' => $start,
        'hasta' => $end,
        'generadoPor' => auth()->user()?->name ?? '',
    ]);
}


   public function update(Request $request, Obra $obra)
{
  
    // 2) Validar
    $data = $request->validate([
        'cliente_id'               => ['required', 'exists:clientes,id'],
        'nombre'                   => ['required', 'string', 'max:255'],
        'clave_obra'               => ['required', 'string', 'max:100', 'unique:obras,clave_obra,' . $obra->id],
        'descripcion'              => ['nullable', 'string'],
        'tipo_obra'                => ['nullable', 'string', 'max:100'],
        'estatus_nuevo'            => ['required', 'numeric', 'in:1,2,3,4,5'],
        'fecha_inicio_programada'  => ['nullable', 'date'],
        'fecha_inicio_real'        => ['nullable', 'date'],
        'fecha_fin_programada'     => ['nullable', 'date'],
        'fecha_fin_real'           => ['nullable', 'date'],
        'monto_contratado'         => ['nullable', 'numeric'],
        'monto_modificado'         => ['nullable', 'numeric'],
        'responsable_id'           => ['nullable', 'exists:users,id'],
        'ubicacion'                => ['nullable', 'string', 'max:255'],
        'profundidad_total'        => ['nullable', 'numeric', 'min:0'],
        'kg_acero_total'           => ['nullable', 'numeric', 'min:0'],
        'bentonita_total'          => ['nullable', 'numeric', 'min:0'],
        'concreto_total'           => ['nullable', 'numeric', 'min:0'],
    ]);

    // 3) Ver qué datos SI están pasando la validación
    // dd($data);

    // 4) (Esto NO se va a ejecutar mientras esté el dd)
    $obra->update($data);

    return redirect()->route('obras.index')
        ->with('success', 'Obra actualizada correctamente.');
}



    public function destroy(Obra $obra)
    {
        $obra->delete();

        return redirect()->route('obras.index')
            ->with('success', 'Obra eliminada correctamente.');
    }

    // En ObraController.php

 public function vincularPresupuesto(Request $request, $id)
{
    $obra = \App\Models\Obra::findOrFail($id);
    
    if ($request->has('presupuestos')) {
        // attach añade los registros a la tabla pivote obra_presupuesto
        $obra->presupuestos_vinculados()->attach($request->presupuestos);
    }

    return redirect()->route('obras.edit', ['obra' => $id, 'tab' => 'presupuestos'])
                     ->with('success', 'Presupuesto vinculado correctamente.');
}

// En app/Http/Controllers/ObraController.php
public function guardarPlaneacion(Request $request, $id)
{
    $obra = Obra::findOrFail($id);
    $datos = $request->input('plan', []);

    if (empty($datos)) {
        return redirect()->back()->with('info', 'No se enviaron datos para procesar.');
    }

    try {
        \DB::beginTransaction();

        foreach ($datos as $gasto_id => $semanas) {
            $gastoBase = \App\Models\ObraPlaneacionGasto::where('id', $gasto_id)
                ->where('numero_semana', 0)
                ->first();

            if (!$gastoBase) {
                \Log::warning("No se encontró gasto base para ID: {$gasto_id}");
                continue;
            }

            foreach ($semanas as $numero_semana => $monto) {
                if ($monto === null || $monto === '') {
                    continue;
                }

                $montoLimpio = (float) preg_replace('/[^-0-9.]/', '', (string) $monto);

                \App\Models\ObraPlaneacionSemanal::updateOrCreate(
                    [
                        'planeacion_gasto_id' => $gastoBase->id,
                        'numero_semana'       => (int) $numero_semana,
                    ],
                    [
                        'monto_programado' => $montoLimpio,
                    ]
                );
            }
        }

        \DB::commit();

        return redirect()->route('obras.edit', [
            'obra' => $id,
            'tab'  => 'planeacion',
        ])->with('success', 'Planeación guardada correctamente.');

    } catch (\Exception $e) {
        \DB::rollBack();

        \Log::error('Error guardando planeación', [
            'obra_id' => $id,
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);

        return redirect()->back()->with('error', 'Ocurrió un error al guardar los datos.');
    }
}
public function relacionarCfdis(Request $request, Obra $obra)
{
    $validated = $request->validate([
        'cfdis' => ['required', 'array', 'min:1'],
        'cfdis.*' => ['integer', 'exists:sat_cfdis,id'],
    ]);

    SatCfdi::whereIn('id', $validated['cfdis'])
        ->update([
            'obra_id' => $obra->id,
        ]);

    return response()->json([
        'ok' => true,
        'message' => 'CFDIs relacionados correctamente.',
        'total' => count($validated['cfdis']),
    ]);
}
}
