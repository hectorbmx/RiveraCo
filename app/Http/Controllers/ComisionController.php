<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Comision;
use App\Models\ObraPila;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ComisionPersonal;
use App\Models\ComisionDetalle;
use App\Models\ComisionPerforacion;
use App\Models\CatalogoRol;
use App\Models\CatalogoActividadComision;
use App\Models\ComisionTarifario;
use App\Models\ComisionTarifarioDetalle;


class ComisionController extends Controller
{
    /**
     * Mostrar listado de comisiones de una obra (TAB Comisiones).
     */
  
    public function index(Request $request, Obra $obra)
    {
        // 1) Fecha seleccionada en el filtro (puede venir nula)
        $selectedFecha = $request->input('fecha');

        // 2) Fechas disponibles (histórico) para llenar el <select>
        $fechasDisponibles = $obra->comisiones()
            ->select('fecha')
            ->distinct()
            ->orderByDesc('fecha')
            ->pluck('fecha');    // colección de fechas

        // 3) Query base
        $query = $obra->comisiones()
            ->with('pila')
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        // 4) Si el usuario filtró por fecha, aplicamos el where
        if ($selectedFecha) {
            $query->whereDate('fecha', $selectedFecha);
        }

        // 5) Paginamos y mantenemos el parámetro fecha en la paginación
        $comisiones = $query->paginate(15)->appends([
            'fecha' => $selectedFecha,
        ]);

        return view('obras.comisiones.index', [
            'obra'              => $obra,
            'comisiones'        => $comisiones,
            'fechasDisponibles' => $fechasDisponibles,
            'selectedFecha'     => $selectedFecha,
        ]);
    }

    /**
     * Mostrar formulario para crear una nueva comisión.
     */
    public function create(Obra $obra)
    {
        // Pilas activas de esta obra
        $pilas = $obra->pilas()
            ->activas()
            ->orderBy('id')
            ->get();
            $actividades = CatalogoActividadComision::where('activa', 1)
    ->orderBy('orden')
    ->get();


        // $roles = CatalogoRol::orderBy('nombre'->get);

        // Empleados asignados a la obra (para llenar tabla de personal)
        $asignacionesEmpleados = ObraEmpleado::with('empleado')
            ->where('obra_id', $obra->id)
            ->where('activo', 1)
            ->get();

        $residenteAsignado = $asignacionesEmpleados->first(function ($asignacion) {
            $emp = $asignacion->empleado;
            if (!$emp) {
                return false;
            }

            // usamos puesto_base o Puesto para identificar RESIDENTE
            $puesto = strtoupper($emp->puesto_base ?? $emp->Puesto ?? '');
            return $puesto === 'RESIDENTE';
        });

        // Máquinas asignadas a la obra y activas
        $asignacionesMaquinas = ObraMaquina::with('maquina')
            ->where('obra_id', $obra->id)
            ->activas()
            ->get();
        
        $maquinaPerforadora = $asignacionesMaquinas->first(function ($asignacion) {
        $maq = $asignacion->maquina;
        if (!$maq) return false;

        // Ajusta el campo: tipo_base, tipo, categoria, etc.
        $tipo = strtoupper($maq->tipo ?? $maq->codigo ?? '');
        return $tipo === 'PERFORADORA';
    }) ?? $asignacionesMaquinas->first(); // fallback

        return view('obras.comisiones.create', [
            'obra'                => $obra,
            'pilas'               => $pilas,
            'asignacionesEmpleados' => $asignacionesEmpleados,
            'asignacionesMaquinas'  => $asignacionesMaquinas,
            'residenteAsignado'   => $residenteAsignado,
            'maquinaPerforadora'  => $maquinaPerforadora,
            'actividades' => $actividades,
            // 'roles'               => $roles,
        ]);
    }

