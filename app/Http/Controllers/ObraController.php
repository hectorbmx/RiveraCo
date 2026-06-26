<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\SatCfdi;
use App\Models\SatFactura;
use App\Models\ObraFacturaPago;
use App\Models\ObraFacturaBorrador;
use App\Models\CuentaBancoEmpresa;
use App\Models\MetodoPagoEmpresa;
use App\Models\SatConcepto;
use App\Models\User;
use App\Models\Empleado;
use App\Models\Area;
use App\Models\ObraTipoConfiguracion;
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
use Illuminate\Validation\ValidationException;
use App\Models\ObraMaquinaRegistro;
use App\Models\CatalogoActividadComision;
use App\Models\ObraAsistencia;
use Carbon\Carbon;
use App\Models\OrdenCompra;
use App\Models\ObraFolio;





class ObraController extends Controller
{
    private const TIPOS_OBRA_FOLIO = [
        'PILAS' => 'PI',
        'POZOS' => 'PO',
    ];

    private const TIPOS_OBRA_AREA = [
        'PILAS' => ['PILAS', 'PI'],
        'POZOS' => ['POZOS', 'PO'],
    ];

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $kpisObras = auth()->user()?->hasRole('super-admin')
            ? $this->kpisObrasEjecutivos()
            : null;

        $statusMap = Obra::estatusSlugs();

