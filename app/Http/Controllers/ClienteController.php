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
            'rfc'              => ['nullable', 'string', 'max:13', 'unique:clientes,rfc'],
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

    // public function edit(Cliente $cliente)
    // {
    //     return view('clientes.edit', compact('cliente'));
    // }
    public function edit(Request $request, Cliente $cliente)
{
    $tab = $request->query('tab', 'general');

    $obras = null;
    $facturas = null;
    $pagos = null; // placeholder
    $contactos = null;
    $documentos = null;
    $notas = null;

    if ($tab === 'obras') {
        $obras = $cliente->obras()
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    if ($tab === 'facturas') {
        // placeholder hasta que exista relación/modelo
        // $facturas = $cliente->facturas()->latest()->paginate(10)->withQueryString();
    }

      if ($tab === 'facturas' && $cliente->rfc) {
        $facturas = $cliente->facturas()
            ->orderByDesc('fecha_emision')
            ->paginate(15)
            ->withQueryString();
    }


    if ($tab === 'contactos') {
        // $contactos = $cliente->contactos()->orderBy('nombre')->paginate(10)->withQueryString();
    }

    if ($tab === 'documentos') {
        // $documentos = $cliente->documentos()->latest()->paginate(10)->withQueryString();
    }

    if ($tab === 'notas') {
        // $notas = $cliente->notas()->with('autor')->latest()->paginate(10)->withQueryString();
    }

    return view('clientes.edit', compact(
        'cliente','tab','obras','facturas','pagos','contactos','documentos','notas'
    ));
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
            'calle'            => ['nullable', 'string', 'max:150'],
            'colonia'          => ['nullable', 'string', 'max:150'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
            'estado'           => ['nullable', 'string', 'max:100'],
            'pais'             => ['nullable', 'string', 'max:100'],

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