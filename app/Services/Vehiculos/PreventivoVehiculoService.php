<?php

namespace App\Services\Vehiculos;

use App\Models\EmpresaConfig;
use App\Models\EmpresaServicioPreventivoTipo;
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

        $ultimosServiciosPorTipo = Mantenimiento::query()
            ->whereIn('vehiculo_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('km_actuales')
            ->whereNotNull('servicio_preventivo_tipo_id')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (Mantenimiento $mantenimiento) => $mantenimiento->vehiculo_id . ':' . $mantenimiento->servicio_preventivo_tipo_id)
            ->groupBy('vehiculo_id')
            ->map(fn (Collection $mantenimientos) => $mantenimientos->keyBy('servicio_preventivo_tipo_id'));

        $ultimosServiciosLegacy = Mantenimiento::query()
            ->whereIn('vehiculo_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('km_actuales')
            ->whereNull('servicio_preventivo_tipo_id')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get()
            ->unique('vehiculo_id')
            ->keyBy('vehiculo_id');

        $serviciosPreventivos = EmpresaServicioPreventivoTipo::query()
            ->vehiculos()
            ->activos()
            ->ordenados()
            ->get();

        return $vehiculos
            ->mapWithKeys(function (Vehiculo $vehiculo) use ($config, $ultimosLogs, $ultimasAsignaciones, $ultimosServiciosPorTipo, $ultimosServiciosLegacy, $serviciosPreventivos) {
                return [
                    $vehiculo->id => $this->calcular(
                        $vehiculo,
                        $config,
                        $ultimosLogs->get($vehiculo->id),
                        $ultimasAsignaciones->get($vehiculo->id),
                        $ultimosServiciosPorTipo->get($vehiculo->id, collect()),
                        $ultimosServiciosLegacy->get($vehiculo->id),
                        $serviciosPreventivos
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
        Collection $ultimosServiciosVehiculo,
        ?Mantenimiento $ultimoServicioLegacy,
        Collection $serviciosPreventivos
    ): array {
        $kmActual = $this->mayorEntero([
            $ultimoLog?->km,
            $ultimaAsignacion?->km_final,
            $ultimaAsignacion?->km_inicial,
        ]);

        if ($serviciosPreventivos->isEmpty()) {
            $serviciosPreventivos = collect([
                new EmpresaServicioPreventivoTipo([
                    'nombre' => 'Servicio preventivo',
                    'codigo' => 'legacy',
                    'intervalo_valor' => (int) ($config?->vehiculo_servicio_km ?? 5000),
                    'intervalo_meses' => (int) ($config?->vehiculo_servicio_meses ?? 6),
                    'alerta_valor' => (int) ($config?->vehiculo_alerta_km ?? 500),
                ]),
            ]);
        }

        $servicios = $serviciosPreventivos
            ->map(function (EmpresaServicioPreventivoTipo $servicio) use ($kmActual, $ultimaAsignacion, $ultimosServiciosVehiculo, $ultimoServicioLegacy, $ultimoLog) {
                $ultimoServicio = $servicio->exists
                    ? $ultimosServiciosVehiculo->get($servicio->id)
                    : $ultimoServicioLegacy;
                $kmBaseServicio = $this->primerEntero([
                    $ultimoServicio?->km_actuales,
                    $ultimaAsignacion?->km_inicial,
                    $kmActual,
                ]);
                $fechaUltimoServicio = $this->fechaUltimoServicio($ultimoServicio);

                return $this->calcularServicio(
                    $servicio,
                    $kmActual,
                    $kmBaseServicio,
                    $fechaUltimoServicio,
                    $ultimoLog
                );
            })
            ->values();

        $principal = $servicios
            ->sort(function (array $a, array $b) {
                $estado = $this->prioridadEstado($b['estado']) <=> $this->prioridadEstado($a['estado']);

                if ($estado !== 0) {
                    return $estado;
                }

                $km = ($a['km_restantes'] ?? PHP_INT_MAX) <=> ($b['km_restantes'] ?? PHP_INT_MAX);

                return $km !== 0
                    ? $km
                    : (($b['intervalo_km'] ?? 0) <=> ($a['intervalo_km'] ?? 0));
            })
            ->first();

        return array_merge($principal, [
            'km_actual' => $kmActual,
            'ultima_captura_fecha' => $ultimoLog?->fecha,
            'ultima_captura_foto' => $ultimoLog?->foto,
            'servicios' => $servicios->all(),
        ]);
    }

    private function calcularServicio(
        EmpresaServicioPreventivoTipo $servicio,
        ?int $kmActual,
        ?int $kmBaseServicio,
        ?Carbon $fechaUltimoServicio,
        ?VehiculoEmpleadoKmLog $ultimoLog
    ): array {
        $intervaloKm = (int) $servicio->intervalo_valor;
        $intervaloMeses = (int) ($servicio->intervalo_meses ?? 0);
        $alertaKm = (int) ($servicio->alerta_valor ?? 0);

        if ($intervaloKm <= 0 || $kmActual === null || $kmBaseServicio === null) {
            return [
                'servicio_id' => $servicio->exists ? $servicio->id : null,
                'servicio_nombre' => $servicio->nombre,
                'servicio_codigo' => $servicio->codigo,
                'servicio_tipo_actual' => null,
                'servicio_tipo_siguiente' => $servicio->nombre,
                'servicio_tipo_codigo' => $servicio->codigo,
                'servicio_tipo_nombre' => $servicio->nombre,
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
        $ciclosCompletos = (int) floor($kmUsados / $intervaloKm);
        $kmEnCiclo = $kmUsados % $intervaloKm;
        $proximoCiclo = $kmUsados > 0 && $kmEnCiclo === 0
            ? max(1, $ciclosCompletos)
            : $ciclosCompletos + 1;
        $kmProximoServicio = $kmBaseServicio + ($proximoCiclo * $intervaloKm);
        $kmRestantes = $kmProximoServicio - $kmActual;
        $porcentaje = min(100, max(0, ($kmEnCiclo / $intervaloKm) * 100));

        if ($kmUsados > 0 && $kmEnCiclo === 0) {
            $porcentaje = 100;
        }

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
            'vencido' => abs($kmRestantes) < 1
                ? 'Servicio requerido'
                : 'Vencido por ' . number_format(abs($kmRestantes)) . ' km',
            'proximo' => 'Proximo: restan ' . number_format($kmRestantes) . ' km',
            default => 'Restan ' . number_format($kmRestantes) . ' km',
        };

        if ($estadoTiempo === 'vencido' && $estadoKm !== 'vencido') {
            $label = 'Vencido por tiempo';
        }

        return [
            'servicio_id' => $servicio->exists ? $servicio->id : null,
            'servicio_nombre' => $servicio->nombre,
            'servicio_codigo' => $servicio->codigo,
            'servicio_tipo_actual' => null,
            'servicio_tipo_siguiente' => $servicio->nombre,
            'servicio_tipo_codigo' => $servicio->codigo,
            'servicio_tipo_nombre' => $servicio->nombre,
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

    private function prioridadEstado(string $estado): int
    {
        return match ($estado) {
            'vencido' => 4,
            'proximo' => 3,
            'ok' => 2,
            default => 1,
        };
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

    private function mayorEntero(array $valores): ?int
    {
        $enteros = [];

        foreach ($valores as $valor) {
            if ($valor !== null && $valor !== '') {
                $enteros[] = (int) $valor;
            }
        }

        return $enteros === [] ? null : max($enteros);
    }

    private function fechaUltimoServicio(?Mantenimiento $ultimoServicio): ?Carbon
    {
        $fecha = $ultimoServicio?->fecha_fin
            ?? $ultimoServicio?->fecha_programada
            ?? $ultimoServicio?->created_at;

        return $fecha ? Carbon::parse($fecha) : null;
    }
}