        $obras = Obra::with(['cliente', 'responsable', 'area'])
            ->tap(fn ($query) => $this->aplicarVisibilidadObras($query))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('clave_obra', 'like', "%{$search}%")
                      ->orWhereHas('cliente', function ($q2) use ($search) {
                          $q2->where('nombre_comercial', 'like', "%{$search}%")
                             ->orWhere('razon_social', 'like', "%{$search}%");
                      });
                });
            })
            ->when($status, function ($query, $status) use ($statusMap) {
                if (isset($statusMap[$status])) {
                    $query->where('estatus_nuevo', $statusMap[$status]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('obras.index', compact('obras', 'search', 'status', 'kpisObras'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre_comercial')->get();
        $responsables = Empleado::where('Estatus', 1)
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get();
        $tiposObraDisponibles = $this->tiposObraDisponibles();

        return view('obras.create', compact('clientes', 'responsables', 'tiposObraDisponibles'));
    }

    public function folioSiguiente(Request $request)
    {
        $data = $request->validate([
            'tipo_obra' => ['required', 'string', 'in:' . implode(',', array_keys($this->tiposObraDisponibles()))],
        ]);

        $anio = (int) Carbon::now('America/Mexico_City')->format('Y');
        $folio = $this->folioPreview($data['tipo_obra'], $anio);

        return response()->json([
            'folio' => $folio,
            'anio' => $anio,
        ]);
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'cliente_id'               => ['required', 'exists:clientes,id'],
        'nombre'                   => ['required', 'string', 'max:255'],
        'clave_obra'               => ['nullable', 'string', 'max:100'],
        'descripcion'              => ['nullable', 'string'],
        'tipo_obra'                => ['required', 'string', 'in:' . implode(',', array_keys($this->tiposObraDisponibles()))],
        'estatus_nuevo'            => ['required', 'numeric', 'in:' . implode(',', array_keys(Obra::estatusLabels()))],
        'fecha_inicio_programada'  => ['nullable', 'date'],
        'fecha_inicio_real'        => ['nullable', 'date'],
        'fecha_fin_programada'     => ['nullable', 'date'],
        'fecha_fin_real'           => ['nullable', 'date'],
        'monto_contratado'         => ['nullable', 'numeric'],
        'monto_modificado'         => ['nullable', 'numeric'],
        'responsable_id'           => ['nullable', 'exists:empleados,id_Empleado'],
        'ubicacion'                => ['nullable', 'string', 'max:255'],
        'profundidad_total'        => ['nullable', 'numeric', 'min:0'],
        'kg_acero_total'           => ['nullable', 'numeric', 'min:0'],
        'bentonita_total'          => ['nullable', 'numeric', 'min:0'],
        'concreto_total'           => ['nullable', 'numeric', 'min:0'],
    ]);

    $obra = DB::transaction(function () use ($data) {
        $data['clave_obra'] = $this->resolverClaveObra($data['tipo_obra'] ?? null, $data['clave_obra'] ?? null);
        $data['area_id'] = $this->areaIdParaTipoObra($data['tipo_obra']);

        if ($data['clave_obra'] === '' || Obra::where('clave_obra', $data['clave_obra'])->exists()) {
            throw ValidationException::withMessages([
                'clave_obra' => 'No se pudo generar una clave de obra disponible.',
            ]);
        }

        return Obra::create($data);
    });

    return redirect()->route('obras.edit', $obra)
        ->with('success', 'Obra creada correctamente.');
}

private function areaUsuarioActualId(): ?int
{
    return $this->empleadoUsuarioActual()?->area_id
        ? (int) $this->empleadoUsuarioActual()->area_id
        : null;
}

private function empleadoUsuarioActual(): ?Empleado
{
    return auth()->user()?->empleado;
}

private function usuarioActualEsResidente(): bool
{
    $empleado = $this->empleadoUsuarioActual();

    if (!$empleado) {
        return false;
    }

    $puesto = mb_strtoupper(trim(($empleado->puesto_base ?: $empleado->Puesto) ?? ''));

    return str_contains($puesto, 'RESIDENTE');
}

private function aplicarVisibilidadObras($query): void
{
    $user = auth()->user();

    if (!$user || $user->hasAnyRole(['super-admin', 'admin-rivera'])) {
        return;
    }

    $empleado = $this->empleadoUsuarioActual();

    if (!$empleado) {
        $query->whereRaw('1 = 0');
        return;
    }

    if ($this->usuarioActualEsResidente()) {
        $empleadoId = $empleado->id_Empleado;

        $query->where(function ($q) use ($empleadoId) {
            $q->where('responsable_id', $empleadoId)
                ->orWhereHas('empleadosAsignados', function ($asignacion) use ($empleadoId) {
                    $asignacion->where('empleado_id', $empleadoId)
                        ->where('activo', true);
                });
        });

        return;
    }

    $areaUsuarioId = $this->areaUsuarioActualId();

    if ($areaUsuarioId) {
        $query->where('area_id', $areaUsuarioId);
        return;
    }

    $query->whereRaw('1 = 0');
}

    private function tiposObraDisponibles(): array
    {
    $tipos = ObraTipoConfiguracion::query()
        ->where('activo', true)
        ->orderBy('tipo_obra')
        ->pluck('label', 'tipo_obra')
        ->all();

    if (!$tipos) {
        $tipos = [
            'PILAS' => 'Pilas',
            'POZOS' => 'Pozos',
        ];
    }

    $areaUsuarioId = $this->areaUsuarioActualId();

    if (!$areaUsuarioId || auth()->user()?->hasRole('admin-rivera') || $this->usuarioActualEsResidente()) {
        return $tipos;
    }

    return collect($tipos)
        ->filter(fn ($label, $tipo) => $this->areaIdParaTipoObra($tipo) === $areaUsuarioId)
        ->all();
}

private function tiposObraDisponiblesConActual(?string $tipoActual): array
{
    $tipos = $this->tiposObraDisponibles();
    $tipoActual = $tipoActual ? strtoupper(trim($tipoActual)) : null;

    if ($tipoActual && !array_key_exists($tipoActual, $tipos)) {
        $config = ObraTipoConfiguracion::where('tipo_obra', $tipoActual)->first();
        $tipos = [$tipoActual => $config?->label ?? ucfirst(strtolower($tipoActual))] + $tipos;
    }

    return $tipos;
}

private function areaIdParaTipoObra(string $tipoObra): ?int
{
    $tipoObra = strtoupper($tipoObra);

    $areaConfigurada = ObraTipoConfiguracion::where('tipo_obra', $tipoObra)->value('area_id');

    if ($areaConfigurada) {
        return (int) $areaConfigurada;
    }

    $codigos = self::TIPOS_OBRA_AREA[$tipoObra] ?? [];

    return Area::query()
        ->where(function ($query) use ($codigos, $tipoObra) {
            $query->whereIn('codigo', $codigos)
                ->orWhere('nombre', 'like', "%{$tipoObra}%")
                ->orWhere('nombre', 'like', '%' . ucfirst(strtolower($tipoObra)) . '%');
        })
        ->value('id');
}

private function abortarSiObraFueraDeArea(Obra $obra): void
{
    $user = auth()->user();

    if (!$user || $user->hasAnyRole(['super-admin', 'admin-rivera'])) {
        return;
    }

    $empleado = $this->empleadoUsuarioActual();

    if (!$empleado) {
        abort(403);
    }

    if ($this->usuarioActualEsResidente()) {
        $esResponsable = (int) $obra->responsable_id === (int) $empleado->id_Empleado;
        $estaAsignado = $obra->empleadosAsignados()
            ->where('empleado_id', $empleado->id_Empleado)
            ->where('activo', true)
            ->exists();

        if (!$esResponsable && !$estaAsignado) {
            abort(403);
        }

        return;
    }

    $areaUsuarioId = $this->areaUsuarioActualId();

    if (!$areaUsuarioId || (int) $obra->area_id !== $areaUsuarioId) {
        abort(403);
    }
}

private function resolverClaveObra(?string $tipoObra, ?string $claveActual): string
{
    $tipoObra = $tipoObra ? strtoupper($tipoObra) : null;
    $claveActual = trim((string) $claveActual);

    if (!$tipoObra || !isset(self::TIPOS_OBRA_FOLIO[$tipoObra])) {
        return $claveActual;
    }

    $anio = (int) Carbon::now('America/Mexico_City')->format('Y');
    $prefijo = self::TIPOS_OBRA_FOLIO[$tipoObra];
    $patronAuto = '/^' . preg_quote($prefijo, '/') . '-' . $anio . '-\d+$/';

    if ($claveActual !== '' && !preg_match($patronAuto, $claveActual)) {
        return $claveActual;
    }

    return $this->reservarFolio($tipoObra, $anio);
}

private function folioPreview(string $tipoObra, int $anio): string
{
    $folio = $this->folioBase($tipoObra, $anio);

    return $this->formatearFolio($folio->prefijo, $folio->anio, $folio->ultimo_consecutivo + 1);
}

private function reservarFolio(string $tipoObra, int $anio): string
{
    $folio = $this->folioBase($tipoObra, $anio, true);
    $folio->ultimo_consecutivo++;
    $folio->save();

    return $this->formatearFolio($folio->prefijo, $folio->anio, $folio->ultimo_consecutivo);
}

private function folioBase(string $tipoObra, int $anio, bool $lock = false): ObraFolio
{
    $tipoObra = strtoupper($tipoObra);
    $prefijo = self::TIPOS_OBRA_FOLIO[$tipoObra];

    $query = ObraFolio::where('tipo_obra', $tipoObra)->where('anio', $anio);

    if ($lock) {
        $query->lockForUpdate();
    }

    $folio = $query->first();

    if ($folio) {
        return $folio;
    }

    return ObraFolio::create([
        'tipo_obra' => $tipoObra,
        'prefijo' => $prefijo,
        'anio' => $anio,
        'ultimo_consecutivo' => $this->ultimoConsecutivoExistente($prefijo, $anio),
    ]);
}

private function ultimoConsecutivoExistente(string $prefijo, int $anio): int
{
    return Obra::where('clave_obra', 'like', "{$prefijo}-{$anio}-%")
        ->pluck('clave_obra')
        ->map(function ($clave) use ($prefijo, $anio) {
            if (preg_match('/^' . preg_quote($prefijo, '/') . '-' . $anio . '-(\d+)$/', $clave, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        })
        ->max() ?? 0;
}

private function formatearFolio(string $prefijo, int $anio, int $consecutivo): string
{
    return "{$prefijo}-{$anio}-{$consecutivo}";
}

private function kpisObrasEjecutivos(): array
{
    $obrasEjecucion = Obra::where('estatus_nuevo', Obra::ESTATUS_EJECUCION)->get([
        'id',
        'monto_contratado',
        'monto_modificado',
    ]);

    $montoVendido = $obrasEjecucion->sum(function (Obra $obra) {
        $montoModificado = (float) ($obra->monto_modificado ?? 0);

        return $montoModificado > 0
            ? $montoModificado
            : (float) ($obra->monto_contratado ?? 0);
    });

    $facturasFacturapi = SatFactura::query()
        ->whereNotNull('obra_id')
        ->whereHas('obra')
        ->where(function ($query) {
            $query->whereNull('estado')
                ->orWhere('estado', '!=', 'cancelada');
        })
        ->get(['id', 'uuid', 'total'])
        ->map(fn (SatFactura $factura) => [
            'key' => $factura->uuid ? 'uuid:' . strtoupper($factura->uuid) : 'sat_factura:' . $factura->id,
            'total' => (float) ($factura->total ?? 0),
        ]);

    $facturasSat = SatCfdi::query()
        ->whereNotNull('obra_id')
        ->whereHas('obra')
        ->get(['id', 'uuid', 'total'])
        ->map(fn (SatCfdi $cfdi) => [
            'key' => $cfdi->uuid ? 'uuid:' . strtoupper($cfdi->uuid) : 'sat_cfdi:' . $cfdi->id,
            'total' => (float) ($cfdi->total ?? 0),
        ]);

    $montoFacturado = $facturasFacturapi
        ->concat($facturasSat)
        ->unique('key')
        ->sum('total');

    $montoCobrado = (float) ObraFacturaPago::whereHas('obra')->sum('monto');

    return [
        'obras_ejecucion' => $obrasEjecucion->count(),
        'monto_vendido' => $montoVendido,
        'monto_facturado' => $montoFacturado,
        'monto_cobrado' => $montoCobrado,
        'pendiente_cobrar' => max(0, $montoFacturado - $montoCobrado),
    ];
}


public function edit(Request $request, Obra $obra)
{
    $this->abortarSiObraFueraDeArea($obra);

    $roles = \DB::table('catalogo_roles')->orderBy('nombre')->get();
    $clientes     = Cliente::orderBy('nombre_comercial')->get();
    $responsables = Empleado::where('Estatus', 1)
        ->orderBy('Nombre')
        ->orderBy('Apellidos')
        ->get();
    $tiposObraDisponibles = $this->tiposObraDisponiblesConActual($obra->tipo_obra);

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

     $statuses = Obra::estatusLabels();
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

    // --- SOLICITUDES DE GASTO (NUEVO) ---
    $solicitudesGastos = \App\Models\ObraSolicitudGasto::with([
            'detalles.planeacionGasto',
            'solicitadoPor',
            'autorizadoPor',
            'pagadoPor'
        ])
        ->where('obra_id', $obra->id)
        ->latest()
        ->get();

    $solicitudesStats = [
        'total' => $solicitudesGastos->count(),
        'solicitadas' => $solicitudesGastos->where('estatus', 'solicitado')->count(),
        'autorizadas' => $solicitudesGastos->where('estatus', 'autorizado')->count(),
        'pagadas'     => $solicitudesGastos->where('estatus', 'pagado')->count(),
    ];

    $solicitudesMontos = [
        'solicitado' => $solicitudesGastos->where('estatus', 'solicitado')->sum('total'),
        'autorizado' => $solicitudesGastos->where('estatus', 'autorizado')->sum('total'),
        'pagado'     => $solicitudesGastos->where('estatus', 'pagado')->sum('total'),
    ];

    // Crear mapa de montos solicitados/autorizados por semana para Planeación
    $montosSolicitadosMap = [];
    foreach ($solicitudesGastos as $sol) {
        if ($sol->estatus !== 'rechazado') {
            foreach ($sol->detalles as $det) {
                if (!isset($montosSolicitadosMap[$det->planeacion_gasto_id][$sol->semana])) {
                    $montosSolicitadosMap[$det->planeacion_gasto_id][$sol->semana] = 0;
                }
                $montosSolicitadosMap[$det->planeacion_gasto_id][$sol->semana] += (float) $det->monto_solicitado;
            }
        }
    }
    // ------------------------------------
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
        'pilas'       => [
            'programadas' => 0,
            'ejecutadas'  => 0,
            'detalle'     => collect(),
        ],
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

        $pilasAvance = $obra->pilas()
            ->withSum('detallesComision as cantidad_ejecutada', 'cantidad')
            ->where('activo', true)
            ->get()
            ->groupBy(fn ($pila) => $pila->tipo ?: 'Sin tipo')
            ->map(function ($items, $tipo) {
                $programadas = (int) $items->sum('cantidad_programada');
                $ejecutadas = (float) $items->sum(fn ($pila) => $pila->cantidad_ejecutada ?? 0);

                return [
                    'tipo' => $tipo,
                    'programadas' => $programadas,
                    'ejecutadas' => $ejecutadas,
                    'faltan' => max($programadas - $ejecutadas, 0),
                ];
            })
            ->values();

        $avanceObra['pilas'] = [
            'programadas' => (int) $pilasAvance->sum('programadas'),
            'ejecutadas'  => (float) $pilasAvance->sum('ejecutadas'),
            'detalle'     => $pilasAvance,
        ];
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

        $rfcCliente = $this->normalizeRfc($obra->cliente?->rfc);
        $clienteId = $obra->cliente_id;

        $facturasSatObra = $this->facturasSatObra($obra);
        $facturasDisponiblesRelacionar = $this->facturasDisponiblesParaObra($rfcCliente, $clienteId);
        $cuentasBanco = CuentaBancoEmpresa::where('activa', true)
            ->orderByDesc('principal')
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get();
        $metodosPago = MetodoPagoEmpresa::where('activo', true)
            ->orderBy('nombre')
            ->get();
        $facturaBorradores = $obra->facturaBorradores()
            ->with(['conceptoSat', 'creador', 'autorizador'])
            ->latest()
            ->get();
        $satConceptos = SatConcepto::where('activo', true)
            ->orderBy('descripcion')
            ->get();
        $usosCfdi = config('sat_catalogs.usos_cfdi', []);
        $metodosPagoCfdi = config('sat_catalogs.metodos_pago', []);
        $formasPagoCfdi = config('sat_catalogs.formas_pago', []);
        $regimenesFiscales = config('sat_catalogs.regimenes_fiscales', []);

        $totalFacturadoSat = (float) $facturasSatObra
            ->where('estado', '!=', 'cancelada')
            ->sum('total');
        $totalPagadoSat = (float) $facturasSatObra->sum('pagado');

        if ($facturasSatObra->isNotEmpty()) {
            $totalFacturado = $totalFacturadoSat;
            $totalPagado = $totalPagadoSat;
            $totalPendiente = max(0, $totalFacturadoSat - $totalPagadoSat);
        }

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
    'tiposObraDisponibles'        => $tiposObraDisponibles,
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
    'facturasSatObra'             => $facturasSatObra,
    'facturasDisponiblesRelacionar' => $facturasDisponiblesRelacionar,
    'cuentasBanco'                => $cuentasBanco,
    'metodosPago'                 => $metodosPago,
    'facturaBorradores'           => $facturaBorradores,
    'satConceptos'                => $satConceptos,
    'usosCfdi'                    => $usosCfdi,
    'metodosPagoCfdi'             => $metodosPagoCfdi,
    'formasPagoCfdi'              => $formasPagoCfdi,
    'regimenesFiscales'           => $regimenesFiscales,

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

    'solicitudesGastos' => $solicitudesGastos,
    'solicitudesStats' => $solicitudesStats,
    'solicitudesMontos' => $solicitudesMontos,
    'montosSolicitadosMap' => $montosSolicitadosMap,

    ]);
}


public function reporteAsistencias(Request $request, Obra $obra)
{
    $this->abortarSiObraFueraDeArea($obra);

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
    $this->abortarSiObraFueraDeArea($obra);
  
    // 2) Validar
    $data = $request->validate([
        'cliente_id'               => ['required', 'exists:clientes,id'],
        'nombre'                   => ['required', 'string', 'max:255'],
        'clave_obra'               => ['required', 'string', 'max:100', 'unique:obras,clave_obra,' . $obra->id],
        'descripcion'              => ['nullable', 'string'],
        'tipo_obra'                => ['required', 'string', 'in:' . implode(',', array_keys($this->tiposObraDisponiblesConActual($obra->tipo_obra)))],
        'estatus_nuevo'            => ['required', 'numeric', 'in:' . implode(',', array_keys(Obra::estatusLabels()))],
        'fecha_inicio_programada'  => ['nullable', 'date'],
        'fecha_inicio_real'        => ['nullable', 'date'],
        'fecha_fin_programada'     => ['nullable', 'date'],
        'fecha_fin_real'           => ['nullable', 'date'],
        'monto_contratado'         => ['nullable', 'numeric'],
        'monto_modificado'         => ['nullable', 'numeric'],
        'responsable_id'           => ['nullable', 'exists:empleados,id_Empleado'],
        'ubicacion'                => ['nullable', 'string', 'max:255'],
        'profundidad_total'        => ['nullable', 'numeric', 'min:0'],
        'kg_acero_total'           => ['nullable', 'numeric', 'min:0'],
        'bentonita_total'          => ['nullable', 'numeric', 'min:0'],
        'concreto_total'           => ['nullable', 'numeric', 'min:0'],
    ]);

    // 3) Ver qué datos SI están pasando la validación
    // dd($data);

    // 4) (Esto NO se va a ejecutar mientras esté el dd)
    $data['area_id'] = $this->areaIdParaTipoObra($data['tipo_obra']);

    $obra->update($data);

    return redirect()->route('obras.index')
        ->with('success', 'Obra actualizada correctamente.');
}



    public function destroy(Obra $obra)
    {
        $this->abortarSiObraFueraDeArea($obra);

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
private function normalizeRfc(?string $rfc): string
{
    return preg_replace('/[^A-Z0-9&Ñ]/u', '', strtoupper(trim((string) $rfc))) ?? '';
}

private function facturasSatObra(Obra $obra): Collection
{
    $pagosPorUuid = ObraFacturaPago::with(['cuentaBanco', 'metodoPago', 'registradoPor'])
        ->where('obra_id', $obra->id)
        ->orderByDesc('fecha_pago')
        ->get()
        ->groupBy(fn (ObraFacturaPago $pago) => strtoupper($pago->factura_uuid));

    $facturasApi = SatFactura::with(['empresa', 'cliente'])
        ->where('obra_id', $obra->id)
        ->whereNotNull('uuid')
        ->orderByDesc('fecha_emision')
        ->get()
        ->map(fn (SatFactura $factura) => [
            'source' => 'sat_facturas',
            'source_label' => 'Facturapi',
            'id' => $factura->id,
            'uuid' => $factura->uuid,
            'serie' => $factura->serie,
            'folio' => $factura->folio,
            'fecha_emision' => optional($factura->fecha_emision)->format('Y-m-d'),
            'fecha_formateada' => optional($factura->fecha_emision)->format('d/m/Y'),
            'receptor_nombre' => $factura->receptor_nombre,
            'receptor_rfc' => $factura->receptor_rfc,
            'total' => (float) $factura->total,
            'moneda' => $factura->moneda ?? 'MXN',
            'estado' => $factura->estado,
            'metodo_pago' => $factura->metodo_pago,
            'pdf_route' => route('sat.facturacion.pdf', $factura),
        ]);

    $cfdisSat = SatCfdi::where('obra_id', $obra->id)
        ->whereNotNull('uuid')
        ->orderByDesc('fecha_emision')
        ->get()
        ->map(fn (SatCfdi $cfdi) => [
            'source' => 'sat_cfdis',
            'source_label' => 'SAT',
            'id' => $cfdi->id,
            'uuid' => $cfdi->uuid,
            'serie' => $cfdi->serie,
            'folio' => $cfdi->folio,
            'fecha_emision' => optional($cfdi->fecha_emision)->format('Y-m-d'),
            'fecha_formateada' => optional($cfdi->fecha_emision)->format('d/m/Y'),
            'receptor_nombre' => $cfdi->receptor_nombre,
            'receptor_rfc' => $cfdi->receptor_rfc ?: $cfdi->rfc_receptor,
            'total' => (float) $cfdi->total,
            'moneda' => $cfdi->moneda ?? 'MXN',
            'estado' => null,
            'metodo_pago' => $cfdi->metodo_pago,
            'pdf_route' => route('sat.cfdis.pdf', $cfdi),
        ]);

    return $facturasApi
        ->concat($cfdisSat)
        ->filter(fn ($factura) => !empty($factura['uuid']))
        ->unique(fn ($factura) => strtoupper($factura['uuid']))
        ->map(function (array $factura) use ($pagosPorUuid) {
            $pagos = $pagosPorUuid->get(strtoupper($factura['uuid']), collect())->values();
            $pagado = (float) $pagos->sum('monto');
            $total = (float) $factura['total'];
            $saldo = max(0, round($total - $pagado, 2));

            $factura['pagos'] = $pagos;
            $factura['pagado'] = $pagado;
            $factura['saldo'] = $saldo;
            $factura['estado_pago'] = $saldo <= 0
                ? 'pagada'
                : ($pagado > 0 ? 'parcial' : 'pendiente');
            $factura['requiere_complemento_pago'] = strtoupper((string) ($factura['metodo_pago'] ?? '')) === 'PPD';

            return $factura;
        })
        ->sortByDesc(fn ($factura) => $factura['fecha_emision'] ?? '')
        ->values();
}

private function facturasDisponiblesParaObra(string $rfcCliente, ?int $clienteId): Collection
{
    $facturasApi = SatFactura::whereNull('obra_id')
        ->whereNotNull('uuid')
        ->where(function ($query) use ($rfcCliente, $clienteId) {
            if ($rfcCliente !== '') {
                $query->where('receptor_rfc', $rfcCliente);
            }

            if ($clienteId) {
                $query->orWhere('cliente_id', $clienteId);
            }
        })
        ->orderByDesc('fecha_emision')
        ->limit(300)
        ->get()
        ->map(fn (SatFactura $factura) => [
            'id' => $factura->uuid,
            'uuid' => $factura->uuid,
            'fecha_emision' => optional($factura->fecha_emision)->format('Y-m-d'),
            'total' => (float) $factura->total,
            'origen' => 'FacturAPI',
            'receptor_nombre' => $factura->receptor_nombre,
            'receptor_rfc' => $factura->receptor_rfc,
        ]);

    $cfdisSat = SatCfdi::whereNull('obra_id')
        ->whereNotNull('uuid')
        ->when($rfcCliente !== '', function ($query) use ($rfcCliente) {
            $query->where(function ($subquery) use ($rfcCliente) {
                $subquery
                    ->where('receptor_rfc', $rfcCliente)
                    ->orWhere('rfc_receptor', $rfcCliente);
            });
        })
        ->orderByDesc('fecha_emision')
        ->limit(300)
        ->get()
        ->map(fn (SatCfdi $cfdi) => [
            'id' => $cfdi->uuid,
            'uuid' => $cfdi->uuid,
            'fecha_emision' => optional($cfdi->fecha_emision)->format('Y-m-d'),
            'total' => (float) $cfdi->total,
            'origen' => 'SAT',
            'receptor_nombre' => $cfdi->receptor_nombre,
            'receptor_rfc' => $cfdi->receptor_rfc ?: $cfdi->rfc_receptor,
        ]);

    return $facturasApi
        ->concat($cfdisSat)
        ->filter(fn ($factura) => !empty($factura['uuid']))
        ->unique(fn ($factura) => strtoupper($factura['uuid']))
        ->sortByDesc(fn ($factura) => $factura['fecha_emision'] ?? '')
        ->values();
}

public function storeFacturaPago(Request $request, Obra $obra)
{
    $data = $request->validate([
        'factura_uuid' => ['required', 'string', 'max:80'],
        'factura_source' => ['nullable', 'string', 'max:30'],
        'monto' => ['required', 'numeric', 'min:0.01'],
        'fecha_pago' => ['required', 'date'],
        'cuenta_banco_empresa_id' => ['nullable', 'exists:cuentas_banco_empresa,id'],
        'metodo_pago_empresa_id' => ['nullable', 'exists:metodos_pago_empresa,id'],
        'referencia' => ['nullable', 'string', 'max:120'],
        'observaciones' => ['nullable', 'string'],
        'comprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
    ]);

    $uuid = trim($data['factura_uuid']);
    $factura = SatFactura::where('obra_id', $obra->id)->where('uuid', $uuid)->first();
    $cfdi = $factura ? null : SatCfdi::where('obra_id', $obra->id)->where('uuid', $uuid)->first();

    if (!$factura && !$cfdi) {
        return back()->with('error', 'La factura no esta ligada a esta obra.');
    }

    $totalFactura = (float) ($factura?->total ?? $cfdi?->total ?? 0);
    $pagadoActual = (float) ObraFacturaPago::where('obra_id', $obra->id)
        ->where('factura_uuid', $uuid)
        ->sum('monto');
    $saldo = max(0, round($totalFactura - $pagadoActual, 2));
    $monto = round((float) $data['monto'], 2);

    if ($monto > $saldo) {
        return back()
            ->withInput()
            ->with('error', 'El monto del pago no puede ser mayor al saldo pendiente de la factura.');
    }

    $metodoPagoCfdi = strtoupper((string) ($factura?->metodo_pago ?? $cfdi?->metodo_pago ?? ''));
    $comprobantePath = null;
    $comprobanteNombreOriginal = null;
    $comprobanteMime = null;

    if ($request->hasFile('comprobante')) {
        $comprobante = $request->file('comprobante');
        $comprobantePath = $comprobante->store("obras/{$obra->id}/pagos-facturas", 'public');
        $comprobanteNombreOriginal = $comprobante->getClientOriginalName();
        $comprobanteMime = $comprobante->getClientMimeType();
    }

    ObraFacturaPago::create([
        'obra_id' => $obra->id,
        'factura_uuid' => $uuid,
        'factura_source' => $data['factura_source'] ?? ($factura ? 'sat_facturas' : 'sat_cfdis'),
        'monto' => $monto,
        'fecha_pago' => $data['fecha_pago'],
        'cuenta_banco_empresa_id' => $data['cuenta_banco_empresa_id'] ?? null,
        'metodo_pago_empresa_id' => $data['metodo_pago_empresa_id'] ?? null,
        'referencia' => $data['referencia'] ?? null,
        'observaciones' => $data['observaciones'] ?? null,
        'comprobante_path' => $comprobantePath,
        'comprobante_nombre_original' => $comprobanteNombreOriginal,
        'comprobante_mime' => $comprobanteMime,
        'requiere_complemento_pago' => $metodoPagoCfdi === 'PPD',
        'registrado_por' => auth()->id(),
        'registrado_at' => now(),
    ]);

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'facturacion'])
        ->with('success', $metodoPagoCfdi === 'PPD'
            ? 'Pago registrado. Esta factura requiere complemento de pago timbrado.'
            : 'Pago registrado correctamente.');
}

public function storeFacturaBorrador(Request $request, Obra $obra)
{
    $this->abortarSiObraFueraDeArea($obra);
    abort_unless(auth()->user()?->can('obra_factura_borradores.create.access'), 403);

    $formasPago = array_keys(config('sat_catalogs.formas_pago', []));
    $metodosPago = array_keys(config('sat_catalogs.metodos_pago', []));
    $usosCfdi = array_keys(config('sat_catalogs.usos_cfdi', []));

    $data = $request->validate([
        'fecha' => ['required', 'date'],
        'forma_pago' => ['nullable', 'string', 'in:' . implode(',', $formasPago)],
        'metodo_pago' => ['required', 'string', 'in:' . implode(',', $metodosPago)],
        'uso_cfdi' => ['required', 'string', 'in:' . implode(',', $usosCfdi)],
        'sat_concepto_id' => ['required', 'exists:sat_conceptos,id'],
        'concepto_descripcion' => ['required', 'string', 'max:255'],
        'cantidad' => ['required', 'numeric', 'min:0.000001'],
        'subtotal' => ['required', 'numeric', 'min:0'],
        'iva' => ['nullable', 'numeric', 'min:0'],
        'retenciones' => ['nullable', 'numeric', 'min:0'],
        'descuentos' => ['nullable', 'numeric', 'min:0'],
    ]);

    $cliente = $obra->cliente;

    if (! $cliente) {
        return back()->withInput()->with('error', 'La obra no tiene cliente asignado.');
    }

    $subtotal = round((float) $data['subtotal'], 2);
    $iva = round((float) ($data['iva'] ?? 0), 2);
    $retenciones = round((float) ($data['retenciones'] ?? 0), 2);
    $descuentos = round((float) ($data['descuentos'] ?? 0), 2);
    $total = round(max(0, $subtotal + $iva - $retenciones - $descuentos), 2);

    ObraFacturaBorrador::create([
        'obra_id' => $obra->id,
        'cliente_id' => $cliente->id,
        'fecha' => $data['fecha'],
        'forma_pago' => $data['forma_pago'] ?? null,
        'metodo_pago' => $data['metodo_pago'],
        'uso_cfdi' => $data['uso_cfdi'],
        'regimen_fiscal' => $cliente->regimen_fiscal,
        'sat_concepto_id' => $data['sat_concepto_id'],
        'concepto_descripcion' => $data['concepto_descripcion'],
        'cantidad' => $data['cantidad'],
        'subtotal' => $subtotal,
        'iva' => $iva,
        'retenciones' => $retenciones,
        'descuentos' => $descuentos,
        'total' => $total,
        'estatus' => ObraFacturaBorrador::ESTATUS_PENDIENTE_REVISION,
        'creado_por' => auth()->id(),
    ]);

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'facturacion'])
        ->with('success', 'Borrador de factura creado correctamente.');
}