    /**
     * Guardar la nueva comisión en BD.
     * (Luego llenamos la lógica completa paso a paso)
     */
// public function store(Request $request, Obra $obra)
// {
  
// $rolResidenteId = CatalogoRol::where('rol_key', 'RESIDENTE')->value('id');

//     $asignacionResidente = ObraEmpleado::with('empleado')
//         ->where('obra_id', $obra->id)
//         ->where('activo', 1)
//         ->where('rol_id', $rolResidenteId)   // <-- CLAVE
//         ->first();

//     $residenteId = $asignacionResidente?->empleado_id; // puede ser null

//     // 2) Validación
//     $validated = $request->validate([
//         'fecha'          => ['required', 'date'],
//         // 'pila_id'        => ['required', 'exists:obras_pilas,id'],
//         // 'pila_id'          => ['nullable', 'exists:obras_pilas,id'],

//         'numero_formato' => ['nullable', 'string', 'max:50'],
//         'cliente_nombre' => ['nullable', 'string', 'max:255'],
//         'observaciones'  => ['nullable', 'string'],
//         'obra_maquina_id' => ['nullable','integer', 'exists:obra_maquina,id'],

//         'personales'                         => ['array'],
//         'personales.*.asignacion_empleado_id'=> ['nullable', 'integer'],
//         'personales.*.hora_inicio'           => ['nullable', 'date_format:H:i'],
//         'personales.*.hora_fin'              => ['nullable', 'date_format:H:i'],
//         'personales.*.tiempo_comida'         => ['nullable', 'numeric', 'min:0'],
//         'personales.*.horas_laboradas'       => ['nullable', 'numeric', 'min:0'],
//         'personales.*.tiempo_extra'          => ['nullable', 'numeric', 'min:0'],
//         'personales.*.actividades' => ['nullable','array'],
//         'personales.*.actividades.*' => ['integer','exists:catalogo_actividades_comision,id'],

//         'detalles'                => ['array'],
//         'detalles.*.pila_id'       => ['required', 'integer', 'exists:obras_pilas,id'],
//         'detalles.*.diametro'     => ['nullable', 'string', 'max:50'],
//         'detalles.*.cantidad'     => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.profundidad'  => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.metros_comision' => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.kg_acero'     => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.vol_bentonita'=> ['nullable', 'numeric', 'min:0'],
//         'detalles.*.vol_concreto' => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.adicional'    => ['nullable', 'numeric', 'min:0'],

//             // 👇 NUEVO: horas de perforación dentro del detalle
//         'detalles.*.hora_inicio'     => ['nullable', 'date_format:H:i'],
//         'detalles.*.hora_fin'        => ['nullable', 'date_format:H:i'],

//         'detalles.*.ml_ademe_bauer' => ['nullable', 'numeric', 'min:0'],
//         'detalles.*.campana_pzas'   => ['nullable', 'integer', 'min:0'],


//         // 'perforaciones'              => ['array'],
//         // 'perforaciones.*.inicio'     => ['nullable', 'date_format:H:i'],
//         // 'perforaciones.*.termino'    => ['nullable', 'date_format:H:i'],
//         // 'perforaciones.*.info_pila'  => ['nullable', 'string', 'max:255'],
//     ]);

//     // 3) Guardar TODO en una sola transacción
//     $obraMaquinaId = $validated['obra_maquina_id'] ?? null;
//     $comision = null;

//     DB::transaction(function () use ($obra, $validated, $residenteId,$obraMaquinaId, &$comision) {

//         $primerDetalle = $validated['detalles'][0] ?? null;
//         $pilaCabeceraId  = $primerDetalle['pila_id'] ?? null;

//         $ids = collect($validated['personales'] ?? [])
//             ->pluck('asignacion_empleado_id')
//             ->filter()
//             ->unique()
//             ->values();

//         $rolMap = ObraEmpleado::whereIn('id', $ids)
//             ->where('obra_id', $obra->id) // seguridad
//             ->pluck('rol_id', 'id');      // [obra_empleado_id => rol_id]

//         // 3.1 Cabecera
//         $comision = Comision::create([
//             'obra_id'        => $obra->id,
//             // 'pila_id'        => $validated['pila_id'] ?? null,
//             'pila_id'        => $pilaCabeceraId,
//             'fecha'          => $validated['fecha'],
//             'residente_id'   => $residenteId, // ahora sí usamos al residente real
//             'numero_formato' => $validated['numero_formato'] ?? null,
//             'cliente_nombre' => $validated['cliente_nombre'] ?? null,
//             'observaciones'  => $validated['observaciones'] ?? null,
//             'created_by'     => auth()->id(),
//             'updated_by'     => auth()->id(),
//         ]);
//         $ids = collect($validated['personales'] ?? [])
//             ->pluck('asignacion_empleado_id')
//             ->filter()
//             ->unique()
//             ->values();

        

//         // 3.2 comision_personal
//       foreach ($validated['personales'] ?? [] as $row) {
//     if (empty($row['asignacion_empleado_id'])) continue;
//     if (empty($row['hora_inicio']) && empty($row['hora_fin'])) continue;

//     $obraEmpleadoId = (int) $row['asignacion_empleado_id'];

//     $cp=ComisionPersonal::create([
//         'comision_id'      => $comision->id,
//         'obra_empleado_id' => $obraEmpleadoId,
//         'obra_maquina_id'  => null,
//         'rol_id'           => $rolMap[$obraEmpleadoId] ?? null,
//         'rol'              => null, // ya no usar texto
//         'trabaja'          => 1,
//         'hora_inicio'      => $row['hora_inicio'] ?? null,
//         'hora_fin'         => $row['hora_fin'] ?? null,
//         'comida_min'       => isset($row['tiempo_comida']) ? (int) round($row['tiempo_comida'] * 60) : 0,
//         'horas_laboradas'  => $row['horas_laboradas'] ?? 0,
//         'tiempo_extra'     => $row['tiempo_extra'] ?? 0,
//     ]);
// }
//     $actividadIds = $row['actividades'] ?? [];
//     $cp->actividades()->sync(is_array($actividadIds) ? $actividadIds : []);


//         // 3.3 comision_detalles
//         foreach ($validated['detalles'] ?? [] as $row) {
//             if (!array_filter($row)) {
//                 continue;
//             }

//             ComisionDetalle::create([
//                 'comision_id'             => $comision->id,
//                 'obra_maquina_id'         => $obraMaquinaId,
//                 'diametro'                => $row['diametro'] ?? null,
//                 'cantidad'                => $row['cantidad'] ?? 0,
//                 'profundidad'             => $row['profundidad'] ?? 0,
//                 'metros_sujetos_comision' => $row['metros_comision'] ?? 0,
//                 'kg_acero'                => $row['kg_acero'] ?? 0,
//                 'vol_bentonita'           => $row['vol_bentonita'] ?? 0,
//                 'vol_concreto'            => $row['vol_concreto'] ?? 0,
//                 'adicional'               => $row['adicional'] ?? null,
//                 'ml_ademe_bauer'          => $row['ml_ademe_bauer'] ?? 0,
//                 'campana_pzas'            => $row['campana_pzas'] ?? 0,
       
//             ]);
//         }

//         // 3.4 comision_perforaciones
//         foreach ($validated['detalles'] ?? [] as $row) {
//             if (!array_filter($row)) {
//                 continue;
//             }

//             ComisionPerforacion::create([
//                 'comision_id'      => $comision->id,
//                 'hora_inicio'      => $row['hora_inicio'] ?? null,
//                 'hora_termino'     => $row['hora_fin'] ?? null,
//                 'informacion_pila' => $row['diametro'] ?? '',
//             ]);
//         }
//     });
// // 1) Tarifario vigente
// $tarifario = ComisionTarifario::query()
//     ->where('estado', 'vigente')
//     ->orderByDesc('vigente_desde')
//     ->first()
//     ?? ComisionTarifario::query()->orderByDesc('id')->first();

// if ($tarifario) {

//     // 2) Totales del formato (sumamos variables desde comision_detalles)
//     $totales = [
//         'metros_sujetos_comision' => (float) $comision->detalles()->sum('metros_sujetos_comision'),
//         'kg_acero'                => (float) $comision->detalles()->sum('kg_acero'),
//         'vol_bentonita'           => (float) $comision->detalles()->sum('vol_bentonita'),
//         'vol_concreto'            => (float) $comision->detalles()->sum('vol_concreto'),
//         'campana_pzas'            => (float) $comision->detalles()->sum('campana_pzas'),
//     ];

//     // 3) Pre-cargar detalles de tarifario para evitar queries por empleado
//     $tarifas = ComisionTarifarioDetalle::query()
//         ->where('tarifario_id', $tarifario->id)
//         ->get()
//         ->groupBy(fn($t) => $t->rol_id.'|'.$t->concepto.'|'.$t->variable_origen);

//     // 4) Recalcular importe por empleado
//     $personales = $comision->personales()->with('actividades')->get();

//     foreach ($personales as $cp) {

//         $rolId = $cp->rol_id;
//         $importe = 0.0;

//         // 4.1 Producción por actividades checked
//         foreach ($cp->actividades as $act) {
//             $var = $act->key; // DEBE coincidir con variable_origen y con la columna en comision_detalles

//             $cantidad = (float) ($totales[$var] ?? 0);

//             $k = $rolId.'|produccion|'.$var;
//             $tarifa = $tarifas[$k][0] ?? null;

//             if ($tarifa && $cantidad > 0) {
//                 $importe += $cantidad * (float) $tarifa->tarifa;
//             }
//         }

//         // 4.2 Hora extra (sin check)
//         $horasExtra = (float) ($cp->tiempo_extra ?? 0);
//         if ($horasExtra > 0) {
//             $k = $rolId.'|hora_extra|hora_extra';
//             $tarifaExtra = $tarifas[$k][0] ?? null;

//             if ($tarifaExtra) {
//                 $importe += $horasExtra * (float) $tarifaExtra->tarifa;
//             }
//         }

//         // 4.3 Guardar
//         $cp->importe_comision = $importe;
//         $cp->save();
//     }
// }

//     return redirect()
//         ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'comisiones'])
//         ->with('success', 'Comisión creada correctamente.');
// }

public function store(Request $request, Obra $obra)
{
    // ==========================================================
    // 1) Residente real (por rol RESIDENTE en obra_empleado)
    // ==========================================================
    $rolResidenteId = CatalogoRol::where('rol_key', 'RESIDENTE')->value('id');

    $asignacionResidente = ObraEmpleado::with('empleado')
        ->where('obra_id', $obra->id)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->where('rol_id', $rolResidenteId)
        ->first();

    $residenteId = $asignacionResidente?->empleado_id; // puede ser null

    // ==========================================================
    // 2) Validación
    // ==========================================================
    $validated = $request->validate([
        'fecha'           => ['required', 'date'],
        'numero_formato'  => ['nullable', 'string', 'max:50'],
        'cliente_nombre'  => ['nullable', 'string', 'max:255'],
        'observaciones'   => ['nullable', 'string'],

        // Máquina seleccionada (se guarda en comision_detalles)
        'obra_maquina_id' => ['nullable', 'integer', 'exists:obra_maquina,id'],

        // Si tu form ya manda trabajo_id, descomenta:
        // 'trabajo_id'      => ['nullable', 'integer', 'exists:catalogo_trabajos_comision,id'],

        'personales'                          => ['array'],
        'personales.*.asignacion_empleado_id' => ['nullable', 'integer'],
        'personales.*.hora_inicio'            => ['nullable', 'date_format:H:i'],
        'personales.*.hora_fin'               => ['nullable', 'date_format:H:i'],
        'personales.*.tiempo_comida'          => ['nullable', 'numeric', 'min:0'], // horas
        'personales.*.horas_laboradas'        => ['nullable', 'numeric', 'min:0'],
        'personales.*.tiempo_extra'           => ['nullable', 'numeric', 'min:0'],
        'personales.*.actividades'            => ['nullable', 'array'],
        'personales.*.actividades.*'          => ['integer', 'exists:catalogo_actividades_comision,id'],

        'detalles'                            => ['array'],
        'detalles.*.pila_id'                  => ['required', 'integer', 'exists:obras_pilas,id'],
        'detalles.*.diametro'                 => ['nullable', 'string', 'max:50'],
        'detalles.*.cantidad'                 => ['nullable', 'numeric', 'min:0'],
        'detalles.*.profundidad'              => ['nullable', 'numeric', 'min:0'],
        'detalles.*.metros_comision'          => ['nullable', 'numeric', 'min:0'],
        'detalles.*.kg_acero'                 => ['nullable', 'numeric', 'min:0'],
        'detalles.*.vol_bentonita'            => ['nullable', 'numeric', 'min:0'],
        'detalles.*.vol_concreto'             => ['nullable', 'numeric', 'min:0'],
        'detalles.*.adicional'                => ['nullable', 'numeric', 'min:0'],
        'detalles.*.ml_ademe_bauer'           => ['nullable', 'numeric', 'min:0'],
        'detalles.*.campana_pzas'             => ['nullable', 'integer', 'min:0'],

        // horas de perforación dentro del detalle (se guardan en comision_perforaciones)
        'detalles.*.hora_inicio'              => ['nullable', 'date_format:H:i'],
        'detalles.*.hora_fin'                 => ['nullable', 'date_format:H:i'],
    ]);

    $obraMaquinaId = $validated['obra_maquina_id'] ?? null;
    // Si ya lo mandas desde form:
    // $trabajoId     = $validated['trabajo_id'] ?? null;
    $trabajoId = $request->input('trabajo_id'); // <- si aún no lo validas, al menos lo lee

    $comision = null;

    DB::transaction(function () use (
        $obra,
        $validated,
        $residenteId,
        $obraMaquinaId,
        $trabajoId,
        &$comision
    ) {

        // ==========================================================
        // 3) Tarifario a usar (vigente o último)
        // ==========================================================
        $tarifario = ComisionTarifario::query()
            ->where('estado', 'vigente')
            ->orderByDesc('vigente_desde')
            ->first()
            ?? ComisionTarifario::query()->orderByDesc('id')->first();

        // ==========================================================
        // 4) Pila cabecera (primer detalle)
        // ==========================================================
        $primerDetalle   = $validated['detalles'][0] ?? null;
        $pilaCabeceraId  = $primerDetalle['pila_id'] ?? null;

        // ==========================================================
        // 5) Mapa de roles para obra_empleado_id seleccionados
        // ==========================================================
        $idsAsignacion = collect($validated['personales'] ?? [])
            ->pluck('asignacion_empleado_id')
            ->filter()
            ->unique()
            ->values();

        $rolMap = ObraEmpleado::query()
            ->where('obra_id', $obra->id)
            ->whereIn('id', $idsAsignacion)
            ->pluck('rol_id', 'id'); // [obra_empleado_id => rol_id]

        // ==========================================================
        // 6) CABECERA: comisiones
        //    (IMPORTANTE: aquí van trabajo_id y tarifario_id)
        // ==========================================================
        $comision = Comision::create([
            'obra_id'        => $obra->id,
            'pila_id'        => $pilaCabeceraId,
            'fecha'          => $validated['fecha'],
            'residente_id'   => $residenteId,
            'numero_formato' => $validated['numero_formato'] ?? null,
            'cliente_nombre' => $validated['cliente_nombre'] ?? null,
            'observaciones'  => $validated['observaciones'] ?? null,

            'trabajo_id'     => $trabajoId ?: null,
            'tarifario_id'   => $tarifario?->id,

            'created_by'     => auth()->id(),
            'updated_by'     => auth()->id(),
        ]);

        // ==========================================================
        // 7) HIJOS: comision_personal + pivot actividades (FIX)
        // ==========================================================
        foreach (($validated['personales'] ?? []) as $row) {

            $obraEmpleadoId = (int) ($row['asignacion_empleado_id'] ?? 0);
            if (!$obraEmpleadoId) continue;

            // Si no capturaron horario, lo ignoramos
            if (empty($row['hora_inicio']) && empty($row['hora_fin'])) continue;

            $cp = ComisionPersonal::create([
                'comision_id'      => $comision->id,
                'obra_empleado_id' => $obraEmpleadoId,
                'obra_maquina_id'  => null, // aquí sigues usando null
                'rol_id'           => $rolMap[$obraEmpleadoId] ?? null,
                'rol'              => null,
                'trabaja'          => 1,
                'hora_inicio'      => $row['hora_inicio'] ?? null,
                'hora_fin'         => $row['hora_fin'] ?? null,
                'comida_min'       => isset($row['tiempo_comida'])
                    ? (int) round(((float)$row['tiempo_comida']) * 60)
                    : 0,
                'horas_laboradas'  => (float) ($row['horas_laboradas'] ?? 0),
                'tiempo_extra'     => (float) ($row['tiempo_extra'] ?? 0),
                'importe_comision' => 0, // se recalcula abajo
            ]);

            // ✅ FIX: sync por cada empleado (antes estaba fuera del foreach)
            $actividadIds = $row['actividades'] ?? [];
            $cp->actividades()->sync(is_array($actividadIds) ? $actividadIds : []);
        }

        // ==========================================================
        // 8) HIJOS: comision_detalles (con obra_maquina_id)
        // ==========================================================
        foreach (($validated['detalles'] ?? []) as $row) {
            if (!array_filter($row)) continue;

            ComisionDetalle::create([
                'comision_id'             => $comision->id,
                'obra_maquina_id'         => $obraMaquinaId, // ✅ ESTE ES EL QUE TE FALTÓ EN TU ÚLTIMA PRUEBA
                'diametro'                => $row['diametro'] ?? null,
                'cantidad'                => (float) ($row['cantidad'] ?? 0),
                'profundidad'             => (float) ($row['profundidad'] ?? 0),
                'metros_sujetos_comision' => (float) ($row['metros_comision'] ?? 0),
                'ml_ademe_bauer'          => (float) ($row['ml_ademe_bauer'] ?? 0),
                'campana_pzas'            => (int)   ($row['campana_pzas'] ?? 0),
                'kg_acero'                => (float) ($row['kg_acero'] ?? 0),
                'vol_bentonita'           => (float) ($row['vol_bentonita'] ?? 0),
                'vol_concreto'            => (float) ($row['vol_concreto'] ?? 0),
                'adicional'               => isset($row['adicional']) ? (float)$row['adicional'] : null,
            ]);
        }

        // ==========================================================
        // 9) HIJOS: comision_perforaciones (desde detalles)
        // ==========================================================
        foreach (($validated['detalles'] ?? []) as $row) {
            if (!array_filter($row)) continue;

            ComisionPerforacion::create([
                'comision_id'      => $comision->id,
                'hora_inicio'      => $row['hora_inicio'] ?? null,
                'hora_termino'     => $row['hora_fin'] ?? null,
                'informacion_pila' => $row['diametro'] ?? '',
            ]);
        }

        // ==========================================================
        // 10) RECÁLCULO importes (usa tarifario_id ya guardado)
        // ==========================================================
        if (!$tarifario) {
            return;
        }

        // Totales desde comision_detalles (ya creados)
        $totales = [
            'metros_sujetos_comision' => (float) $comision->detalles()->sum('metros_sujetos_comision'),
            'kg_acero'                => (float) $comision->detalles()->sum('kg_acero'),
            'vol_bentonita'           => (float) $comision->detalles()->sum('vol_bentonita'),
            'vol_concreto'            => (float) $comision->detalles()->sum('vol_concreto'),
            'campana_pzas'            => (float) $comision->detalles()->sum('campana_pzas'),
        ];

        // Tarifas indexadas: [rol_id][concepto][variable_origen] = tarifa
        $tarifas = ComisionTarifarioDetalle::query()
            ->where('tarifario_id', $tarifario->id)
            ->where('activo', 1)
            ->get(['rol_id', 'concepto', 'variable_origen', 'tarifa'])
            ->groupBy('rol_id')
            ->map(function ($rows) {
                return $rows->groupBy('concepto')->map(function ($rows2) {
                    return $rows2->keyBy('variable_origen')->map(fn($r) => (float)$r->tarifa);
                });
            });

        // Recalcular por empleado según actividades checkeadas
        $personales = $comision->personales()->with('actividades')->get();

        foreach ($personales as $cp) {

            $rolId   = (int) ($cp->rol_id ?? 0);
            $importe = 0.0;

            // Producción (según check)
            foreach ($cp->actividades as $act) {
                $var = $act->key; // ej: metros_sujetos_comision

                $cantidad = (float) ($totales[$var] ?? 0);
                if ($cantidad <= 0) continue;

                $tarifa = (float) ($tarifas[$rolId]['produccion'][$var] ?? 0);
                if ($tarifa <= 0) continue;

                $importe += $cantidad * $tarifa;
            }

            // Hora extra (sin check)
            $horasExtra = (float) ($cp->tiempo_extra ?? 0);
            if ($horasExtra > 0) {
                $tarifaExtra = (float) ($tarifas[$rolId]['hora_extra']['tiempo_extra'] ?? 0);
                if ($tarifaExtra > 0) {
                    $importe += $horasExtra * $tarifaExtra;
                }
            }

            $cp->importe_comision = $importe;
            $cp->save();
        }
    });

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'comisiones'])
        ->with('success', 'Comisión creada correctamente.');
}

    /**
     * Mostrar detalle de una comisión (y desde aquí opción de imprimir).
     */
