<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Area;
use App\Models\CatalogoRol;
use App\Models\EmpleadoNota;
use App\Models\EmpresaConfig;
use App\Models\EmpresaDocumentoTipo;
use App\Models\NominaListaRaya;
use Illuminate\Http\Request;
use App\Services\Empleados\EmpleadoKardexService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmpleadoController extends Controller
{


public function index(Request $request)
{
    $search  = $request->get('q');
    $estatus = $request->get('estatus', 'activo'); // activo / baja / todos
    $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
    $area    = $request->get('area');    // id de area
    $areaCodigo = $request->get('area_codigo');
    $documentosSort = $request->get('documentos_sort');
    $documentosSort = in_array($documentosSort, ['asc', 'desc'], true) ? $documentosSort : null;
    $perPage = (int) $request->get('per_page', 15);
    $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

    $areas = Area::query()
        ->where('activo', 1)
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'codigo']);
    if ($areaCodigo && !$area) {
        $area = Area::where('codigo', $areaCodigo)->value('id');
    }

    $empresa = EmpresaConfig::firstOrFail();

        $documentosObligatorios = EmpresaDocumentoTipo::query()
            ->where('empresa_config_id', $empresa->id)
            ->activos()
            ->aplicaAEmpleado()
            ->where('obligatorio', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    $documentosObligatoriosIds = $documentosObligatorios
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->toArray();

    $empleadosQuery = Empleado::with(['areaRef', 'documentos.documentoTipo'])  
        ->when($search, function ($q) use ($search) {
            $q->where(function($q) use ($search) {
                $q->where('Nombre', 'like', "%{$search}%")
                  ->orWhere('Apellidos', 'like', "%{$search}%")
                  ->orWhere('Puesto', 'like', "%{$search}%")
                  ->orWhereHas('areaRef', function ($areaQ) use ($search) {
                      $areaQ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                  });
            });
        })

        ->when($estatus === 'activo', function ($q) {
            $q->where('Estatus', 1);
        })
        ->when($estatus === 'baja', function ($q) {
            $q->where('Estatus', 2);
        })

        ->when($area, function ($q) use ($area) {
            $q->where('Area', $area);
        });

    if ($documentosSort && count($documentosObligatoriosIds) > 0) {
        $documentosIdsSql = implode(',', $documentosObligatoriosIds);

        $empleadosQuery->orderByRaw("(
            SELECT COUNT(DISTINCT ed.documento_tipo_id)
            FROM empleado_documentos ed
            WHERE ed.empleado_id = empleados.id_Empleado
              AND ed.deleted_at IS NULL
              AND ed.documento_tipo_id IN ({$documentosIdsSql})
        ) {$documentosSort}");
    }

    $empleados = $empleadosQuery
        ->orderByRaw('LOWER(TRIM(COALESCE(Apellidos, ""))) ASC')
        ->orderByRaw('LOWER(TRIM(COALESCE(Nombre, ""))) ASC')
        ->paginate($perPage)
        ->appends([
            'q' => $search,
            'estatus' => $estatus,
            'area' => $area,
            'documentos_sort' => $documentosSort,
        ]);

    return view('empleados.index', 
    compact(
        'empleados', 
        'search', 
        'estatus', 
        'areas', 
        'area',
        'documentosObligatorios',
        'documentosSort',
        'perPage',
        ));
}

public function create()
{
    $areas = Area::orderBy('nombre')->get();
    $roles = CatalogoRol::orderBy('nombre')->get();
    $listasRaya = NominaListaRaya::query()
        ->where('activo', true)
        ->where('es_automatica', false)
        ->orderBy('orden')
        ->orderBy('nombre')
        ->get();

    return view('empleados.create', compact('areas', 'roles', 'listasRaya'));
}

    // public function store(Request $request)
    // {
    //     $data = $this->validateData($request, true);

    //     // Generar id_Empleado siguiente (porque la columna no es auto increment)
    //     $nextId = (Empleado::max('id_Empleado') ?? 0) + 1;
    //     $data['id_Empleado'] = $nextId;

    //     // Valor por defecto de estatus
    //     if (empty($data['Estatus'])) {
    //         $data['Estatus'] = 'ACTIVO';
    //     }

    //     Empleado::create($data);

    //     return redirect()
    //         ->route('empleados.index')
    //         ->with('success', 'Empleado creado correctamente.');
    // }
