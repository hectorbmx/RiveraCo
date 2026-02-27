<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaMovimiento;
use App\Models\Obra;
use App\Models\ObraMaquina;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaquinaService
{
    public function asignarAObra(Maquina $maquina, Obra $obra, array $data = []): ObraMaquina
    {
        return DB::transaction(function () use ($maquina, $obra, $data) {

            // 1) Validaciones de negocio
            if ($maquina->estado !== Maquina::ESTADO_OPERATIVA) {
                throw new \RuntimeException('La máquina no está operativa.');
            }

            // No permitir doble asignación activa
            $activa = ObraMaquina::query()
                ->where('maquina_id', $maquina->id)
                ->where('estado', 'activa')
                ->whereNull('fecha_fin')
                ->first();

            if ($activa) {
                throw new \RuntimeException('La máquina ya tiene una asignación activa.');
            }

            // 2) Crear asignación
            $asignacion = ObraMaquina::create([
                'obra_id'          => $obra->id,
                'maquina_id'       => $maquina->id,
                'fecha_inicio'     => $data['fecha_inicio'] ?? now()->toDateString(),
                'horometro_inicio' => $data['horometro_inicio'] ?? null,
                'estado'           => 'activa',
                'notas'            => $data['notas'] ?? null,
                'created_by'       => Auth::id(),
                'updated_by'       => Auth::id(),
            ]);

            // 3) Cambiar ubicación a en_obra + log
            $this->cambiarUbicacion(
                maquina: $maquina,
                nuevaUbicacion: Maquina::UBIC_EN_OBRA,
                tipo: 'asignacion',
                obraId: $obra->id,
                obraMaquinaId: $asignacion->id,
                motivo: 'Asignación a obra'
            );

            return $asignacion;
        });
    }

    public function finalizarAsignacion(ObraMaquina $asignacion, array $data = []): ObraMaquina
    {
        return DB::transaction(function () use ($asignacion, $data) {

            if ($asignacion->estado !== 'activa' || $asignacion->fecha_fin) {
                throw new \RuntimeException('La asignación no está activa.');
            }

            $asignacion->update([
                'fecha_fin'     => $data['fecha_fin'] ?? now()->toDateString(),
                'horometro_fin' => $data['horometro_fin'] ?? null,
                'estado'        => 'finalizada',
                'updated_by'    => Auth::id(),
            ]);

            $maquina = $asignacion->maquina;

            // Al finalizar, por default la mandamos a patio (puedes cambiarlo después)
            $this->cambiarUbicacion(
                maquina: $maquina,
                nuevaUbicacion: Maquina::UBIC_EN_PATIO,
                tipo: 'desasignacion',
                obraId: $asignacion->obra_id,
                obraMaquinaId: $asignacion->id,
                motivo: 'Fin de asignación'
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
        ?string $notas = null
    ): void {
        DB::transaction(function () use ($maquina, $nuevaUbicacion, $tipo, $obraId, $obraMaquinaId, $motivo, $notas) {

            $anterior = $maquina->ubicacion;

            if ($anterior === $nuevaUbicacion) {
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
                'user_id'            => Auth::id(),
                'fecha_evento'       => now(),
            ]);
        });
    }

    public function cambiarEstado(
        Maquina $maquina,
        string $nuevoEstado,
        ?int $obraId = null,
        ?int $obraMaquinaId = null,
        ?string $motivo = null,
        ?string $notas = null
    ): void {
        DB::transaction(function () use ($maquina, $nuevoEstado, $obraId, $obraMaquinaId, $motivo, $notas) {

            $anterior = $maquina->estado;

            if ($anterior === $nuevoEstado) {
                return;
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
    }
}