// public function show(Obra $obra, Comision $comision)
// {
//     // seguridad: la comisión debe ser de esta obra
//     if ($comision->obra_id !== $obra->id) {
//         abort(404);
//     }

//     // Recargamos la comisión con todas las relaciones necesarias
//     $comision->load([
//         'pila',
//         'personales.asignacionEmpleado.empleado',
//         'personales.asignacionMaquina.maquina',
//         'detalles.asignacionMaquina.maquina',
//         'perforaciones',
//         'personales.actividades',
//         'detalles',
//     ]);

//     // 1) Normalizamos colecciones a 0..N para poder emparejar por índice
//     $detalles       = $comision->detalles->values();
//     $perforaciones  = $comision->perforaciones->sortBy('id')->values();

//     // 2) Pegamos los horarios a cada detalle
//     foreach ($detalles as $i => $detalle) {
//         $perf = $perforaciones[$i] ?? null;

//         // atributos "virtuales" solo para la vista
//         $detalle->hora_inicio_perf = $perf->hora_inicio   ?? null;
//         $detalle->hora_fin_perf    = $perf->hora_termino ?? null;
//     }

//     // 3) Reemplazamos la relación detalles por la versión “enriquecida”
//     $comision->setRelation('detalles', $detalles);

//     return view('obras.comisiones.show', compact('obra', 'comision'));
// }
public function show(Obra $obra, Comision $comision)
{
    if ($comision->obra_id !== $obra->id) abort(404);

    $comision->load([
        'pila',
        'personales.asignacionEmpleado.empleado',
        'personales.asignacionEmpleado.rol',
        'personales.actividades',          // many-to-many a catalogo_actividades_comision
        'perforaciones',                   // solo horarios/info
        'detalles',                        // aquí está la producción
    ]);

    // Normalizamos
    $detalles      = $comision->detalles->values();
    $perforaciones = $comision->perforaciones->sortBy('id')->values();

    // Pegamos horarios a detalle (tu lógica original)
    foreach ($detalles as $i => $detalle) {
        $perf = $perforaciones[$i] ?? null;
        $detalle->hora_inicio_perf = $perf->hora_inicio ?? null;
        $detalle->hora_fin_perf    = $perf->hora_termino ?? null;
    }
    $comision->setRelation('detalles', $detalles);

    // =========================
    // TOTALES (desde comision_detalles)
    // =========================
    $totales = [
        'metros_sujetos_comision' => (float) $detalles->sum('metros_sujetos_comision'),
        'kg_acero'                => (float) $detalles->sum('kg_acero'),
        'vol_bentonita'           => (float) $detalles->sum('vol_bentonita'),
        'vol_concreto'            => (float) $detalles->sum('vol_concreto'),
        'campana_pzas'            => (float) $detalles->sum('campana_pzas'),
    ];

    // Columnas que quieres mostrar (puedes agregar/quitar)
    // key = variable_origen (como está en comision_tarifario_detalles)
    $columnas = [
        'metros_sujetos_comision' => 'Metros',
        'kg_acero'                => 'Acero',
        'vol_bentonita'           => 'Bentonita',
        'vol_concreto'            => 'Concreto',
        'campana_pzas'            => 'Campana',
    ];

    // =========================
    // TARIFAS (desde comision_tarifario_detalles)
    // =========================
    $tarifarioId = $comision->tarifario_id; // OJO: tu campo real
    $tarifas = []; // [rol_id][variable_origen] => tarifa

    if ($tarifarioId) {
        $rows = ComisionTarifarioDetalle::query()
            ->where('tarifario_id', $tarifarioId)
            ->where('activo', 1)
            ->get(['rol_id', 'concepto', 'variable_origen', 'tarifa']);

        foreach ($rows as $r) {
            $tarifas[(int)$r->rol_id][$r->variable_origen] = (float) $r->tarifa;
        }
    }

    // =========================
    // MATRIZ POR EMPLEADO
    // =========================
    $matriz = [];

    foreach ($comision->personales as $p) {

        $empleado = $p->asignacionEmpleado?->empleado;
        $rol      = $p->asignacionEmpleado?->rol;
        $rolId    = $rol?->id;

        // actividades seleccionadas: catalogo_actividades_comision.key
        $seleccionadas = $p->actividades
            ?->pluck('key')
            ->map(fn($v) => strtolower(trim($v)))
            ->flip()
            ?? collect();

        $row = [
            'empleado' => $empleado?->nombre_completo ?? ($empleado?->nombre ?? '—'),
            'rol'      => $rol?->nombre ?? '—',
            'conceptos' => [],
            'importe_extra' => 0.0,
            'total' => 0.0,
        ];

        // Calcula por cada columna (variable_origen)
        foreach ($columnas as $varOrigen => $label) {

            // En tu catálogo la actividad "produccion" o la key puede ser distinta.
            // Aquí asumimos que la actividad key coincide con variable_origen o que mapeas.
            // Si tus keys son: produccion/bentonita/concreto, etc., hacemos mapa abajo.
            $seleccionado = $seleccionadas->has($varOrigen);

            if (!$seleccionado) {
                $row['conceptos'][$varOrigen] = 0.0;
                continue;
            }

            $tarifa = (float) ($tarifas[$rolId][$varOrigen] ?? 0);
            $importe = ($totales[$varOrigen] ?? 0) * $tarifa;

            $row['conceptos'][$varOrigen] = $importe;
            $row['total'] += $importe;
        }

        // Tiempo extra: variable_origen = tiempo_extra
        $tiempoExtra = (float) ($p->tiempo_extra ?? 0);
        $tarifaExtra = (float) ($tarifas[$rolId]['tiempo_extra'] ?? 0);

        $row['importe_extra'] = $tiempoExtra * $tarifaExtra;
        $row['total'] += $row['importe_extra'];

        $matriz[] = $row;
    }

    // Totales por columna
    $totalesCols = array_fill_keys(array_keys($columnas), 0.0);
    $granTotal = 0.0;

    foreach ($matriz as $r) {
        foreach ($columnas as $varOrigen => $_) {
            $totalesCols[$varOrigen] += $r['conceptos'][$varOrigen] ?? 0;
        }
        $granTotal += $r['total'];
    }

    return view('obras.comisiones.show', compact(
        'obra', 'comision', 'columnas', 'totales', 'matriz', 'totalesCols', 'granTotal'
    ));
}

