<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Models\OrdenCompra;
use App\Models\SatCfdi;
use App\Models\Obra;
use App\Models\SatCfdiPago;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{


    public function index(Request $request)
        {
            $proveedor = Proveedor::query()->orderBy('nombre');

            if ($request->filled('q')) {
                $term = trim((string) $request->q);
                $proveedor->where(function ($sub) use ($term) {
                    $sub->where('nombre', 'like', "%{$term}%")
                        ->orWhere('descripcion', 'like', "%{$term}%")
                        ->orWhere('rfc', 'like', "%{$term}%");
                });
            }

            if ($request->filled('activo')) {
                // activo=1 / activo=0
                $proveedor->where('activo', (int) $request->activo);
            }

            $proveedores = $proveedor->paginate(20)->withQueryString();

            return view('proveedores.index', compact('proveedores'));
        }


    public function create()
    {
        return view('proveedores.create', $this->satCatalogos());
    }
      public function store(Request $request)
    {
        $data = $request->validate($this->rulesProveedor(), $this->messagesProveedor());

        // ✅ Bloquear RFC + domicilio duplicado
        $rfc = $data['rfc'] ?? null;
        $dom = $data['domicilio'] ?? null;

        if ($rfc && $dom) {
            $existe = Proveedor::where('rfc', $rfc)
                ->where('domicilio', $dom)
                ->exists();

            if ($existe) {
                return back()
                    ->withInput()
                    ->withErrors(['rfc' => 'Ya existe un proveedor con este RFC y el mismo domicilio.']);
            }
        }

        $data['activo'] = (bool) ($data['activo'] ?? true);

        Proveedor::create($data);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado.');
    }

    private function satCatalogos(): array
    {
        return [
            'regimenesFiscales' => config('sat_catalogs.regimenes_fiscales', []),
            'usosCfdi' => config('sat_catalogs.usos_cfdi', []),
        ];
    }

    private function rulesProveedor(): array
    {
        return [
            'nombre'     => ['required','string','max:100'],
            'razon_social' => ['nullable','string','max:255'],
            'descripcion'=> ['nullable','string','max:255'],
            'rfc'        => ['nullable','string','max:20'],
            'domicilio'  => ['nullable','string','max:255'],
            'codigo_postal' => ['nullable','string','max:10'],
            'regimen_fiscal' => ['nullable','string','max:10', Rule::in(array_keys(config('sat_catalogs.regimenes_fiscales', [])))],
            'uso_cfdi_default' => ['nullable','string','max:10', Rule::in(array_keys(config('sat_catalogs.usos_cfdi', [])))],
            'telefono'   => ['nullable','string','max:30'],
            'email'      => ['nullable','email','max:150'],
            'nombre_contacto' => ['nullable','string','max:150'],
            'telefono_contacto' => ['nullable','string','max:30'],
            'banco'      => ['nullable','string','max:100'],
            'clabe'      => ['nullable','regex:/^[0-9]{18}$/'],
            'cuenta'     => ['nullable','string','max:50'],
            'activo'     => ['nullable','boolean'],
            'fecha_registro' => ['nullable','date'],
        ];
    }

    private function messagesProveedor(): array
    {
        return [
            'regimen_fiscal.in' => 'Selecciona un regimen fiscal valido del catalogo SAT.',
            'uso_cfdi_default.in' => 'Selecciona un uso de CFDI valido del catalogo SAT.',
            'clabe.regex' => 'La CLABE debe tener exactamente 18 digitos y no debe incluir letras.',
        ];
    }

   public function show(Request $request, Proveedor $proveedor)
{
    $tab = $request->get('tab', 'general');

    // Cargas por tab (evita cargar de más)
    if ($tab === 'productos') {
        $proveedor->load(['productos' => function ($q) {
            $q->orderBy('productos.nombre');
        }]);
    }

    $ordenes = null;
    $facturas = null;

    if ($tab === 'ordenes') {
        $q = OrdenCompra::query()
            ->with(['obra', 'areaCatalogo'])
            ->where('proveedor_id', $proveedor->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($request->filled('estado')) {
            $q->where('estado', $request->estado);
        }

        if ($request->filled('desde')) {
            $q->whereDate('fecha', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $q->whereDate('fecha', '<=', $request->hasta);
        }

        $ordenes = $q->paginate(15)->withQueryString();
    }

    if ($tab === 'facturas') {
        $q = SatCfdi::query()
            ->where('rfc_emisor', $proveedor->rfc)
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id');

        if ($request->filled('desde')) {
            $q->whereDate('fecha_emision', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $q->whereDate('fecha_emision', '<=', $request->hasta);
        }

        // if ($request->filled('estatus_pago')) {
        //     $q->where('estatus_pago', $request->estatus_pago);
        // }

        $facturas = $q->paginate(15)->withQueryString();
    }

    return view('proveedores.show', compact(
        'proveedor',
        'tab',
        'ordenes',
        'facturas'
    ));
}
    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', array_merge(
            compact('proveedor'),
            $this->satCatalogos()
        ));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate($this->rulesProveedor(), $this->messagesProveedor());

        // ✅ Bloquear RFC + domicilio duplicado (ignorando el mismo registro)
        $rfc = $data['rfc'] ?? null;
        $dom = $data['domicilio'] ?? null;

        if ($rfc && $dom) {
            $existe = Proveedor::where('rfc', $rfc)
                ->where('domicilio', $dom)
                ->where('id', '!=', $proveedor->id)
                ->exists();

            if ($existe) {
                return back()
                    ->withInput()
                    ->withErrors(['rfc' => 'Ya existe otro proveedor con este RFC y el mismo domicilio.']);
            }
        }

        $data['activo'] = (bool) ($data['activo'] ?? false);

        $proveedor->update($data);

        return redirect()->route('proveedores.show', $proveedor)->with('success', 'Proveedor actualizado.');
    }

    public function toggleActivo(Proveedor $proveedor)
    {
        $proveedor->activo = ! $proveedor->activo;
        $proveedor->save();

        return back()->with('success', 'Estatus actualizado.');
    }




    public function buscar(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 3) {
            return response()->json([]);
        }

        $proveedores = Proveedor::query()
            ->where('activo', 1)
            ->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('descripcion', 'like', "%{$term}%")
                  ->orWhere('rfc', 'like', "%{$term}%");
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id','nombre','descripcion','rfc']);

        $payload = $proveedores->map(function ($p) {
            $label = $p->nombre
                ?? $p->nombre_comercial
                ?? $p->razon_social
                ?? ('Proveedor #' . $p->id);

            return [
                'id'    => $p->id,
                'nombre' => $label,
                'rfc'   => $p->rfc,
            ];
        });

        return response()->json($payload);
    }
    public function showFactura(Proveedor $proveedor, SatCfdi $cfdi)
        {
            if ($cfdi->rfc_emisor !== $proveedor->rfc) {
                abort(404);
            }

            $cfdi->load(['conceptos', 'obra', 'ordenCompra']);

            $obras = Obra::orderBy('nombre')->get();

            $ordenesCompra = OrdenCompra::where('proveedor_id', $proveedor->id)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->get();

            return view('proveedores.facturas.show', compact(
                'proveedor',
                'cfdi',
                'obras',
                'ordenesCompra'
            ));
        }

    public function relacionarFactura(Request $request,Proveedor $proveedor,SatCfdi $cfdi)
{
    $request->validate([
        'tipo' => ['required', 'in:obra,orden_compra'],
        'obra_id' => ['nullable', 'exists:obras,id'],
        'orden_compra_id' => ['nullable', 'exists:ordenes_compra,id'],
    ]);

    $cfdi->obra_id = null;
    $cfdi->orden_compra_id = null;

    if ($request->tipo === 'obra') {
        $cfdi->obra_id = $request->obra_id;
    }

    if ($request->tipo === 'orden_compra') {
        $cfdi->orden_compra_id = $request->orden_compra_id;
    }

    $cfdi->save();

    return back()->with('success', 'Factura relacionada correctamente.');
}

