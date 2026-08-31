<?php

namespace App\Http\Controllers\Admin\Costos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Costos\StoreCommercialMaterialRequest;
use App\Http\Requests\Costos\StoreMaterialFamilyRequest;
use App\Http\Requests\Costos\UpdateCommercialMaterialRequest;
use App\Http\Requests\Costos\UpdateCommercialMaterialStatusRequest;
use App\Http\Requests\Costos\UpdateMaterialFamilyRequest;
use App\Http\Requests\Costos\UpdateMaterialFamilyStatusRequest;
use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilMaterialGroup;
use App\Services\Costos\CatalogoMaterialCostosService;
use App\Services\Costos\CatalogoMaterialCostosWriteService;
use Illuminate\Http\Request;

class CatalogoMaterialController extends Controller
{
    public function __construct(
        private readonly CatalogoMaterialCostosService $catalogoService,
        private readonly CatalogoMaterialCostosWriteService $writeService,
    ) {
    }

    public function index(Request $request)
    {
        return view('admin.costos.materiales.index', $this->catalogoService->indexData($request));
    }

    public function show(Request $request, ObraCivilMaterialGroup $materiale)
    {
        return view('admin.costos.materiales.show', $this->catalogoService->showData($request, $materiale));
    }

    public function storeFamily(StoreMaterialFamilyRequest $request)
    {
        $group = $this->writeService->createFamily($request->validated());

        return redirect()
            ->route('costos.materiales.show', $group)
            ->with('success', 'Familia creada correctamente.');
    }

    public function updateFamily(UpdateMaterialFamilyRequest $request, ObraCivilMaterialGroup $materiale)
    {
        $group = $this->writeService->updateFamily($materiale, $request->validated());

        return redirect()
            ->route('costos.materiales.show', $group)
            ->with('success', 'Familia actualizada correctamente.');
    }

    public function updateFamilyStatus(UpdateMaterialFamilyStatusRequest $request, ObraCivilMaterialGroup $materiale)
    {
        $this->writeService->setFamilyActive($materiale, (bool) $request->validated('is_active'));

        return back()->with('success', 'Estado de familia actualizado correctamente.');
    }

    public function storeChild(StoreCommercialMaterialRequest $request, ObraCivilMaterialGroup $materiale)
    {
        $data = array_merge($request->validated(), [
            'obra_civil_material_group_id' => $materiale->id,
        ]);

        $this->writeService->createCommercialMaterial($data);

        return redirect()
            ->route('costos.materiales.show', $materiale)
            ->with('success', 'Material hijo creado correctamente.');
    }

    public function updateChild(UpdateCommercialMaterialRequest $request, ObraCivilMaterialGroup $materiale, ObraCivilCommercialMaterial $hijo)
    {
        $this->abortIfChildDoesNotBelongToGroup($materiale, $hijo);

        $data = array_merge($request->validated(), [
            'obra_civil_material_group_id' => $materiale->id,
        ]);

        $this->writeService->updateCommercialMaterial($hijo, $data);

        return redirect()
            ->route('costos.materiales.show', $materiale)
            ->with('success', 'Material hijo actualizado correctamente.');
    }

    public function updateChildStatus(UpdateCommercialMaterialStatusRequest $request, ObraCivilMaterialGroup $materiale, ObraCivilCommercialMaterial $hijo)
    {
        $this->abortIfChildDoesNotBelongToGroup($materiale, $hijo);

        $this->writeService->setCommercialMaterialActive($hijo, (bool) $request->validated('is_active'));

        return back()->with('success', 'Estado de material hijo actualizado correctamente.');
    }

    private function abortIfChildDoesNotBelongToGroup(ObraCivilMaterialGroup $group, ObraCivilCommercialMaterial $child): void
    {
        abort_unless((int) $child->obra_civil_material_group_id === (int) $group->id, 404);
    }
}
