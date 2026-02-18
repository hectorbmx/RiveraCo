<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoNota;
use Illuminate\Http\Request;
use App\Services\Empleados\EmpleadoKardexService;

class EmpleadoController extends Controller
{
   public function index(Request $request)
{
    $search = $request->get('q');
    $estatus = $request->get('estatus'); // activo / baja / todos

    $empleados = Empleado::query()

        // BUSCADOR
        ->when($search, function ($q) use ($search) {
            $q->where(function($q) use ($search) {
                $q->where('Nombre', 'like', "%{$search}%")
                  ->orWhere('Apellidos', 'like', "%{$search}%")
                  ->orWhere('Puesto', 'like', "%{$search}%")
                  ->orWhere('Area', 'like', "%{$search}%");
            });
        })

        // FILTRO POR ESTATUS
        ->when($estatus === 'activo', function ($q) {
            $q->where('Estatus', '=', 1);
        })
        ->when($estatus === 'baja', function ($q) {
            $q->where('Estatus', '=', 2);
        })

        ->orderBy('Nombre')
        ->paginate(15)
        ->appends([
            'q' => $search,
            'estatus' => $estatus
        ]);

    return view('empleados.index', compact('empleados', 'search', 'estatus'));
}


    public function create()
    {
        return view('empleados.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, true);

        // Generar id_Empleado siguiente (porque la columna no es auto increment)
        $nextId = (Empleado::max('id_Empleado') ?? 0) + 1;
        $data['id_Empleado'] = $nextId;

        // Valor por defecto de estatus
        if (empty($data['Estatus'])) {
            $data['Estatus'] = 'ACTIVO';
        }

        Empleado::create($data);

        return redirect()
            ->route('empleados.index')
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

    if ($tab === 'nomina') {
        $empleado->load([
            'nominaRecibos.obra',
        ]);
    }

    if ($tab === 'kardex') {
        // ⚠️ Carga lo necesario para construir kardex
        $empleado->load([
            'nominaRecibos.obra',
            // si ya existe relación:
            // 'nominaRecibos.pagosExtra',
        ]);

        $kardex = app(EmpleadoKardexService::class)->build($empleado);
    }
            return view('empleados.edit', compact('empleado', 'tab','kardex'));
        }


    public function update(Request $request, Empleado $empleado)
    {
        $data = $this->validateData($request, false);

        $empleado->update($data);

        return redirect()
            ->route('empleados.edit', $empleado->id_Empleado)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    // Activar / dar de baja
    public function toggleStatus(Empleado $empleado)
    {
        if ($empleado->Estatus === 'BAJA') {
            $empleado->Estatus = 'ACTIVO';
            $empleado->Fecha_baja = null;
        } else {
            $empleado->Estatus = 'BAJA';
            $empleado->Fecha_baja = now()->toDateString();
        }

        $empleado->save();

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Estatus del empleado actualizado.');
    }

    /**
     * Validación centralizada
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
            'Area'             => ['nullable', 'string', 'max:50'],
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
            'Horassemana'      => ['nullable', 'string', 'max:50'],
            'infonavit'        => ['nullable', 'numeric'],

            'Estatus'          => ['nullable', 'string', 'max:50'],
            'Honorarios'       => ['nullable', 'string', 'max:50'],
            'Notas'            => ['nullable', 'string', 'max:200'],
            'foto'             => ['nullable', 'string', 'max:200'],
        ]);
    }
}
