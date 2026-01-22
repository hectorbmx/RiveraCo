<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillObraEmpleadoRolId extends Command
{
    protected $signature = 'roles:backfill-obra-empleado {--dry-run : Solo muestra conteos, no actualiza} {--only-null : Solo llena registros con rol_id NULL}';
    protected $description = 'Asigna obra_empleado.rol_id usando puesto_en_obra y catalogo_roles_alias';

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $onlyNull = (bool) $this->option('only-null');

        // Conteo de candidatos
        $base = DB::table('obra_empleado as oe')
            ->join('catalogo_roles_alias as a', 'a.alias', '=', 'oe.puesto_en_obra');

        if ($onlyNull) {
            $base->whereNull('oe.rol_id');
        }

        $candidatos = (clone $base)->count();

        $this->info("Candidatos a actualizar: {$candidatos}");

        // Conteo de no mapeados (para saber qué te falta agregar como alias)
        $noMapeadosQuery = DB::table('obra_empleado as oe')
            ->whereNotNull('oe.puesto_en_obra')
            ->when($onlyNull, fn($q) => $q->whereNull('oe.rol_id'))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('catalogo_roles_alias as a')
                  ->whereColumn('a.alias', 'oe.puesto_en_obra');
            })
            ->select('oe.puesto_en_obra', DB::raw('COUNT(*) as total'))
            ->groupBy('oe.puesto_en_obra')
            ->orderByDesc('total');

        $noMapeados = $noMapeadosQuery->get();

        $this->info('Puestos en obra SIN mapeo (top 20):');
        foreach ($noMapeados->take(20) as $row) {
            $this->line(" - {$row->puesto_en_obra} ({$row->total})");
        }

        if ($dryRun) {
            $this->warn('Dry-run activado: no se actualizó nada.');
            return self::SUCCESS;
        }

        // Update real
        $update = DB::table('obra_empleado as oe')
            ->join('catalogo_roles_alias as a', 'a.alias', '=', 'oe.puesto_en_obra')
            ->when($onlyNull, fn($q) => $q->whereNull('oe.rol_id'))
            ->update(['oe.rol_id' => DB::raw('a.rol_id')]);

        $this->info("Registros actualizados: {$update}");

        return self::SUCCESS;
    }
}
