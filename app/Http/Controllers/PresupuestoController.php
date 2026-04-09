<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
class PresupuestoController extends Controller
{
    public function index()
    {
        // Traemos los presupuestos ordenados por los más recientes
        $presupuestos = Presupuesto::orderBy('created_at', 'desc')->get();
        
        return view('presupuesto.index', compact('presupuestos'));
    }



    public function show($id)
    {
        // Aquí usamos Eager Loading para traer todas las tablas relacionadas de un golpe
        $presupuesto = Presupuesto::with(['detalles', 'pilas', 'resumenes'])->findOrFail($id);
        
        return view('presupuesto.show', compact('presupuesto'));
    }
   public function exportPdf($id)
    {
        $presupuesto = Presupuesto::with(['resumenes', 'pilas'])->findOrFail($id);

        // Preparamos la data unificada para la tabla del PDF
        $resumenes = $presupuesto->resumenes->where('cantidad', '>', 0)->map(function($i) {
            return [
                'concepto' => $i->concepto, 
                'unidad' => $i->unidad, 
                'cantidad' => $i->cantidad, 
                'precio' => $i->precio_unitario, 
                'importe' => $i->importe
            ];
        });

        $pilas = $presupuesto->pilas->where('cantidad', '>', 0)->map(function($i) {
            return [
                'concepto' => $i->concepto, 
                'unidad' => $i->unidad, 
                'cantidad' => $i->cantidad, 
                'precio' => $i->costo, 
                'importe' => $i->total
            ];
        });

        $tablaConsolidada = $resumenes->concat($pilas);
        $totalGeneral = $tablaConsolidada->sum('importe');

        // IMPORTANTE: Cambia "PDF::" por "Pdf::" (en minúsculas la d y f si usas la versión reciente)
        $pdf = Pdf::loadView('presupuesto.pdf_template', compact('presupuesto', 'tablaConsolidada', 'totalGeneral'));
        
        return $pdf->stream("Presupuesto_{$presupuesto->codigo_proyecto}.pdf");
    }
}