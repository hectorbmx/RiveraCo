<?php

namespace App\Services\Vehiculos;

use App\Models\EmpresaConfig;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use App\Models\VehiculoEmpleado;
use App\Models\VehiculoEmpleadoKmLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PreventivoVehiculoService
{
    public function calcularParaColeccion(Collection $vehiculos, ?EmpresaConfig $config = null): array
    {
        $config ??= EmpresaConfig::first();
        $ids = $vehiculos->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $ultimosLogs = VehiculoEmpleadoKmLog::query()
            ->select('vehiculo_empleado_km_logs.*')
            ->join('vehiculo_empleado', 'vehiculo_empleado.id', '=', 'vehiculo_empleado_km_logs.vehiculo_empleado_id')
            ->whereIn('vehiculo_empleado.vehiculo_id', $ids)
            ->with('asignacion')
            ->orderByDesc('vehiculo_empleado_km_logs.fecha')
            ->orderByDesc('vehiculo_empleado_km_logs.id')
            ->get()
            ->unique(fn (VehiculoEmpleadoKmLog $log) => $log->asignacion?->vehiculo_id)
            ->keyBy(fn (VehiculoEmpleadoKmLog $log) => $log->asignacion?->vehiculo_id);

        $ultimasAsignaciones = VehiculoEmpleado::query()
            ->whereIn('vehiculo_id', $ids)
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->orderByDesc('id')
            ->get()
            ->unique('vehiculo_id')
            ->keyBy('vehiculo_id');

        $ultimosServicios = Mantenimiento::query()
            ->whereIn('vehiculo_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('km_actuales')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get()
            ->unique('vehiculo_id')
            ->keyBy('vehiculo_id');

        return $vehiculos
            ->mapWithKeys(function (Vehiculo $vehiculo) use ($config, $ultimosLogs, $ultimasAsignaciones, $ultimosServicios) {
                return [
                    $vehiculo->id => $this->calcular(
                        $vehiculo,
                        $config,
                        $ultimosLogs->get($vehiculo->id),
                        $ultimasAsignaciones->get($vehiculo->id),
                        $ultimosServicios->get($vehiculo->id)
                    ),
                ];
            })
            ->all();
    }

    public function calcularParaVehiculo(Vehiculo $vehiculo, ?EmpresaConfig $config = null): array
    {
        return $this->calcularParaColeccion(collect([$vehiculo]), $config)[$vehiculo->id];
    }

    private function calcular(
        Vehiculo $vehiculo,
        ?EmpresaConfig $config,
        ?VehiculoEmpleadoKmLog $ultimoLog,
        ?VehiculoEmpleado $ultimaAsignacion,
        ?Mantenimiento $ultimoServicio
    ): array {
        $intervaloKm = (int) ($config?->vehiculo_servicio_km ?? 5000);
        $intervaloMeses = (int) ($config?->vehiculo_servicio_meses ?? 6);
        $alertaKm = (int) ($config?->vehiculo_alerta_km ?? 500);

        $kmActual = $this->primerEntero([
            $ultimoLog?->km,
            $ultimaAsignacion?->km_final,
            $ultimaAsignacion?->km_inicial,
        ]);

        $kmBaseServicio = $this->primerEntero([
            $ultimoServicio?->km_actuales,
            $ultimaAsignacion?->km_inicial,
            $kmActual,
        ]);

        if ($intervaloKm <= 0 || $kmActual === null || $kmBaseServicio === null) {
            return [
                'estado' => 'sin_datos',
                'label' => $intervaloKm <= 0 ? 'Configurar intervalo' : 'Sin kilometraje',
                'color' => 'slate',
                'km_actual' => $kmActual,
                'km_ultimo_servicio' => $kmBaseServicio,
                'km_usados' => null,
                'km_restantes' => null,
                'intervalo_km' => $intervaloKm,
                'porcentaje' => 0,
                'km_proximo_servicio' => null,
                'ultimo_servicio_fecha' => null,
                'proximo_fecha' => null,
                'ultima_captura_fecha' => $ultimoLog?->fecha,
                'ultima_captura_foto' => $ultimoLog?->foto,
            ];
        }

        $kmUsados = max(0, $kmActual - $kmBaseServicio);
        $kmProximoServicio = $kmBaseServicio + $intervaloKm;
        $kmRestantes = $kmProximoServicio - $kmActual;
        $porcentaje = min(100, max(0, ($kmUsados / $intervaloKm) * 100));

        $fechaUltimoServicio = $this->fechaUltimoServicio($ultimoServicio);
        $proximoFecha = $fechaUltimoServicio && $intervaloMeses > 0
            ? $fechaUltimoServicio->copy()->addMonths($intervaloMeses)
            : null;

        $estadoKm = match (true) {
            $kmRestantes <= 0 => 'vencido',
            $kmRestantes <= $alertaKm => 'proximo',
            default => 'ok',
        };

        $estadoTiempo = $proximoFecha && now()->greaterThanOrEqualTo($proximoFecha)
            ? 'vencido'
            : 'ok';

        $estado = $estadoTiempo === 'vencido' ? 'vencido' : $estadoKm;

        $color = match ($estado) {
            'vencido' => 'rose',
            'proximo' => 'amber',
            default => 'emerald',
        };

        $label = match ($estado) {
            'vencido' => 'Vencido por ' . number_format(abs($kmRestantes)) . ' km',
            'proximo' => 'Proximo: restan ' . number_format($kmRestantes) . ' km',
            default => 'Restan ' . number_format($kmRestantes) . ' km',
        };

        if ($estadoTiempo === 'vencido' && $estadoKm !== 'vencido') {
            $label = 'Vencido por tiempo';
        }

        return [
            'estado' => $estado,
            'label' => $label,
            'color' => $color,
            'km_actual' => $kmActual,
            'km_ultimo_servicio' => $kmBaseServicio,
            'km_usados' => $kmUsados,
            'km_restantes' => $kmRestantes,
            'intervalo_km' => $intervaloKm,
            'porcentaje' => $porcentaje,
            'km_proximo_servicio' => $kmProximoServicio,
            'ultimo_servicio_fecha' => $fechaUltimoServicio,
            'proximo_fecha' => $proximoFecha,
            'ultima_captura_fecha' => $ultimoLog?->fecha,
            'ultima_captura_foto' => $ultimoLog?->foto,
        ];
    }

    private function primerEntero(array $valores): ?int
    {
        foreach ($valores as $valor) {
            if ($valor !== null && $valor !== '') {
                return (int) $valor;
            }
        }

        return null;
    }

    private function fechaUltimoServicio(?Mantenimiento $ultimoServicio): ?Carbon
    {
        $fecha = $ultimoServicio?->fecha_fin
            ?? $ultimoServicio?->fecha_programada
            ?? $ultimoServicio?->created_at;

        return $fecha ? Carbon::parse($fecha) : null;
    }
}