public function programarPagoFactura(Request $request, Proveedor $proveedor, SatCfdi $cfdi)
{
        // dd($request->all(), $proveedor->id, $cfdi->id);

    if ($cfdi->rfc_emisor !== $proveedor->rfc) {
        abort(404);
    }

    $data = $request->validate([
        'fecha_pago'    => ['required', 'date'],
        'monto'         => ['required', 'numeric', 'min:0.01'],
        'moneda'        => ['nullable', 'string', 'max:10'],
        'metodo_pago'   => ['nullable', 'string', 'max:50'],
        'referencia'    => ['nullable', 'string', 'max:255'],
        'observaciones' => ['nullable', 'string'],
    ]);

    SatCfdiPago::create([
        'sat_cfdi_id'   => $cfdi->id,
        'cfdi_uuid'     => $cfdi->uuid,
        'fecha_pago'    => $data['fecha_pago'],
        'monto'         => $data['monto'],
        'moneda'        => $data['moneda'] ?? ($cfdi->moneda ?? 'MXN'),
        'metodo_pago'   => $data['metodo_pago'] ?? null,
        'referencia'    => $data['referencia'] ?? null,
        'observaciones' => $data['observaciones'] ?? null,
        'estatus'       => 'programado',
        'created_by'    => auth()->id(),
    ]);

    return back()->with('success', 'Pago programado correctamente.');
}
public function pagosProgramados(Request $request)
{
    $desde = $request->get('desde', now()->startOfWeek()->toDateString());
    $hasta = now()->parse($desde)->endOfWeek()->toDateString();

    $pagos = SatCfdiPago::with([
            'cfdi',
            'cfdi.obra',
            'cfdi.ordenCompra',
        ])
        ->whereBetween('fecha_pago', [$desde, $hasta])
        ->orderBy('fecha_pago')
        ->get();

    $totalProgramado = $pagos->where('estatus', 'programado')->sum('monto');
    $totalPagado = $pagos->where('estatus', 'pagado')->sum('monto');
    $totalCancelado = $pagos->where('estatus', 'cancelado')->sum('monto');

    return view('proveedores.pagos-programados.index', compact(
        'pagos',
        'desde',
        'hasta',
        'totalProgramado',
        'totalPagado',
        'totalCancelado'
    ));
}
}
