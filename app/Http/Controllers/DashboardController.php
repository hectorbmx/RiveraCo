<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Obra;

class DashboardController extends Controller
{
      public function index()
    {
        return view('dashboard.index', [
            'totalClientes'   => Cliente::count(),

            // obras en ejecución (estatus_nuevo = 2)
            'obrasActivas'    => Obra::where('estatus_nuevo',Obra::ESTATUS_EJECUCION)->count(),

            // obras terminadas (estatus_nuevo = 4)
            'obrasTerminadas' => Obra::where('estatus_nuevo', Obra::ESTATUS_TERMINADA)->count(),
        ]);
    }
}
