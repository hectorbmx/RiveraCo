<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaMovimiento;
use App\Events\MaquinaEstadoCambiado;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MaquinaService
{
    public function asignarAObra(Maquina $maquina, Obra $obra, array $data = [], ?User $user = null): ObraMaquina
    {
        return DB::transaction(function () use ($maquina, $obra, $data, $user) {
            // Orden común con la captura de horas: máquina antes de asignaciones.
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquina->id);
            $obra = Obra::query()->lockForUpdate()->findOrFail($obra->id);

            // 1) Validaciones de negocio
            if ($maquina->estado !== Maquina::ESTADO_OPERATIVA) {
                throw new \RuntimeException('La máquina no está operativa.');
            }

            // No permitir doble asignación activa
            $activa = ObraMaquina::query()
                ->where('maquina_id', $maquina->id)
                ->where('estado', 'activa')
                ->whereNull('fecha_fin')
                ->lockForUpdate()
                ->first();

            if ($activa) {
                throw new \RuntimeException('La máquina ya tiene una asignación activa.');
            }

            if ($obra->estatus_nuevo === null || in_array((int) $obra->estatus_nuevo, [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA], true)) {
                throw ValidationException::withMessages(['obra_id' => 'Selecciona una obra vigente.']);
            }

            $tienePilasActivas = $obra->pilas()
                ->where('activo', true)
                ->exists();

            if (! $tienePilasActivas) {
                throw new \RuntimeException('La obra seleccionada no tiene pilas activas asignadas.');
            }

            $horometroService = app(MaquinaHorometroService::class);
            $actual = $horometroService->horometroActual($maquina);
            $data['fecha_inicio'] = $data['fecha_inicio'] ?? now()->toDateString();
            $data['horometro_inicio'] = $data['horometro_inicio'] ?? $actual ?? 0;
            Validator::make($data, [
                'fecha_inicio' => ['required', 'date_format:Y-m-d'],
                'horometro_inicio' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
                'notas' => ['nullable', 'string', 'max:1000'],
            ])->validate();
            if ((float) $data['horometro_inicio'] < ($actual ?? 0)) {
                throw ValidationException::withMessages(['horometro_inicio' => 'La lectura inicial no puede ser menor al horómetro actual.']);
            }
            $ultimaLectura = $horometroService->fechaUltimaLectura($maquina)?->toDateString();
            $ultimoFin = ObraMaquina::query()->where('maquina_id', $maquina->id)->max('fecha_fin');
            foreach ([$ultimaLectura, $ultimoFin] as $referencia) {
                if ($referencia && $data['fecha_inicio'] < Carbon::parse($referencia)->toDateString()) {
                    throw ValidationException::withMessages(['fecha_inicio' => 'La fecha de asignación no puede anteceder la última lectura o el retiro anterior.']);
                }
            }

            // 2) Crear asignación
            $asignacion = ObraMaquina::create([
                'obra_id'          => $obra->id,
                'maquina_id'       => $maquina->id,
                'fecha_inicio'     => $data['fecha_inicio'] ?? now()->toDateString(),
                'horometro_inicio' => $data['horometro_inicio'] ?? null,
                'estado'           => 'activa',
                'notas'            => $data['notas'] ?? null,
                'created_by'       => $user?->id ?? Auth::id(),
                'updated_by'       => $user?->id ?? Auth::id(),
            ]);

            // 3) Cambiar ubicación a en_obra + log
            $this->cambiarUbicacion(
                maquina: $maquina,
                nuevaUbicacion: Maquina::UBIC_EN_OBRA,
                tipo: 'asignacion',
                obraId: $obra->id,
                obraMaquinaId: $asignacion->id,
                motivo: 'Asignación a obra',
                user: $user
            );

            return $asignacion;
        });
    }

    public function finalizarAsignacion(ObraMaquina $asignacion, array $data = [], ?User $user = null): ObraMaquina
    {
        return DB::transaction(function () use ($asignacion, $data, $user) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($asignacion->maquina_id);
            $asignacion = ObraMaquina::query()->where('maquina_id', $maquina->id)->lockForUpdate()->findOrFail($asignacion->id);

            if ($asignacion->estado !== 'activa' || $asignacion->fecha_fin) {
                throw new \RuntimeException('La asignación no está activa.');
            }

            $data['fecha_fin'] = $data['fecha_fin'] ?? now()->toDateString();
            Validator::make($data, [
                'fecha_fin' => ['required', 'date_format:Y-m-d'],
                'horometro_fin' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            ])->validate();
            $horometroService = app(MaquinaHorometroService::class);
            $ultimoRegistro = $asignacion->registrosHoras()->whereNotNull('horometro_fin')->max('fin');
            foreach ([$asignacion->fecha_inicio, $ultimoRegistro, $horometroService->fechaUltimaLectura($maquina)] as $referencia) {
                if ($referencia && $data['fecha_fin'] < Carbon::parse($referencia)->toDateString()) {
                    throw ValidationException::withMessages(['fecha_fin' => 'El retiro no puede anteceder la asignación ni las lecturas registradas.']);
                }
            }
            if (isset($data['horometro_fin']) && (float) $data['horometro_fin'] < ($horometroService->horometroActual($maquina) ?? 0)) {
                throw ValidationException::withMessages(['horometro_fin' => 'La lectura final no puede ser menor al horómetro actual.']);
            }

            $asignacion->update([
                'fecha_fin'     => $data['fecha_fin'] ?? now()->toDateString(),
                'horometro_fin' => $data['horometro_fin'] ?? $asignacion->horometro_fin,
                'estado'        => 'finalizada',
                'updated_by'    => $user?->id ?? Auth::id(),
            ]);

            // Al finalizar, por default la mandamos a patio (puedes cambiarlo después)
            $this->cambiarUbicacion(
                maquina: $maquina,
                nuevaUbicacion: Maquina::UBIC_EN_PATIO,
                tipo: 'desasignacion',
                obraId: $asignacion->obra_id,
                obraMaquinaId: $asignacion->id,
                motivo: 'Fin de asignación',
                user: $user
            );

            return $asignacion;
        });
    }

    public function cambiarUbicacion(
        Maquina $maquina,
        string $nuevaUbicacion,
        string $tipo = 'cambio_ubicacion',
        ?int $obraId = null,
        ?int $obraMaquinaId = null,
        ?string $motivo = null,
        ?string $notas = null,
        ?User $user = null
    ): void {
        DB::transaction(function () use ($maquina, $nuevaUbicacion, $tipo, $obraId, $obraMaquinaId, $motivo, $notas, $user) {

            $anterior = $maquina->ubicacion;

            if ($anterior === $nuevaUbicacion && ! in_array($tipo, ['asignacion', 'desasignacion'], true)) {
                return; // no hacemos ruido en log si no cambia
            }

            $maquina->update([
                'ubicacion' => $nuevaUbicacion,
            ]);

            MaquinaMovimiento::create([
                'maquina_id'         => $maquina->id,
                'obra_id'            => $obraId,
                'obra_maquina_id'    => $obraMaquinaId,
                'tipo'               => $tipo,
                'ubicacion_anterior' => $anterior,
                'ubicacion_nueva'    => $nuevaUbicacion,
                'motivo'             => $motivo,
                'notas'              => $notas,
                'user_id'            => $user?->id ?? Auth::id(),
                'fecha_evento'       => now(),
            ]);
        });
    }

    // public function cambiarEstado(
    //     Maquina $maquina,
    //     string $nuevoEstado,
    //     ?int $obraId = null,
    //     ?int $obraMaquinaId = null,
    //     ?string $motivo = null,
    //     ?string $notas = null
    // ): void {
    //     DB::transaction(function () use ($maquina, $nuevoEstado, $obraId, $obraMaquinaId, $motivo, $notas) {

    //         $anterior = $maquina->estado;

    //         if ($anterior === $nuevoEstado) {
    //             return;
    //         }
    //         $permitidos = [
    //             Maquina::ESTADO_OPERATIVA       => [Maquina::ESTADO_FUERA_SERVICIO, Maquina::ESTADO_BAJA_DEFINITIVA],
    //             Maquina::ESTADO_FUERA_SERVICIO  => [Maquina::ESTADO_EN_REPARACION, Maquina::ESTADO_OPERATIVA, Maquina::ESTADO_BAJA_DEFINITIVA],
    //             Maquina::ESTADO_EN_REPARACION   => [Maquina::ESTADO_OPERATIVA, Maquina::ESTADO_BAJA_DEFINITIVA],
    //             Maquina::ESTADO_BAJA_DEFINITIVA => [],
    //         ];

    //         if (!in_array($nuevoEstado, $permitidos[$anterior] ?? [], true)) {
    //             throw new \RuntimeException("Transición de estado no permitida: {$anterior} → {$nuevoEstado}");
    //         }
    //         $maquina->update([
    //             'estado' => $nuevoEstado,
    //         ]);

    //         MaquinaMovimiento::create([
    //             'maquina_id'      => $maquina->id,
    //             'obra_id'         => $obraId,
    //             'obra_maquina_id' => $obraMaquinaId,
    //             'tipo'            => 'cambio_estado',
    //             'estado_anterior' => $anterior,
    //             'estado_nuevo'    => $nuevoEstado,
    //             'motivo'          => $motivo,
    //             'notas'           => $notas,
    //             'user_id'         => Auth::id(),
    //             'fecha_evento'    => now(),
    //         ]);
    //     });
    // }
    public function cambiarEstado(
        Maquina $maquina,
        string $nuevoEstado,
        ?int $obraId = null,
        ?int $obraMaquinaId = null,
        ?string $motivo = null,
        ?string $notas = null
    ): void {
        // 1. Guardamos el estado actual antes de entrar a la transacción
        $estadoAnterior = $maquina->estado;

        DB::transaction(function () use ($maquina, $nuevoEstado, $obraId, $obraMaquinaId, $motivo, $notas) {
            $anterior = $maquina->estado;

            if ($anterior === $nuevoEstado) {
                return;
            }

            $permitidos = [
                Maquina::ESTADO_OPERATIVA       => [Maquina::ESTADO_FUERA_SERVICIO, Maquina::ESTADO_BAJA_DEFINITIVA],
                Maquina::ESTADO_FUERA_SERVICIO  => [Maquina::ESTADO_EN_REPARACION, Maquina::ESTADO_OPERATIVA, Maquina::ESTADO_BAJA_DEFINITIVA],
                Maquina::ESTADO_EN_REPARACION   => [Maquina::ESTADO_OPERATIVA, Maquina::ESTADO_BAJA_DEFINITIVA],
                Maquina::ESTADO_BAJA_DEFINITIVA => [],
            ];

            if (!in_array($nuevoEstado, $permitidos[$anterior] ?? [], true)) {
                throw new \RuntimeException("Transición de estado no permitida: {$anterior} → {$nuevoEstado}");
            }

            $maquina->update([
                'estado' => $nuevoEstado,
            ]);

            MaquinaMovimiento::create([
                'maquina_id'      => $maquina->id,
                'obra_id'         => $obraId,
                'obra_maquina_id' => $obraMaquinaId,
                'tipo'            => 'cambio_estado',
                'estado_anterior' => $anterior,
                'estado_nuevo'    => $nuevoEstado,
                'motivo'          => $motivo,
                'notas'           => $notas,
                'user_id'         => Auth::id(),
                'fecha_evento'    => now(),
            ]);
        });

        // 2. Disparamos el evento después de que la transacción fue exitosa
        if ($estadoAnterior !== $nuevoEstado) {
            event(new \App\Events\MaquinaEstadoCambiado(
                $maquina, 
                $estadoAnterior, 
                $nuevoEstado, 
                $motivo,
                $notas
            ));
        }
    }
}