// public function show(Obra $obra, Comision $comision)
// {
//     abort_unless($comision->obra_id === $obra->id, 404);

//     $comision->load([
//         'pila',
//         'personales.asignacionEmpleado.empleado',
//         'personales.asignacionEmpleado.rol',
//         'perforaciones',
//         'tarifario.detalles', // si YA creaste la relación tarifario() en Comision
//         'personales.actividades', // si lo sigues usando en otras secciones
//     ]);

//     // 1) Normalizar perforaciones (MISMA base para totales y para render)
//     $perforaciones = $comision->perforaciones?->sortBy('id')->values() ?? collect();

//     // 2) Totales de producción (suma de perforaciones/detalles)
//     $totales = [
//         'metros'    => (float) $perforaciones->sum('mts_comision'),
//         'acero'     => (float) $perforaciones->sum('kg_acero'),
//         'bentonita' => (float) $perforaciones->sum('vol_bentonita'),
//         'concreto'  => (float) $perforaciones->sum('vol_concreto'),
//         'campana'   => (float) $perforaciones->sum('campana_pzas'),
//         // 'adicional' => (float) $perforaciones->sum('adicional'),
//     ];

//     // 3) Catálogo de conceptos (columnas)
//     $conceptos = [
//         'metros'    => 'Metros',
//         'acero'     => 'Acero',
//         'bentonita' => 'Bentonita',
//         'concreto'  => 'Concreto',
//         'campana'   => 'Campana',
//     ];

