<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\NominaCorrida;
use App\Models\NominaRecibo;
use App\Models\Empleado;
use Carbon\Carbon;
use App\Services\Nomina\ListaRayaResolver;


class NominaCorridaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'tipo'  => 'required|in:semanal,quincenal,mensual',
        ]);

        $desde = Carbon::parse($request->desde)->startOfDay();
        $hasta = Carbon::parse($request->hasta)->startOfDay();
        $tipo  = $request->tipo;

        [$periodoLabel, $fechaPago] = $this->buildPeriodoLabelYFechaPago($tipo, $desde, $hasta);

        $existe = NominaCorrida::where([
            'fecha_inicio' => $desde->toDateString(),
            'fecha_fin'    => $hasta->toDateString(),
            'tipo_pago'    => $tipo,
        ])->whereIn('status', ['abierta', 'cerrada', 'pagada'])
          ->exists();

        if ($existe) {
            return back()->with('error', 'Ya existe una corrida con esos parámetros.');
        }

        $corrida = NominaCorrida::create([
            'tipo_pago'     => $tipo,
            'subtipo'       => null,
            'periodo_label' => $periodoLabel,
            'fecha_inicio'  => $desde->toDateString(),
            'fecha_fin'     => $hasta->toDateString(),
            'fecha_pago'    => $fechaPago?->toDateString(),
            'status'        => 'abierta',
            'notas'         => null,
            'created_by'    => auth()->id(),
        ]);

        return redirect()
            ->route('nomina.corridas.show', $corrida)
            ->with('success', "Corrida generada: {$corrida->periodo_label}");
    }

    /**
     * Genera periodo_label y fecha_pago sugerida.
     */
    private function buildPeriodoLabelYFechaPago(string $tipo, Carbon $desde, Carbon $hasta): array
    {
        $mesCorto = [
            1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'
        ];

        if ($tipo === 'semanal') {
            $week = (int) $desde->isoWeek();
            $year = (int) $desde->isoWeekYear();
            $label = sprintf(
                'Semana %02d %d (%02d-%02d %s)',
                $week, $year,
                (int)$desde->day, (int)$hasta->day,
                $mesCorto[(int)$hasta->month]
            );
            $fechaPago = $hasta->copy();
            return [$label, $fechaPago];
        }

        if ($tipo === 'quincenal') {
            $year = (int) $hasta->year;
            $month = (int) $hasta->month;
            $q = ($desde->day <= 15 && $hasta->day <= 15) ? 1 : 2;
            $label = sprintf(
                'Quincena %d %s %d (%02d-%02d)',
                $q, $mesCorto[$month], $year,
                (int)$desde->day, (int)$hasta->day
            );
            $fechaPago = $q === 1
                ? Carbon::create($year, $month, 15)
                : $hasta->copy();
            return [$label, $fechaPago];
        }

        // mensual
        $year = (int) $hasta->year;
        $month = (int) $hasta->month;
        $label = sprintf('Mensual %s %d', $mesCorto[$month], $year);
        $fechaPago = $hasta->copy();
        return [$label, $fechaPago];
    }

    // Mostrar una corrida despues de haber sido creada
    public function show(NominaCorrida $corrida)
    {
        $corrida->load(['recibos.empleado', 'recibos.obra', 'recibos.listaRaya', 'recibos.pagosExtra']);

        $totalBruto = $corrida->recibos->sum(function ($recibo) {
            $ex = $recibo->pagosExtra->sum('monto');
            return (float) ($recibo->total_percepciones ?? 0)
                + (float) ($recibo->horas_extra ?? 0)
                + (float) ($recibo->metros_lin_monto ?? 0)
                + (float) ($recibo->comisiones_monto ?? 0)
                + (float) $ex;
        });
        $totalDeducciones = $corrida->recibos->sum('total_deducciones');
        $totalNeto = $corrida->recibos->sum('sueldo_neto');

        $obras = \App\Models\Obra::query()
            ->where('estatus_nuevo', '!=', \App\Models\Obra::ESTATUS_CANCELADA)
            ->orderBy('clave_obra')
            ->get(['id','clave_obra','nombre']);

        return view('nomina.corridas.show', compact(
            'corrida','totalBruto','totalDeducciones','totalNeto','obras'
        ));
    }

    // Generar recibos base para la corrida
    public function generarRecibos(Request $request, NominaCorrida $corrida, ListaRayaResolver $listaRayaResolver)
    {
        if (($corrida->status ?? null) !== 'abierta') {
            return back()->with('error', 'Solo puedes generar recibos cuando la corrida esta ABIERTA.');
        }

        $sueldoTipo = match ($corrida->tipo_pago) {
            'semanal'   => 1,
            'quincenal' => 2,
            'mensual'   => 3,
            default     => null,
        };

        $listaRayaResolver->syncObrasVivas();

        $queryEmpleados = Empleado::query()
            ->where('Estatus', 1);

        $queryEmpleados->with('obraActiva');

        if (!is_null($sueldoTipo)) {
            $queryEmpleados->where('Sueldo_tipo', $sueldoTipo);
        }

        $empleadoIds = $request->input('empleado_ids');
        if (is_array($empleadoIds) && count($empleadoIds) > 0) {
            $queryEmpleados->whereIn('id_Empleado', $empleadoIds);
        }

        $empleados = $queryEmpleados->get();

        if ($empleados->isEmpty()) {
            return back()->with('error', 'No hay empleados para generar recibos con ese tipo de pago/estatus.');
        }

        $fechaInicio = Carbon::parse($corrida->fecha_inicio)->startOfDay();
        $fechaFin    = Carbon::parse($corrida->fecha_fin)->endOfDay();
        $comisionFechaCol = 'fecha';

        $comisiones = DB::table('comision_personal as cp')
            ->join('obra_empleado as oe', 'oe.id', '=', 'cp.obra_empleado_id')
            ->join('comisiones as c', 'c.id', '=', 'cp.comision_id')
            ->whereBetween("c.$comisionFechaCol", [$fechaInicio, $fechaFin])
            ->groupBy('oe.empleado_id')
            ->selectRaw('
                oe.empleado_id as empleado_id,
                MAX(oe.obra_id) as obra_id,
                COALESCE(SUM(cp.importe_comision),0) as comisiones_monto,
                COALESCE(SUM(cp.tiempo_extra),0) as horas_extra
            ')
            ->get()
            ->keyBy('empleado_id');

        $creados = 0;
        $actualizados = 0;

        DB::transaction(function () use ($corrida, $empleados, $comisiones, $listaRayaResolver, &$creados, &$actualizados) {
            $corrida = NominaCorrida::whereKey($corrida->id)->lockForUpdate()->first();

            foreach ($empleados as $emp) {
                $sueldoImss   = (float)($emp->Sueldo ?? $emp->sueldo ?? 0);
                $complemento  = (float)($emp->Complemento ?? $emp->complemento ?? 0);
                $sueldoReal   = (float)($emp->Sueldo_real ?? $emp->sueldo_real ?? $emp->SueldoReal ?? 0);

                $base = $sueldoReal > 0 ? $sueldoReal : ($sueldoImss + $complemento);
                if ($sueldoReal <= 0) {
                    $sueldoReal = $sueldoImss + $complemento;
                }

                $infonavitEmpleado = (float)($emp->Infonavit ?? $emp->infonavit ?? 0);

                $cx = $comisiones->get($emp->id_Empleado);
                $comisionesMonto = (float)($cx->comisiones_monto ?? 0);
                $horasExtra      = (float)($cx->horas_extra ?? 0);
                $obraPorComision = $cx->obra_id ?? null;

                $obraActiva = null;
                if ($emp->relationLoaded('obraActiva') && $emp->obraActiva && $emp->obraActiva->count() > 0) {
                    $obraActiva = $emp->obraActiva->first();
                }
                $obraIdFinal = $obraPorComision ?: ($obraActiva?->id);
                $listaRaya = $listaRayaResolver->resolverParaEmpleado($emp);

                $metrosLinMonto = 0;
                $faltas     = 0;
                $descuentos = 0;

                $bruto = $base + $horasExtra + $metrosLinMonto + $comisionesMonto;
                $dedu  = $faltas + $descuentos + $infonavitEmpleado;
                $neto  = max(0, $bruto - $dedu);

                $where = [
                    'corrida_id'  => $corrida->id,
                    'empleado_id' => $emp->id_Empleado,
                ];

                $payload = [
                    'obra_id'               => $obraIdFinal,
                    'lista_raya_id'         => $listaRaya?->id,
                    'lista_raya_nombre'     => $listaRaya?->nombre,
                    'lista_raya_tipo'       => $listaRaya?->tipo,
                    'obra_legacy'           => null,
                    'tipo_pago'             => $corrida->tipo_pago,
                    'subtipo'               => $corrida->subtipo,
                    'periodo_label'         => $corrida->periodo_label,
                    'fecha_inicio'          => $corrida->fecha_inicio,
                    'fecha_fin'             => $corrida->fecha_fin,
                    'fecha_pago'            => $corrida->fecha_pago,
                    'sueldo_imss_snapshot'  => $sueldoImss,
                    'complemento_snapshot'  => $complemento,
                    'infonavit_snapshot'    => $infonavitEmpleado,
                    'faltas'               => 0,
                    'descuentos'           => 0,
                    'descuentos_legacy'    => 0,
                    'infonavit_legacy'     => $infonavitEmpleado,
                    'horas_extra'          => $horasExtra,
                    'metros_lineales'      => 0,
                    'metros_lin_monto'     => $metrosLinMonto,
                    'comisiones_monto'     => $comisionesMonto,
                    'comisiones_lock'      => 0,
                    'comisiones_cargadas_at' => null,
                    'comisiones_cargadas_by' => null,
                    'factura_monto'        => 0,
                    'notas_legacy'         => null,
                    'total_percepciones'   => $base,
                    'total_deducciones'    => $dedu,
                    'sueldo_neto'          => $neto,
                    'status'               => 'pendiente',
                    'folio'                => null,
                    'referencia_externa'   => null,
                ];

                $recibo = NominaRecibo::updateOrCreate($where, $payload);
                if ($recibo->wasRecentlyCreated) $creados++;
                else $actualizados++;
            }
        });

        return back()->with('success', "Recibos generados. Nuevos: {$creados}, actualizados: {$actualizados}.");
    }

    // Guardar todos los recibos de la corrida (submit general)
    public function guardarRecibos(Request $request, NominaCorrida $corrida, ListaRayaResolver $listaRayaResolver)
    {
        if (($corrida->status ?? null) !== 'abierta') {
            return back()->with('error', 'La corrida no esta abierta. No se puede editar.');
        }

        $data = $request->input('recibos', []);
        if (!is_array($data) || empty($data)) {
            return back()->with('error', 'No se recibieron cambios.');
        }

        DB::transaction(function () use ($corrida, $data, $listaRayaResolver) {
            $corridaLocked = NominaCorrida::whereKey($corrida->id)->lockForUpdate()->first();
            $reciboIds = array_map('intval', array_keys($data));

            $recibos = NominaRecibo::with('empleado')
                ->where('corrida_id', $corridaLocked->id)
                ->whereIn('id', $reciboIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data as $reciboId => $row) {
                $reciboId = (int)$reciboId;
                $recibo = $recibos->get($reciboId);
                if (!$recibo) continue;

                // 1) Campos editables
                $recibo->infonavit_legacy = (float)($row['infonavit'] ?? $recibo->infonavit_legacy);
                $recibo->faltas           = (float)($row['faltas'] ?? $recibo->faltas);
                $recibo->descuentos       = (float)($row['descuentos'] ?? $recibo->descuentos);
                $recibo->horas_extra      = (float)($row['horas_extra'] ?? $recibo->horas_extra);
                $recibo->metros_lin_monto = (float)($row['metros_lin_monto'] ?? $recibo->metros_lin_monto);
                $recibo->comisiones_monto = (float)($row['comisiones_monto'] ?? $recibo->comisiones_monto);

                $recibo->obra_id = (($row['obra_id'] ?? '') !== '') ? (int)$row['obra_id'] : null;
                $listaRaya = $recibo->obra_id
                    ? $listaRayaResolver->resolverParaObra($recibo->obra_id)
                    : ($recibo->empleado ? $listaRayaResolver->resolverParaEmpleado($recibo->empleado) : null);

                $recibo->lista_raya_id = $listaRaya?->id;
                $recibo->lista_raya_nombre = $listaRaya?->nombre;
                $recibo->lista_raya_tipo = $listaRaya?->tipo;
                $recibo->notas_legacy = $row['notas'] ?? $recibo->notas_legacy;

                // 2) Sincronizar extras multiples
                $extraMontoTotal = $this->syncExtras($recibo, $row['extras'] ?? [], $corridaLocked);

                // 3) Recalcular totales
                $sueldoReal = (float)($recibo->total_percepciones ?? 0);
                $bruto = $sueldoReal
                    + (float)($recibo->horas_extra ?? 0)
                    + (float)($recibo->metros_lin_monto ?? 0)
                    + (float)($recibo->comisiones_monto ?? 0)
                    + $extraMontoTotal;

                $deds = (float)($recibo->infonavit_legacy ?? 0)
                    + (float)($recibo->faltas ?? 0)
                    + (float)($recibo->descuentos ?? 0);

                $neto = max(0, $bruto - $deds);

                $recibo->total_deducciones = $deds;
                $recibo->sueldo_neto = $neto;
                $recibo->save();
            }
        });

        return back()->with('success', 'Recibos guardados correctamente.');
    }

    // Autosave: guardar un recibo individual via AJAX
    public function autosave(Request $request, NominaCorrida $corrida, NominaRecibo $recibo, ListaRayaResolver $listaRayaResolver)
    {
        if (($corrida->status ?? null) !== 'abierta') {
            return response()->json(['success' => false, 'message' => 'La corrida no esta abierta.'], 422);
        }

        if ($recibo->corrida_id !== $corrida->id) {
            return response()->json(['success' => false, 'message' => 'El recibo no pertenece a esta corrida.'], 422);
        }

        $request->validate([
            'infonavit'        => 'nullable|numeric',
            'faltas'           => 'nullable|numeric',
            'descuentos'       => 'nullable|numeric',
            'horas_extra'      => 'nullable|numeric',
            'metros_lin_monto' => 'nullable|numeric',
            'comisiones_monto' => 'nullable|numeric',
            'obra_id'          => 'nullable|integer',
            'notas'            => 'nullable|string|max:1000',
            'extras'           => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($corrida, $recibo, $request, $listaRayaResolver) {
                $recibo = NominaRecibo::with('empleado')->whereKey($recibo->id)->lockForUpdate()->first();

                // 1) Campos editables
                if ($request->has('infonavit'))        $recibo->infonavit_legacy  = (float)$request->input('infonavit');
                if ($request->has('faltas'))            $recibo->faltas            = (float)$request->input('faltas');
                if ($request->has('descuentos'))        $recibo->descuentos        = (float)$request->input('descuentos');
                if ($request->has('horas_extra'))       $recibo->horas_extra       = (float)$request->input('horas_extra');
                if ($request->has('metros_lin_monto'))  $recibo->metros_lin_monto  = (float)$request->input('metros_lin_monto');
                if ($request->has('comisiones_monto'))  $recibo->comisiones_monto  = (float)$request->input('comisiones_monto');
                if ($request->has('notas'))             $recibo->notas_legacy      = $request->input('notas');

                if ($request->has('obra_id')) {
                    $obraId = $request->input('obra_id');
                    $recibo->obra_id = (($obraId ?? '') !== '') ? (int)$obraId : null;

                    $listaRaya = $recibo->obra_id
                        ? $listaRayaResolver->resolverParaObra($recibo->obra_id)
                        : ($recibo->empleado ? $listaRayaResolver->resolverParaEmpleado($recibo->empleado) : null);

                    $recibo->lista_raya_id     = $listaRaya?->id;
                    $recibo->lista_raya_nombre = $listaRaya?->nombre;
                    $recibo->lista_raya_tipo   = $listaRaya?->tipo;
                }

                // 2) Sincronizar extras multiples
                $extraMontoTotal = $this->syncExtras($recibo, $request->input('extras') ?? [], $corrida);

                // 3) Recalcular totales
                $sueldoReal = (float)($recibo->total_percepciones ?? 0);
                $bruto = $sueldoReal
                    + (float)($recibo->horas_extra ?? 0)
                    + (float)($recibo->metros_lin_monto ?? 0)
                    + (float)($recibo->comisiones_monto ?? 0)
                    + $extraMontoTotal;

                $deds = (float)($recibo->infonavit_legacy ?? 0)
                    + (float)($recibo->faltas ?? 0)
                    + (float)($recibo->descuentos ?? 0);

                $neto = max(0.0, $bruto - $deds);

                $recibo->total_deducciones = $deds;
                $recibo->sueldo_neto       = $neto;
                $recibo->save();
            });

            // Recalcular KPIs globales
            $corrida->load('recibos.pagosExtra');
            $totalBruto = $corrida->recibos->sum(function ($r) {
                $ex = $r->pagosExtra->sum('monto');
                return (float)($r->total_percepciones ?? 0)
                    + (float)($r->horas_extra ?? 0)
                    + (float)($r->metros_lin_monto ?? 0)
                    + (float)($r->comisiones_monto ?? 0)
                    + (float)$ex;
            });
            $totalDeducciones = $corrida->recibos->sum('total_deducciones');
            $totalNeto        = $corrida->recibos->sum('sueldo_neto');

            $recibo->refresh();
            $currentExtras = DB::table('nomina_pagos_extra')->where('recibo_id', $recibo->id)->get();

            return response()->json([
                'success' => true,
                'recibo'  => [
                    'id'                 => $recibo->id,
                    'total_percepciones' => $recibo->total_percepciones,
                    'total_deducciones'  => $recibo->total_deducciones,
                    'sueldo_neto'        => $recibo->sueldo_neto,
                    'lista_raya_id'      => $recibo->lista_raya_id,
                    'lista_raya_nombre'  => $recibo->lista_raya_nombre ?: 'Sin clasificar',
                    'lista_raya_tipo'    => $recibo->lista_raya_tipo,
                    'extras' => $currentExtras->map(fn ($ex) => [
                        'id'    => $ex->id,
                        'tipo'  => $ex->tipo,
                        'monto' => $ex->monto,
                        'notas' => $ex->notas,
                    ]),
                ],
                'kpis' => [
                    'total_bruto'       => $totalBruto,
                    'total_deducciones' => $totalDeducciones,
                    'total_neto'        => $totalNeto,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza extras multiples para un recibo.
     * Devuelve el monto acumulado de todos los extras vigentes.
     */
    private function syncExtras(NominaRecibo $recibo, array $extrasEnviados, $corrida): float
    {
        $extraMontoTotal = 0.0;
        $idsProcesados   = [];

        if (!is_array($extrasEnviados)) {
            $extrasEnviados = [];
        }

        foreach ($extrasEnviados as $exRow) {
            $tipo  = trim((string)($exRow['tipo'] ?? ''));
            $monto = (float)($exRow['monto'] ?? 0);
            $notas = (string)($exRow['notas'] ?? '');
            $exId  = isset($exRow['id']) && $exRow['id'] !== '' ? (int)$exRow['id'] : null;

            if ($tipo === '' || $monto <= 0) {
                continue;
            }

            $payloadExtra = [
                'empleado_id'        => (int)($exRow['empleado_id'] ?? $recibo->empleado_id),
                'obra_id'            => (($exRow['obra_id'] ?? '') !== '') ? (int)$exRow['obra_id'] : $recibo->obra_id,
                'tipo'               => $tipo,
                'anio'               => (int)($exRow['anio'] ?? now()->year),
                'concepto'           => $tipo,
                'monto'              => $monto,
                'fecha_pago'         => $exRow['fecha_pago'] ?? $corrida->fecha_pago,
                'folio'              => $exRow['folio'] ?? null,
                'referencia_externa' => $exRow['referencia_externa'] ?? null,
                'notas'              => $notas,
                'updated_at'         => now(),
            ];

            if ($exId) {
                DB::table('nomina_pagos_extra')
                    ->where('id', $exId)
                    ->where('recibo_id', $recibo->id)
                    ->update($payloadExtra);
                $idsProcesados[] = $exId;
            } else {
                $payloadExtra['recibo_id']  = $recibo->id;
                $payloadExtra['created_at'] = now();
                $newId = DB::table('nomina_pagos_extra')->insertGetId($payloadExtra);
                $idsProcesados[] = $newId;
            }

            $extraMontoTotal += $monto;
        }

        // Eliminar extras que no vinieron en la peticion
        DB::table('nomina_pagos_extra')
            ->where('recibo_id', $recibo->id)
            ->whereNotIn('id', $idsProcesados)
            ->delete();

        return $extraMontoTotal;
    }

    public function destroyRecibos(NominaCorrida $corrida)
    {
        if (($corrida->status ?? null) !== 'abierta') {
            return back()->with('error', 'Solo puedes borrar recibos si la corrida esta ABIERTA.');
        }

        $deleted = NominaRecibo::where('corrida_id', $corrida->id)->delete();
        return back()->with('success', "Recibos eliminados: {$deleted}.");
    }

    public function destroy(NominaCorrida $corrida)
    {
        if (($corrida->status ?? null) !== 'abierta') {
            return back()->with('error', 'Solo puedes eliminar una corrida si esta ABIERTA.');
        }

        DB::transaction(function () use ($corrida) {
            NominaRecibo::where('corrida_id', $corrida->id)->delete();
            $corrida->delete();
        });

        return redirect()
            ->route('nomina.generador.index')
            ->with('success', 'Corrida eliminada correctamente.');
    }

    public function cerrar(NominaCorrida $corrida)
    {
        if (($corrida->status ?? null) !== 'abierta') return back()->with('error', 'Solo puedes cerrar una corrida ABIERTA.');
        if (!$corrida->recibos()->exists()) return back()->with('error', 'No hay recibos para cerrar.');

        $corrida->update(['status' => 'cerrada']);
        return back()->with('success', 'Corrida cerrada.');
    }

    public function marcarPagada(NominaCorrida $corrida)
    {
        if (($corrida->status ?? null) !== 'cerrada') return back()->with('error', 'Solo puedes marcar pagada una corrida CERRADA.');

        $corrida->update(['status' => 'pagada']);
        return back()->with('success', 'Corrida marcada como PAGADA.');
    }

    public function reabrir(NominaCorrida $corrida)
    {
        if (($corrida->status ?? null) !== 'cerrada') return back()->with('error', 'Solo puedes reabrir una corrida CERRADA.');

        $corrida->update(['status' => 'abierta']);
        return back()->with('success', 'Corrida reabierta.');
    }
}
