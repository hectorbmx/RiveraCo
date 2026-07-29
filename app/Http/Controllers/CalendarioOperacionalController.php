<?php

namespace App\Http\Controllers;

use App\Services\Calendario\CalendarioOperacionalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarioOperacionalController extends Controller
{
    public function index(CalendarioOperacionalService $calendario): View
    {
        return view('calendario-operacional.index', [
            'categorias' => $calendario->categoriasDisponibles(),
        ]);
    }

    public function events(Request $request, CalendarioOperacionalService $calendario): JsonResponse
    {
        $categoriasDisponibles = array_keys($calendario->categoriasDisponibles());

        $data = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
            'categories' => ['nullable'],
            'categories.*' => ['string', Rule::in($categoriasDisponibles)],
            'types' => ['nullable'],
            'types.*' => ['string', 'max:80'],
            'obra_id' => ['nullable', 'integer'],
            'cliente_id' => ['nullable', 'integer'],
            'responsable_id' => ['nullable', 'integer'],
            'vehiculo_id' => ['nullable', 'integer'],
            'maquina_id' => ['nullable', 'integer'],
            'proveedor_id' => ['nullable', 'integer'],
            'empleado_activo' => ['nullable', 'boolean'],
        ]);

        $categorias = $this->arrayInput($request, 'categories');
        validator(
            ['categories' => $categorias],
            ['categories.*' => ['string', Rule::in($categoriasDisponibles)]]
        )->validate();

        $tipos = $this->arrayInput($request, 'types');
        $filtros = collect($data)
            ->only([
                'obra_id',
                'cliente_id',
                'responsable_id',
                'vehiculo_id',
                'maquina_id',
                'proveedor_id',
                'empleado_activo',
            ])
            ->all();

        $eventos = $calendario->eventos(
            $data['start'] ?? null,
            $data['end'] ?? null,
            $categorias,
            $filtros
        );

        if ($tipos !== []) {
            $eventos = array_values(array_filter(
                $eventos,
                fn (array $evento) => in_array($evento['type'] ?? null, $tipos, true)
            ));
        }

        return response()->json([
            'ok' => true,
            'events' => $eventos,
            'meta' => [
                'count' => count($eventos),
                'start' => $data['start'] ?? null,
                'end' => $data['end'] ?? null,
                'categories' => $categorias,
                'types' => $tipos,
                'filters' => $filtros,
            ],
        ]);
    }

    private function arrayInput(Request $request, string $key): array
    {
        $value = $request->input($key, []);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $value
        )));
    }
}