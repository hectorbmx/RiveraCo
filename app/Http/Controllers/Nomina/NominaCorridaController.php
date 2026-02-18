<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\NominaCorrida;
use App\Models\NominaRecibo;
use App\Models\Empleado;
use Carbon\Carbon;


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

        // Autogenerar periodo_label y fecha_pago sugerida
        [$periodoLabel, $fechaPago] = $this->buildPeriodoLabelYFechaPago($tipo, $desde, $hasta);

        // Evitar duplicados (por tipo + rango)
        $existe = NominaCorrida::where([
            'fecha_inicio' => $desde->toDateString(),
            'fecha_fin'    => $hasta->toDateString(),
            'tipo_pago'    => $tipo,
        ])->whereIn('status', ['abierta', 'cerrada', 'pagada']) // si manejas esos 3
          ->exists();

        if ($existe) {
            return back()->with('error', 'Ya existe una corrida con esos parámetros.');
        }

        $corrida = NominaCorrida::create([
            'tipo_pago'     => $tipo,
            'subtipo'       => null, // si luego lo ocupas (ej. obra/oficina), aquí entra
            'periodo_label' => $periodoLabel,
            'fecha_inicio'  => $desde->toDateString(),
            'fecha_fin'     => $hasta->toDateString(),
            'fecha_pago'    => $fechaPago?->toDateString(),
            'status'        => 'abierta',
            'notas'         => null,
            'created_by'    => auth()->id(),
        ]);

        // ✅ Opción A: si ya tienes show de corridas:
        // return redirect()->route('nomina.corridas.show', $corrida)->with('success', 'Corrida generada correctamente.');

        // ✅ Opción B: si aún no existe show, regresa al generador con la corrida seleccionada:
        // return redirect()->route('nomina.corridas.show', array_filter([
        //     'desde' => $desde->toDateString(),
        //     'hasta' => $hasta->toDateString(),
        //     'tipo'  => $tipo,
        //     'corrida_id' => $corrida->id,
        // ]))->with('success', "Corrida generada: {$corrida->periodo_label}");
        return redirect()
        ->route('nomina.corridas.show', $corrida)
        ->with('success', "Corrida generada: {$corrida->periodo_label}");

    }

    /**
     * Genera periodo_label y fecha_pago sugerida.
     * - semanal: "Semana 07 2026 (10–16 Feb)" y pago = fin del periodo (ajustable)
     * - quincenal: "Quincena 1 Feb 2026 (01–15)" o "Quincena 2 Feb 2026 (16–28)"
     * - mensual: "Mensual Feb 2026"
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
                'Semana %02d %d (%02d–%02d %s)',
                $week,
                $year,
                (int)$desde->day,
                (int)$hasta->day,
                $mesCorto[(int)$hasta->month]
            );

            // regla simple: pagar el último día del periodo
            $fechaPago = $hasta->copy();
            return [$label, $fechaPago];
        }

        if ($tipo === 'quincenal') {
            $year = (int) $hasta->year;
            $month = (int) $hasta->month;

            // Determinar si es 1ra o 2da quincena según el rango
            $q = ($desde->day <= 15 && $hasta->day <= 15) ? 1 : 2;

            $label = sprintf(
                'Quincena %d %s %d (%02d–%02d)',
                $q,
                $mesCorto[$month],
                $year,
                (int)$desde->day,
                (int)$hasta->day
            );

            // regla: pago el 15 o último día del mes según quincena
            $fechaPago = $q === 1
                ? Carbon::create($year, $month, 15)
                : $hasta->copy();

            return [$label, $fechaPago];
        }

        // mensual
        $year = (int) $hasta->year;
        $month = (int) $hasta->month;

        $label = sprintf('Mensual %s %d', $mesCorto[$month], $year);

        // regla: pago el último día del mes (o fin de periodo)
        $fechaPago = $hasta->copy();

        return [$label, $fechaPago];
    }
    //mostrar una corrida despues de haber sido creada
public function show(NominaCorrida $corrida)
{
    $corrida->load(['recibos.empleado', 'recibos.obra']);

    $totalBruto = $corrida->recibos->sum('total_percepciones');
    $totalDeducciones = $corrida->recibos->sum('total_deducciones');
    $totalNeto = $corrida->recibos->sum('sueldo_neto');

    $obras = \App\Models\Obra::orderBy('clave_obra')->get(['id','clave_obra','nombre']);

    return view('nomina.corridas.show', compact(
        'corrida','totalBruto','totalDeducciones','totalNeto','obras'
    ));
}

//generador de pagos

// ...

public function generarRecibos(Request $request, NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'abierta') {
        return back()->with('error', 'Solo puedes generar recibos cuando la corrida está ABIERTA.');
    }

    // Map tipo_pago -> Sueldo_tipo (legacy)
    $sueldoTipo = match ($corrida->tipo_pago) {
        'semanal'   => 1,
        'quincenal' => 2,
        'mensual'   => 3,
        default     => null,
    };

    // ✅ Base query empleados
    $queryEmpleados = Empleado::query()
        ->where('Estatus', 1); 

    // ✅ Traer obra activa (para no hacer N+1)
    // Asumo que ya tienes relación obraActiva porque la usas en generador.blade
    $queryEmpleados->with('obraActiva');

    // ✅ Filtrar por tipo de sueldo
    if (!is_null($sueldoTipo)) {
        $queryEmpleados->where('Sueldo_tipo', $sueldoTipo);
    }

    // Opcional: subset manual
    $empleadoIds = $request->input('empleado_ids');
    if (is_array($empleadoIds) && count($empleadoIds) > 0) {
        $queryEmpleados->whereIn('id_Empleado', $empleadoIds);
    }

    $empleados = $queryEmpleados->get();

    if ($empleados->isEmpty()) {
        return back()->with('error', 'No hay empleados para generar recibos con ese tipo de pago/estatus.');
    }

    // =========================
    // ✅ Precargar comisiones del rango (1 query)
    // =========================
    $fechaInicio = \Carbon\Carbon::parse($corrida->fecha_inicio)->startOfDay();
    $fechaFin    = \Carbon\Carbon::parse($corrida->fecha_fin)->endOfDay();

    // 👇 AJUSTA este nombre de columna si no se llama "fecha"
    $comisionFechaCol  = 'fecha';

    // IMPORTANTE: ajusta nombre de tabla/columnas si difieren
    // $comisionesPorEmpleado = DB::table('comision_personal')
    //     ->selectRaw('empleado_id,
    //                  COALESCE(SUM(importe_comision),0) AS comisiones_monto,
    //                  COALESCE(SUM(tiempo_extra),0) AS horas_extra')
    //     ->whereBetween($fechaCol, [$fechaInicio->toDateString(), $fechaFin->toDateString()])
    //     ->groupBy('empleado_id')
    //     ->pluck(DB::raw("JSON_OBJECT('comisiones_monto', comisiones_monto, 'horas_extra', horas_extra)"), 'empleado_id')
    //     ->map(function ($json) {
    //         return json_decode($json, true) ?: ['comisiones_monto' => 0, 'horas_extra' => 0];
    //     });
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

    DB::transaction(function () use ($corrida, $empleados, $comisiones, &$creados, &$actualizados) {

        // Lock para evitar doble click / concurrencia
        $corrida = \App\Models\NominaCorrida::whereKey($corrida->id)->lockForUpdate()->first();

        foreach ($empleados as $emp) {

            // Leer sueldos (soporta legacy mayúsculas/minúsculas)
            $sueldoImss   = (float)($emp->Sueldo ?? $emp->sueldo ?? 0);
            $complemento  = (float)($emp->Complemento ?? $emp->complemento ?? 0);
            $sueldoReal   = (float)($emp->Sueldo_real ?? $emp->sueldo_real ?? $emp->SueldoReal ?? 0);
        
            $base = $sueldoReal > 0 ? $sueldoReal : ($sueldoImss + $complemento);

            if ($sueldoReal <= 0) {
                $sueldoReal = $sueldoImss + $complemento;
            }

            // ✅ Infonavit base (para no salir en 0)
            $infonavitEmpleado = (float)($emp->Infonavit ?? $emp->infonavit ?? 0);

            // ✅ Obra vigente (tu regla: última/activa al corte)
            // En tu generador ya existe obraActiva; idealmente esa relación ya viene ordenada por "alta desc".
            $obraActiva = null;
            if ($emp->relationLoaded('obraActiva') && $emp->obraActiva && $emp->obraActiva->count() > 0) {
                // toma la primera como "última vigente"
                $obraActiva = $emp->obraActiva->first();
            }

            // ✅ Comisiones/extra del rango (si no hay, 0)
            // $cx = $comisionesPorEmpleado[$emp->id_Empleado] ?? ['comisiones_monto' => 0, 'horas_extra' => 0];
            $cx = $comisiones->get($emp->id_Empleado);
            

            $comisionesMonto = (float)($cx->comisiones_monto ?? 0); // ✅
            $horasExtra      = (float)($cx->horas_extra ?? 0);      // ✅
            $obraPorComision = $cx->obra_id ?? null;
            
            
            $obraActiva = null;
            if ($emp->relationLoaded('obraActiva') && $emp->obraActiva && $emp->obraActiva->count() > 0) {
                $obraActiva = $emp->obraActiva->first();
            }
            $obraIdFinal = $obraPorComision ?: ($obraActiva?->id);

            // Por ahora 0; si luego mapeas metros desde comisiones, aquí lo metes
            $metrosLinMonto = 0;

            // ✅ Dedupe correcto: 1 recibo por empleado por corrida
            $where = [
                'corrida_id'  => $corrida->id,
                'empleado_id' => $emp->id_Empleado,
            ];

            // ✅ Totales base como en el generador (sin faltas/descuentos al inicio)
            $faltas     = 0;
            $descuentos = 0;

            $bruto = $sueldoReal + $complemento + $horasExtra + $metrosLinMonto + $comisionesMonto;
            $dedu  = $faltas + $descuentos + $infonavitEmpleado;
            $neto  = max(0, $bruto - $dedu);

            $payload = [
                // ✅ Obra sugerida/última vigente
                'obra_id'     => $obraIdFinal,
                'obra_legacy' => null,

                'tipo_pago'     => $corrida->tipo_pago,
                'subtipo'       => $corrida->subtipo,
                'periodo_label' => $corrida->periodo_label,
                'fecha_inicio'  => $corrida->fecha_inicio,
                'fecha_fin'     => $corrida->fecha_fin,
                'fecha_pago'    => $corrida->fecha_pago,

                // snapshots base
                'sueldo_imss_snapshot' => $sueldoImss,
                'complemento_snapshot' => $complemento,
                'infonavit_snapshot'   => $infonavitEmpleado,

                // editable (rojo/azul) default + precargas
                'faltas'            => 0,
                'descuentos'        => 0,
                'descuentos_legacy' => 0,

                // 👇 este es el que estás usando en UI como input infonavit
                'infonavit_legacy'  => $infonavitEmpleado,

                'horas_extra'      => $horasExtra,
                'metros_lineales'  => 0,
                'metros_lin_monto' => $metrosLinMonto,

                'comisiones_monto'       => $comisionesMonto,
                'comisiones_lock'        => 0,
                'comisiones_cargadas_at' => null,
                'comisiones_cargadas_by' => null,
                

                'factura_monto'      => 0,
                'notas_legacy'       => null,

                // ✅ Totales precargados
                'total_percepciones' => $base,
                'total_deducciones'  => 0,
                'sueldo_neto'        => $base,

                'status'             => 'pendiente',
                'folio'              => null,
                'referencia_externa' => null,
            ];

            $recibo = NominaRecibo::updateOrCreate($where, $payload);

            if ($recibo->wasRecentlyCreated) $creados++;
            else $actualizados++;
        }
    });

    return back()->with('success', "Recibos generados. Nuevos: {$creados}, actualizados: {$actualizados}.");
}
public function guardarRecibos(Request $request, NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'abierta') {
        return back()->with('error', 'La corrida no está abierta. No se puede editar.');
    }

    $data = $request->input('recibos', []);
    if (!is_array($data) || empty($data)) {
        return back()->with('error', 'No se recibieron cambios.');
    }

    DB::transaction(function () use ($corrida, $data) {

        // 🔒 Lock corrida (anti doble submit / concurrencia)
        $corridaLocked = NominaCorrida::whereKey($corrida->id)->lockForUpdate()->first();

        $reciboIds = array_map('intval', array_keys($data));

        // 🔒 Lock recibos (1 query)
        $recibos = NominaRecibo::where('corrida_id', $corridaLocked->id)
            ->whereIn('id', $reciboIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($data as $reciboId => $row) {
            $reciboId = (int)$reciboId;

            /** @var NominaRecibo|null $recibo */
            $recibo = $recibos->get($reciboId);
            if (!$recibo) continue;

            // =========================
            // 1) Campos editables (alineados a tu Blade)
            // =========================
            $recibo->infonavit_legacy = (float)($row['infonavit'] ?? $recibo->infonavit_legacy);
            $recibo->faltas           = (float)($row['faltas'] ?? $recibo->faltas);
            $recibo->descuentos       = (float)($row['descuentos'] ?? $recibo->descuentos);

            $recibo->horas_extra      = (float)($row['horas_extra'] ?? $recibo->horas_extra);
            $recibo->metros_lin_monto = (float)($row['metros_lin_monto'] ?? $recibo->metros_lin_monto);
            $recibo->comisiones_monto = (float)($row['comisiones_monto'] ?? $recibo->comisiones_monto);

            $recibo->obra_id = (($row['obra_id'] ?? '') !== '') ? (int)$row['obra_id'] : null;
            $recibo->notas_legacy = $row['notas'] ?? $recibo->notas_legacy;

            // =========================
            // 2) Extra único por recibo (nomina_pagos_extra)
            // FK + UNIQUE(recibo_id) ya existen
            // =========================
            $extraMonto = 0.0;

            if (isset($row['extra']) && is_array($row['extra'])) {
                $extra = $row['extra'];

                $tipo  = trim((string)($extra['tipo'] ?? ''));
                $monto = (float)($extra['monto'] ?? 0);
                $notas = (string)($extra['notas'] ?? null);

                if ($tipo === '' || $monto <= 0) {
                    // Si lo vacían, borramos el extra
                    DB::table('nomina_pagos_extra')
                        ->where('recibo_id', $recibo->id)
                        ->delete();
                } else {
                    // updateOrInsert por recibo_id (1 extra máximo)
                    DB::table('nomina_pagos_extra')->updateOrInsert(
                        ['recibo_id' => $recibo->id],
                        [
                            'empleado_id'        => (int)($extra['empleado_id'] ?? $recibo->empleado_id),
                            'obra_id'            => (($extra['obra_id'] ?? '') !== '') ? (int)$extra['obra_id'] : $recibo->obra_id,
                            'tipo'               => $tipo,
                            'anio'               => (int)($extra['anio'] ?? now()->year),
                            'concepto'           => $tipo,
                            'monto'              => $monto,
                            'fecha_pago'         => $extra['fecha_pago'] ?? $corridaLocked->fecha_pago,
                            'folio'              => $extra['folio'] ?? null,
                            'referencia_externa' => $extra['referencia_externa'] ?? null,
                            'notas'              => $notas,
                            'updated_at'         => now(),
                            'created_at'         => now(), // MySQL lo ignora si ya existe, pero queda OK
                        ]
                    );

                    $extraMonto = $monto;
                }
            }

            // =========================
            // 3) Recalcular (misma regla que tu JS)
            // =========================
            $sueldoReal = (float)($recibo->total_percepciones ?? 0);

            $bruto = $sueldoReal
                + (float)($recibo->horas_extra ?? 0)
                + (float)($recibo->metros_lin_monto ?? 0)
                + (float)($recibo->comisiones_monto ?? 0)
                + (float)$extraMonto;

            $deds = (float)($recibo->infonavit_legacy ?? 0)
                + (float)($recibo->faltas ?? 0)
                + (float)($recibo->descuentos ?? 0);

            $neto = $bruto - $deds;
            if ($neto < 0) $neto = 0;

            $recibo->total_deducciones = $deds;
            $recibo->sueldo_neto = $neto;

            $recibo->save();
        }
    });

    return back()->with('success', 'Recibos guardados correctamente.');
}
// public function guardarRecibos(Request $request, NominaCorrida $corrida)
// {
//     if (($corrida->status ?? null) !== 'abierta') {
//         return back()->with('error', 'La corrida no está abierta. No se puede editar.');
//     }

