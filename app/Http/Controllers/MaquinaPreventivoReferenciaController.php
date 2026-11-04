<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Models\MaquinaPreventivoReferencia;
use App\Services\Maquinas\MaquinaPreventivoReferenciaService;
use App\Services\Maquinas\PreventivoMaquinaService;
use Illuminate\Http\Request;

class MaquinaPreventivoReferenciaController extends Controller
{
    public function edit(Request $request, Maquina $maquina, PreventivoMaquinaService $preventivos)
    {
        $this->autorizar($request);
        $preventivo = $preventivos->calcularParaColeccion(collect([$maquina]))[$maquina->id];
        $historial = MaquinaPreventivoReferencia::with(['usuario:id,name', 'servicio:id,nombre'])
            ->where('maquina_id', $maquina->id)->latest('id')->paginate(10);

        return view('maquinas.ajuste-servicio', compact('maquina', 'preventivo', 'historial'));
    }

    public function store(Request $request, Maquina $maquina, MaquinaPreventivoReferenciaService $referencias)
    {
        $this->autorizar($request);
        $referencias->guardar($maquina->id, $request->only(['servicio_preventivo_tipo_id', 'horometro_esperado', 'motivo']), $request->user());

        return redirect()->route('maquinas.preventivo.edit', $maquina)
            ->with('success', 'Referencia del preventivo guardada. El horómetro de la máquina se conserva.');
    }

    private function autorizar(Request $request): void
    {
        $usuario = $request->user();
        abort_unless($usuario && ! $usuario->hasDeniedPermission(MaquinaPreventivoReferenciaService::PERMISO)
            && $usuario->can(MaquinaPreventivoReferenciaService::PERMISO), 403);
    }
}
