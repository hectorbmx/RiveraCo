<?php

namespace App\Services\Compras;

use App\Http\Requests\StoreCompraRecepcionDirectaRequest;
use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CompraRecepcionDirectaService
{
    public function __construct(
        private CompraRecepcionDirectaValidacionService $validacion,
        private CompraRecepcionDirectaCostoService $costos,
    ) {}

    public function confirmar(OrdenCompra $orden, array $datos, User $compras): CompraRecepcionDirecta
    {
        if (! $compras->can(CompraRecepcionDirecta::PERMISO_CONFIRMAR)) {
            throw new AuthorizationException('No tienes permiso para confirmar recepciones directas.');
        }
        $datos = Validator::make($datos, (new StoreCompraRecepcionDirectaRequest())->rules())->validate();
        $solicitudHash = $this->solicitudHash($orden, $datos, $compras);
        $archivosCreados = [];

        try {
            return DB::transaction(function () use ($orden, $datos, $compras, $solicitudHash, &$archivosCreados) {
                $orden = OrdenCompra::query()->whereKey($orden->id)->lockForUpdate()->firstOrFail();
                $existente = CompraRecepcionDirecta::where('operacion_uuid', $datos['operacion_uuid'])->lockForUpdate()->first();
                if ($existente) {
                    return $this->recuperarOperacion($existente, $orden, $solicitudHash);
                }
                $orden->setRelation('detalles', $orden->detalles()->with('producto')->orderBy('id')->lockForUpdate()->get());
                $lineas = $this->validacion->validar($orden, $datos['partidas']);
                app(CompraRequisicionSaldoService::class)->aplicarEntrega($orden, $lineas);
                $moneda = strtoupper((string) ($orden->moneda ?: 'MXN'));
                $tipoCambioReferencia = (float) $orden->tipo_cambio;
                if ($tipoCambioReferencia <= 0) $tipoCambioReferencia = (float) $lineas->first()['detalle']->tipo_cambio;

                $recepcion = CompraRecepcionDirecta::create([
                    'operacion_uuid' => $datos['operacion_uuid'], 'orden_compra_id' => $orden->id,
                    'solicitud_hash' => $solicitudHash,
                    'obra_id' => $orden->obra_id, 'almacen_id' => $orden->almacen_destino_id,
                    'fecha_recepcion' => $datos['fecha_recepcion'], 'receptor_nombre' => $datos['receptor_nombre'],
                    'remision' => $datos['remision'] ?? null, 'observaciones' => $datos['observaciones'] ?? null,
                    'moneda' => $moneda,
                    'tipo_cambio' => $moneda === 'MXN' ? 1 : $tipoCambioReferencia,
                    'confirmado_por' => $compras->id,
                ]);
                $recepcion->update(['folio' => 'RDO-'.str_pad((string) $recepcion->id, 8, '0', STR_PAD_LEFT)]);

                foreach ($datos['evidencias'] as $archivo) {
                    $path = Storage::disk('local')->putFile('compras/recepciones-directas/'.$recepcion->operacion_uuid, $archivo);
                    if (! $path) throw new RuntimeException('No se pudo guardar la evidencia de entrega.');
                    $archivosCreados[] = $path;
                    $recepcion->archivos()->create([
                        'disk' => 'local', 'path' => $path, 'nombre_original' => $archivo->getClientOriginalName(),
                        'mime_type' => $archivo->getMimeType(), 'size_bytes' => $archivo->getSize(),
                        'hash_sha256' => hash_file('sha256', $archivo->getRealPath()), 'uploaded_by' => $compras->id,
                    ]);
                }

                $documentos = [];
                foreach (['entrada', 'salida'] as $tipo) {
                    $doc = $recepcion->documentos()->create([
                        'tipo' => $tipo, 'almacen_id' => $recepcion->almacen_id, 'obra_id' => $recepcion->obra_id,
                        'orden_compra_id' => $orden->id, 'proveedor_id' => $orden->proveedor_id,
                        'estado' => 'aplicado', 'fecha' => $recepcion->fecha_recepcion,
                        'creado_por' => $compras->id, 'motivo' => 'recepcion_directa_obra',
                        'notas' => $recepcion->folio.' · Entrega directa confirmada por Compras',
                        'documento_origen_id' => $tipo === 'salida' ? $documentos['entrada']->id : null,
                    ]);
                    $doc->update(['folio' => ($tipo === 'entrada' ? 'ENTD-' : 'SALD-').str_pad((string) $doc->id, 8, '0', STR_PAD_LEFT)]);
                    $documentos[$tipo] = $doc;
                }

                $totalOc = 0.0;
                $totalMxn = 0.0;
                foreach ($lineas->sortBy(fn ($linea) => (int) $linea['detalle']->producto_id) as $linea) {
                    $detalle = $linea['detalle'];
                    $cantidad = $linea['cantidad_inventario'];
                    if ($cantidad > 99999999999.999) throw ValidationException::withMessages(['cantidad' => 'La cantidad excede la precisión de inventario.']);
                    $costo = $this->costos->calcular($orden, $detalle, $linea['cantidad'], $linea['factor_inventario']);
                    $anteriores = \App\Models\CompraRecepcionDirectaDetalle::where('orden_compra_detalle_id', $detalle->id)
                        ->whereHas('recepcion', fn ($q) => $q->where('estado', CompraRecepcionDirecta::ESTADO_APLICADA))->get();
                    if (abs((float) $anteriores->sum('cantidad_recibida') - (float) $detalle->cantidad_recibida) > 0.000001) {
                        throw ValidationException::withMessages(['costo' => 'El saldo recibido no coincide con las recepciones directas aplicadas. Revisa la OC.']);
                    }
                    foreach ($anteriores as $anterior) {
                        if (abs((float) $anterior->importe_neto_oc - $costo['importe_neto_oc']) > 0.000001
                            || abs((float) $anterior->cantidad_ordenada - (float) $detalle->cantidad) > 0.000001
                            || abs((float) $anterior->tipo_cambio - $costo['tipo_cambio']) > 0.00000001
                            || abs((float) $anterior->factor_inventario - $linea['factor_inventario']) > 0.00000001) {
                            throw ValidationException::withMessages(['costo' => 'La base de costo o conversión de la OC cambió después de recibir material. Requiere revisión antes de otra entrega.']);
                        }
                    }
                    $costo = $this->costos->calcular($orden, $detalle, $linea['cantidad'], $linea['factor_inventario'], [
                        'cantidad' => $anteriores->sum('cantidad_recibida'), 'importe_oc' => $anteriores->sum('importe_oc'),
                        'importe_mxn' => $anteriores->sum('importe_mxn'),
                    ]);

                    DB::table('inventario_stock')->insertOrIgnore([
                        'almacen_id' => $recepcion->almacen_id, 'producto_id' => $detalle->producto_id,
                        'stock_actual' => 0, 'stock_reservado' => 0, 'valor_total' => 0, 'costo_promedio' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $stock = DB::table('inventario_stock')->where('almacen_id', $recepcion->almacen_id)
                        ->where('producto_id', $detalle->producto_id)->lockForUpdate()->first();
                    if (! $stock) throw new RuntimeException('No se pudo bloquear el saldo de inventario.');

                    $detalleRecepcion = $recepcion->detalles()->create([
                        'obra_requisicion_detalle_id' => $detalle->obra_requisicion_detalle_id,
                        'orden_compra_detalle_id' => $detalle->id, 'producto_id' => $detalle->producto_id,
                        'descripcion' => $detalle->descripcion ?: $detalle->producto->nombre, 'unidad' => $detalle->unidad,
                        'cantidad_ordenada' => $detalle->cantidad, 'cantidad_recibida' => $linea['cantidad'],
                        'factor_inventario' => $linea['factor_inventario'], 'cantidad_inventario' => $cantidad,
                        'unidad_inventario' => $linea['unidad_inventario'], ...$costo,
                    ]);
                    foreach ($documentos as $tipo => $doc) {
                        $doc->detalles()->create([
                            'compra_recepcion_directa_detalle_id' => $detalleRecepcion->id,
                            'producto_id' => $detalle->producto_id, 'orden_compra_id' => $orden->id,
                            'orden_compra_detalle_id' => $detalle->id, 'cantidad' => $cantidad,
                            'costo_unitario' => round($costo['costo_unitario_mxn'], 4),
                            'notas' => $recepcion->folio.' · '.$linea['unidad_inventario'],
                        ]);
                        $recepcion->movimientos()->create([
                            'compra_recepcion_directa_detalle_id' => $detalleRecepcion->id,
                            'almacen_id' => $recepcion->almacen_id, 'producto_id' => $detalle->producto_id,
                            'documento_id' => $doc->id, 'fecha' => $recepcion->fecha_recepcion,
                            'tipo_movimiento' => $tipo === 'entrada' ? 'in' : 'out', 'cantidad' => $cantidad,
                            'costo_unitario' => round($costo['costo_unitario_mxn'], 4), 'importe_costo' => $costo['importe_mxn'],
                            'saldo_cantidad' => (float) $stock->stock_actual + ($tipo === 'entrada' ? $cantidad : 0),
                            'obra_id' => $recepcion->obra_id, 'creado_por' => $compras->id,
                        ]);
                    }
                    $detalle->cantidad_recibida = (float) $detalle->cantidad_recibida + $linea['cantidad'];
                    $detalle->save();
                    $totalOc += $costo['importe_oc'];
                    $totalMxn += $costo['importe_mxn'];
                }

                app(OrdenCompraRecepcionEstadoService::class)->sincronizar($orden);
                $recepcion->update([
                    'entrada_documento_id' => $documentos['entrada']->id, 'salida_documento_id' => $documentos['salida']->id,
                    'total_oc' => round($totalOc, 2), 'total_mxn' => round($totalMxn, 2),
                    'estado' => CompraRecepcionDirecta::ESTADO_APLICADA, 'confirmado_at' => now(),
                ]);

                return $recepcion;
            });
        } catch (Throwable $e) {
            foreach ($archivosCreados as $path) Storage::disk('local')->delete($path);
            if ($e instanceof \Illuminate\Database\QueryException) {
                $existente = CompraRecepcionDirecta::where('operacion_uuid', $datos['operacion_uuid'])->first();
                if ($existente) return $this->recuperarOperacion($existente, $orden, $solicitudHash);
            }
            throw $e;
        }
    }

    private function recuperarOperacion(CompraRecepcionDirecta $existente, OrdenCompra $orden, string $hash): CompraRecepcionDirecta
    {
        if ((int) $existente->orden_compra_id !== (int) $orden->id
            || ! $existente->solicitud_hash || ! hash_equals($existente->solicitud_hash, $hash)) {
            throw new ConflictHttpException('Esta operación ya fue utilizada con datos distintos. Inicia una nueva recepción.');
        }
        if ($existente->estado !== CompraRecepcionDirecta::ESTADO_APLICADA) {
            throw new ConflictHttpException('La recepción ya no está aplicada y no puede confirmarse nuevamente.');
        }
        return $existente;
    }

    private function solicitudHash(OrdenCompra $orden, array $datos, User $compras): string
    {
        $partidas = collect($datos['partidas'])->map(fn ($partida) => [
            'id' => (int) $partida['orden_compra_detalle_id'],
            'cantidad' => number_format((float) $partida['cantidad_recibida'], 3, '.', ''),
        ])->sortBy('id')->values()->all();
        $archivos = collect($datos['evidencias'])->map(fn ($archivo) => [
            'sha256' => hash_file('sha256', $archivo->getRealPath()),
            'size' => $archivo->getSize(), 'mime' => $archivo->getMimeType(),
        ])->sortBy(fn ($archivo) => $archivo['sha256'].':'.$archivo['size'].':'.$archivo['mime'])->values()->all();

        return hash('sha256', json_encode([
            'oc' => (int) $orden->id, 'compras' => (int) $compras->id,
            'fecha' => $datos['fecha_recepcion'], 'receptor' => trim($datos['receptor_nombre']),
            'remision' => trim($datos['remision'] ?? ''),
            'observaciones' => str_replace("\r\n", "\n", trim($datos['observaciones'] ?? '')),
            'partidas' => $partidas, 'archivos' => $archivos,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
