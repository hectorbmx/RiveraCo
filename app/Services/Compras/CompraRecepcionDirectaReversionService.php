<?php

namespace App\Services\Compras;

use App\Models\CompraRecepcionDirecta;
use App\Models\InventarioDocumento;
use App\Models\ObraRequisicion;
use App\Models\ObraRequisicionDetalle;
use App\Models\OrdenCompra;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraRecepcionDirectaReversionService
{
    public function revertir(CompraRecepcionDirecta $recepcion, User $usuario, string $motivo): void
    {
        if (! $usuario->can(CompraRecepcionDirecta::PERMISO_REVERTIR)) throw new AuthorizationException();
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 2000) throw ValidationException::withMessages(['motivo' => 'Indica el motivo de la reversión (máximo 2000 caracteres).']);

        DB::transaction(function () use ($recepcion, $usuario, $motivo) {
            $orden = OrdenCompra::whereKey($recepcion->orden_compra_id)->lockForUpdate()->firstOrFail();
            $recepcion = CompraRecepcionDirecta::whereKey($recepcion->id)->lockForUpdate()->firstOrFail();
            if ((int) $recepcion->orden_compra_id !== (int) $orden->id) $this->error('La recepción ya no corresponde a la orden indicada.');
            if ($recepcion->estado !== CompraRecepcionDirecta::ESTADO_APLICADA) $this->error('Solo se puede revertir una recepción aplicada una vez.');
            $detallesOc = $orden->detalles()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $detalles = $recepcion->detalles()->orderBy('id')->get();
            if ($detalles->isEmpty()) $this->error('La recepción no tiene partidas.');
            if (\Illuminate\Support\Facades\Schema::hasTable('obra_devolucion_detalles') && DB::table('obra_devolucion_detalles as p')
                ->join('obra_devoluciones as d', 'd.id', '=', 'p.devolucion_id')
                ->whereIn('p.recepcion_directa_detalle_id', $detalles->pluck('id'))
                ->whereIn('d.estado', ['solicitada', 'recepcion_parcial', 'recibida'])->exists()) {
                $this->error('La recepción tiene solicitudes de devolución presentadas; requiere revisión antes de revertir.');
            }
            $idsReq = $detalles->pluck('obra_requisicion_detalle_id')->filter()->unique();
            $idsCabecera = ObraRequisicionDetalle::whereIn('id', $idsReq)->pluck('obra_requisicion_id');
            ObraRequisicion::whereIn('id', $idsCabecera)->orderBy('id')->lockForUpdate()->get();
            $reqs = ObraRequisicionDetalle::whereIn('id', $idsReq)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $docs = InventarioDocumento::whereIn('id', [$recepcion->entrada_documento_id, $recepcion->salida_documento_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $entrada = $docs->get($recepcion->entrada_documento_id); $salida = $docs->get($recepcion->salida_documento_id);
            if (! $entrada || ! $salida || $entrada->estado !== 'aplicado' || $salida->estado !== 'aplicado'
                || $entrada->tipo !== 'entrada' || $salida->tipo !== 'salida'
                || (int) $entrada->compra_recepcion_directa_id !== (int) $recepcion->id || (int) $salida->compra_recepcion_directa_id !== (int) $recepcion->id) $this->error('Los documentos originales no están disponibles para reversión.');
            if (DB::table('inventario_documentos')->where('documento_origen_id', $salida->id)->whereNull('deleted_at')->exists()) $this->error('La salida tiene documentos posteriores; revisa sus movimientos antes de revertir.');

            foreach ($detalles as $linea) {
                $oc = $detallesOc->get($linea->orden_compra_detalle_id);
                if (! $oc || (float) $oc->cantidad_recibida + 0.000001 < (float) $linea->cantidad_recibida) $this->error('El saldo recibido de la OC no permite esta reversión.');
                $movs = DB::table('inventario_movimientos')->where('compra_recepcion_directa_detalle_id', $linea->id)->orderBy('id')->get();
                if ($movs->count() !== 2 || ! $movs->contains(fn ($m) => (int) $m->documento_id === (int) $entrada->id && $m->tipo_movimiento === 'in' && abs((float) $m->cantidad - (float) $linea->cantidad_inventario) < 0.000001)
                    || ! $movs->contains(fn ($m) => (int) $m->documento_id === (int) $salida->id && $m->tipo_movimiento === 'out' && abs((float) $m->cantidad - (float) $linea->cantidad_inventario) < 0.000001)) $this->error('Los movimientos originales no coinciden con la recepción.');
            }
            foreach ($detalles->whereNotNull('obra_requisicion_detalle_id')->groupBy('obra_requisicion_detalle_id') as $id => $grupo) {
                $req = $reqs->get($id); $cantidad = (float) $grupo->sum('cantidad_inventario');
                if (! $req || in_array($req->estado, ['cancelada', 'rechazada'], true) || (float) $req->cantidad_surtida + 0.000001 < $cantidad) $this->error('El estado o saldo surtido de requisición no permite esta reversión.');
                $req->cantidad_surtida = round((float) $req->cantidad_surtida - $cantidad, 3);
                $req->estado = (float) $req->cantidad_surtida > 0 ? 'surtida_parcial' : 'pendiente';
                $req->save();
            }
            $cancelaciones = [];
            foreach ([$salida, $entrada] as $original) {
                $doc = InventarioDocumento::create(['tipo' => 'cancelacion', 'almacen_id' => $recepcion->almacen_id,
                    'obra_id' => $recepcion->obra_id, 'orden_compra_id' => $orden->id, 'compra_recepcion_directa_id' => $recepcion->id,
                    'estado' => 'aplicado', 'fecha' => now(), 'motivo' => 'Reversión '.$recepcion->folio,
                    'notas' => $motivo, 'creado_por' => $usuario->id, 'documento_origen_id' => $original->id]);
                $doc->update(['folio' => 'REVD-'.str_pad((string) $doc->id, 8, '0', STR_PAD_LEFT)]);
                $cancelaciones[$original->tipo] = $doc;
            }
            foreach ($detalles->sortBy('producto_id') as $linea) {
                $stock = DB::table('inventario_stock')->where('almacen_id', $recepcion->almacen_id)->where('producto_id', $linea->producto_id)->lockForUpdate()->first();
                if (! $stock) $this->error('No existe el saldo de inventario relacionado.');
                foreach (['salida' => 'in', 'entrada' => 'out'] as $originalTipo => $tipo) {
                    $doc = $cancelaciones[$originalTipo];
                    $doc->detalles()->create(['compra_recepcion_directa_detalle_id' => $linea->id, 'producto_id' => $linea->producto_id,
                        'orden_compra_id' => $orden->id, 'orden_compra_detalle_id' => $linea->orden_compra_detalle_id,
                        'cantidad' => $linea->cantidad_inventario, 'costo_unitario' => round((float) $linea->costo_unitario_mxn, 4), 'notas' => 'Reversión '.$recepcion->folio]);
                    $recepcion->movimientos()->create(['compra_recepcion_directa_detalle_id' => $linea->id, 'almacen_id' => $recepcion->almacen_id,
                        'producto_id' => $linea->producto_id, 'documento_id' => $doc->id, 'fecha' => now(), 'tipo_movimiento' => $tipo,
                        'cantidad' => $linea->cantidad_inventario, 'costo_unitario' => round((float) $linea->costo_unitario_mxn, 4),
                        'importe_costo' => $linea->importe_mxn, 'saldo_cantidad' => (float) $stock->stock_actual + ($tipo === 'in' ? (float) $linea->cantidad_inventario : 0),
                        'obra_id' => $recepcion->obra_id, 'creado_por' => $usuario->id]);
                }
                $oc = $detallesOc->get($linea->orden_compra_detalle_id);
                $oc->cantidad_recibida = round((float) $oc->cantidad_recibida - (float) $linea->cantidad_recibida, 3); $oc->save();
            }
            $entrada->update(['estado' => 'cancelado']); $salida->update(['estado' => 'cancelado']);
            $recepcion->update(['estado' => CompraRecepcionDirecta::ESTADO_REVERTIDA, 'revertido_por' => $usuario->id, 'revertido_at' => now(), 'motivo_reversion' => $motivo]);
            app(OrdenCompraRecepcionEstadoService::class)->sincronizar($orden);
        });
    }

    private function error(string $mensaje): never { throw ValidationException::withMessages(['reversion' => $mensaje]); }
}
