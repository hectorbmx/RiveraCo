<?php

namespace App\Http\Controllers;

use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\EmpresaServicioPreventivoMaterial;
use App\Models\Maquina;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaServicioPreventivoTipoController extends Controller
{
    public function validarCodigo(Request $request)
    {
        $data = $request->validate([
            'ambito' => ['required', Rule::in([
                EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
                EmpresaServicioPreventivoTipo::AMBITO_VEHICULO,
            ])],
            'codigo' => ['required', 'string', 'max:80'],
            'ignore_id' => ['nullable', 'integer'],
        ]);

        $codigo = str($data['codigo'])->lower()->slug('_')->toString();

        $exists = EmpresaServicioPreventivoTipo::query()
            ->where('ambito', $data['ambito'])
            ->where('codigo', $codigo)
            ->when($data['ignore_id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        return response()->json([
            'exists' => $exists,
            'codigo' => $codigo,
        ]);
    }
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        EmpresaServicioPreventivoTipo::create($data);

        return back()->with('success', 'Tipo de servicio preventivo creado correctamente.');
    }

    public function update(Request $request, EmpresaServicioPreventivoTipo $tipo)
    {
        $data = $this->validatedData($request, $tipo);

        $tipo->update($data);

        return back()->with('success', 'Tipo de servicio preventivo actualizado correctamente.');
    }

    public function toggleActivo(EmpresaServicioPreventivoTipo $tipo)
    {
        $tipo->update([
            'activo' => ! $tipo->activo,
        ]);

        $estado = $tipo->activo ? 'activado' : 'desactivado';

        return back()->with('success', "Tipo de servicio preventivo {$estado} correctamente.");
    }

    public function materialesPorServicio(EmpresaServicioPreventivoTipo $tipo)
    {
        $this->ensureMaquinaria($tipo);

        $maquinas = Maquina::query()
            ->orderBy('nombre')
            ->orderBy('id')
            ->get(['id', 'nombre', 'codigo', 'numero_serie']);
        $materiales = $tipo->materiales()
            ->with('producto:id,nombre,sku,unidad')
            ->get()
            ->groupBy('maquina_id');
        $productosCatalogo = Producto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sku', 'unidad']);

        return view('empresa_config.servicio_preventivo_materiales', compact(
            'tipo',
            'maquinas',
            'materiales',
            'productosCatalogo',
        ));
    }

    public function materiales(Request $request, EmpresaServicioPreventivoTipo $tipo, ?Maquina $maquina = null)
    {
        $this->ensureMaquinaria($tipo);

        if ($maquina) {
            $request->merge(['maquina_id' => $maquina->id]);
        }

        $data = $request->validate([
            'maquina_id' => ['nullable', 'integer', 'exists:maquinas,id'],
        ]);

        $query = $tipo->materiales()->with('producto:id,nombre,sku,unidad');

        if (array_key_exists('maquina_id', $data)) {
            $query->where('maquina_id', $data['maquina_id']);
        }

        return response()->json($query
            ->get()
            ->map(fn (EmpresaServicioPreventivoMaterial $material) => [
                'id' => $material->id,
                'producto_id' => $material->producto_id,
                'producto' => $material->producto,
                'cantidad_requerida' => $material->cantidad_requerida,
                'unidad' => $material->unidad,
                'orden' => $material->orden,
                'activo' => $material->activo,
            ]));
    }

    public function storeMaterial(Request $request, EmpresaServicioPreventivoTipo $tipo, ?Maquina $maquina = null)
    {
        $this->ensureMaquinaria($tipo);

        if ($maquina) {
            $request->merge(['maquina_id' => $maquina->id]);
        }

        $data = $this->validatedMaterialData($request, $tipo);
        $producto = Producto::query()->findOrFail($data['producto_id']);

        $material = $tipo->materiales()->create([
            ...$data,
            'unidad' => $data['unidad'] ?: $producto->unidad,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json($material->load('producto:id,nombre,sku,unidad'), 201);
    }

    public function updateMaterial(Request $request, EmpresaServicioPreventivoMaterial $material)
    {
        $this->ensureMaquinaria($material->servicioPreventivoTipo);

        $data = $this->validatedMaterialData($request, $material->servicioPreventivoTipo, $material);
        $producto = Producto::query()->findOrFail($data['producto_id']);

        $material->update([
            ...$data,
            'unidad' => $data['unidad'] ?: $producto->unidad,
            'activo' => $request->boolean('activo', $material->activo),
        ]);

        return response()->json($material->fresh()->load('producto:id,nombre,sku,unidad'));
    }

    public function toggleMaterialActivo(EmpresaServicioPreventivoMaterial $material)
    {
        $this->ensureMaquinaria($material->servicioPreventivoTipo);

        $material->update([
            'activo' => ! $material->activo,
        ]);

        return response()->json([
            'id' => $material->id,
            'activo' => $material->activo,
        ]);
    }

    private function validatedMaterialData(
        Request $request,
        EmpresaServicioPreventivoTipo $tipo,
        ?EmpresaServicioPreventivoMaterial $material = null
    ): array {
        $data = $request->validate([
            'maquina_id' => ['nullable', 'integer', 'exists:maquinas,id'],
            'producto_id' => [
                'required',
                'integer',
                Rule::exists('productos', 'id')->where(fn ($query) => $query->where('activo', true)),
                Rule::unique('empresa_servicio_preventivo_materiales', 'producto_id')
                    ->where(function ($query) use ($tipo, $request) {
                        $query->where('servicio_preventivo_tipo_id', $tipo->id);

                        if ($request->filled('maquina_id')) {
                            $query->where('maquina_id', $request->input('maquina_id'));
                        } else {
                            $query->whereNull('maquina_id');
                        }
                    })
                    ->ignore($material?->id),
            ],
            'cantidad_requerida' => ['required', 'numeric', 'gt:0'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['orden'] = $data['orden'] ?? 0;

        return $data;
    }

    private function ensureMaquinaria(EmpresaServicioPreventivoTipo $tipo): void
    {
        abort_unless(
            $tipo->ambito === EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
            404
        );
    }

    private function validatedData(Request $request, ?EmpresaServicioPreventivoTipo $tipo = null): array
    {
        $ambitos = [
            EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
            EmpresaServicioPreventivoTipo::AMBITO_VEHICULO,
        ];

        $request->merge([
            'codigo' => str((string) $request->input('codigo'))->lower()->slug('_')->toString(),
        ]);

        $data = $request->validate([
            'ambito' => ['required', Rule::in($ambitos)],
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => [
                'required',
                'string',
                'max:80',
                Rule::unique('empresa_servicio_preventivo_tipos', 'codigo')
                    ->where(fn ($query) => $query->where('ambito', $request->input('ambito')))
                    ->ignore($tipo?->id),
            ],
            'intervalo_valor' => ['required', 'integer', 'min:1'],
            'intervalo_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'alerta_valor' => ['nullable', 'integer', 'min:0'],
            'alerta_dias' => ['nullable', 'integer', 'min:0'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['unidad'] = $data['ambito'] === EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA
            ? EmpresaServicioPreventivoTipo::UNIDAD_HORAS
            : EmpresaServicioPreventivoTipo::UNIDAD_KM;
        $data['activo'] = $request->has('activo');

        if ($data['ambito'] === EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA) {
            $data['alerta_dias'] = null;
        }

        return $data;
    }
}
