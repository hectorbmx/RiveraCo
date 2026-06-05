<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ComisionEtapa;
use App\Models\Comision;
use App\Services\Comisiones\ResidenteComisionesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResidenteComisionController extends Controller
{
    public function __construct(private ResidenteComisionesService $service)
    {
    }

    public function index(Request $request)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->service->indexForUser($request->user()),
        ]);
    }

    public function show(Request $request, Comision $comision)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->service->showForUser($request->user(), $comision),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->storeRules());

        return response()->json([
            'ok' => true,
            'data' => [
                'comision' => $this->service->createForUser($request->user(), $data),
            ],
        ], 201);
    }

    public function updateEtapa(Request $request, Comision $comision, string $etapa)
    {
        $data = $request->validate($this->etapaRules());

        return response()->json([
            'ok' => true,
            'data' => [
                'comision' => $this->service->updateEtapaForUser($request->user(), $comision, $etapa, $data),
            ],
        ]);
    }

    public function storeFoto(Request $request, Comision $comision, string $etapa)
    {
        $data = $request->validate([
            'foto' => ['required', 'file', 'image', 'max:8192'],
            'comentario' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'foto' => $this->service->storeFotoForUser(
                    $request->user(),
                    $comision,
                    $etapa,
                    $data['foto'],
                    $data['comentario'] ?? null,
                ),
            ],
        ], 201);
    }

    private function storeRules(): array
    {
        return array_merge([
            'fecha' => ['required', 'date'],
            'pila_id' => ['required', 'integer', 'exists:obras_pilas,id'],
            'numero_formato' => ['nullable', 'string', 'max:50'],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'trabajo_id' => ['nullable', 'integer'],
            'opcionales' => ['nullable', 'array'],
            'opcionales.bentonita' => ['nullable', 'boolean'],
            'opcionales.ademe' => ['nullable', 'boolean'],
            'perforacion' => ['nullable', 'array'],
        ], $this->etapaRules('perforacion.'));
    }

    private function etapaRules(string $prefix = ''): array
    {
        return [
            "{$prefix}estado" => [
                'nullable',
                Rule::in([
                    ComisionEtapa::ESTADO_PENDIENTE,
                    ComisionEtapa::ESTADO_EN_PROCESO,
                    ComisionEtapa::ESTADO_COMPLETADA,
                    ComisionEtapa::ESTADO_NO_APLICA,
                ]),
            ],
            "{$prefix}hora_inicio" => ['nullable', 'date_format:H:i'],
            "{$prefix}hora_fin" => ['nullable', 'date_format:H:i'],
            "{$prefix}observaciones" => ['nullable', 'string'],
            "{$prefix}requiere_foto" => ['nullable', 'boolean'],
            "{$prefix}diametro" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}cantidad" => ['nullable', 'integer', 'min:0'],
            "{$prefix}profundidad" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}metros_comision" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}kg_acero" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}vol_bentonita" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}vol_concreto" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}ml_ademe_bauer" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}campana_pzas" => ['nullable', 'integer', 'min:0'],
            "{$prefix}adicional" => ['nullable', 'string', 'max:100'],
            "{$prefix}personal" => ['nullable', 'array'],
            "{$prefix}personal.*.obra_empleado_id" => ['required_with:' . "{$prefix}personal", 'integer', 'exists:obra_empleado,id'],
            "{$prefix}personal.*.actividad_id" => ['nullable', 'integer', 'exists:catalogo_actividades_comision,id'],
            "{$prefix}personal.*.comisiona" => ['nullable', 'boolean'],
            "{$prefix}personal.*.importe_comision" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}personal.*.notas" => ['nullable', 'string'],
        ];
    }
}