//     $data = $request->input('recibos', []);
//     if (!is_array($data) || empty($data)) {
//         return back()->with('error', 'No se recibieron cambios.');
//     }

//     foreach ($data as $reciboId => $row) {
//         $recibo = \App\Models\NominaRecibo::where('corrida_id', $corrida->id)
//             ->where('id', $reciboId)
//             ->first();

//         if (!$recibo) continue;

//         // Campos editables (rojo + azul + obra + notas)
//         $recibo->faltas = (float)($row['faltas'] ?? $recibo->faltas);
//         $recibo->descuentos = (float)($row['descuentos'] ?? $recibo->descuentos);

//         $recibo->horas_extra = (float)($row['horas_extra'] ?? $recibo->horas_extra);
//         $recibo->metros_lineales = (float)($row['metros_lineales'] ?? $recibo->metros_lineales);
//         $recibo->comisiones_monto = (float)($row['comisiones_monto'] ?? $recibo->comisiones_monto);

//         $recibo->obra_id = $row['obra_id'] !== '' ? (int)$row['obra_id'] : null;
//         $recibo->notas_legacy = $row['notas'] ?? $recibo->notas_legacy;

//         // ✅ Recalcular totales (simple por ahora)
//         // percepciones = total_percepciones (ya trae sueldo_real) + comisiones + (a futuro: horas_extra/m_lineales si tienen monto)
//         $percepciones = (float)$recibo->total_percepciones + (float)$recibo->comisiones_monto;

