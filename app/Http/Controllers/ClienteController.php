<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EmpresaDocumentoTipo;
use App\Models\PhoneCall;
use App\Models\TelephonyPhoneNumber;
use App\Rules\ValidMexicanPhone;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $activo = $request->query('activo', 'todos');
        $perPageOpciones = [10, 20, 50, 100];
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, $perPageOpciones, true)) {
            $perPage = 10;
        }


        $documentosObligatoriosIds = EmpresaDocumentoTipo::query()
            ->activos()
            ->aplicaACliente()
            ->where('obligatorio', true)
            ->pluck('id');

        $totalDocumentosObligatoriosCliente = $documentosObligatoriosIds->count();

        $clientes = Cliente::query()
            ->withCount([
                'documentos as documentos_obligatorios_cargados_count' => function ($query) use ($documentosObligatoriosIds) {
                    $query->where('vigente', true)
                        ->whereIn('documento_tipo_id', $documentosObligatoriosIds);
                },
            ])->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_comercial', 'like', "%{$search}%")
                      ->orWhere('razon_social', 'like', "%{$search}%")
                      ->orWhere('rfc', 'like', "%{$search}%");
                });
            })
            ->when($activo !== 'todos', function ($query) use ($activo) {
                $query->where('activo', (int) $activo);
            })
            ->orderBy('nombre_comercial')
            ->paginate($perPage)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'search', 'activo', 'perPage', 'perPageOpciones', 'totalDocumentosObligatoriosCliente'));
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
            'telefono'         => ['nullable', 'string', 'max:20', new ValidMexicanPhone()],
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

        Artisan::call('telephony:index-phones');

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Request $request, Cliente $cliente)
    {
        $tab = $request->query('tab', 'general');

        $obras = null;
        $facturas = null;
        $pagos = null; // placeholder
        $contactos = null;
        $documentos = collect();
        $documentosTipos = collect();
        $canUploadClienteDocumentos = false;
        $canDeleteClienteDocumentos = false;
        $notas = null;
        $llamadasSeguimiento = null;
        $portales = collect();
        $telefonosSeguimiento = collect();
        $extensionTelefoniaActual = null;

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
            $contactos = $cliente->contactos()->orderBy('nombre')->get();
        }

        if ($tab === 'docs') {
            $documentos = $cliente->documentos()
                ->with('documentoTipo')
                ->latest()
                ->get();

            $documentosTipos = EmpresaDocumentoTipo::query()
                ->activos()
                ->aplicaACliente()
                ->ordenados()
                ->get();

            $canUploadClienteDocumentos = $this->userHasClientePermission($request->user(), ['clientes.edit', 'clientes.access']);
            $canDeleteClienteDocumentos = $this->userHasClientePermission($request->user(), ['clientes.delete', 'clientes.edit', 'clientes.access']);
        }

        if ($tab === 'notas') {
            // $notas = $cliente->notas()->with('autor')->latest()->paginate(10)->withQueryString();
        }

        if ($tab === 'portales') {
            $portales = $cliente->portales()
                ->latest()
                ->get();
        }

        if ($tab === 'seguimiento') {
            $llamadasSeguimiento = PhoneCall::query()
                ->with(['extension', 'user'])
                ->where('phoneable_type', Cliente::class)
                ->where('phoneable_id', $cliente->id)
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'llamadas_page')
                ->withQueryString();

            $telefonosSeguimiento = TelephonyPhoneNumber::query()
                ->where('phoneable_type', Cliente::class)
                ->where('phoneable_id', $cliente->id)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('label')
                ->get();

            $extensionTelefoniaActual = $request->user()?->phoneExtensions()
                ->where(function ($query) {
                    $query->whereNull('out_of_service')
                        ->orWhere('out_of_service', false);
                })
                ->orderBy('extension')
                ->first();
        }

        return view('clientes.edit', compact(
            'cliente','tab','obras','facturas','pagos','contactos','documentos','notas','llamadasSeguimiento','telefonosSeguimiento','extensionTelefoniaActual','portales','documentosTipos','canUploadClienteDocumentos','canDeleteClienteDocumentos'
        ));
    }

    private function userHasClientePermission($user, array $permissions): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return $user->getAllPermissions()
            ->pluck('name')
            ->intersect($permissions)
            ->isNotEmpty();
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social'     => ['nullable', 'string', 'max:255'],
            'rfc'              => ['nullable', 'string', 'max:13', 'unique:clientes,rfc,' . $cliente->id],
            'telefono'         => ['nullable', 'string', 'max:20', new ValidMexicanPhone()],
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

        Artisan::call('telephony:index-phones');

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