public function store(Request $request)
{
    $data = $this->validateData($request, true);

    // âš ï¸ evita que se guarde el tmp path
    unset($data['foto']);

    $nextId = (Empleado::max('id_Empleado') ?? 0) + 1;
    $data['id_Empleado'] = $nextId;

    if (empty($data['Estatus'])) {
        $data['Estatus'] = 1;
    }

    // Normalizar textos
    if (!empty($data['Nombre'])) {
        $data['Nombre'] = trim($data['Nombre']);
    }

    if (!empty($data['Apellidos'])) {
        $data['Apellidos'] = trim($data['Apellidos']);
    }

    if (!empty($data['Puesto'])) {
        $data['Puesto'] = trim($data['Puesto']);
    }

    /*
    |--------------------------------------------------------------------------
    | puesto_base
    |--------------------------------------------------------------------------
    | Si no viene puesto_base lo tomamos del Puesto
    | y lo normalizamos en MAYÃšSCULAS
    */
    if (empty($data['puesto_base']) && !empty($data['Puesto'])) {
        $data['puesto_base'] = $data['Puesto'];
    }

    if (!empty($data['puesto_base'])) {
        $data['puesto_base'] = strtoupper(trim($data['puesto_base']));
    }

    DB::transaction(function () use ($request, &$data) {

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $ext = strtolower($request->file('foto')->getClientOriginalExtension() ?: 'jpg');
            $filename = 'empleado_' . $data['id_Empleado'] . '_' . Str::uuid() . '.' . $ext;

            $path = $request->file('foto')->storeAs('empleados', $filename, 'public');
            $data['foto'] = $path;
        }

        Empleado::create($data);
    });

    Artisan::call('telephony:index-phones');

    return redirect()->route('empleados.index')
        ->with('success', 'Empleado creado correctamente.');
}
    // public function edit(Empleado $empleado)
    // {
    //     return view('empleados.edit', compact('empleado'));
    // }
    public function edit(Request $request, Empleado $empleado)
{
    $tab = $request->query('tab', 'datos');

    if ($tab === 'notas') {
        $empleado->load('notas.autor');
    }

    if ($tab === 'emergencia') {
        $empleado->load('contactosEmergencia');
    }

    $kardex = collect();
    $documentos = collect();

    if ($tab === 'nomina') {
        $empleado->load([
            'nominaRecibos.obra',
        ]);
    }

    if ($tab === 'kardex') {
        $empleado->load([
            'nominaRecibos.obra',
        ]);

        $kardex = app(EmpleadoKardexService::class)->build($empleado);
    }
$kardex = collect();
$documentos = collect();
$documentosTipos = collect();

if ($tab === 'documentos') {
    $empleado->load([
        'documentos.documentoTipo',
    ]);

    $documentos = $empleado->documentos;

    $empresa = EmpresaConfig::firstOrFail();

    $documentosTipos = EmpresaDocumentoTipo::query()
        ->where('empresa_config_id', $empresa->id)
        ->activos()
        ->aplicaAEmpleado()
        ->ordenados()
        ->get();
}

if ($tab === 'epp') {
    $empleado->load(['eppEntregas.entregadoPor', 'eppEntregas.obra', 'eppEntregas.area']);
}

$obrasActivas = collect();
if ($tab === 'epp') {
    $obrasActivas = \App\Models\Obra::query()
        ->whereNotIn('estatus_nuevo', [\App\Models\Obra::ESTATUS_TERMINADA, \App\Models\Obra::ESTATUS_CANCELADA])
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'clave_obra', 'estatus_nuevo']);
}

    $areas = Area::where('activo', true)
        ->orderBy('nombre')
        ->get();

    $roles = \App\Models\CatalogoRol::orderBy('nombre')->get();
    $listasRaya = NominaListaRaya::query()
        ->where('activo', true)
        ->where('es_automatica', false)
        ->orderBy('orden')
        ->orderBy('nombre')
        ->get();

    return view('empleados.edit', compact(
        'empleado',
        'tab',
        'kardex',
        'documentos',
        'areas',
        'roles',
        'listasRaya',
        'documentosTipos',
        'obrasActivas'
    ));
}

    // public function update(Request $request, Empleado $empleado)
    // {
    //     $data = $this->validateData($request, false);

    //     $empleado->update($data);

    //     return redirect()
    //         ->route('empleados.edit', $empleado->id_Empleado)
    //         ->with('success', 'Empleado actualizado correctamente.');
    // }
 public function update(Request $request, Empleado $empleado)
{
    $data = $this->validateData($request, false);

    // âš ï¸ evita guardar tmp path si no procesas el archivo
    unset($data['foto']);

    // Normalizaciones
    if (array_key_exists('Estatus', $data) && $data['Estatus'] !== null && $data['Estatus'] !== '') {
        $data['Estatus'] = (int) $data['Estatus'];
    }

    if (!empty($data['puesto_base'])) {
        $data['puesto_base'] = strtoupper(trim($data['puesto_base']));
    }

    if (!empty($data['Puesto'])) {
        $data['Puesto'] = trim($data['Puesto']);
    }

    if (!empty($data['Nombre'])) {
        $data['Nombre'] = trim($data['Nombre']);
    }

    if (!empty($data['Apellidos'])) {
        $data['Apellidos'] = trim($data['Apellidos']);
    }

    DB::transaction(function () use ($request, $empleado, &$data) {

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {

            // borrar anterior (solo si era ruta de storage)
            if (!empty($empleado->foto) && str_starts_with($empleado->foto, 'empleados/')) {
                Storage::disk('public')->delete($empleado->foto);
            }

            $ext = strtolower($request->file('foto')->getClientOriginalExtension() ?: 'jpg');
            $filename = 'empleado_' . $empleado->id_Empleado . '_' . Str::uuid() . '.' . $ext;

            $path = $request->file('foto')->storeAs('empleados', $filename, 'public');
            $data['foto'] = $path;
        }

        $empleado->update($data);
    });

    Artisan::call('telephony:index-phones');

    return redirect()->route('empleados.edit', $empleado->id_Empleado)
        ->with('success', 'Empleado actualizado correctamente.');
}

    // Activar / dar de baja
  public function toggleStatus(Empleado $empleado)
{
    if ((int)$empleado->Estatus === 2) {
        $empleado->Estatus = 1;          // Activo
        $empleado->Fecha_baja = null;
    } else {
        $empleado->Estatus = 2;          // Baja
        $empleado->Fecha_baja = now()->toDateString();
    }

    $empleado->save();

    Artisan::call('telephony:index-phones');

    return redirect()
        ->route('empleados.index')
        ->with('success', 'Estatus del empleado actualizado.');
}


    /**
     * ValidaciÃ³n centralizada
     */
    protected function validateData(Request $request, bool $isCreate = true): array
    {
        return $request->validate([
            'Nombre'           => ['required', 'string', 'max:100'],
            'Apellidos'        => ['required', 'string', 'max:100'],
            'Email'            => ['nullable', 'email', 'max:50'],
            'Fecha_nacimiento' => ['nullable', 'date'],
            'Fecha_ingreso'    => ['nullable', 'date'],
            'Fecha_baja'       => ['nullable', 'date'],
            // 'Area'             => ['nullable', 'string', 'max:50'],
            'Area' => ['nullable', 'integer', 'exists:areas,id'],
            'Puesto'           => ['nullable', 'string', 'max:50'],
            'Telefono'         => ['nullable', 'string', 'max:50'],
            'Celular'          => ['nullable', 'string', 'max:50'],
            'Direccion'        => ['nullable', 'string', 'max:100'],
            'Colonia'          => ['nullable', 'string', 'max:100'],
            'Ciudad'           => ['nullable', 'string', 'max:100'],
            'CP'               => ['nullable', 'string', 'max:50'],
            'RFC'              => ['nullable', 'string', 'max:50'],
            'CURP'             => ['nullable', 'string', 'max:50'],
            'IMSS'             => ['nullable', 'string', 'max:50'],
            'Sangre'           => ['nullable', 'string', 'max:50'],
            'Cuenta_banco'     => ['nullable', 'string', 'max:50'],

            'Sueldo'           => ['nullable', 'numeric'],
            'Sueldo_real'      => ['nullable', 'numeric'],
            'Complemento'      => ['nullable', 'numeric'],
            'Sueldo_tipo'      => ['nullable', 'integer'],
            'listaraya'        => ['nullable', 'integer'],
            'lista_raya_principal_id' => ['nullable', 'integer', 'exists:nomina_listas_raya,id'],
            'Horassemana'      => ['nullable', 'string', 'max:50'],
            'infonavit'        => ['nullable', 'numeric'],

            'Estatus'          => ['nullable', 'string', 'max:50'],
            'Honorarios'       => ['nullable', 'string', 'max:50'],
            'Notas'            => ['nullable', 'string', 'max:200'],
            // 'foto'             => ['nullable', 'string', 'max:200'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }
    public function export(Request $request)
    {
        $search  = $request->get('q');
        $estatus = $request->get('estatus', 'activo');
        $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
        $area    = $request->get('area');
        $areaCodigo = $request->get('area_codigo');

        if ($areaCodigo && !$area) {
            $area = Area::where('codigo', $areaCodigo)->value('id');
        }

        $query = Empleado::with(['areaRef'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('Nombre', 'like', "%{$search}%")
                        ->orWhere('Apellidos', 'like', "%{$search}%")
                        ->orWhere('Puesto', 'like', "%{$search}%")
                        ->orWhereHas('areaRef', function ($areaQ) use ($search) {
                            $areaQ->where('nombre', 'like', "%{$search}%")
                                ->orWhere('codigo', 'like', "%{$search}%");
                        });
                });
            })
            ->when($estatus === 'activo', fn ($q) => $q->where('Estatus', 1))
            ->when($estatus === 'baja', fn ($q) => $q->where('Estatus', 2))
            ->when($area, fn ($q) => $q->where('Area', $area))
            ->orderByRaw('LOWER(TRIM(COALESCE(Apellidos, ""))) ASC')
            ->orderByRaw('LOWER(TRIM(COALESCE(Nombre, ""))) ASC');

        $fileName = 'empleados_' . now()->format('Y_m_d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        Log::info('Iniciando exportacion de empleados', [
            'file' => $fileName,
            'filters' => [
                'q' => $search,
                'estatus' => $estatus,
                'area' => $area,
                'area_codigo' => $areaCodigo,
            ],
        ]);

        return response()->streamDownload(function () use ($query, $fileName) {
            try {
                Log::info('Abriendo stream de exportacion de empleados', [
                    'file' => $fileName,
                ]);

                $handle = fopen('php://output', 'w');

                if ($handle === false) {
                    throw new \RuntimeException('No se pudo abrir php://output para exportar empleados.');
                }

                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'Empleado',
                    'Area',
                    'Puesto',
                    'Sueldo',
                    'Estatus',
                ]);

                $exportados = 0;

                $query->chunk(500, function ($empleados) use ($handle, &$exportados) {
                    foreach ($empleados as $emp) {
                        fputcsv($handle, [
                            trim(($emp->Apellidos ?? '') . ' ' . ($emp->Nombre ?? '')),
                            $emp->areaRef->nombre ?? '-',
                            $emp->Puesto ?? '-',
                            $emp->Sueldo_real ?? $emp->Sueldo ?? 0,
                            (int) $emp->Estatus === 2 ? 'Baja' : 'Activo',
                        ]);

                        $exportados++;
                    }
                });

                fclose($handle);

                Log::info('Exportacion de empleados finalizada', [
                    'file' => $fileName,
                    'registros' => $exportados,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error durante exportacion de empleados', [
                    'file' => $fileName,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }, $fileName, $headers);
    }
}




