<?php

namespace App\Services\Requisiciones;

use App\Models\ObraRequisicion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutorizarRequisicionService
{
    public function autorizar(int $obraId, int $requisicionId, User $usuario, array $cantidades): void
    {
        abort_unless($usuario->can('requisiciones.autorizar.access'), 403);
        DB::transaction(function () use ($obraId, $requisicionId, $usuario, $cantidades) {
            $requisicion = ObraRequisicion::query()->whereKey($requisicionId)->where('obra_id', $obraId)->lockForUpdate()->firstOrFail();
            if (! $requisicion->solicitado_por || (int) $requisicion->solicitado_por === (int) $usuario->id) {
                throw ValidationException::withMessages(['autorizacion' => 'La persona que solicita no puede autorizar su propia requisición; debe identificarse al solicitante.']);
            }
            if (! in_array($requisicion->estado, ['pendiente_revision', 'autorizada', 'en_surtido', 'surtida_parcial', 'surtida'], true)) {
                throw ValidationException::withMessages(['autorizacion' => 'Esta requisición no admite autorizaciones.']);
            }
            if ($requisicion->autorizado_por && (int) $requisicion->autorizado_por === (int) $requisicion->solicitado_por) {
                throw ValidationException::withMessages(['autorizacion' => 'La autorización previa pertenece al solicitante y requiere revisión.']);
            }
            $detalles = $requisicion->detalles()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $cambios = [];
            foreach ($cantidades as $id => $cantidad) {
                if (! ctype_digit((string) $id) || ! $detalles->has((int) $id) || ! is_numeric($cantidad) || ! preg_match('/^\d+(?:\.\d{1,3})?$/', (string) $cantidad)) {
                    throw ValidationException::withMessages(['cantidades' => 'Las partidas y cantidades deben pertenecer a esta requisición y tener hasta tres decimales.']);
                }
                $detalle = $detalles->get((int) $id);
                $actual = (int) round((float) $detalle->cantidad_autorizada * 1000);
                $nuevo = (int) round((float) $cantidad * 1000);
                $solicitado = (int) round((float) $detalle->cantidad_solicitada * 1000);
                $surtido = (int) round((float) $detalle->cantidad_surtida * 1000);
                if ($nuevo < $actual || $nuevo < $surtido || $nuevo > $solicitado || (in_array($detalle->estado, ['rechazada', 'cancelada'], true) && $nuevo !== $actual)) {
                    throw ValidationException::withMessages(['cantidades' => 'La cantidad autorizada no puede disminuir, superar lo solicitado ni modificar una partida cancelada o rechazada.']);
                }
                if ($nuevo > $actual) {
                    $cambios[] = [$detalle, $actual, $nuevo, $solicitado, $surtido];
                }
            }
            if (! $cambios) {
                throw ValidationException::withMessages(['cantidades' => 'Indica una cantidad mayor a la ya autorizada en al menos una partida.']);
            }
            $ahora = now();
            $autorizacionId = DB::table('obra_requisicion_autorizaciones')->insertGetId(['obra_requisicion_id' => $requisicion->id, 'usuario_id' => $usuario->id, 'created_at' => $ahora, 'updated_at' => $ahora]);
            foreach ($cambios as [$detalle, $actual, $nuevo, $solicitado, $surtido]) {
                DB::table('obra_requisicion_autorizacion_detalles')->insert(['autorizacion_id' => $autorizacionId, 'requisicion_detalle_id' => $detalle->id, 'cantidad_anterior' => $actual / 1000, 'cantidad_nueva' => $nuevo / 1000, 'created_at' => $ahora, 'updated_at' => $ahora]);
                $estado = $surtido > 0 ? ($surtido >= $nuevo ? 'surtida' : 'surtida_parcial') : ($nuevo < $solicitado ? 'autorizada_parcial' : 'autorizada');
                $detalle->update(['cantidad_autorizada' => $nuevo / 1000, 'estado' => $estado]);
            }
            $requisicion->update([
                'estado' => $requisicion->estado === 'pendiente_revision' ? 'autorizada' : ($requisicion->estado === 'surtida' ? 'surtida_parcial' : $requisicion->estado),
                'autorizado_por' => $requisicion->autorizado_por ?: $usuario->id,
                'autorizado_at' => $requisicion->autorizado_at ?: $ahora,
            ]);
        }, 3);
    }
}
