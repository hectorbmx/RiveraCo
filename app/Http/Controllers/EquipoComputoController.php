<?php

namespace App\Http\Controllers;

use App\Models\EquipoComputo;
use App\Models\EquipoComputoFoto;
use App\Models\EquipoComputoMovimiento;
use App\Models\SatCfdi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EquipoComputoController extends Controller
{
    private array $tipos = ['laptop', 'desktop', 'monitor', 'impresora', 'tablet', 'otro'];
    private array $estatus = ['activo', 'asignado', 'mantenimiento', 'resguardo', 'baja'];

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        unset($data['factura_archivo'], $data['resguardo_archivo'], $data['fotos']);
        $data['responsable_actual_id'] = $data['responsable_actual_id'] ?? null;
        $data['area_id'] = $data['area_id'] ?? null;
        $data['estatus'] = $data['estatus'] ?? 'activo';

        if ($data['responsable_actual_id'] && $data['estatus'] === 'activo') {
            $data['estatus'] = 'asignado';
        }

        if ($request->hasFile('factura_archivo')) {
            $data['factura_path'] = $request->file('factura_archivo')
                ->store('equipos-computo/facturas', 'public');
        }

        if ($request->hasFile('resguardo_archivo')) {
            $data['resguardo_path'] = $request->file('resguardo_archivo')
                ->store('equipos-computo/resguardos', 'public');
        }

        DB::transaction(function () use ($data, $request) {
            $equipo = EquipoComputo::create($data);

            $movimiento = $this->registrarMovimiento($equipo, [
                'tipo' => 'alta',
                'responsable_nuevo_id' => $equipo->responsable_actual_id,
                'area_nueva_id' => $equipo->area_id,
                'ubicacion_nueva' => $equipo->ubicacion,
                'estatus_nuevo' => $equipo->estatus,
                'fecha_movimiento' => now()->toDateString(),
                'notas' => $request->input('movimiento_notas') ?: 'Alta del equipo de computo.',
            ]);

            $this->guardarFotos($request, $equipo, $movimiento);
        });

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'equipos_computo'])
            ->with('success', 'Equipo de computo registrado correctamente.');
    }

    public function update(Request $request, EquipoComputo $equipo)
    {
        $data = $this->validatedData($request, $equipo);
        unset($data['factura_archivo'], $data['resguardo_archivo'], $data['fotos']);
        $data['responsable_actual_id'] = $data['responsable_actual_id'] ?? null;
        $data['area_id'] = $data['area_id'] ?? null;

        $anterior = [
            'responsable_actual_id' => $equipo->responsable_actual_id,
            'area_id' => $equipo->area_id,
            'ubicacion' => $equipo->ubicacion,
            'estatus' => $equipo->estatus,
        ];

        if ($request->hasFile('factura_archivo')) {
            if ($equipo->factura_path && Storage::disk('public')->exists($equipo->factura_path)) {
                Storage::disk('public')->delete($equipo->factura_path);
            }

            $data['factura_path'] = $request->file('factura_archivo')
                ->store('equipos-computo/facturas', 'public');
        }

        if ($request->hasFile('resguardo_archivo')) {
            if ($equipo->resguardo_path && Storage::disk('public')->exists($equipo->resguardo_path)) {
                Storage::disk('public')->delete($equipo->resguardo_path);
            }

            $data['resguardo_path'] = $request->file('resguardo_archivo')
                ->store('equipos-computo/resguardos', 'public');
        }

        DB::transaction(function () use ($equipo, $data, $anterior, $request) {
            $equipo->update($data);

            $movimiento = null;

            if (
                (int) ($anterior['responsable_actual_id'] ?? 0) !== (int) ($equipo->responsable_actual_id ?? 0)
                || (int) ($anterior['area_id'] ?? 0) !== (int) ($equipo->area_id ?? 0)
                || (string) ($anterior['ubicacion'] ?? '') !== (string) ($equipo->ubicacion ?? '')
                || (string) ($anterior['estatus'] ?? '') !== (string) ($equipo->estatus ?? '')
            ) {
                $movimiento = $this->registrarMovimiento($equipo, [
                    'tipo' => 'actualizacion',
                    'responsable_anterior_id' => $anterior['responsable_actual_id'],
                    'responsable_nuevo_id' => $equipo->responsable_actual_id,
                    'area_anterior_id' => $anterior['area_id'],
                    'area_nueva_id' => $equipo->area_id,
                    'ubicacion_anterior' => $anterior['ubicacion'],
                    'ubicacion_nueva' => $equipo->ubicacion,
                    'estatus_anterior' => $anterior['estatus'],
                    'estatus_nuevo' => $equipo->estatus,
                    'fecha_movimiento' => now()->toDateString(),
                    'notas' => $request->input('movimiento_notas') ?: 'Actualizacion de datos del equipo.',
                ]);
            } elseif ($request->hasFile('fotos')) {
                $movimiento = $this->registrarMovimiento($equipo, [
                    'tipo' => 'fotos',
                    'responsable_nuevo_id' => $equipo->responsable_actual_id,
                    'area_nueva_id' => $equipo->area_id,
                    'ubicacion_nueva' => $equipo->ubicacion,
                    'estatus_nuevo' => $equipo->estatus,
                    'fecha_movimiento' => now()->toDateString(),
                    'notas' => $request->input('movimiento_notas') ?: 'Evidencia fotografica del equipo.',
                ]);
            }

            $this->guardarFotos($request, $equipo, $movimiento);
        });

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'equipos_computo'])
            ->with('success', 'Equipo de computo actualizado.');
    }

    public function asignar(Request $request, EquipoComputo $equipo)
    {
        $data = $request->validate([
            'responsable_actual_id' => ['required', 'integer', 'exists:empleados,id_Empleado'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'ubicacion' => ['nullable', 'string', 'max:160'],
            'fecha_movimiento' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
            'resguardo_archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'fotos' => ['nullable', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $anterior = [
            'responsable_actual_id' => $equipo->responsable_actual_id,
            'area_id' => $equipo->area_id,
            'ubicacion' => $equipo->ubicacion,
            'estatus' => $equipo->estatus,
        ];

        DB::transaction(function () use ($equipo, $data, $anterior, $request) {
            $equipoUpdate = [
                'responsable_actual_id' => $data['responsable_actual_id'],
                'area_id' => $data['area_id'] ?? $equipo->area_id,
                'ubicacion' => $data['ubicacion'] ?? $equipo->ubicacion,
                'estatus' => 'asignado',
            ];

            if ($request->hasFile('resguardo_archivo')) {
                if ($equipo->resguardo_path && Storage::disk('public')->exists($equipo->resguardo_path)) {
                    Storage::disk('public')->delete($equipo->resguardo_path);
                }

                $equipoUpdate['resguardo_path'] = $request->file('resguardo_archivo')
                    ->store('equipos-computo/resguardos', 'public');
            }

            $equipo->update([
                ...$equipoUpdate,
            ]);

            $movimiento = $this->registrarMovimiento($equipo, [
                'tipo' => 'cambio_responsable',
                'responsable_anterior_id' => $anterior['responsable_actual_id'],
                'responsable_nuevo_id' => $equipo->responsable_actual_id,
                'area_anterior_id' => $anterior['area_id'],
                'area_nueva_id' => $equipo->area_id,
                'ubicacion_anterior' => $anterior['ubicacion'],
                'ubicacion_nueva' => $equipo->ubicacion,
                'estatus_anterior' => $anterior['estatus'],
                'estatus_nuevo' => $equipo->estatus,
                'fecha_movimiento' => $data['fecha_movimiento'],
                'notas' => $data['notas'] ?? null,
            ]);

            $this->guardarFotos($request, $equipo, $movimiento);
        });

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'equipos_computo'])
            ->with('success', 'Responsable del equipo actualizado.');
    }

    public function baja(Request $request, EquipoComputo $equipo)
    {
        $data = $request->validate([
            'fecha_movimiento' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $anterior = [
            'responsable_actual_id' => $equipo->responsable_actual_id,
            'area_id' => $equipo->area_id,
            'ubicacion' => $equipo->ubicacion,
            'estatus' => $equipo->estatus,
        ];

        DB::transaction(function () use ($equipo, $data, $anterior, $request) {
            $equipo->update([
                'responsable_actual_id' => null,
                'estatus' => 'baja',
            ]);

            $movimiento = $this->registrarMovimiento($equipo, [
                'tipo' => 'baja',
                'responsable_anterior_id' => $anterior['responsable_actual_id'],
                'responsable_nuevo_id' => null,
                'area_anterior_id' => $anterior['area_id'],
                'area_nueva_id' => $equipo->area_id,
                'ubicacion_anterior' => $anterior['ubicacion'],
                'ubicacion_nueva' => $equipo->ubicacion,
                'estatus_anterior' => $anterior['estatus'],
                'estatus_nuevo' => 'baja',
                'fecha_movimiento' => $data['fecha_movimiento'],
                'notas' => $data['notas'] ?? null,
            ]);

            $this->guardarFotos($request, $equipo, $movimiento);
        });

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'equipos_computo'])
            ->with('success', 'Equipo marcado como baja. No se elimino el registro.');
    }

    private function validatedData(Request $request, ?EquipoComputo $equipo = null): array
    {
        return $request->validate([
            'codigo_inventario' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('equipos_computo', 'codigo_inventario')->ignore($equipo?->id),
            ],
            'tipo' => ['required', 'string', Rule::in($this->tipos)],
            'marca' => ['required', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'numero_serie' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('equipos_computo', 'numero_serie')->ignore($equipo?->id),
            ],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'fecha_compra' => ['nullable', 'date'],
            'factura_folio' => ['nullable', 'string', 'max:120'],
            'factura_uuid' => ['nullable', 'string', 'max:80'],
            'factura_archivo' => ['nullable', 'file', 'mimes:pdf,xml,jpg,jpeg,png', 'max:10240'],
            'resguardo_archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'ubicacion' => ['nullable', 'string', 'max:160'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'responsable_actual_id' => ['nullable', 'integer', 'exists:empleados,id_Empleado'],
            'estatus' => ['nullable', 'string', Rule::in($this->estatus)],
            'notas' => ['nullable', 'string'],
            'movimiento_notas' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    public function buscarFacturas(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $term = trim($data['q']);

        $cfdis = SatCfdi::query()
            ->where(function ($query) use ($term) {
                $query->where('uuid', 'like', "%{$term}%")
                    ->orWhere('folio', 'like', "%{$term}%")
                    ->orWhere('serie', 'like', "%{$term}%")
                    ->orWhere('emisor_nombre', 'like', "%{$term}%")
                    ->orWhere('emisor_rfc', 'like', "%{$term}%")
                    ->orWhere('rfc_emisor', 'like', "%{$term}%");
            })
            ->orderByDesc('fecha_emision')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $cfdis->map(fn (SatCfdi $cfdi) => [
                'id' => $cfdi->id,
                'uuid' => $cfdi->uuid,
                'folio' => trim(($cfdi->serie ?? '') . ' ' . ($cfdi->folio ?? '')),
                'emisor' => $cfdi->emisor_nombre ?? $cfdi->rfc_emisor ?? $cfdi->emisor_rfc,
                'fecha' => optional($cfdi->fecha_emision)->format('d/m/Y'),
                'total' => (float) $cfdi->total,
            ])->values(),
        ]);
    }

    private function registrarMovimiento(EquipoComputo $equipo, array $data): EquipoComputoMovimiento
    {
        return EquipoComputoMovimiento::create(array_merge([
            'equipo_computo_id' => $equipo->id,
            'created_by' => auth()->id(),
        ], $data));
    }

    private function guardarFotos(Request $request, EquipoComputo $equipo, ?EquipoComputoMovimiento $movimiento = null): void
    {
        if (!$request->hasFile('fotos')) {
            return;
        }

        foreach ($request->file('fotos', []) as $foto) {
            $path = $foto->store('equipos-computo/fotos', 'public');

            EquipoComputoFoto::create([
                'equipo_computo_id' => $equipo->id,
                'equipo_computo_movimiento_id' => $movimiento?->id,
                'path' => $path,
                'original_name' => $foto->getClientOriginalName(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
