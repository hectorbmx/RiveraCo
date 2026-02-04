<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;

use App\Models\InventarioDocumento;
use App\Models\Almacen;
use App\Models\InventarioDocumentoDetalle;
use App\Services\Inventario\InventarioDocumentoService;
use Illuminate\Http\Request;

class InventarioDocumentoController extends Controller
{
    
 public function index(Request $request)
    {
        $almacenId = $request->integer('almacen_id');
        $tipo      = trim((string) $request->get('tipo', ''));
        $estado    = trim((string) $request->get('estado', '')); // borrador|aplicado|cancelado (según tu modelo)
        $q         = trim((string) $request->get('q', ''));

        $docs = InventarioDocumento::query()
            ->with(['almacen'])
            ->when($almacenId, fn($qq) => $qq->where('almacen_id', $almacenId))
            ->when($tipo !== '', fn($qq) => $qq->where('tipo', $tipo))
            ->when($estado !== '', fn($qq) => $qq->where('estado', $estado))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('folio', 'like', "%{$q}%")
                      ->orWhere('referencia', 'like', "%{$q}%")
                      ->orWhere('observaciones', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $almacenes = Almacen::query()->orderBy('nombre')->get(['id','nombre']);

        // Ajusta estos tipos a lo que tú uses (entrada/salida/cancelacion/ajuste/etc.)
        $tipos = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'cancelacion' => 'Cancelación',
        ];

        // Ajusta a tu enum real
        $estados = [
            'borrador' => 'Borrador',
            'aplicado' => 'Aplicado',
            'cancelado' => 'Cancelado',
        ];

        return view('inventario.documentos.index', compact('docs','almacenes','almacenId','tipo','estado','q','tipos','estados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'        => ['required','in:inicial,entrada,salida,ajuste,resguardo,devolucion'],
            'almacen_id'  => ['required','integer'],
            'obra_id'     => ['nullable','integer'],
            'proveedor_id'=> ['nullable','integer'],
            'orden_compra_id' => ['nullable','integer'],
            'fecha'       => ['nullable','date'],
            'motivo'      => ['nullable','string','max:120'],
            'notas'       => ['nullable','string'],

            'detalles'                => ['required','array','min:1'],
            'detalles.*.producto_id'  => ['required','integer'],
            'detalles.*.cantidad'     => ['required','numeric','gt:0'],
            'detalles.*.costo_unitario'=> ['nullable','numeric','gte:0'],
            'detalles.*.notas'        => ['nullable','string','max:150'],
        ]);

        // Reglas mínimas: obra_id obligatorio si salida/resguardo
        if (in_array($data['tipo'], ['salida','resguardo'], true) && empty($data['obra_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'obra_id es obligatorio para salidas y resguardos.'
            ], 422);
        }

        $doc = InventarioDocumento::create([
            'tipo'           => $data['tipo'],
            'almacen_id'     => $data['almacen_id'],
            'obra_id'        => $data['obra_id'] ?? null,
            'proveedor_id'   => $data['proveedor_id'] ?? null,
            'orden_compra_id'=> $data['orden_compra_id'] ?? null,
            'estado'         => 'borrador',
            'fecha'          => $data['fecha'] ?? now(),
            'motivo'         => $data['motivo'] ?? null,
            'notas'          => $data['notas'] ?? null,
            'creado_por'     => auth()->id() ?? ($data['creado_por'] ?? null),
            'residente_id'   => null, // luego lo llenamos derivándolo de la obra
        ]);

        foreach ($data['detalles'] as $d) {
            InventarioDocumentoDetalle::create([
                'documento_id'  => $doc->id,
                'producto_id'   => $d['producto_id'],
                'cantidad'      => $d['cantidad'],
                'costo_unitario'=> $d['costo_unitario'] ?? null,
                'notas'         => $d['notas'] ?? null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'data' => $doc->load('detalles')
        ]);
    }

  public function show(InventarioDocumento $doc)
    {
        $doc->load(['almacen','detalles']); // agrega relaciones necesarias
        return view('inventario.documentos.show', compact('doc'));
    }

    public function aplicar(InventarioDocumento $doc)
    {
        // Opcional: Gate/Policy aquí (ej. permisos inventario.aplicar)
        InventarioDocumentoService::aplicar($doc);

        return redirect()
            ->route('inventario.documentos.index')
            ->with('status', "Documento #{$doc->id} aplicado.");
    }

     public function cancelar(InventarioDocumento $doc)
    {
        InventarioDocumentoService::cancelar($doc);

        return redirect()
            ->route('inventario.documentos.index')
            ->with('status', "Documento #{$doc->id} cancelado (reversado).");
    }
}
