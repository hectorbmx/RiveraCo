<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Models\OrdenCompra;

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
        return view('proveedores.create');
    }
      public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => ['required','string','max:100'],
            'descripcion'=> ['nullable','string','max:255'],
            'rfc'        => ['nullable','string','max:20'],
            'domicilio'  => ['nullable','string','max:255'],
            'telefono'   => ['nullable','string','max:30'],
            'email'      => ['nullable','email','max:150'],
            'banco'      => ['nullable','string','max:100'],
            'clabe'      => ['nullable','string','max:25'],
            'cuenta'     => ['nullable','string','max:50'],
            'activo'     => ['nullable','boolean'],
            'fecha_registro' => ['nullable','date'],
        ]);

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

            if ($tab === 'ordenes') {
                $q = OrdenCompra::query()
                    ->with(['obra','areaCatalogo'])
                    ->where('proveedor_id', $proveedor->id)
                    ->orderByDesc('fecha')
                    ->orderByDesc('id');

                if ($request->filled('estado')) {
                    $estado = strtolower($request->estado);
                    // si tu OrdenCompraController tiene estadoToLegacy, aquí puedes duplicar lógica
                    // por ahora filtramos por estado legacy directo si viene así:
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

            return view('proveedores.show', compact('proveedor', 'tab', 'ordenes'));
        }
    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate([
            'nombre'     => ['required','string','max:100'],
            'descripcion'=> ['nullable','string','max:255'],
            'rfc'        => ['nullable','string','max:20'],
            'domicilio'  => ['nullable','string','max:255'],
            'telefono'   => ['nullable','string','max:30'],
            'email'      => ['nullable','email','max:150'],
            'banco'      => ['nullable','string','max:100'],
            'clabe'      => ['nullable','string','max:25'],
            'cuenta'     => ['nullable','string','max:50'],
            'activo'     => ['nullable','boolean'],
            'fecha_registro' => ['nullable','date'],
        ]);

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
}