//     // 4) Lookup de tarifas: [rol_id][actividad_key] => tarifa
//     $tarifarioId = $comision->comision_tarifario_id ?? null;

//     $tarifas = [];
//     if ($tarifarioId) {
//         $rows = ComisionTarifarioDetalle::query()
//             ->where('comision_tarifario_id', $tarifarioId)
//             ->get(['rol_id', 'actividad_key', 'tarifa']);

//         foreach ($rows as $r) {
//             $tarifas[$r->rol_id][$r->actividad_key] = (float) $r->tarifa;
//         }
//     }

//     // 5) Construir matriz por empleado (1 fila por empleado)
//     $matriz = [];

//     foreach (($comision->personales ?? collect()) as $p) {

//         // OJO: aquí es asignacionEmpleado (así se llama tu relación)
//         $empleado = $p->asignacionEmpleado?->empleado;
//         $rol      = $p->asignacionEmpleado?->rol;

//         $rolId = $rol?->id;

//         $row = [
//             'empleado'     => $empleado?->nombre_completo ?? ($empleado?->nombre ?? '—'),
//             'rol'          => $rol?->nombre ?? '—',
//             'tiempo_extra' => (float) ($p->tiempo_extra ?? 0),
//             'conceptos'    => [],
//             'total'        => 0.0,
//         ];

