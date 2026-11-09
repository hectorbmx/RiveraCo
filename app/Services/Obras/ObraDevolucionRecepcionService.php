<?php

namespace App\Services\Obras;

use App\Models\Almacen;
use App\Models\Obra;
use App\Models\ObraDevolucion;
use App\Models\ObraDevolucionRecepcion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ObraDevolucionRecepcionService
{
    public function confirmar(ObraDevolucion $solicitud, User $usuario, array $datos): ObraDevolucionRecepcion
    {
        if (! $usuario->can('obra.devoluciones.view.access') || ! $usuario->can('obra.devoluciones.receive.access')) throw new AuthorizationException();
        $datos = Validator::make($datos, [
            'operacion_uuid' => ['required', 'uuid'], 'almacen_id' => ['required', 'integer', 'min:1'],
            'fecha_recepcion' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:2000'], 'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'partidas.*.cantidad' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'partidas.*.condicion' => ['required', 'in:bueno,danado,desgastado'],
            'partidas.*.observaciones' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $lineas = collect($datos['partidas'])->map(fn ($p) => ['id' => (int) $p['id'], 'cantidad' => number_format((float) $p['cantidad'], 3, '.', ''), 'condicion' => $p['condicion'], 'observaciones' => trim($p['observaciones'] ?? '')])->sortBy('id')->values();
        return DB::transaction(function () use ($solicitud, $usuario, $datos, $lineas) {
            Obra::whereKey($solicitud->obra_id)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            $existente = ObraDevolucionRecepcion::where('operacion_uuid', $datos['operacion_uuid'])->lockForUpdate()->first();
            if ($existente) {
                $previas = $existente->detalles()->orderBy('devolucion_detalle_id')->get()->map(fn ($p) => ['id' => (int) $p->devolucion_detalle_id, 'cantidad' => number_format((float) $p->cantidad_recibida, 3, '.', ''), 'condicion' => $p->condicion_recibida, 'observaciones' => trim($p->observaciones ?? '')])->values();
                if ((int) $existente->devolucion_id !== (int) $solicitud->id || (int) $existente->confirmado_por !== (int) $usuario->id || (int) $existente->almacen_id !== (int) $datos['almacen_id'] || $existente->fecha_recepcion->format('Y-m-d') !== $datos['fecha_recepcion'] || trim($existente->observaciones ?? '') !== trim($datos['observaciones'] ?? '') || $previas->all() !== $lineas->all()) {
                    throw ValidationException::withMessages(['operacion_uuid' => 'Esta operación ya se utilizó con datos diferentes.']);
                }
                return $existente;
            }
            if (! in_array($solicitud->estado, ['solicitada', 'recepcion_parcial'], true)) throw ValidationException::withMessages(['partidas' => 'La solicitud no está pendiente de recepción.']);
            if (! Almacen::whereKey($datos['almacen_id'])->where('activo', true)->lockForUpdate()->exists()) throw ValidationException::withMessages(['almacen_id' => 'Selecciona un almacén activo.']);
            $solicitud->detalles()->orderBy('id')->lockForUpdate()->get();
            $consulta = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $solicitud->id)->firstOrFail();
            $partidas = $consulta->detalles->keyBy('id');
            foreach ($lineas as $linea) {
                $partida = $partidas->get($linea['id']);
                if (! $partida || $partida->consulta_pendiente === null || (int) round((float) $linea['cantidad'] * 1000) > (int) round($partida->consulta_pendiente * 1000)) throw ValidationException::withMessages(['partidas' => 'Una partida no pertenece a la solicitud, requiere revisión o excede la cantidad pendiente.']);
            }
            $recepcion = ObraDevolucionRecepcion::create(['devolucion_id' => $solicitud->id, 'operacion_uuid' => $datos['operacion_uuid'], 'almacen_id' => $datos['almacen_id'], 'fecha_recepcion' => $datos['fecha_recepcion'], 'estado' => 'pendiente_aplicacion', 'confirmado_por' => $usuario->id, 'confirmado_at' => now(), 'observaciones' => trim($datos['observaciones'] ?? '') ?: null]);
            $recepcion->update(['folio' => 'REC-OB-'.str_pad((string) $recepcion->id, 8, '0', STR_PAD_LEFT)]);
            foreach ($lineas as $linea) {
                $partida = $partidas->get($linea['id']);
                $recepcion->detalles()->create(['devolucion_detalle_id' => $partida->id, 'cantidad_recibida' => $linea['cantidad'], 'condicion_recibida' => $linea['condicion'], 'observaciones' => $linea['observaciones'] ?: null, 'costo_unitario_mxn' => $partida->costo_unitario_mxn]);
            }
            $solicitud->update(['estado' => 'recepcion_parcial']);
            return $recepcion;
        }, 3);
    }
}
