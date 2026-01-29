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

    return view('usuarios.edit', compact('usuario'));
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