//         foreach ($conceptos as $key => $label) {

//             /**
//              * IMPORTANTE:
//              * Ajusta esta línea al nombre REAL de tus flags en comision_personal.
//              * Si tus columnas son: metros, acero, bentonita, concreto, campana
//              * entonces usa: data_get($p, $key)
//              *
//              * Si son: comisiona_metros, comisiona_acero, etc:
//              * data_get($p, "comisiona_{$key}")
//              *
//              * Si son: act_metros, act_acero... (como está)
//              */
//             $seleccionado = (bool) data_get($p, "act_{$key}");

//             if (!$seleccionado) {
//                 $row['conceptos'][$key] = 0.0;
//                 continue;
//             }

//             $tarifa  = (float) ($tarifas[$rolId][$key] ?? 0);
//             $importe = $totales[$key] * $tarifa;

//             $row['conceptos'][$key] = $importe;
//             $row['total'] += $importe;
//         }

//         // 6) Tiempo extra (si existe tarifa por rol)
//         $tarifaExtra   = (float) ($tarifas[$rolId]['tiempo_extra'] ?? 0);
//         $importeExtra  = $row['tiempo_extra'] * $tarifaExtra;

//         $row['importe_extra'] = $importeExtra;
//         $row['total'] += $importeExtra;

//         $matriz[] = $row;
//     }

