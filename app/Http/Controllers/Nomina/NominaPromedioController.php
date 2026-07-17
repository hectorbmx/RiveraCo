<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NominaPromedioController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->input('desde') ?: now()->startOfYear()->toDateString();
        $hasta = $request->input('hasta') ?: now()->toDateString();
        $tipo = $request->input('tipo', '');
        $empleadoId = $request->input('empleado_id', '');

        $desdeDate = Carbon::parse($desde)->toDateString();
        $hastaDate = Carbon::parse($hasta)->toDateString();

        $query = DB::table('empleados as e')
            ->join('nomina_recibos as nr', 'nr.empleado_id', '=', 'e.id_Empleado')
            ->join('nomina_corridas as nc', 'nc.id', '=', 'nr.corrida_id')
            ->whereIn('nc.status', ['cerrada', 'pagada'])
            ->whereDate('nr.fecha_inicio', '>=', $desdeDate)
            ->whereDate('nr.fecha_fin', '<=', $hastaDate);

        if ($tipo !== '') {
            $query->where('e.Sueldo_tipo', (int) $tipo);
        }

        if ($empleadoId !== '') {
            $query->where('e.id_Empleado', (int) $empleadoId);
        }

        $rows = $query
            ->selectRaw("
                e.id_Empleado as empleado_id,
                e.Nombre as nombre,
                e.Apellidos as apellidos,
                e.Sueldo_tipo as sueldo_tipo,
                COALESCE(e.Sueldo_real, 0) as sueldo_real,
                COALESCE(e.Sueldo, 0) as sueldo_imss,
                COALESCE(e.Complemento, 0) as complemento,
                COUNT(nr.id) as recibos_count,
                MIN(nr.fecha_inicio) as primer_periodo,
                MAX(nr.fecha_fin) as ultimo_periodo,
                SUM(COALESCE(nr.sueldo_neto, 0)) as total_neto,
                SUM(COALESCE(nr.sueldo_imss_snapshot, 0) + COALESCE(nr.complemento_snapshot, 0)) as total_pago_base,
                SUM(COALESCE(nr.total_percepciones, 0)) as total_percepciones,
                SUM(COALESCE(nr.total_percepciones, 0) - (COALESCE(nr.sueldo_imss_snapshot, 0) + COALESCE(nr.complemento_snapshot, 0)) - COALESCE(nr.horas_extra, 0) - COALESCE(nr.metros_lin_monto, 0) - COALESCE(nr.comisiones_monto, 0)) as ajuste_sueldo_real,
                SUM(COALESCE(nr.total_deducciones, 0)) as total_deducciones,
                SUM(COALESCE(nr.faltas, 0)) as faltas,
                SUM(COALESCE(nr.descuentos, 0) + COALESCE(nr.descuentos_legacy, 0)) as descuentos,
                SUM(COALESCE(nr.horas_extra, 0)) as horas_extra,
                SUM(COALESCE(nr.metros_lin_monto, 0)) as metros_lin_monto,
                SUM(COALESCE(nr.comisiones_monto, 0)) as comisiones_monto
            ")
            ->groupBy(
                'e.id_Empleado',
                'e.Nombre',
                'e.Apellidos',
                'e.Sueldo_tipo',
                'e.Sueldo_real',
                'e.Sueldo',
                'e.Complemento'
            )
            ->orderBy('e.Nombre')
            ->orderBy('e.Apellidos')
            ->get()
            ->map(function ($row) {
                $primerPeriodo = $row->primer_periodo ? Carbon::parse($row->primer_periodo) : null;
                $ultimoPeriodo = $row->ultimo_periodo ? Carbon::parse($row->ultimo_periodo) : null;
                $dias = ($primerPeriodo && $ultimoPeriodo)
                    ? max(1, $primerPeriodo->diffInDays($ultimoPeriodo) + 1)
                    : 0;

                $mesesEquivalentes = $dias > 0 ? ($dias / 30.42) : 0;
                $sueldoBase = (float) $row->sueldo_imss + (float) $row->complemento;
                $row->dias_transcurridos = $dias;
                $row->meses_equivalentes = $mesesEquivalentes;
                $row->promedio_mensual_real = $mesesEquivalentes > 0
                    ? ((float) $row->total_pago_base / $mesesEquivalentes)
                    : 0;
                $row->promedio_por_recibo = ((int) $row->recibos_count) > 0
                    ? ((float) $row->total_pago_base / (int) $row->recibos_count)
                    : 0;
                $row->sueldo_teorico_diario = $this->sueldoTeoricoDiario($sueldoBase, (int) $row->sueldo_tipo);
                $row->sueldo_teorico_mensual = $this->sueldoTeoricoMensual($sueldoBase, (int) $row->sueldo_tipo);

                return $row;
            });

        $empleados = Empleado::query()
            ->where('Estatus', 1)
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get(['id_Empleado', 'Nombre', 'Apellidos']);

        $totales = [
            'empleados' => $rows->count(),
            'recibos' => $rows->sum('recibos_count'),
            'neto' => $rows->sum('total_neto'),
            'base_promedio' => $rows->sum('total_pago_base'),
            'promedio_mensual' => $rows->avg('promedio_mensual_real') ?: 0,
        ];

        return view('nomina.promedios.index', compact(
            'desde',
            'hasta',
            'tipo',
            'empleadoId',
            'rows',
            'empleados',
            'totales'
        ));
    }

    public function detalle(Request $request, int $empleado)
    {
        $desde = $request->input('desde') ?: now()->startOfYear()->toDateString();
        $hasta = $request->input('hasta') ?: now()->toDateString();
        $tipo = $request->input('tipo', '');

        $desdeDate = Carbon::parse($desde)->toDateString();
        $hastaDate = Carbon::parse($hasta)->toDateString();

        $empleadoModel = Empleado::query()
            ->where('id_Empleado', $empleado)
            ->firstOrFail();

        $recibos = DB::table('nomina_recibos as nr')
            ->join('nomina_corridas as nc', 'nc.id', '=', 'nr.corrida_id')
            ->where('nr.empleado_id', $empleadoModel->id_Empleado)
            ->whereIn('nc.status', ['cerrada', 'pagada'])
            ->whereDate('nr.fecha_inicio', '>=', $desdeDate)
            ->whereDate('nr.fecha_fin', '<=', $hastaDate)
            ->selectRaw('
                nr.id,
                nr.corrida_id,
                nr.periodo_label,
                nr.fecha_inicio,
                nr.fecha_fin,
                nr.fecha_pago,
                nr.sueldo_imss_snapshot,
                nr.complemento_snapshot,
                nr.total_percepciones,
                (COALESCE(nr.total_percepciones, 0) - (COALESCE(nr.sueldo_imss_snapshot, 0) + COALESCE(nr.complemento_snapshot, 0)) - COALESCE(nr.horas_extra, 0) - COALESCE(nr.metros_lin_monto, 0) - COALESCE(nr.comisiones_monto, 0)) as ajuste_sueldo_real,
                nr.total_deducciones,
                nr.sueldo_neto,
                nr.faltas,
                nr.descuentos,
                nr.descuentos_legacy,
                nr.infonavit_legacy,
                nr.horas_extra,
                nr.metros_lin_monto,
                nr.comisiones_monto,
                nr.status as recibo_status,
                nc.status as corrida_status,
                nc.periodo_label as corrida_label
            ')
            ->orderBy('nr.fecha_inicio')
            ->orderBy('nr.id')
            ->get()
            ->map(function ($recibo) {
                $recibo->pago_base = (float) $recibo->sueldo_imss_snapshot + (float) $recibo->complemento_snapshot;
                $recibo->variable = (float) $recibo->horas_extra
                    + (float) $recibo->metros_lin_monto
                    + (float) $recibo->comisiones_monto;
                $recibo->descuentos_total = (float) $recibo->descuentos + (float) $recibo->descuentos_legacy;

                return $recibo;
            });

        $totales = [
            'recibos' => $recibos->count(),
            'pago_base' => $recibos->sum('pago_base'),
            'percepciones' => $recibos->sum('total_percepciones'),
            'deducciones' => $recibos->sum('total_deducciones'),
            'neto' => $recibos->sum('sueldo_neto'),
            'variable' => $recibos->sum('variable'),
            'ajuste_sueldo_real' => $recibos->sum('ajuste_sueldo_real'),
        ];

        $primerPeriodo = $recibos->min('fecha_inicio');
        $ultimoPeriodo = $recibos->max('fecha_fin');
        $diasPeriodo = ($primerPeriodo && $ultimoPeriodo)
            ? max(1, Carbon::parse($primerPeriodo)->diffInDays(Carbon::parse($ultimoPeriodo)) + 1)
            : 0;
        $mesesEquivalentes = $diasPeriodo > 0 ? ($diasPeriodo / 30.42) : 0;
        $sueldoBaseActual = (float) ($empleadoModel->Sueldo ?? 0) + (float) ($empleadoModel->Complemento ?? 0);

        $totales['dias_periodo'] = $diasPeriodo;
        $totales['meses_equivalentes'] = $mesesEquivalentes;
        $totales['promedio_teorico_mensual'] = $this->sueldoTeoricoMensual($sueldoBaseActual, (int) $empleadoModel->Sueldo_tipo);
        $totales['promedio_real_mensual'] = $mesesEquivalentes > 0
            ? ((float) $totales['neto'] / $mesesEquivalentes)
            : 0;
        $tipoLabel = match ((int) $empleadoModel->Sueldo_tipo) {
            1 => 'Semanal',
            2 => 'Quincenal',
            3 => 'Mensual',
            default => 'Sin tipo',
        };

        return view('nomina.promedios.detalle', compact(
            'desde',
            'hasta',
            'tipo',
            'empleadoModel',
            'tipoLabel',
            'recibos',
            'totales'
        ));
    }

    public function recalcular(Request $request, int $empleado)
    {
        $desde = $request->input('desde') ?: now()->startOfYear()->toDateString();
        $hasta = $request->input('hasta') ?: now()->toDateString();
        $tipo = $request->input('tipo', '');

        $desdeDate = Carbon::parse($desde)->toDateString();
        $hastaDate = Carbon::parse($hasta)->toDateString();

        $empleadoModel = Empleado::query()
            ->where('id_Empleado', $empleado)
            ->firstOrFail();

        $sueldoImss = (float) ($empleadoModel->Sueldo ?? 0);
        $complemento = (float) ($empleadoModel->Complemento ?? 0);
        $base = $sueldoImss + $complemento;

        if ($base <= 0) {
            return redirect()
                ->route('nomina.promedios.empleados.show', array_filter([
                    'empleado' => $empleadoModel->id_Empleado,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'tipo' => $tipo,
                ]))
                ->with('error', 'No se recalculo: el empleado no tiene Sueldo + Complemento valido.');
        }

        $actualizados = 0;

        DB::transaction(function () use ($empleadoModel, $desdeDate, $hastaDate, $sueldoImss, $complemento, $base, &$actualizados) {
            $recibos = DB::table('nomina_recibos as nr')
                ->join('nomina_corridas as nc', 'nc.id', '=', 'nr.corrida_id')
                ->where('nr.empleado_id', $empleadoModel->id_Empleado)
                ->whereIn('nc.status', ['cerrada', 'pagada'])
                ->whereDate('nr.fecha_inicio', '>=', $desdeDate)
                ->whereDate('nr.fecha_fin', '<=', $hastaDate)
                ->select('nr.*')
                ->lockForUpdate()
                ->get();

            foreach ($recibos as $recibo) {
                $variable = (float) ($recibo->horas_extra ?? 0)
                    + (float) ($recibo->metros_lin_monto ?? 0)
                    + (float) ($recibo->comisiones_monto ?? 0);

                $deducciones = (float) ($recibo->faltas ?? 0)
                    + (float) ($recibo->descuentos ?? 0)
                    + (float) ($recibo->descuentos_legacy ?? 0)
                    + (float) ($recibo->infonavit_legacy ?? $recibo->infonavit_snapshot ?? 0);

                $percepciones = $base + $variable;
                $neto = max(0, $percepciones - $deducciones);

                DB::table('nomina_recibos')
                    ->where('id', $recibo->id)
                    ->update([
                        'sueldo_imss_snapshot' => $sueldoImss,
                        'complemento_snapshot' => $complemento,
                        'total_percepciones' => $percepciones,
                        'total_deducciones' => $deducciones,
                        'sueldo_neto' => $neto,
                        'updated_at' => now(),
                    ]);

                $actualizados++;
            }
        });

        return redirect()
            ->route('nomina.promedios.empleados.show', array_filter([
                'empleado' => $empleadoModel->id_Empleado,
                'desde' => $desde,
                'hasta' => $hasta,
                'tipo' => $tipo,
            ]))
            ->with('success', "Recibos recalculados: {$actualizados}.");
    }
    private function sueldoTeoricoDiario(float $sueldoBase, int $tipo): float
    {
        return match ($tipo) {
            1 => $sueldoBase / 7,
            2 => $sueldoBase / 15,
            3 => $sueldoBase / 30,
            default => 0,
        };
    }

    private function sueldoTeoricoMensual(float $sueldoBase, int $tipo): float
    {
        return match ($tipo) {
            1 => ($sueldoBase / 7) * 30,
            2 => ($sueldoBase / 15) * 30,
            3 => $sueldoBase,
            default => 0,
        };
    }
}