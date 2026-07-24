<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteContacto;
use Illuminate\Http\Request;

class ClienteContactoController extends Controller
{
    public function store(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre'   => ['required', 'string', 'max:150'],
            'cargo'    => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ext'      => ['nullable', 'string', 'max:10'],
            'email'    => ['nullable', 'email', 'max:150'],
            'activo'   => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $cliente->contactos()->create($data);

        return redirect()
            ->route('clientes.edit', [$cliente, 'tab' => 'contactos'])
            ->with('success', 'Contacto agregado correctamente.');
    }

    public function update(Request $request, Cliente $cliente, ClienteContacto $contacto)
    {
        abort_unless($contacto->cliente_id === $cliente->id, 403);

        $data = $request->validate([
            'nombre'   => ['required', 'string', 'max:150'],
            'cargo'    => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ext'      => ['nullable', 'string', 'max:10'],
            'email'    => ['nullable', 'email', 'max:150'],
            'activo'   => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $contacto->update($data);

        return redirect()
            ->route('clientes.edit', [$cliente, 'tab' => 'contactos'])
            ->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Cliente $cliente, ClienteContacto $contacto)
    {
        abort_unless($contacto->cliente_id === $cliente->id, 403);

        $contacto->delete();

        return redirect()
            ->route('clientes.edit', [$cliente, 'tab' => 'contactos'])
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
