<?php

namespace App\Http\Controllers\Admin\Costos;

use App\Http\Controllers\Controller;
use App\Models\ObraCivilMaterialGroup;
use App\Services\Costos\CatalogoMaterialCostosService;
use Illuminate\Http\Request;

class CatalogoMaterialController extends Controller
{
    public function __construct(
        private readonly CatalogoMaterialCostosService $catalogoService,
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
}