public function printFacturaBorrador(Obra $obra, ObraFacturaBorrador $borrador)
{
    $this->abortarSiObraFueraDeArea($obra);
    abort_unless(auth()->user()?->can('obra_factura_borradores.print.access'), 403);

    if ((int) $borrador->obra_id !== (int) $obra->id) {
        abort(404);
    }

    $borrador->load(['obra.cliente', 'conceptoSat', 'creador', 'autorizador']);

    return view('obras.factura-borradores.print', [
        'obra' => $obra,
        'borrador' => $borrador,
        'regimenesFiscales' => config('sat_catalogs.regimenes_fiscales', []),
        'usosCfdi' => config('sat_catalogs.usos_cfdi', []),
        'metodosPagoCfdi' => config('sat_catalogs.metodos_pago', []),
        'formasPagoCfdi' => config('sat_catalogs.formas_pago', []),
    ]);
}

public function relacionarCfdis(Request $request, Obra $obra)
{
    $validated = $request->validate([
        'cfdis' => ['required', 'array', 'min:1'],
        'cfdis.*' => ['string'], // Ahora recibimos UUIDs
    ]);

    $uuids = $validated['cfdis'];

    \App\Models\SatCfdi::whereIn('uuid', $uuids)
        ->update([
            'obra_id' => $obra->id,
        ]);

    \App\Models\SatFactura::whereIn('uuid', $uuids)
        ->update([
            'obra_id' => $obra->id,
        ]);

    return response()->json([
        'ok' => true,
        'message' => 'Facturas relacionadas correctamente.',
        'total' => count($uuids),
    ]);
}
}
