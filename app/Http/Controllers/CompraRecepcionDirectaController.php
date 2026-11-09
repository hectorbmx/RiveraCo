<?php

namespace App\Http\Controllers;

use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Http\Requests\StoreCompraRecepcionDirectaRequest;
use App\Services\Compras\CompraRecepcionDirectaService;

class CompraRecepcionDirectaController extends Controller
{
    public function revertir(\Illuminate\Http\Request $request, OrdenCompra $ordenCompra, CompraRecepcionDirecta $recepcion)
    {
        abort_unless($request->user()?->can(CompraRecepcionDirecta::PERMISO_REVERTIR), 403);
        abort_unless((int) $recepcion->orden_compra_id === (int) $ordenCompra->id, 404);
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:2000']]);
        app(\App\Services\Compras\CompraRecepcionDirectaReversionService::class)->revertir($recepcion, $request->user(), $datos['motivo']);
        return redirect()->route('ordenes_compra.recepciones_directas.show', [$ordenCompra, $recepcion])->with('success', 'Recepción revertida.');
    }
    private function verificarConsulta(OrdenCompra $ordenCompra, CompraRecepcionDirecta $recepcion): void
    {
        abort_unless(auth()->user()?->can(CompraRecepcionDirecta::PERMISO_VER), 403);
        abort_unless((int) $recepcion->orden_compra_id === (int) $ordenCompra->id, 404);
    }

    public function imprimir(OrdenCompra $ordenCompra, CompraRecepcionDirecta $recepcion)
    {
        $this->verificarConsulta($ordenCompra, $recepcion);
        return view('compras.recepciones-directas.imprimir', [
            'recepcion' => app(\App\Services\Compras\CompraRecepcionDirectaConsultaService::class)->cargar($recepcion),
        ]);
    }

    public function evidencia(OrdenCompra $ordenCompra, CompraRecepcionDirecta $recepcion, \App\Models\CompraRecepcionDirectaArchivo $archivo)
    {
        $this->verificarConsulta($ordenCompra, $recepcion);
        abort_unless((int) $archivo->recepcion_id === (int) $recepcion->id && $archivo->disk === 'local', 404);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($archivo->path), 404);
        return $disk->download($archivo->path, $archivo->nombre_original, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function show(OrdenCompra $ordenCompra, CompraRecepcionDirecta $recepcion)
    {
        $this->verificarConsulta($ordenCompra, $recepcion);

        return view('compras.recepciones-directas.show', [
            'recepcion' => app(\App\Services\Compras\CompraRecepcionDirectaConsultaService::class)->cargar($recepcion)->load('archivos'),
        ]);
    }

    public function create(OrdenCompra $ordenCompra)
    {
        abort_unless(auth()->user()?->can(CompraRecepcionDirecta::PERMISO_CONFIRMAR), 403);
        abort_unless($ordenCompra->modalidad_entrega === OrdenCompra::ENTREGA_OBRA_DIRECTA, 422,
            'La orden de compra no tiene entrega directa a obra.');
        abort_unless(strtoupper((string) $ordenCompra->estado) === 'AUTORIZADA', 422,
            'La orden de compra debe estar autorizada para recibir material.');

        $ordenCompra->load(['proveedor', 'obra', 'almacenDestino', 'detalles.producto']);

        return view('compras.recepciones-directas.create', [
            'ordenCompra' => $ordenCompra,
            'partidas' => app(\App\Services\Compras\CompraRecepcionDirectaPartidasService::class)->pendientes($ordenCompra),
            'confirmacionHabilitada' => true,
            'accionConfirmar' => route('ordenes_compra.recepciones_directas.store', $ordenCompra),
        ]);
    }

    public function store(StoreCompraRecepcionDirectaRequest $request, OrdenCompra $ordenCompra, CompraRecepcionDirectaService $service)
    {
        $recepcion = $service->confirmar($ordenCompra, $request->validated(), $request->user());

        return redirect()->route('ordenes_compra.edit', $ordenCompra)
            ->with('success', 'Recepción '.$recepcion->folio.' registrada y entregada a la obra.');
    }
}
