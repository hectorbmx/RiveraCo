<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatEmpresa;
use Illuminate\Support\Facades\DB;

class SatCfdiEstadisticaController extends Controller
{
    //
   public function index(SatEmpresa $empresa)
{
    $empresaRfc = $empresa->rfc;
    $year = (int) request('year', now()->year);

    $years = DB::table('sat_cfdis')
    ->selectRaw('YEAR(fecha_emision) as year')
    ->whereNotNull('fecha_emision')
    ->where(function ($q) use ($empresaRfc) {
        $q->where('rfc_emisor', $empresaRfc)
          ->orWhere('rfc_receptor', $empresaRfc);
    })
    ->groupByRaw('YEAR(fecha_emision)')
    ->orderByDesc('year')
    ->pluck('year');

    $ingresos = DB::table('sat_cfdis')
        ->selectRaw('MONTH(fecha_emision) as mes, SUM(total) as total')
        ->whereYear('fecha_emision', $year)
        ->where('rfc_emisor', $empresaRfc)
        ->groupBy('mes')
        ->pluck('total', 'mes');

    $gastos = DB::table('sat_cfdis')
        ->selectRaw('MONTH(fecha_emision) as mes, SUM(total) as total')
        ->whereYear('fecha_emision', $year)
        ->where('rfc_receptor', $empresaRfc)
        ->groupBy('mes')
        ->pluck('total', 'mes');

       

    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    $dataIngresos = [];
    $dataGastos = [];

    for ($i = 1; $i <= 12; $i++) {
        $dataIngresos[] = (float) ($ingresos[$i] ?? 0);
        $dataGastos[] = (float) ($gastos[$i] ?? 0);
    }
 $totalIngresos = array_sum($dataIngresos);
        $totalGastos = array_sum($dataGastos);
        $balance = $totalIngresos - $totalGastos;

        $totalCfdisIngresos = DB::table('sat_cfdis')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_emisor', $empresaRfc)
            ->count();

        $totalCfdisGastos = DB::table('sat_cfdis')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_receptor', $empresaRfc)
            ->count();
        $topClientes = DB::table('sat_cfdis')
            ->selectRaw('
                rfc_receptor as rfc,
                receptor_nombre as nombre,
                SUM(total) as total
            ')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_emisor', $empresaRfc)
            ->groupBy('rfc_receptor', 'receptor_nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topProveedores = DB::table('sat_cfdis')
            ->selectRaw('
                rfc_emisor as rfc,
                emisor_nombre as nombre,
                SUM(total) as total
            ')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_receptor', $empresaRfc)
            ->groupBy('rfc_emisor', 'emisor_nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

$topClientesLabels = $topClientes->map(fn ($item) => $item->nombre ?: $item->rfc)->values();
$topClientesData = $topClientes->map(fn ($item) => (float) $item->total)->values();

$topProveedoresLabels = $topProveedores->map(fn ($item) => $item->nombre ?: $item->rfc)->values();
$topProveedoresData = $topProveedores->map(fn ($item) => (float) $item->total)->values();
    return view('sat.cfdis.estadisticas.index', compact(
        'year',
        'years',
        'empresa',
        'empresaRfc',
        'meses',
        'dataIngresos',
        'dataGastos',
        'totalIngresos',
        'totalGastos',
        'balance',
        'totalCfdisIngresos',
        'totalCfdisGastos',
        'topClientesLabels',
        'topClientesData',
        'topProveedoresLabels',
        'topProveedoresData',
    ));
}
}
