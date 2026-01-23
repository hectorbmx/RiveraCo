<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\Comision;
use App\Models\ObraEmpleado;
use App\Models\ComisionPersonal;
use App\Models\ComisionDetalle;
use App\Models\ComisionPerforacion;
use App\Models\CatalogoRol;
use App\Models\CatalogoActividadComision;
use App\Models\ComisionTarifario;
use App\Models\ComisionTarifarioDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionController extends Controller
{
    public function store(Request $request, Obra $obra)
    {
        // ==========================================================
        // 0) Normalizar payload APP (móvil) -> shape web
        // ==========================================================

        // cliente_nombre_formato -> cliente_nombre
        if (!$request->has('cliente_nombre') && $request->filled('cliente_nombre_formato')) {
            $request->merge(['cliente_nombre' => $request->input('cliente_nombre_formato')]);
        }

        // perforaciones[] -> detalles[]
        if (!$request->has('detalles') && $request->has('perforaciones')) {
            $pilaId = $request->input('pila_id');

            $detalles = collect($request->input('perforaciones', []))->map(function ($d) use ($pilaId) {
                return [
                    'pila_id'         => $d['pila_id'] ?? $pilaId,
                    'diametro'        => $d['diametro'] ?? null,
                    'cantidad'        => $d['cantidad'] ?? 0,
                    'profundidad'     => $d['profundidad'] ?? 0,
                    'metros_comision' => $d['metros_comision'] ?? 0,
                    'kg_acero'        => $d['kg_acero'] ?? 0,
                    'vol_bentonita'   => $d['vol_bentonita'] ?? 0,
                    'vol_concreto'    => $d['vol_concreto'] ?? 0,
                    'adicional'       => $d['adicional'] ?? null,
                    'ml_ademe_bauer'  => $d['ml_ademe_bauer'] ?? 0,
                    'campana_pzas'    => $d['campana_pzas'] ?? 0,
                    'hora_inicio'     => $d['inicio_perf'] ?? null,
                    'hora_fin'        => $d['termino_perf'] ?? null,
                ];
            })->values()->all();

            $request->merge(['detalles' => $detalles]);
        }

        // personal[] -> personales[]
        if (!$request->has('personales') && $request->has('personal')) {

            // key real -> id
            $actMap = CatalogoActividadComision::query()
                ->pluck('id', 'key')
                ->toArray();

            $flagToKey = [
                'metros'    => 'metros_sujetos_comision',
                'acero'     => 'kg_acero',
                'bentonita' => 'vol_bentonita',
                'concreto'  => 'vol_concreto',
                'campana'   => 'campana_pzas',
            ];

            $personales = collect($request->input('personal', []))
                ->map(function ($p) use ($actMap, $flagToKey) {

                    // Ignorar residente en hijos (la cabecera lo maneja en residente_id)
                    $rol = mb_strtolower(trim((string)($p['rol_nombre'] ?? '')));
                    if ($rol === 'residente') {
                        return null;
                    }

                   $flags = is_array($p['actividades'] ?? null) ? $p['actividades'] : [];

                        foreach ($flagToKey as $flag => $key) {
                            if (!empty($flags[$flag]) && isset($actMap[$key])) {
                                $actividadIds[] = (int) $actMap[$key];
                            }
                        }


                    return [
                        'asignacion_empleado_id' => $p['obra_empleado_id'] ?? null,
                        'hora_inicio'            => $p['inicio'] ?? null,
                        'hora_fin'               => $p['fin'] ?? null,
                        'tiempo_comida'          => $p['comida_hrs'] ?? 0,
                        'horas_laboradas'        => $p['horas_laboradas'] ?? 0,
                        'tiempo_extra'           => $p['tiempo_extra'] ?? 0,
                        'actividades'            => $actividadIds,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            $request->merge(['personales' => $personales]);
        }

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

        $residenteId = $asignacionResidente?->empleado_id;

        // ==========================================================
        // 2) Validación (shape web)
        // ==========================================================
        $validated = $request->validate([
            'fecha'           => ['required', 'date'],
            'numero_formato'  => ['nullable', 'string', 'max:50'],
            'cliente_nombre'  => ['nullable', 'string', 'max:255'],
            'observaciones'   => ['nullable', 'string'],

            'obra_maquina_id' => ['nullable', 'integer', 'exists:obra_maquina,id'],
            'trabajo_id'      => ['nullable', 'integer'],

            'personales'                          => ['array'],
            'personales.*.asignacion_empleado_id' => ['nullable', 'integer'],
            'personales.*.hora_inicio'            => ['nullable', 'date_format:H:i'],
            'personales.*.hora_fin'               => ['nullable', 'date_format:H:i'],
            'personales.*.tiempo_comida'          => ['nullable', 'numeric', 'min:0'],
            'personales.*.horas_laboradas'        => ['nullable', 'numeric', 'min:0'],
            'personales.*.tiempo_extra'           => ['nullable', 'numeric', 'min:0'],
            'personales.*.actividades'            => ['nullable', 'array'],
            'personales.*.actividades.*'          => ['integer', 'exists:catalogo_actividades_comision,id'],

            'detalles'                            => ['array'],
            'detalles.*.pila_id'                  => ['required', 'integer', 'exists:obras_pilas,id'],
            'detalles.*.diametro'                 => ['nullable', 'numeric', 'min:0'],
            'detalles.*.cantidad'                 => ['nullable', 'numeric', 'min:0'],
            'detalles.*.profundidad'              => ['nullable', 'numeric', 'min:0'],
            'detalles.*.metros_comision'          => ['nullable', 'numeric', 'min:0'],
            'detalles.*.kg_acero'                 => ['nullable', 'numeric', 'min:0'],
            'detalles.*.vol_bentonita'            => ['nullable', 'numeric', 'min:0'],
            'detalles.*.vol_concreto'             => ['nullable', 'numeric', 'min:0'],
            'detalles.*.adicional'                => ['nullable', 'numeric', 'min:0'],
            'detalles.*.ml_ademe_bauer'           => ['nullable', 'numeric', 'min:0'],
            'detalles.*.campana_pzas'             => ['nullable', 'integer', 'min:0'],
            'detalles.*.hora_inicio'              => ['nullable', 'date_format:H:i'],
            'detalles.*.hora_fin'                 => ['nullable', 'date_format:H:i'],
        ]);

        $obraMaquinaId = $validated['obra_maquina_id'] ?? null;
        $trabajoId     = $validated['trabajo_id'] ?? null;

        $comision = null;

        DB::transaction(function () use (
            $obra,
            $validated,
            $residenteId,
            $obraMaquinaId,
            $trabajoId,
            &$comision
        ) {
            // Tarifario
            $tarifario = ComisionTarifario::query()
                ->where('estado', 'vigente')
                ->orderByDesc('vigente_desde')
                ->first()
                ?? ComisionTarifario::query()->orderByDesc('id')->first();

            // Pila cabecera
            $primerDetalle  = $validated['detalles'][0] ?? null;
            $pilaCabeceraId = $primerDetalle['pila_id'] ?? null;

            // Mapa de roles
            $idsAsignacion = collect($validated['personales'] ?? [])
                ->pluck('asignacion_empleado_id')
                ->filter()
                ->unique()
                ->values();

            $rolMap = ObraEmpleado::query()
                ->where('obra_id', $obra->id)
                ->whereIn('id', $idsAsignacion)
                ->pluck('rol_id', 'id');

            // CABECERA
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

            // PERSONAL + ACTIVIDADES
            foreach (($validated['personales'] ?? []) as $row) {

                $obraEmpleadoId = (int) ($row['asignacion_empleado_id'] ?? 0);
                if (!$obraEmpleadoId) continue;

                if (empty($row['hora_inicio']) && empty($row['hora_fin'])) continue;

                $cp = ComisionPersonal::create([
                    'comision_id'      => $comision->id,
                    'obra_empleado_id' => $obraEmpleadoId,
                    'obra_maquina_id'  => null,
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
                    'importe_comision' => 0,
                ]);

                $actividadIds = $row['actividades'] ?? [];
                $cp->actividades()->sync(is_array($actividadIds) ? $actividadIds : []);
            }

            // DETALLES
            foreach (($validated['detalles'] ?? []) as $row) {
                if (!array_filter($row)) continue;

                ComisionDetalle::create([
                    'comision_id'             => $comision->id,
                    'obra_maquina_id'         => $obraMaquinaId,
                    'diametro' => isset($row['diametro']) ? (string)$row['diametro'] : null,
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

            // PERFORACIONES
            foreach (($validated['detalles'] ?? []) as $row) {
                if (!array_filter($row)) continue;

                ComisionPerforacion::create([
                    'comision_id'      => $comision->id,
                    'hora_inicio'      => $row['hora_inicio'] ?? null,
                    'hora_termino'     => $row['hora_fin'] ?? null,
                    'informacion_pila' => $row['diametro'] ?? '',
                ]);
            }

            // RECALCULO
            if (!$tarifario) return;

            $totales = [
                'metros_sujetos_comision' => (float) $comision->detalles()->sum('metros_sujetos_comision'),
                'kg_acero'                => (float) $comision->detalles()->sum('kg_acero'),
                'vol_bentonita'           => (float) $comision->detalles()->sum('vol_bentonita'),
                'vol_concreto'            => (float) $comision->detalles()->sum('vol_concreto'),
                'campana_pzas'            => (float) $comision->detalles()->sum('campana_pzas'),
            ];

            $tarifas = ComisionTarifarioDetalle::query()
                ->where('tarifario_id', $tarifario->id)
                ->where('activo', 1)
                ->get(['rol_id', 'concepto', 'variable_origen', 'tarifa'])
                ->groupBy('rol_id')
                ->map(fn($rows) => $rows->groupBy('concepto')->map(
                    fn($rows2) => $rows2->keyBy('variable_origen')->map(fn($r) => (float)$r->tarifa)
                ));

            $personales = $comision->personales()->with('actividades')->get();

            foreach ($personales as $cp) {
                $rolId   = (int) ($cp->rol_id ?? 0);
                $importe = 0.0;

                foreach ($cp->actividades as $act) {
                    $var = $act->key;
                    $cantidad = (float) ($totales[$var] ?? 0);
                    if ($cantidad <= 0) continue;

                    $tarifa = (float) ($tarifas[$rolId]['produccion'][$var] ?? 0);
                    if ($tarifa <= 0) continue;

                    $importe += $cantidad * $tarifa;
                }

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

        return response()->json([
            'ok' => true,
            'data' => [
                'comision_id' => $comision->id,
            ],
        ], 201);
    }
}
