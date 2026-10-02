<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\Mantenimiento;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MaquinaHorometroService
{
    public function horometroActual(Maquina $maquina): ?float
    {
        $maxRegistro = ObraMaquinaRegistro::query()
            ->where('maquina_id', $maquina->id)
            ->max('horometro_fin');

        $maxAsignacionInicio = ObraMaquina::query()
            ->where('maquina_id', $maquina->id)
            ->max('horometro_inicio');

        $maxAsignacionFin = ObraMaquina::query()
            ->where('maquina_id', $maquina->id)
            ->max('horometro_fin');

        $maxMantenimiento = Mantenimiento::query()
            ->where('maquina_id', $maquina->id)
            ->whereNotNull('horometro')
            ->max('horometro');

        return $this->mayorNumero([
            $maquina->horometro_base,
            $maxAsignacionInicio,
            $maxAsignacionFin,
            $maxRegistro,
            $maxMantenimiento,
        ]);
    }

    public function horometroSugeridoParaAsignacion(Maquina $maquina): float
    {
        return $this->horometroActual($maquina) ?? 0.0;
    }

    public function horometroInicioParaRegistro(ObraMaquina $asignacion): float
    {
        $asignacion->loadMissing('maquina');

        $ultimoRegistro = $asignacion->registrosHoras()
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->first();

        return $this->mayorNumero([
            $ultimoRegistro?->horometro_fin,
            $asignacion->horometro_inicio,
            $asignacion->maquina?->horometro_base,
            $asignacion->maquina ? $this->horometroActual($asignacion->maquina) : null,
        ]) ?? 0.0;
    }

    public function crearRegistro(ObraMaquina $asignacion, array $data, ?User $user = null, string $origen = 'web'): ObraMaquinaRegistro
    {
        if ($asignacion->estado !== 'activa') {
            throw new RuntimeException('No puedes registrar horas en una asignación finalizada.');
        }

        return DB::transaction(function () use ($asignacion, $data, $user, $origen) {
            $horometroInicio = $this->horometroInicioParaRegistro($asignacion);
            $horometroFin = (float) $data['horometro_fin'];

            if ($horometroFin < $horometroInicio) {
                throw new RuntimeException("El horómetro final no puede ser menor al último registrado ({$horometroInicio}).");
            }

            $inicio = $data['inicio'] ?? now();
            $fin = $data['fin'] ?? now();

            return ObraMaquinaRegistro::create([
                'obra_maquina_id' => $asignacion->id,
                'obra_id' => $asignacion->obra_id,
                'maquina_id' => $asignacion->maquina_id,
                'inicio' => $inicio,
                'fin' => $fin,
                'horometro_inicio' => $horometroInicio,
                'horometro_fin' => $horometroFin,
                'horas' => round(max(0, $horometroFin - $horometroInicio), 2),
                'notas' => $data['notas'] ?? null,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'origen' => $origen,
            ]);
        });
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
}
