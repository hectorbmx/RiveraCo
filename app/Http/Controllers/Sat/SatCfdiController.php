<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatCfdi;
use App\Models\SatEmpresa;
use Illuminate\Http\Request;

class SatCfdiController extends Controller
{
    public function index(Request $request)
    {
        $empresas = SatEmpresa::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $empresaSeleccionada = null;
        $cfdis = collect();

        $totalGeneral = 0;
        $totalFiltrado = 0;

        $resumenIngresos = 0;
        $resumenEgresos = 0;
        $resumenPagos = 0;

        $subtotalIngresos = 0;
        $subtotalEgresos = 0;
        $subtotalPagos = 0;

        // Si no hay empresa seleccionada, no cargamos nada
        if (! $request->filled('sat_empresa_id')) {
            return view('sat.cfdis.index', compact(
                'empresas',
                'empresaSeleccionada',
                'cfdis',
                'totalGeneral',
                'totalFiltrado',
                'resumenIngresos',
                'resumenEgresos',
                'resumenPagos',
                'subtotalIngresos',
                'subtotalEgresos',
                'subtotalPagos'
            ));
        }

        $empresaSeleccionada = SatEmpresa::find($request->sat_empresa_id);

        if (! $empresaSeleccionada) {
            return view('sat.cfdis.index', compact(
                'empresas',
                'empresaSeleccionada',
                'cfdis',
                'totalGeneral',
                'totalFiltrado',
                'resumenIngresos',
                'resumenEgresos',
                'resumenPagos',
                'subtotalIngresos',
                'subtotalEgresos',
                'subtotalPagos'
            ));
        }

        $q = SatCfdi::query()->where(function ($sub) use ($empresaSeleccionada) {
            $sub->where('rfc_emisor', $empresaSeleccionada->rfc)
                ->orWhere('rfc_receptor', $empresaSeleccionada->rfc);
        });

        // Total de esa empresa antes de filtros adicionales
        $totalGeneral = (clone $q)->count();

        // Filtros adicionales
        if ($request->filled('uuid')) {
            $q->where('uuid', 'like', '%' . $request->uuid . '%');
        }

        if ($request->filled('rfc_emisor')) {
            $q->where('rfc_emisor', 'like', '%' . $request->rfc_emisor . '%');
        }

        if ($request->filled('rfc_receptor')) {
            $q->where('rfc_receptor', 'like', '%' . $request->rfc_receptor . '%');
        }

        if ($request->filled('tipo_comprobante')) {
            $q->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('fecha_inicio')) {
            $q->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $q->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }

        $totalFiltrado = (clone $q)->count();

        $resumenIngresos = (clone $q)->where('tipo_comprobante', 'I')->count();
        $resumenEgresos = (clone $q)->where('tipo_comprobante', 'E')->count();
        $resumenPagos = (clone $q)->where('tipo_comprobante', 'P')->count();
        $resumenNominas = (clone $q)->where('tipo_comprobante', 'N')->count();

        $subtotalIngresos = (clone $q)->where('tipo_comprobante', 'I')->sum('total');
        $subtotalEgresos = (clone $q)->where('tipo_comprobante', 'E')->sum('total');
        $subtotalPagos = (clone $q)->where('tipo_comprobante', 'P')->sum('total');
        $subtotalNominas = (clone $q)->where('tipo_comprobante', 'N')->sum('total');

        $cfdis = $q->orderByDesc('fecha_emision')
            ->paginate(20)
            ->withQueryString();

        return view('sat.cfdis.index', compact(
            'empresas',
            'empresaSeleccionada',
            'cfdis',
            'totalGeneral',
            'totalFiltrado',
            'resumenIngresos',
            'resumenEgresos',
            'resumenPagos',
            'resumenNominas',
            'subtotalIngresos',
            'subtotalEgresos',
            'subtotalPagos',
            'subtotalNominas',
        ));
    }
    public function show(\App\Models\SatCfdi $cfdi)
    {
        $cfdi->load('conceptos');

        return view('sat.cfdis.show', compact('cfdi'));
    }
    public function detalle(\App\Models\SatCfdi $cfdi)
        {
            $cfdi->load('conceptos');

            return response()->json([
                'cfdi' => $cfdi,
            ]);
        }
}