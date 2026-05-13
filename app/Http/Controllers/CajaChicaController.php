<?php

namespace App\Http\Controllers;

use App\Models\ObraReposicionGasto;
use Illuminate\Http\Request;

class CajaChicaController extends Controller
{
    public function index(Request $request)
    {
       $reposiciones = ObraReposicionGasto::query()
                ->with([
                    'obra',
                    'partida',
                    'solicitadoPor',
                    'revisadoPor',
                    'aprovisionadoPor',
                    'aprobadoPor',
                    'pagadoPor',
                    'cuentaBancoEmpresa',
                    'metodoPagoEmpresa',
                ])
                ->withCount('detalles')
                ->latest('id')
                ->paginate(20);
        return view('cajas-chicas.index', compact(
            'reposiciones'
        ));
    }
}