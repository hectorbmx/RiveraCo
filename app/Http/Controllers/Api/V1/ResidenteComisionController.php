<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comision;
use App\Services\Comisiones\ResidenteComisionesService;
use App\Services\Comisiones\ComisionEtapaRules;
use Illuminate\Http\Request;

class ResidenteComisionController extends Controller
{
    public function __construct(private ResidenteComisionesService $service)
    {
    }

    public function index(Request $request)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->service->indexForUser($request->user(), $this->obraId($request)),
        ]);
    }

    public function show(Request $request, Comision $comision)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->service->showForUser($request->user(), $comision, $this->obraId($request)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->storeRules());

        return response()->json([
            'ok' => true,
            'data' => [
                'comision' => $this->service->createForUser($request->user(), $data, $this->obraId($request)),
            ],
        ], 201);
    }

    public function updateEtapa(Request $request, Comision $comision, string $etapa)
    {
        $data = $request->validate(array_merge([
            'obra_id' => ['nullable', 'integer'],
        ], ComisionEtapaRules::rules()));

        return response()->json([
            'ok' => true,
            'data' => [
                'comision' => $this->service->updateEtapaForUser($request->user(), $comision, $etapa, $data, $this->obraId($request)),
            ],
        ]);
    }

    public function storeFoto(Request $request, Comision $comision, string $etapa)
    {
        $data = $request->validate([
            'obra_id' => ['nullable', 'integer'],
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
                    $this->obraId($request),
                ),
            ],
        ], 201);
    }

    private function storeRules(): array
    {
        return array_merge([
            'obra_id' => ['nullable', 'integer'],
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
        ], ComisionEtapaRules::rules('perforacion.'));
    }

    private function obraId(Request $request): ?int
    {
        $obraId = $request->input('obra_id', $request->query('obra_id'));

        return $obraId ? (int) $obraId : null;
    }
}
