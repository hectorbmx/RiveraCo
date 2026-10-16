<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaHorometroAjuste;
use App\Models\Mantenimiento;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Models\User;
use Carbon\Carbon;
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

        $maxAjuste = MaquinaHorometroAjuste::query()
            ->where('maquina_id', $maquina->id)
            ->max('horometro_nuevo');

        return $this->mayorNumero([
            $maquina->horometro_base,
            $maxAsignacionInicio,
            $maxAsignacionFin,
            $maxRegistro,
            $maxMantenimiento,
            $maxAjuste,
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

    public function crearAjuste(Maquina $maquina, array $data, ?User $user = null, string $origen = 'web_maquinas'): MaquinaHorometroAjuste
    {
        return DB::transaction(function () use ($maquina, $data, $user, $origen) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquina->id);
            $horometroAnterior = $this->horometroActual($maquina) ?? 0.0;
            $horometroNuevo = (float) $data['horometro_nuevo'];
            $fechaRegistro = $data['fecha_registro'] ?? now();

            $this->validarSecuenciaGlobal($maquina, $horometroNuevo, $fechaRegistro, $horometroAnterior);

            return MaquinaHorometroAjuste::create([
                'maquina_id' => $maquina->id,
                'obra_id' => $data['obra_id'] ?? null,
                'obra_maquina_id' => $data['obra_maquina_id'] ?? null,
                'fecha_registro' => $data['fecha_registro'] ?? null,
                'horometro_anterior' => $horometroAnterior,
                'horometro_nuevo' => $horometroNuevo,
                'diferencia' => round($horometroNuevo - $horometroAnterior, 2),
                'tipo' => $data['tipo'] ?? 'ajuste',
                'notas' => $data['notas'] ?? null,
                'origen' => $origen,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);
        });
    }

    public function crearRegistro(ObraMaquina $asignacion, array $data, ?User $user = null, string $origen = 'web'): ObraMaquinaRegistro
    {
        if ($asignacion->estado !== 'activa') {
            throw new RuntimeException('No puedes registrar horas en una asignación finalizada.');
        }

        return DB::transaction(function () use ($asignacion, $data, $user, $origen) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($asignacion->maquina_id);
            $horometroInicio = $this->horometroInicioParaRegistro($asignacion);
            $horometroFin = (float) $data['horometro_fin'];
            $fechaOperativa = $data['fin'] ?? $data['inicio'] ?? now();

            $this->validarSecuenciaGlobal($maquina, $horometroFin, $fechaOperativa, $horometroInicio);

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

    /**
     * Valida la secuencia global antes de crear cualquier lectura nueva.
     * La fecha de ajustes antiguos se interpreta con created_at como respaldo.
     */
    private function validarSecuenciaGlobal(Maquina $maquina, float $horometroNuevo, mixed $fechaNueva, float $horometroAnterior): void
    {
        if ($horometroNuevo <= $horometroAnterior) {
            throw new RuntimeException("El horómetro nuevo debe ser mayor al último registrado ({$horometroAnterior}).");
        }

        $ultimaFecha = $this->fechaUltimaLectura($maquina);

        if (! $ultimaFecha || blank($fechaNueva)) {
            return;
        }

        $fechaNuevaCarbon = Carbon::parse($fechaNueva);
        $fechaEsSoloDia = is_string($fechaNueva) && strlen($fechaNueva) <= 10;

        $esAnterior = $fechaEsSoloDia
            ? $fechaNuevaCarbon->toDateString() < $ultimaFecha->toDateString()
            : $fechaNuevaCarbon->lt($ultimaFecha);

        if ($esAnterior) {
            throw new RuntimeException(
                "La fecha del registro ({$fechaNuevaCarbon->format('d/m/Y')}) no puede ser anterior a la última lectura válida ({$ultimaFecha->format('d/m/Y')})."
            );
        }
    }

    public function fechaUltimaLectura(Maquina $maquina): ?Carbon
    {
        $fechas = [];

        $ultimaLectura = ObraMaquinaRegistro::query()
            ->where('maquina_id', $maquina->id)
            ->whereNotNull('horometro_fin')
            ->latest('fin')
            ->latest('id')
            ->value('fin');

        $ultimoAjuste = MaquinaHorometroAjuste::query()
            ->where('maquina_id', $maquina->id)
            ->whereNotNull('horometro_nuevo')
            ->orderByRaw('COALESCE(fecha_registro, created_at) DESC')
            ->orderByDesc('id')
            ->first(['fecha_registro', 'created_at']);

        $ultimoMantenimiento = Mantenimiento::query()
            ->where('maquina_id', $maquina->id)
            ->whereNotNull('horometro')
            ->orderByRaw('COALESCE(fecha_fin, fecha_inicio, created_at) DESC')
            ->orderByDesc('id')
            ->first(['fecha_fin', 'fecha_inicio', 'created_at']);

        if ($ultimaLectura) {
            $fechas[] = Carbon::parse($ultimaLectura);
        }

        if ($ultimoAjuste) {
            $fechas[] = Carbon::parse($ultimoAjuste->fecha_registro ?? $ultimoAjuste->created_at);
        }

        if ($ultimoMantenimiento) {
            $fechas[] = Carbon::parse(
                $ultimoMantenimiento->fecha_fin
                    ?? $ultimoMantenimiento->fecha_inicio
                    ?? $ultimoMantenimiento->created_at
            );
        }

        if ($fechas === []) {
            return null;
        }

        return collect($fechas)->sortByDesc(fn (Carbon $fecha) => $fecha->getTimestamp())->first();
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