//     // 7) Totales por columna + gran total
//     $totalesCols = array_fill_keys(array_keys($conceptos), 0.0);
//     $granTotal   = 0.0;

//     foreach ($matriz as $r) {
//         foreach ($conceptos as $key => $_) {
//             $totalesCols[$key] += $r['conceptos'][$key] ?? 0;
//         }
//         $granTotal += $r['total'];
//     }

//     return view('obras.comisiones.show', compact(
//         'obra',
//         'comision',
//         'conceptos',
//         'totales',
//         'matriz',
//         'totalesCols',
//         'granTotal',
//         'perforaciones' // por si el Blade lo usa
//     ));
// }


    /**
     * Formulario para editar una comisión existente.
     */
    public function edit(Obra $obra, Comision $comision)
{
    if ($comision->obra_id !== $obra->id) {
        abort(404);
    }

    // Pilas de la obra
    $pilas = $obra->pilas()
        ->activas()
        ->orderBy('numero_pila')
        ->get();

    // Empleados asignados (con rol normalizado)
    $asignacionesEmpleados = ObraEmpleado::with(['empleado', 'rol'])
        ->where('obra_id', $obra->id)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->get();

    // Máquinas asignadas
    $asignacionesMaquinas = ObraMaquina::with('maquina')
        ->where('obra_id', $obra->id)
        ->activas()
        ->get();

    // Cargar la comisión y relaciones necesarias para precargar el formulario
    $comision->load([
        // Si tu relación en ComisionPersonal es obraEmpleado() o asignacionEmpleado(), ajusta aquí.
        'personales.obraEmpleado.empleado',
        'personales.obraEmpleado.rol',
        'personales.asignacionMaquina.maquina',

        'detalles.asignacionMaquina.maquina',
        'perforaciones',
    ]);

    return view('obras.comisiones.edit', compact(
        'obra',
        'comision',
        'pilas',
        'asignacionesEmpleados',
        'asignacionesMaquinas'
    ));
}


    /**
     * Actualizar una comisión.
     */
    public function update(Request $request, Obra $obra, Comision $comision)
    {
        if ($comision->obra_id !== $obra->id) {
            abort(404);
        }

        // TODO: validación + actualización de encabezado y detalles
        return back()->with('status', 'Función update de comisiones aún no implementada.');
    }

    /**
     * Eliminar una comisión.
     */
   public function destroy(Obra $obra, Comision $comision)
{
    // Seguridad: evitar borrar comisiones que no sean de esta obra
    if ($comision->obra_id !== $obra->id) {
        abort(404);
    }

    DB::transaction(function () use ($comision) {

        // 1) Eliminar registros relacionados
        $comision->personales()->delete();
        $comision->detalles()->delete();
        $comision->perforaciones()->delete();

        // 2) Eliminar la comisión
        $comision->delete();
    });

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'comisiones'])
        ->with('success', 'La comisión fue eliminada correctamente.');
}


    /**
     * Vista para imprimir el formato de comisión.
     */
    public function print(Obra $obra, Comision $comision)
    {
        if ($comision->obra_id !== $obra->id) {
            abort(404);
        }

        $comision->load([
            'pila',
            'obra',
            'personales.asignacionEmpleado.empleado',
            'personales.asignacionMaquina.maquina',
            'detalles.asignacionMaquina.maquina',
            'perforaciones',
        ]);

        return view('obras.comisiones.print', [
            'obra'     => $obra,
            'comision' => $comision,
        ]);
    }
    

