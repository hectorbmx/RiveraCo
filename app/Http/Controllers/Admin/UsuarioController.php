<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Empleado;
use App\Models\UsuarioApp;
use App\Models\OrdenCompra;
use App\Models\ObraSolicitudGasto;
use App\Models\ObraReposicionGasto;
use App\Models\InventarioMovimiento;
use App\Models\ObraAsistencia;
use App\Models\MaquinaMovimiento;
use App\Models\EmpleadoNota;
use App\Models\Comision;
use App\Models\ComisionEtapa;

class UsuarioController extends Controller
{
    public function index()
    {
        //
            $usuarios = User::orderBy('name')->paginate(15);

            return view('usuarios.index', compact('usuarios'));
    }

    // public function create()
    // {
    //     //
    //     return view('usuarios.create');
    // }
    public function create()
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id','name']);

        return view('usuarios.create', compact('roles'));
    }
    //buscador de empleados para crear usuarios
    public function searchEmpleados(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $rows = Empleado::query()
            ->select([
                'id_Empleado',
                'Nombre',
                'Apellidos',
                'Email',
                'Puesto',
                'Telefono',
                'Celular',
            ])
            ->where(function ($qq) use ($q) {
                $qq->where('Nombre', 'like', "%{$q}%")
                   ->orWhere('Apellidos', 'like', "%{$q}%")
                   ->orWhere(DB::raw("CONCAT(Nombre,' ',Apellidos)"), 'like', "%{$q}%")
                   ->orWhere('Email', 'like', "%{$q}%")
                   ->orWhere('id_Empleado', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get();

        $data = $rows->map(fn ($e) => [
            'id'        => (int) $e->id_Empleado,
            'nombre'    => trim(($e->Nombre ?? '').' '.($e->Apellidos ?? '')),
            'email'     => (string) ($e->Email ?? ''),
            'puesto'    => (string) ($e->Puesto ?? ''),
            'telefono'  => (string) ($e->Telefono ?? ''),
            'celular'   => (string) ($e->Celular ?? ''),
        ]);

        return response()->json(['data' => $data]);
    }


 public function store(Request $request)
    {
        $data = $request->validate([
            'empleado_id'           => ['required','integer'],
            'email'                 => ['required','email','max:190'],
            'password'              => ['required','string','min:8','confirmed'],
            'role'                  => ['required','string'],
            'is_active'             => ['nullable','boolean'],
        ]);

        $empleado = Empleado::query()->findOrFail($data['empleado_id']);

        // Reglas para evitar duplicados (muy importante)
        // 1) un empleado solo puede tener 1 usuario_app
        if (UsuarioApp::where('empleado_id', $empleado->id_Empleado)->exists()) {
            return back()->withInput()->withErrors([
                'empleado_id' => 'Este empleado ya tiene un usuario app ligado.',
            ]);
        }

        // 2) email único en users
        if (User::where('email', $data['email'])->exists()) {
            return back()->withInput()->withErrors([
                'email' => 'El email ya está registrado en el sistema.',
            ]);
        }

        $roleName = $data['role'];
        $isActive = (bool) ($data['is_active'] ?? true);

        DB::transaction(function () use ($data, $empleado, $roleName, $isActive) {

            $user = User::create([
                'name'     => trim(($empleado->Nombre ?? '').' '.($empleado->Apellidos ?? '')),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Spatie: asignar rol
            $user->assignRole($roleName);

            // usuarios_app: puedes guardar hash también (o dejar null si no lo usas)
            UsuarioApp::create([
                'user_id'      => $user->id,
                'empleado_id'  => (int) $empleado->id_Empleado,
                'password'     => $user->password, // mismo hash
                'is_active'    => $isActive ? 1 : 0,
                'activation_token' => null,
                'activated_at' => $isActive ? now() : null,
            ]);
        });

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }


public function edit(User $usuario)
{
    $usuario->load(['usuarioApp.empleado']);

    // 1. Autorizaciones
    $autorizaciones = collect();

    // OCs autorizadas
    $ocs_auth = OrdenCompra::where('usuario_autoriza', $usuario->name)
        ->latest('fecha_autorizacion')
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Orden de Compra',
            'referencia' => $item->folio,
            'fecha' => $item->fecha_autorizacion,
            'monto' => $item->total,
            'status' => $item->estado
        ]);
    $autorizaciones = $autorizaciones->concat($ocs_auth);

    // Solicitudes Gasto autorizadas
    $gastos_auth = ObraSolicitudGasto::where('autorizado_por', $usuario->id)
        ->latest('autorizado_at')
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Solicitud Gasto',
            'referencia' => "Semana {$item->semana} - {$item->obra?->nombre}",
            'fecha' => $item->autorizado_at,
            'monto' => $item->total,
            'status' => $item->estatus
        ]);
    $autorizaciones = $autorizaciones->concat($gastos_auth);

    // Reposiciones aprobadas
    $repos_auth = ObraReposicionGasto::where('aprobado_por', $usuario->id)
        ->latest('aprobado_at')
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Reposición Gasto',
            'referencia' => "Semana {$item->semana} - {$item->obra?->nombre}",
            'fecha' => $item->aprobado_at,
            'monto' => $item->total,
            'status' => $item->estatus
        ]);
    $autorizaciones = $autorizaciones->concat($repos_auth);

    // 2. Compras y Gastos (Creados por él)
    $comprasGastos = collect();

    $ocs_created = OrdenCompra::where('usuario_registro', $usuario->name)
        ->latest()
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Orden de Compra',
            'referencia' => $item->folio,
            'fecha' => $item->fecha,
            'monto' => $item->total,
            'status' => $item->estado
        ]);
    $comprasGastos = $comprasGastos->concat($ocs_created);

    $gastos_created = ObraSolicitudGasto::where('solicitado_por', $usuario->id)
        ->latest('solicitado_at')
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Solicitud Gasto',
            'referencia' => "Semana {$item->semana}",
            'fecha' => $item->solicitado_at,
            'monto' => $item->total,
            'status' => $item->estatus
        ]);
    $comprasGastos = $comprasGastos->concat($gastos_created);

    $repos_created = ObraReposicionGasto::where('solicitado_por', $usuario->id)
        ->latest('solicitado_at')
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Reposición Gasto',
            'referencia' => "Semana {$item->semana}",
            'fecha' => $item->solicitado_at,
            'monto' => $item->total,
            'status' => $item->estatus
        ]);
    $comprasGastos = $comprasGastos->concat($repos_created);

    // 3. Operaciones
    $operaciones = collect();

    $maquina_movs = MaquinaMovimiento::where('user_id', $usuario->id)
        ->with(['maquina', 'obra'])
        ->latest()
        ->limit(10)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Maquinaria',
            'referencia' => "Movimiento: " . ($item->maquina?->economico ?? 'Máquina'),
            'fecha' => $item->created_at,
            'detalle' => "Hacia: " . ($item->obra?->nombre ?? 'N/A'),
        ]);
    $operaciones = $operaciones->concat($maquina_movs);

    $inv_movs = InventarioMovimiento::where('creado_por', $usuario->id)
        ->with(['producto', 'almacen'])
        ->latest()
        ->limit(15)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Inventario',
            'referencia' => ($item->tipo_movimiento == 'in' ? 'Entrada: ' : 'Salida: ') . ($item->producto?->nombre ?? 'Producto'),
            'fecha' => $item->fecha,
            'detalle' => "{$item->cantidad} unidades en {$item->almacen?->nombre}",
        ]);
    $operaciones = $operaciones->concat($inv_movs);

    $asistencias = ObraAsistencia::where('registrado_por_user_id', $usuario->id)
        ->with('empleado')
        ->latest('checked_at')
        ->limit(15)
        ->get()
        ->map(fn($item) => [
            'tipo' => 'Asistencia',
            'referencia' => "Pase de lista: " . ($item->empleado?->Nombre ?? 'Empleado'),
            'fecha' => $item->checked_at,
            'detalle' => "Tipo: {$item->tipo}",
        ]);
    $operaciones = $operaciones->concat($asistencias);

    // 4. Bitácora (Combinada: Notas + Pasos de Pilas)
    $notas = EmpleadoNota::where('user_id', $usuario->id)
        ->with('empleado')
        ->latest()
        ->limit(20)
        ->get()
        ->map(fn($n) => (object)[
            'tipo' => 'nota',
            'titulo' => "Nota sobre: " . ($n->empleado?->Nombre ?? 'Empleado'),
            'contenido' => $n->nota,
            'fecha' => $n->created_at,
            'color' => 'blue'
        ]);

    $pasos = ComisionEtapa::where('updated_by', $usuario->id)
        ->with(['obra', 'pila'])
        ->latest('updated_at')
        ->limit(20)
        ->get()
        ->map(fn($p) => (object)[
            'tipo' => 'pila',
            'titulo' => "Paso: " . ucfirst($p->etapa) . " - Pila: " . ($p->pila?->numero_pila ?? 'N/A'),
            'contenido' => "Obra: " . ($p->obra?->nombre ?? 'N/A') . ". Estado: " . ucfirst($p->estado),
            'fecha' => $p->updated_at,
            'color' => 'green'
        ]);

    $bitacora = $notas->concat($pasos)->sortByDesc('fecha')->take(20);

    // 5. Pilas (Comisiones) - Búsqueda más inclusiva
    $empleado_id = $usuario->usuarioApp?->empleado_id;

    $pilas = Comision::query()
        ->where(function($q) use ($usuario, $empleado_id) {
            $q->where('created_by', $usuario->id)
              ->orWhere('updated_by', $usuario->id);
            if ($empleado_id) {
                $q->orWhere('residente_id', $empleado_id);
            }
        })
        ->orWhereIn('id', function($q) use ($usuario) {
            $q->select('comision_id')
              ->from('comision_etapas')
              ->where('updated_by', $usuario->id)
              ->orWhere('created_by', $usuario->id);
        })
        ->with(['obra', 'pila'])
        ->latest('updated_at')
        ->limit(20)
        ->get()
        ->map(fn($item) => [
            'obra' => $item->obra?->nombre ?? 'N/A',
            'pila' => $item->pila?->numero_pila ?? 'N/A',
            'fecha' => $item->fecha,
            'folio' => $item->numero_formato,
            'estado' => $item->estado
        ]);

    return view('usuarios.edit', compact(
        'usuario', 
        'autorizaciones', 
        'comprasGastos', 
        'operaciones', 
        'bitacora',
        'pilas'
    ));
}

  public function update(Request $request, User $usuario)
{
    $data = $request->validate([
        'name' => ['required','string','max:255'],
        'email' => ['required','email','max:255'],

        // Password opcional (si viene, se actualiza en users)
        'password' => ['nullable','string','min:8','confirmed'],

        // Estado app (si lo estás mostrando)
        'is_active' => ['nullable','boolean'],
    ]);

    $usuario->name  = $data['name'];
    $usuario->email = $data['email'];

    if (!empty($data['password'])) {
        $usuario->password = Hash::make($data['password']);
    }

    $usuario->save();

    // Actualiza usuarios_app por user_id (si existe y si mandaste is_active)
    if (array_key_exists('is_active', $data)) {
        UsuarioApp::where('user_id', $usuario->id)->update([
            'is_active' => (bool) $data['is_active'],
        ]);
    }

    return redirect()
        ->route('usuarios.index')
        ->with('success', 'Usuario actualizado.');
}
}
