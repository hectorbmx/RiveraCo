<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Empleado;
use App\Models\ObraEmpleado;
use App\Models\Comision;
use App\Models\ComisionDetalle;
use App\Models\ObraMaquina;
use App\Models\Maquina;
use App\Models\Pila;
use App\Models\ObraPila;
use App\Models\CatalogoPila;
use Illuminate\Support\Facades\DB;
use App\Models\ObraMaquinaRegistro;
use App\Models\CatalogoActividadComision;
use App\Models\ObraAsistencia;
use Carbon\Carbon;




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
    

    if (!$desde && !$hasta) {
    $start = Carbon::now('America/Mexico_City')->startOfWeek(Carbon::MONDAY);
    $end   = Carbon::now('America/Mexico_City')->endOfWeek(Carbon::SUNDAY);

    $desde = $start->toDateString(); // YYYY-MM-DD
    $hasta = $end->toDateString();
}

    // Puestos BASE que se pueden asignar a una obra
    // (estos son los grupos normalizados en la columna puesto_base)
    $puestosBaseAsignables = [
        'RESIDENTE',
        'INGENIERO',
        'ARQUITECTO',
        'OPERADOR',
        'AYUDANTE',
        'TUBERO',
        'SOLDADOR',
        'PERFORADOR',
        'MECANICO',
        'CHOFER',
        'SUPERVISOR',
    ];

    // Cargar relaciones principales de la obra
    $obra->load([
        'cliente',
        'contratos',
        'planos',
        'presupuestos',
        'empleadosAsignados.empleado',
        'maquinasAsignadas.maquina',
    ]);

    $tab = $request->query('tab', 'general');

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
    $daysCount = 0;

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



    $currentStatus = $obra->estatus_nuevo;
    if (!is_null($currentStatus) && !is_numeric($currentStatus)) {
            $reverse = array_flip($statuses); // ['planeacion' => 1, ...]
            $currentStatus = $reverse[$currentStatus] ?? null;
        }

    // Empleados que YA están asignados activamente en alguna obra
    $empleadosOcupadosIds = ObraEmpleado::where('activo', true)
        ->whereNull('fecha_baja')
        ->pluck('empleado_id');


    $empleadosAsignables = Empleado::query()
        ->where('Estatus', 1)
        ->whereIn('puesto_base', $puestosBaseAsignables)
        ->whereNotIn('id_Empleado', $empleadosOcupadosIds)
        ->orderBy('Apellidos')
        ->orderBy('Nombre')
        ->get([
            'id_Empleado',
            'Nombre',
            'Apellidos',
            'Puesto',
            'puesto_base',
        ]);

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
}
