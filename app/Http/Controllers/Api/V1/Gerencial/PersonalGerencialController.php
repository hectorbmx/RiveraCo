<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Empleado; // AJUSTA: este es el modelo de tu tabla empleados
use App\Models\ObraEmpleado;
use Illuminate\Http\Request;

class PersonalGerencialController extends Controller
{
    public function index(Request $request)
    {
        $q = Empleado::query()
            ->select([
                'id_Empleado',
                'Nombre',
                'Apellidos',
                'Telefono',
                'Puesto',
                'Area',
                'Fecha_nacimiento',
                'Fecha_ingreso',
                'Fecha_baja',
                'Celular',
                'RFC',
                'Sueldo',
                'Sueldo_real',
                'Complemento',
                'foto',
                // agrega solo si existen y son útiles:
                // 'Email',
                // 'Estatus',
            ])
            ->with([
                'area:id,nombre' // 👈 ya no exponemos el ID crudo
            ])
            ->orderBy('Nombre');

        // 🔎 Búsqueda (nombre/apellidos/teléfono/clave)
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($x) use ($term) {
                $x->where('Nombre', 'like', "%{$term}%")
                  ->orWhere('Apellidos', 'like', "%{$term}%")
                  ->orWhere('Telefono', 'like', "%{$term}%");
                  // ->orWhere('Email', 'like', "%{$term}%");
            });
        }

        // 🎛️ Filtro: asignado a obra (opcional)
        if ($request->filled('obra_id')) {
            $obraId = (int) $request->obra_id;
            $q->whereIn('id_Empleado', function ($sub) use ($obraId) {
                $sub->from('obra_empleado')
                    ->select('empleado_id')
                    ->where('obra_id', $obraId)
                    ->where('activo', 1)
                    ->whereNull('fecha_baja');
            });
        }

        // 🎛️ Filtro: por rol/puesto en obra (rol_id) (opcional)
        if ($request->filled('rol_id')) {
            $rolId = (int) $request->rol_id;
            $q->whereIn('id_Empleado', function ($sub) use ($rolId) {
                $sub->from('obra_empleado')
                    ->select('empleado_id')
                    ->where('rol_id', $rolId)
                    ->where('activo', 1)
                    ->whereNull('fecha_baja');
            });
        }
        if ($request->filled('area_id')) {
            $q->where('Area', (int) $request->area_id); // FK en empleados
        }

        $perPage = min(max((int) $request->get('per_page', 25), 1), 50);
        $rows = $q->paginate($perPage)->withQueryString();

        return response()->json([
            'ok' => true,
            'data' => $rows->through(function ($e) {
                return [
                    'id_Empleado' => (int) $e->id_Empleado,
                    'nombre' => trim(($e->Nombre ?? '') . ' ' . ($e->Apellidos ?? '')),
                    // 'telefono' => $e->Telefono ?? null,
                    'puesto' =>$e->Puesto ?? null,
                    'Telefono' =>$e->Telefono ?? null,
                       'area' => $e->area ? [
                        'id' => (int) $e->area->id,
                        'nombre' => $e->area->nombre,
                    ] : null,
                    'Fecha_nacimiento'=>$e->Fecha_nacimiento ?? null,
                    'Fecha_ingreso'=>$e->Fecha_ingreso ?? null,
                    'Fecha_baja'=>$e->Fecha_baja ?? null,
                    'Celular'=>$e->Celular ?? null,
                    'RFC'=>$e->RFC ?? null,
                    'Sueldo'=>$e->Sueldo ?? null,
                    'Sueldo_real'=>$e->Sueldo_real ?? null,
                    'Complemento'=>$e->Complemento ?? null,
                    'foto'=>$e->foto ?? null
                ];
            }),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }
public function show(Request $request, Empleado $empleado)
{
    $empleado->load(['area:id,nombre']);

    return response()->json([
        'ok' => true,
        'data' => [
            'id_Empleado' => (int) $empleado->id_Empleado,
            'nombre' => trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? '')),
            'puesto' => $empleado->puesto ?? null,
            'telefono' => $empleado->Telefono ?? null,
            'celular' => $empleado->Celular ?? null,

            'area' => $empleado->area ? [
                'id' => (int) $empleado->area->id,
                'nombre' => $empleado->area->nombre,
            ] : null,

            'fecha_nacimiento' => optional($empleado->Fecha_nacimiento)->toDateString(),
            'fecha_ingreso' => optional($empleado->Fecha_ingreso)->toDateString(),
            'fecha_baja' => optional($empleado->Fecha_baja)->toDateString(),

            'rfc' => $empleado->RFC ?? null,
            'sueldo' => $empleado->Sueldo ?? null,
            'sueldo_real' => $empleado->Sueldo_real ?? null,
            'complemento' => $empleado->Complemento ?? null,
            'foto' => $empleado->foto ?? null,
        ],
    ]);
}


}
