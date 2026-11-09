<?php

namespace App\Services\Obras;

use App\Models\Obra;
use App\Models\ObraDevolucion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ObraDevolucionSolicitudService
{
    private const ORIGENES = ['recepcion_directa' => 'recepcion_directa_detalle_id', 'salida_general' => 'inventario_movimiento_id', 'salida_huentitan' => 'huentitan_salida_detalle_id'];

    public function crear(Obra $obra, User $usuario, array $datos): ObraDevolucion
    {
        if (! $usuario->can('obra.devoluciones.create.access')) throw new AuthorizationException();
        $datos = Validator::make($datos, [
            'operacion_uuid' => ['required', 'uuid'], 'accion' => ['required', 'in:borrador,solicitar'],
            'observaciones' => ['nullable', 'string', 'max:2000'], 'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.origen_tipo' => ['required', 'in:recepcion_directa,salida_general,salida_huentitan'],
            'partidas.*.origen_id' => ['required', 'integer', 'min:1'],
            'partidas.*.cantidad' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'partidas.*.condicion' => ['nullable', 'in:bueno,danado,desgastado'],
            'partidas.*.observaciones' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $lineas = collect($datos['partidas'])->map(fn ($p) => ['origen_tipo' => $p['origen_tipo'], 'origen_id' => (int) $p['origen_id'], 'cantidad' => number_format((float) $p['cantidad'], 3, '.', ''), 'condicion' => $p['condicion'] ?? null, 'observaciones' => trim($p['observaciones'] ?? '')])->sortBy(fn ($p) => $p['origen_tipo'].':'.str_pad((string) $p['origen_id'], 20, '0', STR_PAD_LEFT))->values();
        if ($lineas->map(fn ($p) => $p['origen_tipo'].':'.$p['origen_id'])->unique()->count() !== $lineas->count()) $this->error('Una misma entrega no puede repetirse en la solicitud.');
        $hash = hash('sha256', json_encode(['obra' => $obra->id, 'usuario' => $usuario->id, 'accion' => $datos['accion'], 'observaciones' => trim($datos['observaciones'] ?? ''), 'partidas' => $lineas->all()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $hashAnterior = $lineas->every(fn ($p) => $p['condicion'] === null && $p['observaciones'] === '')
            ? hash('sha256', json_encode(['obra' => $obra->id, 'usuario' => $usuario->id, 'accion' => $datos['accion'], 'observaciones' => trim($datos['observaciones'] ?? ''), 'partidas' => $lineas->map(fn ($p) => ['origen_tipo' => $p['origen_tipo'], 'origen_id' => $p['origen_id'], 'cantidad' => $p['cantidad']])->all()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) : null;
        try {
            return DB::transaction(function () use ($obra, $usuario, $datos, $lineas, $hash, $hashAnterior) {
                Obra::whereKey($obra->id)->lockForUpdate()->firstOrFail();
                $existente = ObraDevolucion::where('operacion_uuid', $datos['operacion_uuid'])->lockForUpdate()->first();
                if ($existente) return $this->recuperar($existente, $obra, $usuario, $hash, $hashAnterior);
                if ($lineas->contains(fn ($p) => ! in_array($p['condicion'], ['bueno', 'danado', 'desgastado'], true))) $this->error('Selecciona la condición de cada partida.');
                $this->bloquearOrigenes($lineas, (int) $obra->id);
                $entregas = app(MaterialDevolucionSaldoService::class)->porObra($obra->id)['entregas']->keyBy(fn ($e) => $e->origen_tipo.':'.$e->origen_id);
                foreach ($lineas as $linea) $this->validarLinea($entregas->get($linea['origen_tipo'].':'.$linea['origen_id']), (float) $linea['cantidad'], $datos['accion'] === 'solicitar');
                $solicitud = ObraDevolucion::create(['obra_id' => $obra->id, 'estado' => $datos['accion'] === 'solicitar' ? 'solicitada' : 'borrador',
                    'operacion_uuid' => $datos['operacion_uuid'], 'solicitud_hash' => $hash, 'creado_por' => $usuario->id,
                    'presentado_por' => $datos['accion'] === 'solicitar' ? $usuario->id : null,
                    'presentado_at' => $datos['accion'] === 'solicitar' ? now() : null, 'observaciones' => $datos['observaciones'] ?? null]);
                $solicitud->update(['folio' => 'DEV-OB-'.str_pad((string) $solicitud->id, 8, '0', STR_PAD_LEFT)]);
                foreach ($lineas as $linea) {
                    $entrega = $entregas->get($linea['origen_tipo'].':'.$linea['origen_id']);
                    $solicitud->detalles()->create([self::ORIGENES[$linea['origen_tipo']] => $linea['origen_id'], 'producto_id' => $entrega->producto_id,
                        'descripcion' => $entrega->descripcion, 'unidad' => $entrega->unidad, 'cantidad_solicitada' => $linea['cantidad'],
                        'cantidad_entregada_snapshot' => $entrega->cantidad, 'costo_unitario_mxn' => $entrega->costo_unitario_mxn,
                        'importe_origen_mxn' => $entrega->importe_mxn, 'condicion_declarada' => $linea['condicion'], 'observaciones' => $linea['observaciones'] ?: null]);
                }
                return $solicitud;
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            $existente = ObraDevolucion::where('operacion_uuid', $datos['operacion_uuid'])->first();
            if ($existente) return $this->recuperar($existente, $obra, $usuario, $hash, $hashAnterior);
            throw $e;
        }
    }

    public function presentar(Obra $obra, ObraDevolucion $solicitud, User $usuario): void
    {
        if (! $usuario->can('obra.devoluciones.create.access')) throw new AuthorizationException();
        DB::transaction(function () use ($obra, $solicitud, $usuario) {
            Obra::whereKey($obra->id)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitud->id)->where('obra_id', $obra->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado === 'solicitada') return;
            if ($solicitud->estado !== 'borrador') $this->error('Solo se puede presentar una solicitud en borrador.');
            $detalles = $solicitud->detalles()->orderBy('id')->get();
            if ($detalles->isEmpty()) $this->error('La solicitud no tiene partidas.');
            if ($detalles->contains(fn ($p) => ! in_array($p->condicion_declarada, ['bueno', 'danado', 'desgastado'], true))) $this->error('Define la condición de cada partida en el borrador antes de presentarlo.');
            $lineas = $detalles->map(function ($p) {
                foreach (self::ORIGENES as $tipo => $campo) if ($p->$campo) return ['origen_tipo' => $tipo, 'origen_id' => (int) $p->$campo, 'cantidad' => $p->cantidad_solicitada, 'detalle' => $p];
                $this->error('Una partida no tiene origen válido.');
            });
            if ($lineas->map(fn ($p) => $p['origen_tipo'].':'.$p['origen_id'])->unique()->count() !== $lineas->count()) $this->error('Una misma entrega no puede repetirse en la solicitud.');
            $this->bloquearOrigenes($lineas, (int) $obra->id);
            $entregas = app(MaterialDevolucionSaldoService::class)->porObra($obra->id)['entregas']->keyBy(fn ($e) => $e->origen_tipo.':'.$e->origen_id);
            foreach ($lineas as $linea) {
                $entrega = $entregas->get($linea['origen_tipo'].':'.$linea['origen_id']);
                $this->validarLinea($entrega, (float) $linea['cantidad'], true);
                $partida = $linea['detalle'];
                if ((int) $partida->producto_id !== (int) $entrega->producto_id
                    || \App\Support\Inventario\UnidadMedidaCatalogo::equivalente($partida->unidad) !== \App\Support\Inventario\UnidadMedidaCatalogo::equivalente($entrega->unidad)
                    || abs((float) $partida->cantidad_entregada_snapshot - (float) $entrega->cantidad) > 0.000001
                    || abs((float) $partida->costo_unitario_mxn - (float) $entrega->costo_unitario_mxn) > 0.00000001
                    || abs((float) $partida->importe_origen_mxn - (float) $entrega->importe_mxn) > 0.000001) {
                    $this->error('La entrega o su costo cambió desde el borrador; revisa el origen y crea una solicitud nueva.');
                }
            }
            $solicitud->update(['estado' => 'solicitada', 'presentado_por' => $usuario->id, 'presentado_at' => now()]);
        }, 3);
    }

    public function actualizarBorrador(Obra $obra, ObraDevolucion $solicitud, User $usuario, array $cantidades, ?string $observaciones = null, array $condiciones = [], array $observacionesPartidas = []): void
    {
        if (! $usuario->can('obra.devoluciones.create.access')) throw new AuthorizationException();
        Validator::make(['observaciones' => $observaciones], ['observaciones' => ['nullable', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($obra, $solicitud, $cantidades, $observaciones, $condiciones, $observacionesPartidas) {
            Obra::whereKey($obra->id)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitud->id)->where('obra_id', $obra->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado !== 'borrador') $this->error('Solo se puede modificar un borrador.');
            $detalles = $solicitud->detalles()->orderBy('id')->lockForUpdate()->get();
            if ($detalles->isEmpty() || count($cantidades) !== $detalles->count()) $this->error('Las cantidades no coinciden con las partidas del borrador.');
            Validator::make(['cantidades' => $cantidades, 'condiciones' => $condiciones, 'notas' => $observacionesPartidas], ['cantidades' => ['required', 'array'], 'cantidades.*' => ['required', 'numeric', 'gt:0', 'decimal:0,3'], 'condiciones' => ['required', 'array'], 'condiciones.*' => ['required', 'in:bueno,danado,desgastado'], 'notas' => ['array'], 'notas.*' => ['nullable', 'string', 'max:2000']])->validate();
            if (count($condiciones) !== $detalles->count() || count(array_diff(array_keys($observacionesPartidas), $detalles->modelKeys())) > 0) $this->error('Las condiciones no coinciden con las partidas del borrador.');
            $lineas = $detalles->map(function ($p) use ($cantidades, $condiciones, $observacionesPartidas) {
                if (! array_key_exists($p->id, $cantidades) || ! array_key_exists($p->id, $condiciones)) $this->error('Falta una partida del borrador.');
                foreach (self::ORIGENES as $tipo => $campo) if ($p->$campo) return ['origen_tipo' => $tipo, 'origen_id' => (int) $p->$campo, 'cantidad' => $cantidades[$p->id], 'condicion' => $condiciones[$p->id], 'nota' => $observacionesPartidas[$p->id] ?? null, 'detalle' => $p];
                $this->error('Una partida no tiene origen válido.');
            });
            $this->bloquearOrigenes($lineas, (int) $obra->id);
            $entregas = app(MaterialDevolucionSaldoService::class)->porObra($obra->id)['entregas']->keyBy(fn ($e) => $e->origen_tipo.':'.$e->origen_id);
            foreach ($lineas as $linea) {
                $this->validarLinea($entregas->get($linea['origen_tipo'].':'.$linea['origen_id']), (float) $linea['cantidad'], false);
                $linea['detalle']->update(['cantidad_solicitada' => $linea['cantidad'], 'condicion_declarada' => $linea['condicion'], 'observaciones' => $linea['nota']]);
            }
            $solicitud->update(['observaciones' => $observaciones]);
        }, 3);
    }

    public function cancelar(Obra $obra, ObraDevolucion $solicitud, User $usuario, string $motivo): void
    {
        if (! $usuario->can('obra.devoluciones.create.access')) throw new AuthorizationException();
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 2000) $this->error('Indica un motivo de cancelación de hasta 2000 caracteres.');
        DB::transaction(function () use ($obra, $solicitud, $usuario, $motivo) {
            Obra::whereKey($obra->id)->lockForUpdate()->firstOrFail();
            $solicitud = ObraDevolucion::whereKey($solicitud->id)->where('obra_id', $obra->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado === 'cancelada') return;
            if (! in_array($solicitud->estado, ['borrador', 'solicitada'], true)
                || $solicitud->recepciones()->whereIn('estado', ['aplicada', 'aplicado', 'pendiente_aplicacion'])->exists()) $this->error('No se puede cancelar una solicitud con recepciones confirmadas.');
            $solicitud->update(['estado' => 'cancelada', 'cancelado_por' => $usuario->id, 'cancelado_at' => now(), 'motivo_cancelacion' => $motivo]);
        }, 3);
    }

    private function bloquearOrigenes($lineas, int $obraId): void
    {
        $directos = $lineas->where('origen_tipo', 'recepcion_directa')->pluck('origen_id');
        $recepciones = DB::table('compra_recepcion_directa_detalles')->whereIn('id', $directos)->pluck('recepcion_id');
        if ($recepciones->count() !== $directos->count()) $this->error('Una entrega directa ya no está disponible.');
        $ordenes = DB::table('compra_recepciones_directas')->whereIn('id', $recepciones)->pluck('orden_compra_id');
        DB::table('ordenes_compra')->whereIn('id', $ordenes)->orderBy('id')->lockForUpdate()->get();
        $recepcionesBloqueadas = DB::table('compra_recepciones_directas')->whereIn('id', $recepciones)->orderBy('id')->lockForUpdate()->get();
        if ($recepcionesBloqueadas->count() !== $recepciones->unique()->count()
            || $recepcionesBloqueadas->contains(fn ($r) => $r->estado !== 'aplicada' || (int) $r->obra_id !== $obraId)) $this->error('Una entrega directa ya no está aplicada en esta obra.');
        DB::table('compra_recepcion_directa_detalles')->whereIn('id', $directos)->orderBy('id')->lockForUpdate()->get();
        $movimientos = $lineas->where('origen_tipo', 'salida_general')->pluck('origen_id');
        $documentos = DB::table('inventario_movimientos')->whereIn('id', $movimientos)->pluck('documento_id');
        if ($documentos->count() !== $movimientos->count()) $this->error('Una salida de almacén ya no está disponible.');
        $documentosBloqueados = DB::table('inventario_documentos')->whereIn('id', $documentos)->orderBy('id')->lockForUpdate()->get();
        if ($documentosBloqueados->count() !== $documentos->unique()->count()
            || $documentosBloqueados->contains(fn ($d) => $d->estado !== 'aplicado' || $d->tipo !== 'salida' || $d->deleted_at !== null || (int) $d->obra_id !== $obraId)) $this->error('Una salida de almacén ya no está aplicada en esta obra.');
        DB::table('inventario_movimientos')->whereIn('id', $movimientos)->orderBy('id')->lockForUpdate()->get();
        $ht = $lineas->where('origen_tipo', 'salida_huentitan')->pluck('origen_id');
        $salidas = DB::table('huentitan_salida_detalles')->whereIn('id', $ht)->pluck('huentitan_salida_id');
        if ($salidas->count() !== $ht->count()) $this->error('Una salida Huentitán ya no está disponible.');
        $salidasBloqueadas = DB::table('huentitan_salidas')->whereIn('id', $salidas)->orderBy('id')->lockForUpdate()->get();
        if ($salidasBloqueadas->count() !== $salidas->unique()->count()
            || $salidasBloqueadas->contains(fn ($s) => $s->estado !== 'aplicada' || (int) $s->obra_id !== $obraId)) $this->error('Una salida Huentitán ya no está aplicada en esta obra.');
        DB::table('huentitan_salida_detalles')->whereIn('id', $ht)->orderBy('id')->lockForUpdate()->get();
    }

    private function validarLinea($entrega, float $cantidad, bool $presentar): void
    {
        if (! $entrega || $entrega->requiere_revision || ! $entrega->unidad || $entrega->costo_unitario_mxn === null || $entrega->importe_mxn === null) $this->error('Una entrega no está disponible o requiere revisión de origen, unidad o costo.');
        $maximo = $presentar ? $entrega->cantidad_disponible_solicitar : (float) $entrega->cantidad;
        if (! is_finite($cantidad) || $cantidad <= 0 || $cantidad > $maximo + 0.000001) $this->error('La cantidad solicitada excede el saldo disponible de una entrega.');
    }

    private function recuperar(ObraDevolucion $solicitud, Obra $obra, User $usuario, string $hash, ?string $hashAnterior = null): ObraDevolucion
    {
        if ((int) $solicitud->obra_id !== (int) $obra->id || (int) $solicitud->creado_por !== (int) $usuario->id || ! $solicitud->solicitud_hash || (! hash_equals($solicitud->solicitud_hash, $hash) && (! $hashAnterior || ! hash_equals($solicitud->solicitud_hash, $hashAnterior)))) throw new ConflictHttpException('La operación ya se utilizó con datos distintos.');
        return $solicitud;
    }

    private function error(string $mensaje): never { throw ValidationException::withMessages(['partidas' => $mensaje]); }
}
