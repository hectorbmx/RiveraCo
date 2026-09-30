<?php

namespace App\Services\Maquinas;

use App\Models\EmpresaConfig;
use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PreventivoMaquinaService
{
    public function calcularParaColeccion(Collection $maquinas, ?EmpresaConfig $config = null): array
    {
        $config ??= EmpresaConfig::first();
        $ids = $maquinas->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $ultimosRegistros = ObraMaquinaRegistro::query()
            ->whereIn('maquina_id', $ids)
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->get()
            ->unique('maquina_id')
            ->keyBy('maquina_id');

        $ultimasAsignaciones = ObraMaquina::query()
            ->whereIn('maquina_id', $ids)
            ->orderByDesc('fecha_fin')
            ->orderByDesc('id')
            ->get()
            ->unique('maquina_id')
            ->keyBy('maquina_id');

        $ultimosServicios = Mantenimiento::query()
            ->whereIn('maquina_id', $ids)
            ->where('tipo', 'programado')
            ->where('estatus', 'completado')
            ->whereNotNull('horometro')
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
            ->mapWithKeys(function (Maquina $maquina) use ($config, $ultimosRegistros, $ultimasAsignaciones, $ultimosServicios, $serviciosPreventivos) {
                $ultimoRegistro = $ultimosRegistros->get($maquina->id);
                $ultimaAsignacion = $ultimasAsignaciones->get($maquina->id);
                $ultimoServicio = $ultimosServicios->get($maquina->id);

                return [
                    $maquina->id => $this->calcular(
                        $maquina,
                        $config,
                        $ultimoRegistro,
                        $ultimaAsignacion,
                        $ultimoServicio,
                        $serviciosPreventivos
                    ),
                ];
            })
            ->all();
    }

    private function calcular(
        Maquina $maquina,
        ?EmpresaConfig $config,
        ?ObraMaquinaRegistro $ultimoRegistro,
        ?ObraMaquina $ultimaAsignacion,
        ?Mantenimiento $ultimoServicio,
        Collection $serviciosPreventivos
    ): array {
        $horometroActual = $this->mayorNumero([
            $ultimoRegistro?->horometro_fin,
            $ultimaAsignacion?->horometro_fin,
            $ultimaAsignacion?->horometro_inicio,
            $maquina->horometro_base,
        ]);

        $horometroBaseServicio = $this->primerNumero([
            $ultimoServicio?->horometro,
            $maquina->horometro_base,
        ]);

        $fechaUltimoServicio = $this->fechaUltimoServicio($ultimoServicio);

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
            ->map(fn (EmpresaServicioPreventivoTipo $servicio) => $this->calcularServicio(
                $servicio,
                $horometroActual,
                $horometroBaseServicio,
                $fechaUltimoServicio
            ))
            ->values();

        $principal = $servicios
            ->sort(function (array $a, array $b) {
                $estado = $this->prioridadEstado($b['estado']) <=> $this->prioridadEstado($a['estado']);

                return $estado !== 0
                    ? $estado
                    : (($a['horas_restantes'] ?? PHP_FLOAT_MAX) <=> ($b['horas_restantes'] ?? PHP_FLOAT_MAX));
            })
            ->first();

        return array_merge($principal, [
            'horometro_actual' => $horometroActual,
            'horometro_ultimo_servicio' => $horometroBaseServicio,
            'ultimo_servicio_fecha' => $fechaUltimoServicio,
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
        $proximoHorometro = $horometroBaseServicio + $intervaloHoras;
        $horasRestantes = $proximoHorometro - $horometroActual;
        $porcentaje = min(100, max(0, ($horasUsadas / $intervaloHoras) * 100));
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
            'vencido' => 'Vencido por ' . number_format(abs($horasRestantes), 1) . ' h',
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

    private function mayorNumero(array $valores): ?float
    {
        $numeros = [];

        foreach ($valores as $valor) {
            if ($valor !== null && $valor !== '') {
                $numeros[] = (float) $valor;
            }
        }

        return $numeros === [] ? null : max($numeros);
    }
    private function fechaUltimoServicio(?Mantenimiento $ultimoServicio): ?Carbon
    {
        $fecha = $ultimoServicio?->fecha_fin
            ?? $ultimoServicio?->fecha_programada
            ?? $ultimoServicio?->created_at;

        return $fecha ? Carbon::parse($fecha) : null;
    }
}




