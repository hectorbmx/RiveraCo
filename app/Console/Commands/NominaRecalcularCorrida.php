<?php

namespace App\Console\Commands;

use App\Models\NominaCorrida;
use App\Services\Nomina\ListaRayaResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NominaRecalcularCorrida extends Command
{
    protected $signature = 'nomina:recalcular-corrida {corrida? : ID de corrida} {--all : Recalcular todas las corridas}';

    protected $description = 'Recalcula lista de raya, obra viva sugerida y totales de recibos existentes sin borrar captura.';

    public function handle(ListaRayaResolver $listaRayaResolver): int
    {
        if (! $this->option('all') && ! $this->argument('corrida')) {
            $this->error('Indica una corrida o usa --all.');
            return self::FAILURE;
        }

        $listaRayaResolver->syncObrasVivas();

        $query = NominaCorrida::query()->with(['recibos.empleado']);

        if (! $this->option('all')) {
            $query->whereKey((int) $this->argument('corrida'));
        }

        $corridas = $query->get();

        if ($corridas->isEmpty()) {
            $this->warn('No se encontraron corridas para recalcular.');
            return self::SUCCESS;
        }

        $recibosActualizados = 0;

        foreach ($corridas as $corrida) {
            DB::transaction(function () use ($corrida, $listaRayaResolver, &$recibosActualizados) {
                foreach ($corrida->recibos as $recibo) {
                    $empleado = $recibo->empleado;
                    if (! $empleado) {
                        continue;
                    }

                    $listaRaya = $listaRayaResolver->resolverParaEmpleado($empleado);
                    $obraViva = $listaRayaResolver->obraVivaDelEmpleado($empleado);

                    $extraMonto = (float) DB::table('nomina_pagos_extra')
                        ->where('recibo_id', $recibo->id)
                        ->sum('monto');

                    $base = (float) ($recibo->total_percepciones ?? 0);
                    $bruto = $base
                        + (float) ($recibo->horas_extra ?? 0)
                        + (float) ($recibo->metros_lin_monto ?? 0)
                        + (float) ($recibo->comisiones_monto ?? 0)
                        + $extraMonto;

                    $deducciones = (float) ($recibo->infonavit_legacy ?? 0)
                        + (float) ($recibo->faltas ?? 0)
                        + (float) ($recibo->descuentos ?? 0);

                    $recibo->lista_raya_id = $listaRaya?->id;
                    $recibo->lista_raya_nombre = $listaRaya?->nombre;
                    $recibo->lista_raya_tipo = $listaRaya?->tipo;

                    if ($obraViva && ! $recibo->obra_id) {
                        $recibo->obra_id = $obraViva->id;
                    }

                    $recibo->total_deducciones = $deducciones;
                    $recibo->sueldo_neto = max(0, $bruto - $deducciones);
                    $recibo->save();

                    $recibosActualizados++;
                }
            });
        }

        $this->info("Corridas recalculadas: {$corridas->count()}. Recibos actualizados: {$recibosActualizados}.");

        return self::SUCCESS;
    }
}