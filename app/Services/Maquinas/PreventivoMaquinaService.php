<?php

namespace App\Services\Maquinas;

use App\Models\EmpresaConfig;
use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Mantenimiento;
use App\Models\Maquina;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PreventivoMaquinaService
{
    public function __construct(private MaquinaHorometroService $horometroService)
    {
    }

    public function calcularParaColeccion(Collection $maquinas, ?EmpresaConfig $config = null): array
    {
        $config ??= EmpresaConfig::first();
        $ids = $maquinas->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $ultimosServiciosPorTipo = Mantenimiento::query()
            ->whereIn('maquina_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('horometro')
            ->whereNotNull('servicio_preventivo_tipo_id')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (Mantenimiento $mantenimiento) => $mantenimiento->maquina_id . ':' . $mantenimiento->servicio_preventivo_tipo_id)
            ->groupBy('maquina_id')
            ->map(fn (Collection $mantenimientos) => $mantenimientos->keyBy('servicio_preventivo_tipo_id'));

        $ultimosServiciosLegacy = Mantenimiento::query()
            ->whereIn('maquina_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('horometro')
            ->whereNull('servicio_preventivo_tipo_id')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get()
            ->unique('maquina_id')
            ->keyBy('maquina_id');

        $serviciosPreventivos = EmpresaServicioPreventivoTipo::query()
            ->maquinaria()
            ->activos()
            ->ordenados()
            ->get();

        return $maquinas
            ->mapWithKeys(function (Maquina $maquina) use ($config, $ultimosServiciosPorTipo, $ultimosServiciosLegacy, $serviciosPreventivos) {
                $ultimosServiciosMaquina = $ultimosServiciosPorTipo->get($maquina->id, collect());
                $ultimoServicioLegacy = $ultimosServiciosLegacy->get($maquina->id);
                $horometroActual = $this->horometroService->horometroActual($maquina);

                return [
                    $maquina->id => $this->calcular(
                        $maquina,
                        $config,
                        $horometroActual,
                        $ultimosServiciosMaquina,
                        $ultimoServicioLegacy,
                        $serviciosPreventivos
                    ),
                ];
            })
            ->all();
    }

    private function calcular(
        Maquina $maquina,
        ?EmpresaConfig $config,
        ?float $horometroActual,
        Collection $ultimosServiciosMaquina,
        ?Mantenimiento $ultimoServicioLegacy,
        Collection $serviciosPreventivos
    ): array {
        if ($serviciosPreventivos->isEmpty()) {
            $serviciosPreventivos = collect([
                new EmpresaServicioPreventivoTipo([
                    'nombre' => 'Servicio preventivo',
                    'codigo' => 'legacy',
                    'intervalo_valor' => (int) ($config?->maquinaria_servicio_horas ?? 250),
                    'intervalo_meses' => (int) ($config?->maquinaria_servicio_meses ?? 6),
                    'alerta_valor' => (int) ($config?->maquinaria_alerta_horas ?? 20),
                ]),
            ]);
        }

        $servicios = $serviciosPreventivos
            ->map(function (EmpresaServicioPreventivoTipo $servicio) use ($maquina, $horometroActual, $ultimosServiciosMaquina, $ultimoServicioLegacy) {
                $ultimoServicio = $servicio->exists
                    ? $ultimosServiciosMaquina->get($servicio->id)
                    : $ultimoServicioLegacy;
                $horometroBaseServicio = $this->primerNumero([
                    $ultimoServicio?->horometro,
                    $maquina->horometro_base,
                    $horometroActual !== null ? 0 : null,
                ]);
                $fechaUltimoServicio = $this->fechaUltimoServicio($ultimoServicio);

                return $this->calcularServicio(
                    $servicio,
                    $horometroActual,
                    $horometroBaseServicio,
                    $fechaUltimoServicio
                );
            })
            ->values();

        $principal = $servicios
            ->sort(function (array $a, array $b) {
                $estado = $this->prioridadEstado($b['estado']) <=> $this->prioridadEstado($a['estado']);

                if ($estado !== 0) {
                    return $estado;
                }

                $horas = ($a['horas_restantes'] ?? PHP_FLOAT_MAX) <=> ($b['horas_restantes'] ?? PHP_FLOAT_MAX);

                return $horas !== 0
                    ? $horas
                    : (($b['intervalo_horas'] ?? 0) <=> ($a['intervalo_horas'] ?? 0));
            })
            ->first();

        return array_merge($principal, [
            'horometro_actual' => $horometroActual,
            'servicios' => $servicios->all(),
        ]);
    }

    private function calcularServicio(
        EmpresaServicioPreventivoTipo $servicio,
        ?float $horometroActual,
        ?float $horometroBaseServicio,
        ?Carbon $fechaUltimoServicio
    ): array {
        $intervaloHoras = (float) $servicio->intervalo_valor;
        $intervaloMeses = (int) ($servicio->intervalo_meses ?? 0);
        $alertaHoras = (float) ($servicio->alerta_valor ?? 0);

        if ($intervaloHoras <= 0 || $horometroActual === null || $horometroBaseServicio === null) {
            return [
                'servicio_id' => $servicio->exists ? $servicio->id : null,
                'servicio_nombre' => $servicio->nombre,
                'servicio_codigo' => $servicio->codigo,
                'servicio_tipo_actual' => null,
                'servicio_tipo_siguiente' => $servicio->nombre,
                'servicio_tipo_codigo' => $servicio->codigo,
                'servicio_tipo_nombre' => $servicio->nombre,
                'estado' => 'sin_datos',
                'label' => $intervaloHoras <= 0 ? 'Configurar intervalo' : 'Sin horometro',
                'color' => 'slate',
                'horas_usadas' => null,
                'horas_restantes' => null,
                'intervalo_horas' => $intervaloHoras,
                'porcentaje' => 0,
                'proximo_horometro' => null,
                'proximo_fecha' => null,
            ];
        }

        $horasUsadas = max(0, $horometroActual - $horometroBaseServicio);
        $ciclosCompletos = (int) floor($horasUsadas / $intervaloHoras);
        $horasEnCiclo = fmod($horasUsadas, $intervaloHoras);
        $proximoCiclo = $horasUsadas > 0 && abs($horasEnCiclo) < 0.00001
            ? max(1, $ciclosCompletos)
            : $ciclosCompletos + 1;
        $proximoHorometro = $horometroBaseServicio + ($proximoCiclo * $intervaloHoras);
        $horasRestantes = $proximoHorometro - $horometroActual;
        $porcentaje = min(100, max(0, ($horasEnCiclo / $intervaloHoras) * 100));

        if ($horasUsadas > 0 && abs($horasEnCiclo) < 0.00001) {
            $porcentaje = 100;
        }
        $proximoFecha = $fechaUltimoServicio && $intervaloMeses > 0
            ? $fechaUltimoServicio->copy()->addMonths($intervaloMeses)
            : null;

        $estadoHoras = match (true) {
            $horasRestantes <= 0 => 'vencido',
            $horasRestantes <= $alertaHoras => 'proximo',
            default => 'ok',
        };

        $estadoTiempo = $proximoFecha && now()->greaterThanOrEqualTo($proximoFecha)
            ? 'vencido'
            : 'ok';

        $estado = $estadoTiempo === 'vencido' ? 'vencido' : $estadoHoras;

        $color = match ($estado) {
            'vencido' => 'rose',
            'proximo' => 'amber',
            default => 'emerald',
        };

        $label = match ($estado) {
            'vencido' => abs($horasRestantes) < 0.00001
                ? 'Servicio requerido'
                : 'Vencido por ' . number_format(abs($horasRestantes), 1) . ' h',
            'proximo' => 'Proximo: restan ' . number_format($horasRestantes, 1) . ' h',
            default => 'Restan ' . number_format($horasRestantes, 1) . ' h',
        };

        if ($estadoTiempo === 'vencido' && $estadoHoras !== 'vencido') {
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
            'horas_usadas' => $horasUsadas,
            'horas_restantes' => $horasRestantes,
            'intervalo_horas' => $intervaloHoras,
            'porcentaje' => $porcentaje,
            'proximo_horometro' => $proximoHorometro,
            'proximo_fecha' => $proximoFecha,
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

    private function primerNumero(array $valores): ?float
    {
        foreach ($valores as $valor) {
            if ($valor !== null && $valor !== '') {
                return (float) $valor;
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

