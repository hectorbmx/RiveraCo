<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatFacturaBorrador;
use Illuminate\Http\Request;

class SatFacturaBorradorController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('q', ''));

        $borradores = SatFacturaBorrador::query()
            ->with(['cliente', 'obra', 'empresa', 'user'])
            ->where('estado', 'borrador')
            ->whereNull('sat_factura_id')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('titulo', 'like', "%{$busqueda}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($busqueda) {
                            $clienteQuery->where('razon_social', 'like', "%{$busqueda}%")
                                ->orWhere('nombre_comercial', 'like', "%{$busqueda}%")
                                ->orWhere('rfc', 'like', "%{$busqueda}%");
                        })
                        ->orWhereHas('obra', function ($obraQuery) use ($busqueda) {
                            $obraQuery->where('nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('clave_obra', 'like', "%{$busqueda}%");
                        })
                        ->orWhereHas('empresa', function ($empresaQuery) use ($busqueda) {
                            $empresaQuery->where('nombre', 'like', "%{$busqueda}%")
                                ->orWhere('rfc', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->latest('updated_at')
            ->paginate(15)
            ->appends($request->query());

        return view('sat.borradores.index', compact('borradores', 'busqueda'));
    }
}