private function recalcularImportes(Comision $comision): void
{
    // 1) Tarifario vigente
    $tarifario = ComisionTarifario::query()
        ->where('estado', 'vigente')
        ->orderByDesc('vigente_desde')
        ->first()
        ?? ComisionTarifario::query()->orderByDesc('id')->first();

    if (!$tarifario) {
        return; // sin tarifario, no calculamos
    }

    // 2) Totales de la comisión (desde comision_detalles)
    $totales = [
        'metros_sujetos_comision' => (float) $comision->detalles()->sum('metros_sujetos_comision'),
        'kg_acero'                => (float) $comision->detalles()->sum('kg_acero'),
        'vol_bentonita'           => (float) $comision->detalles()->sum('vol_bentonita'),
        'vol_concreto'            => (float) $comision->detalles()->sum('vol_concreto'),
        'campana_pzas'            => (float) $comision->detalles()->sum('campana_pzas'),
    ];

    // 3) Traer tarifas del tarifario (cache en memoria)
    $tarifas = ComisionTarifarioDetalle::query()
        ->where('tarifario_id', $tarifario->id)
        ->get()
        ->keyBy(fn ($t) => $t->rol_id . '|' . $t->concepto . '|' . $t->variable_origen);

    // 4) Por cada empleado, sumar actividades + hora extra
    $personales = $comision->personales()->with('actividades')->get();

    foreach ($personales as $cp) {
        $rolId = $cp->rol_id;

        $importe = 0.0;

        // Producción por actividades checked
        foreach ($cp->actividades as $act) {
            $var = $act->key; // coincide con columnas y con variable_origen
            $cantidad = (float) ($totales[$var] ?? 0);

            $tarifa = $tarifas[$rolId . '|produccion|' . $var] ?? null;

            if ($tarifa && $cantidad > 0) {
                $importe += $cantidad * (float) $tarifa->tarifa;
            }
        }

        // Hora extra (sin check)
        $horasExtra = (float) ($cp->tiempo_extra ?? 0);
        if ($horasExtra > 0) {
            // Convención: variable_origen = 'hora_extra'
            $tarifaExtra = $tarifas[$rolId . '|hora_extra|hora_extra'] ?? null;

            if ($tarifaExtra) {
                $importe += $horasExtra * (float) $tarifaExtra->tarifa;
            }
        }

        $cp->importe_comision = $importe;
        $cp->save();
    }
}

}
