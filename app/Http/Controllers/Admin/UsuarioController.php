<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UsuarioApp;

use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        //
            $usuarios = User::orderBy('name')->paginate(15);

            return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        //
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        //
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
