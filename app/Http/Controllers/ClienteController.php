<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('nombre_comercial')->paginate(10);

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social'     => ['nullable', 'string', 'max:255'],
            'rfc'              => ['nullable', 'string', 'max:13', 'unique:clientes,rfc' . ($cliente->id ?? '')],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'calle'            => ['nullable', 'string', 'max:150'],
            'colonia'          => ['nullable', 'string', 'max:150'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
            'estado'           => ['nullable', 'string', 'max:100'],
            'pais'             => ['nullable', 'string', 'max:100'],
            'activo'           => ['nullable', 'boolean'],
        ]);


        $data['activo'] = $request->boolean('activo', true);

        Cliente::create($data);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social'     => ['nullable', 'string', 'max:255'],
            'rfc'              => ['nullable', 'string', 'max:13', 'unique:clientes,rfc,' . $cliente->id],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'activo'           => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $cliente->update($data);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
