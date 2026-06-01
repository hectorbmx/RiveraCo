<?php

namespace App\Services\Maquinas;

use App\Models\EmpresaConfig;
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

        return $maquinas
            ->mapWithKeys(function (Maquina $maquina) use ($config, $ultimosRegistros, $ultimasAsignaciones, $ultimosServicios) {
                $ultimoRegistro = $ultimosRegistros->get($maquina->id);
                $ultimaAsignacion = $ultimasAsignaciones->get($maquina->id);
                $ultimoServicio = $ultimosServicios->get($maquina->id);

                return [
                    $maquina->id => $this->calcular(
                        $maquina,
                        $config,
                        $ultimoRegistro,
                        $ultimaAsignacion,
                        $ultimoServicio
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
        ?Mantenimiento $ultimoServicio
    ): array {
        $intervaloHoras = (float) ($config?->maquinaria_servicio_horas ?? 250);
        $intervaloMeses = (int) ($config?->maquinaria_servicio_meses ?? 6);
        $alertaHoras = (float) ($config?->maquinaria_alerta_horas ?? 20);

        $horometroActual = $this->primerNumero([
            $ultimoRegistro?->horometro_fin,
            $ultimaAsignacion?->horometro_fin,
            $ultimaAsignacion?->horometro_inicio,
            $maquina->horometro_base,
        ]);

        $horometroBaseServicio = $this->primerNumero([
            $ultimoServicio?->horometro,
            $maquina->horometro_base,
        ]);

        if ($intervaloHoras <= 0 || $horometroActual === null || $horometroBaseServicio === null) {
            return [
                'estado' => 'sin_datos',
                'label' => $intervaloHoras <= 0 ? 'Configurar intervalo' : 'Sin horometro',
                'color' => 'slate',
                'horometro_actual' => $horometroActual,
                'horometro_ultimo_servicio' => $horometroBaseServicio,
                'horas_usadas' => null,
                'horas_restantes' => null,
                'intervalo_horas' => $intervaloHoras,
                'porcentaje' => 0,
                'proximo_horometro' => null,
                'ultimo_servicio_fecha' => null,
                'proximo_fecha' => null,
            ];
        }

        $horasUsadas = max(0, $horometroActual - $horometroBaseServicio);
        $proximoHorometro = $horometroBaseServicio + $intervaloHoras;
        $horasRestantes = $proximoHorometro - $horometroActual;
        $porcentaje = min(100, max(0, ($horasUsadas / $intervaloHoras) * 100));

        $fechaUltimoServicio = $this->fechaUltimoServicio($ultimoServicio);
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

        $estado = $estadoHoras;
        if ($estadoTiempo === 'vencido') {
            $estado = 'vencido';
        }

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
            'estado' => $estado,
            'label' => $label,
            'color' => $color,
            'horometro_actual' => $horometroActual,
            'horometro_ultimo_servicio' => $horometroBaseServicio,
            'horas_usadas' => $horasUsadas,
            'horas_restantes' => $horasRestantes,
            'intervalo_horas' => $intervaloHoras,
            'porcentaje' => $porcentaje,
            'proximo_horometro' => $proximoHorometro,
            'ultimo_servicio_fecha' => $fechaUltimoServicio,
            'proximo_fecha' => $proximoFecha,
        ];
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
