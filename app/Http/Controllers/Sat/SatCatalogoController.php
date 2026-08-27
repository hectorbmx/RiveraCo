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
        'descripcion' => ['required', 'string', 'max:1000'],
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

public function updateConcepto(Request $request, SatConcepto $concepto)
{
    $data = $request->validate([
        'codigo' => ['nullable', 'string', 'max:100'],
        'clave_producto_servicio' => ['required', 'string', 'max:20'],
        'clave_unidad' => ['required', 'string', 'max:20'],
        'descripcion' => ['required', 'string', 'max:1000'],
        'unidad' => ['nullable', 'string', 'max:100'],
        'objeto_impuesto' => ['required', 'string', 'max:10'],
        'iva_tasa' => ['required', 'numeric'],
        'incluye_iva' => ['nullable', 'boolean'],
        'precio_unitario' => ['required', 'numeric', 'min:0'],
        'activo' => ['nullable', 'boolean'],
        'observaciones' => ['nullable', 'string'],
    ]);

    $data['incluye_iva'] = $request->boolean('incluye_iva');
    $data['activo'] = $request->boolean('activo');

    $concepto->update($data);

    return redirect()
        ->route('sat.catalogos.conceptos')
        ->with('success', 'Concepto SAT actualizado correctamente.');
}

public function buscarProductosSat(Request $request)
{
    $data = $request->validate([
        'q' => ['required', 'string', 'min:2', 'max:80'],
    ]);

    return $this->buscarCatalogoFacturapi('/v2/catalogs/products', $data['q']);
}

public function buscarUnidadesSat(Request $request)
{
    $data = $request->validate([
        'q' => ['required', 'string', 'min:1', 'max:80'],
    ]);

    return $this->buscarCatalogoFacturapi('/v2/catalogs/units', $data['q']);
}

private function buscarCatalogoFacturapi(string $uri, string $query): \Illuminate\Http\JsonResponse
{
    $catalogKey = config('services.facturapi.catalog_api_key');

    if (blank($catalogKey)) {
        return response()->json([
            'message' => 'Facturapi Catalog API Key no configurada. Define FACTURAPI_CATALOG_API_KEY en .env.',
            'data'    => [],
        ], 503);
    }

    try {
        $response = Http::withToken($catalogKey)
            ->acceptJson()
            ->get('https://www.facturapi.io' . $uri, [
                'q'     => trim($query),
                'limit' => 10,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => $response->json('message')
                    ?? $response->json('error')
                    ?? 'No se pudo consultar el catalogo SAT.',
                'data' => [],
            ], $response->status());
        }

        $items = collect($response->json('data', []))
            ->map(fn ($item) => [
                'key'         => (string) ($item['key'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
            ])
            ->filter(fn ($item) => $item['key'] !== '')
            ->values();

        return response()->json(['data' => $items]);

    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'No se pudo consultar el catalogo SAT: ' . $e->getMessage(),
            'data'    => [],
        ], 500);
    }
}
}