//         $deducciones = (float)$recibo->descuentos; // faltas pueden ser monto o días; si son días, lo definimos luego

//         $recibo->total_deducciones = $deducciones;
//         $recibo->sueldo_neto = max(0, $percepciones - $deducciones);

//         $recibo->save();
//     }

//     return back()->with('success', 'Recibos guardados correctamente.');
// }
public function destroyRecibos(NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'abierta') {
        return back()->with('error', 'Solo puedes borrar recibos si la corrida está ABIERTA.');
    }

    $deleted = \App\Models\NominaRecibo::where('corrida_id', $corrida->id)->delete();

    return back()->with('success', "Recibos eliminados: {$deleted}.");
}

public function destroy(NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'abierta') {
        return back()->with('error', 'Solo puedes eliminar una corrida si está ABIERTA.');
    }

    DB::transaction(function () use ($corrida) {
        // borrar hijos primero (por si no hay ON DELETE CASCADE)
        NominaRecibo::where('corrida_id', $corrida->id)->delete();

        // borrar corrida
        $corrida->delete();
    });

    return redirect()
        ->route('nomina.generador.index')
        ->with('success', 'Corrida eliminada correctamente.');
}
public function cerrar(NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'abierta') return back()->with('error','Solo puedes cerrar una corrida ABIERTA.');

    // opcional: validar que tenga recibos
    if (!$corrida->recibos()->exists()) return back()->with('error','No hay recibos para cerrar.');

    $corrida->update(['status' => 'cerrada']);
    return back()->with('success','Corrida cerrada.');
}

public function marcarPagada(NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'cerrada') return back()->with('error','Solo puedes marcar pagada una corrida CERRADA.');

    $corrida->update(['status' => 'pagada']);

    // opcional: marcar recibos pagados
    // $corrida->recibos()->update(['status' => 'pagado']);

    return back()->with('success','Corrida marcada como PAGADA.');
}

public function reabrir(NominaCorrida $corrida)
{
    if (($corrida->status ?? null) !== 'cerrada') return back()->with('error','Solo puedes reabrir una corrida CERRADA.');

    $corrida->update(['status' => 'abierta']);
    return back()->with('success','Corrida reabierta.');
}
}
