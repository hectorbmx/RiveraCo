<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatConcepto;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class SatCatalogoController extends Controller
{
    public function conceptos()
    {
        $conceptos = SatConcepto::latest()->paginate(20);

        return view('sat.catalogos.conceptos', compact('conceptos'));
    }
    public function storeConcepto(Request $request)
{
    $data = $request->validate([
        'codigo' => ['nullable', 'string', 'max:100'],
        'clave_producto_servicio' => ['required', 'string', 'max:20'],
        'clave_unidad' => ['required', 'string', 'max:20'],
        'descripcion' => ['required', 'string', 'max:255'],
        'unidad' => ['nullable', 'string', 'max:100'],
        'objeto_impuesto' => ['required', 'string', 'max:10'],
        'iva_tasa' => ['required', 'numeric'],
        'incluye_iva' => ['nullable', 'boolean'],
        'precio_unitario' => ['required', 'numeric', 'min:0'],
        'observaciones' => ['nullable', 'string'],
    ]);

    $data['incluye_iva'] = $request->boolean('incluye_iva');

    \App\Models\SatConcepto::create($data);

    return redirect()
        ->route('sat.catalogos.conceptos')
        ->with('success', 'Concepto SAT creado correctamente.');
}

public function buscarProductosSat(Request $request)
{
    $data = $request->validate([
        'q' => ['required', 'string', 'min:2', 'max:80'],
    ]);

    try {
        $response = Http::withBasicAuth(config('services.facturapi.secret_key'), '')
            ->acceptJson()
            ->get('https://www.facturapi.io/v2/catalogs/products', [
                'q' => $data['q'],
                'limit' => 10,
            ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => $response->json('message')
                    ?? $response->json('error')
                    ?? 'No se pudo consultar el catalogo SAT.',
                'data' => [],
            ], $response->status());
        }

        $items = collect($response->json('data', []))
            ->map(fn ($item) => [
                'key' => (string) ($item['key'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
            ])
            ->filter(fn ($item) => $item['key'] !== '')
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'No se pudo consultar el catalogo SAT: ' . $e->getMessage(),
            'data' => [],
        ], 500);
    }
}
}
