<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $clientes = Cliente::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_comercial', 'like', "%{$search}%")
                      ->orWhere('razon_social', 'like', "%{$search}%")
                      ->orWhere('rfc', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre_comercial')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'search'));
    }

    public function checkDuplicate(Request $request)
    {
        $rfc = $request->query('rfc');
        $razon_social = $request->query('razon_social');
        
        $query = Cliente::query();
        
        if ($rfc) {
            $query->where('rfc', $rfc);
        } elseif ($razon_social) {
            $query->where('razon_social', 'like', "%{$razon_social}%")
                  ->orWhere('nombre_comercial', 'like', "%{$razon_social}%");
        } else {
            return response()->json(['matches' => []]);
        }

        $matches = $query->limit(5)->get(['id', 'nombre_comercial', 'razon_social', 'rfc']);
        
        return response()->json(['matches' => $matches]);
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

        // SAT / CFDI
        'codigo_postal'    => ['nullable', 'string', 'max:10'],
        'regimen_fiscal'   => ['nullable', 'string', 'max:10'],
        'uso_cfdi_default' => ['nullable', 'string', 'max:10'],

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
        // Buscamos en ambas tablas usando el RFC del cliente (si lo tiene)
        if ($cliente->rfc) {
            $rfc = $cliente->rfc;

            $deSatFactura = \App\Models\SatFactura::where(function($q) use ($rfc, $cliente) {
                    $q->where('receptor_rfc', $rfc)
                      ->orWhere('cliente_id', $cliente->id);
                })
                ->orderByDesc('fecha_emision')
                ->get()
                ->map(function ($f) {
                    return [
                        'origen'        => 'FacturAPI',
                        'fecha'         => optional($f->fecha_emision)->format('Y-m-d'),
                        'serie_folio'   => trim($f->serie . ' ' . $f->folio),
                        'uuid'          => $f->uuid,
                        'moneda'        => $f->moneda,
                        'total'         => (float) $f->total,
                        'estado'        => $f->estado,
                        'obra_id'       => $f->obra_id,
                        'pdf_path'      => $f->pdf_path,
                    ];
                });

            $deSatCfdi = \App\Models\SatCfdi::where('receptor_rfc', $rfc)
                ->where('emisor_rfc', 'RCO820921T66')
                ->orderByDesc('fecha_emision')
                ->get()
                ->map(function ($c) {
                    return [
                        'origen'        => 'SAT',
                        'fecha'         => optional($c->fecha_emision)->format('Y-m-d'),
                        'serie_folio'   => trim($c->serie . ' ' . $c->folio),
                        'uuid'          => $c->uuid,
                        'moneda'        => $c->moneda,
                        'total'         => (float) $c->total,
                        'estado'        => null,
                        'obra_id'       => $c->obra_id,
                        'pdf_path'      => null,
                    ];
                });

            // Unir, deduplicar por UUID (FacturAPI tiene prioridad), ordenar por fecha
            $seenUuids = [];
            $merged = collect();
            foreach ($deSatFactura as $item) {
                if ($item['uuid'] && !in_array(strtoupper($item['uuid']), $seenUuids)) {
                    $merged->push($item);
                    $seenUuids[] = strtoupper($item['uuid']);
                }
            }
            foreach ($deSatCfdi as $item) {
                if ($item['uuid'] && !in_array(strtoupper($item['uuid']), $seenUuids)) {
                    $merged->push($item);
                    $seenUuids[] = strtoupper($item['uuid']);
                }
            }

            $facturas = $merged->sortByDesc('fecha')->values();
        } else {
            $facturas = collect();
        }
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

        // SAT / CFDI
        'codigo_postal'    => ['nullable', 'string', 'max:10'],
        'regimen_fiscal'   => ['nullable', 'string', 'max:10'],
        'uso_cfdi_default' => ['nullable', 'string', 'max:10'],

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