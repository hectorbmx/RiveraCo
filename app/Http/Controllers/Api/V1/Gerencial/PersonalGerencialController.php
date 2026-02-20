<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Empleado; // AJUSTA: este es el modelo de tu tabla empleados
use App\Models\ObraEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class PersonalGerencialController extends Controller
{
public function index(Request $request)
{
    $q = Empleado::query()
        ->select([
            'empleados.id_Empleado', // Especificar tabla para evitar ambigüedad
            'empleados.Nombre',
            'empleados.Apellidos',
            'empleados.Telefono',
            'empleados.Celular',
            'empleados.Puesto',
            'empleados.Area',
            'empleados.Estatus',
            'empleados.foto',
            'empleados.Fecha_ingreso',
            'empleados.Fecha_baja',
            'empleados.Sueldo',
            'empleados.Complemento',
            'empleados.Sueldo_real',
        ])
        // Cargamos la relación de la obra activa para saber SIEMPRE en qué obra está
        ->with(['area:id,nombre', 'obraActiva' => function($query) {
            $query->select('obras.id', 'obras.nombre','obras.clave_obra'); // Ajusta según tus columnas de Obra
        }])
        ->orderBy('Nombre');

    // 🔎 Búsqueda
    if ($request->filled('q')) {
        $term = trim((string) $request->q);
        $q->where(function ($x) use ($term) {
            $x->where('Nombre', 'like', "%{$term}%")
              ->orWhere('Apellidos', 'like', "%{$term}%")
              ->orWhere('Telefono', 'like', "%{$term}%")
              ->orWhere('Celular', 'like', "%{$term}%")
              ->orWhere('RFC', 'like', "%{$term}%");

            if (ctype_digit($term)) {
                $x->orWhere('id_Empleado', (int) $term);
            }
        });
    }

$status = $request->get('status'); // default null => TODOS

if ($status === null || $status === '' || $status === 'todos') {
    // no filtrar
} elseif ($status === 'activos' || (string)$status === '1') {
    $q->where('empleados.Estatus', 1);
} elseif ($status === 'baja' || (string)$status === '2') {
    $q->where('empleados.Estatus', 2);
}
// si viene null / 'todos' => no filtra

    // 🎛️ Filtro: área
    if ($request->filled('area_id')) {
        $q->where('Area', (int) $request->area_id);
    }

    // 🎛️ Filtro y Flag de Obra Específica
    if ($request->filled('obra_id')) {
        $obraId = (int) $request->obra_id;

        // Inyectamos un booleano para saber si pertenece a la obra solicitada en el filtro
        $q->addSelect([
            'es_de_esta_obra' => ObraEmpleado::query()
                ->selectRaw('count(*)')
                ->whereColumn('empleado_id', 'empleados.id_Empleado')
                ->where('obra_id', $obraId)
                ->where('activo', 1)
                ->limit(1)
        ]);

        if ($request->boolean('solo_asignados', true)) {
            $q->whereHas('obraActiva', function($sub) use ($obraId) {
                $sub->where('obras.id', $obraId);
            });
        }
    }

    $perPage = min(max((int) $request->get('per_page', 25), 1), 50);
    $rows = $q->paginate($perPage);

    return response()->json([
        'ok' => true,
        'data' => $rows->getCollection()->map(function ($e) {
            
            // Lógica de Foto (simplificada)
          // En PersonalGerencialController.php
            $fotoUrl = null;
            if ($e->foto) {
                if (str_starts_with($e->foto, 'http')) {
                    $fotoUrl = $e->foto;
                } else {
                    // Limpiamos rutas relativas viejas como ../img/foto.jpg
                    $path = str_replace('../', '', $e->foto);
                    
                    // Verificamos si el archivo existe físicamente en el disco público
                    if (Storage::disk('public')->exists($path)) {
                        $fotoUrl = asset('storage/' . ltrim($path, '/'));
                    } else {
                        // Si no existe en public, quizás está en una carpeta img directa de public
                        $fotoUrl = asset(ltrim($path, '/'));
                    }
                }
            }

            // Obtenemos la obra activa (si tiene)
            $obraActual = $e->obraActiva->first();

            return [
                'id' => (int) $e->id_Empleado,
                'nombre_completo' => $e->nombre_completo,
                'puesto' => $e->Puesto,
                'sueldo' => $e->Sueldo,
                'sueldo_real' => $e->Sueldo_real,
                'complemento' => $e->Complemento,
                'estatus' => (int) $e->Estatus,
                'area' => $e->area ? ['id' => $e->area->id, 'nombre' => $e->area->nombre] : null,
                'foto_url' => $fotoUrl,
                
                // Información de asignación
                'esta_asignado' => $e->obraActiva->isNotEmpty(),
                'obra_actual' => $obraActual ? [
                    'id' => $obraActual->id,
                    'nombre' => $obraActual->nombre,
                    'clave_obra' => $obraActual->clave_obra,
                    'puesto_en_obra' => $obraActual->pivot->puesto_en_obra ?? null
                ] : null,

                // Si se filtró por obra_id, esto dirá si pertenece específicamente a esa
                'es_de_esta_obra' => isset($e->es_de_esta_obra) ? (bool)$e->es_de_esta_obra : null,
            ];
        }),
        'meta' => [
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows ->perPage(),
            'total' => $rows->total(),
        ],
    ]);
}
//mostar detalle de un empleado
public function show(Empleado $empleado)
{
    // Cargamos las relaciones necesarias para el detalle
    $empleado->load([
        'area:id,nombre', 
        'obraActiva.cliente', // Por si quieres mostrar el cliente de la obra
    ]);

    // Procesamos la foto igual que en el index
    $fotoUrl = null;
    if ($empleado->foto) {
        $path = str_replace('../', '', $empleado->foto);
        $fotoUrl = str_starts_with($empleado->foto, 'http') 
            ? $empleado->foto 
            : asset('storage/' . ltrim($path, '/'));
    }

    return response()->json([
        'ok' => true,
        'data' => [
            'id' => (int) $empleado->id_Empleado,
            'nombre_completo' => $empleado->nombre_completo,
            'puesto' => $empleado->Puesto,
            'sueldo' => $empleado->Sueldo,
            'complemento' => $empleado->Complemento,
            'sueldo_real' => $empleado->Sueldo_real,
            'telefono' => $empleado->Telefono,
            'celular' => $empleado->Celular,
            'email' => $empleado->Email, // Asumiendo que existe
            'nss' => $empleado->IMSS,      // Datos sensibles que solo van en detalle
            'curp' => $empleado->CURP,
            'rfc' => $empleado->RFC,
            'fecha_ingreso' => $empleado->Fecha_ingreso,
            'estatus' => (int) $empleado->Estatus,
            'foto_url' => $fotoUrl,
            'area' => $empleado->area,
            'obra_actual' => $empleado->obraActiva->first() ? [
                'id' => $empleado->obraActiva->first()->id,
                'nombre' => $empleado->obraActiva->first()->nombre,
                'clave_obra' => $empleado->obraActiva->first()->clave_obra,
                'puesto_en_obra' => $empleado->obraActiva->first()->pivot->puesto_en_obra ?? null,
                'fecha_asignacion' => $empleado->obraActiva->first()->pivot->created_at ?? null,
            ] : null,
        ]
    ]);
}
}
