<?php

namespace App\Console\Commands;

use App\Models\GiraldaHoraExtra;
use App\Services\Empleados\GiraldaHoraExtraSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CompletarGiraldaHorasExtrasSnapshots extends Command
{
    protected $signature = 'giralda:horas-extras-snapshots
        {--aplicar : Guardar; sin esta opcion solo simula}
        {--estimar-sueldo-actual : Permitir estimaciones identificadas con el sueldo actual}
        {--empleado= : Limitar a un empleado}
        {--desde= : Fecha inicial YYYY-MM-DD}
        {--hasta= : Fecha final YYYY-MM-DD}
        {--lote=200 : Registros por lote, de 1 a 1000}
        {--detalle : Mostrar ID, empleado, fecha y resultado de cada registro}';

    protected $description = 'Completa snapshots faltantes de horas extra Giralda sin sobrescribir importes existentes.';

    public function handle(GiraldaHoraExtraSnapshotService $service): int
    {
        $validator = Validator::make($this->options(), [
            'empleado' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => array_filter(['nullable', 'date_format:Y-m-d', $this->option('desde') ? 'after_or_equal:desde' : null]),
            'lote' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::INVALID;
        }
        $aplicar = (bool) $this->option('aplicar');
        $this->info($aplicar ? 'APLICACION: se guardaran snapshots faltantes.' : 'SIMULACION: no se guardara ningun cambio.');
        if ($this->option('estimar-sueldo-actual')) {
            $this->warn('Los calculos automaticos se marcaran estimado_sueldo_actual; no representan un pago historico confirmado.');
        }
        $conteos = [];
        GiraldaHoraExtra::query()->where(function ($q) {
            $q->whereNull('snapshot_at')->orWhereNull('costo_hora_extra')->orWhereNull('importe')
                ->orWhereNull('origen_tarifa')->orWhereNull('snapshot_fuente');
        })
            ->when($this->option('empleado'), fn ($q, $id) => $q->where('empleado_id', $id))
            ->when($this->option('desde'), fn ($q, $fecha) => $q->whereDate('fecha', '>=', $fecha))
            ->when($this->option('hasta'), fn ($q, $fecha) => $q->whereDate('fecha', '<=', $fecha))
            ->chunkById((int) $this->option('lote'), function ($registros) use ($service, $aplicar, &$conteos) {
                foreach ($registros as $registro) {
                    $resultado = $service->completar($registro, $aplicar, (bool) $this->option('estimar-sueldo-actual'));
                    $conteos[$resultado] = ($conteos[$resultado] ?? 0) + 1;
                    if ($this->option('detalle')) {
                        $this->line($registro->id.' | '.$registro->empleado_id.' | '.$registro->fecha->toDateString().' | '.$resultado);
                    }
                }
            });
        $this->table(['Resultado', 'Registros'], collect($conteos)->map(fn ($total, $estado) => [$estado, $total])->values()->all());
        $listos = ($conteos['cuota_fija'] ?? 0) + ($conteos['estimado'] ?? 0);
        $pendientes = array_sum($conteos) - $listos - ($conteos['existente'] ?? 0);
        $this->info(($aplicar ? 'Guardados: ' : 'Se pueden completar: ').$listos.'. Pendientes de revision: '.$pendientes.'.');
        if ($pendientes > 0) {
            $this->warn('Consulta --detalle para localizar pendientes. No se modifican recibos ni periodos protegidos.');
        }

        return self::SUCCESS;
    }
}
