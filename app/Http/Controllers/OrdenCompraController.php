<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;
use App\Models\Area;
use App\Models\Proveedor;
use App\Models\Obra;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use FDPF; 

class OrdenCompraController extends Controller
{
    /**
     * Listado básico
     */
    public function index(Request $request)
    {
        $q = OrdenCompra::query()
            ->with(['proveedor','obra','areaCatalogo','detalles'])
            ->orderByDesc('fecha')
            ->orderByDesc('id');

    $proveedores = Proveedor::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $areas = Area::orderBy('nombre')->get();
    $obras = Obra::orderBy('nombre')->get();

        if ($request->filled('estado')) {
            // aceptamos programada/autorizada/cancelada y lo mapeamos a legacy
            $estado = strtolower($request->estado);
            $q->where('estado', $this->estadoToLegacy($estado));
        }

        if ($request->filled('proveedor_id')) {
            $q->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('area_id')) {
            $q->where('area_id', $request->area_id);
        }

        if ($request->filled('obra_id')) {
            $q->where('obra_id', $request->obra_id);
        }

        $ordenes = $q->paginate(20);

        foreach ($ordenes as $oc) {
            $subtotal = 0;
            $iva = 0;

            foreach ($oc->detalles as $detalle) {
                $lineaSubtotal = $detalle->precio_unitario * $detalle->cantidad;
                $lineaIva = ($lineaSubtotal * ($detalle->iva ?? 0)) / 100;

                $subtotal += $lineaSubtotal;
                $iva += $lineaIva;
            }

            $oc->subtotal = $subtotal;
            $oc->iva = $iva;
            $oc->otros_impuestos = (float) ($oc->otros_impuestos ?? 0);
            $oc->total = $subtotal + $iva + $oc->otros_impuestos;
        }

        // return view('rdenes_compra.index', compact('ordenes'));
        return view('ordencompra.index', compact('ordenes','areas','obras','proveedores'));
    }

   public function create()
{
    $proveedores = Proveedor::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $areas = Area::orderBy('nombre')->get();

    $obras = Obra::orderBy('nombre')->get();

    return view('ordencompra.create', compact('proveedores','areas','obras'));
}

    /**
     * Guardar OC (estado inicial: programada -> legacy BORRADOR)
     */
    public function store(StoreOrdenCompraRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $area = Area::findOrFail($request->area_id);

            $oc = new OrdenCompra();

            $oc->folio        = $this->generarFolioPorArea($area); // folio por área
            $oc->proveedor_id = (int) $request->proveedor_id;
            $oc->obra_id      = $request->obra_id ? (int)$request->obra_id : null;

            // nuevo
            $oc->area_id      = (int) $request->area_id;
            $oc->moneda       = $request->moneda;
            $oc->tipo_cambio  = $request->tipo_cambio;

            // legacy útil
            $oc->area         = $area->nombre; // mantenemos el texto por compatibilidad/histórico
            $oc->cotizacion   = $request->cotizacion;
            $oc->atencion     = $request->atencion;
            $oc->tipo_pago    = $request->tipo_pago;
            $oc->forma_pago   = $request->forma_pago;
            $oc->comentarios  = $request->comentarios;

            $oc->fecha        = $request->fecha;

            // Estado inicial
            $oc->estado = 'BORRADOR';

            // Usuario registro (si hay auth)
            $oc->usuario_registro = $this->usuarioActualNombre();

            // Totales iniciales (0). Se recalcularán al guardar detalles.
            $oc->subtotal = 0;
            $oc->iva = 0;
            $oc->otros_impuestos = 0;
            $oc->total = 0;

            $oc->save();

            return redirect()
                ->route('ordenes_compra.edit', $oc->id)
                ->with('success', 'Orden de compra creada (programada).');
        });
    }

    public function edit($id)
{
    $oc = OrdenCompra::with(['detalles.producto','proveedor','obra','areaCatalogo'])->findOrFail($id);
    $areas = Area::where('activo', 1)->orderBy('nombre')->get();

    $subtotalGeneral = 0;
    $ivaGeneral = 0;

    // 🔹 Calcular subtotal, IVA y total para cada detalle
    foreach ($oc->detalles as $detalle) {
        $detalle->subtotal = $detalle->precio_unitario * $detalle->cantidad;
        $detalle->iva_calculado = ($detalle->subtotal * $detalle->iva) / 100;
        $detalle->total = $detalle->subtotal + $detalle->iva_calculado;
          // Acumular
        $subtotalGeneral += $detalle->subtotal;
        $ivaGeneral += $detalle->iva_calculado;

    }
 // Asignar totales al objeto principal
    $oc->subtotal = $subtotalGeneral;
    $oc->iva = $ivaGeneral;
    $oc->otros_impuestos = $oc->otros_impuestos ?? 0; // si existe campo en DB
    $oc->total = $subtotalGeneral + $ivaGeneral + $oc->otros_impuestos;



    return view('ordencompra.edit', compact('oc','areas'));
}


    /**
     * Actualizar encabezado (solo si no está autorizada/cancelada)
     */
    public function update(UpdateOrdenCompraRequest $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);

        // Regla: si ya está autorizada o cancelada, no se edita encabezado
        $estadoNorm = $oc->estado_normalizado;
        if (in_array($estadoNorm, ['autorizada','cancelada'], true)) {
            return back()->with('error', 'No puedes editar una orden autorizada o cancelada.');
        }

        return DB::transaction(function () use ($request, $oc) {

            $area = Area::findOrFail($request->area_id);

            $oc->proveedor_id = (int) $request->proveedor_id;
            $oc->obra_id      = $request->obra_id ? (int)$request->obra_id : null;

            $oc->area_id      = (int) $request->area_id;
            $oc->moneda       = $request->moneda;
            $oc->tipo_cambio  = $request->tipo_cambio;

            $oc->area         = $area->nombre;
            $oc->cotizacion   = $request->cotizacion;
            $oc->atencion     = $request->atencion;
            $oc->tipo_pago    = $request->tipo_pago;
            $oc->forma_pago   = $request->forma_pago;
            $oc->comentarios  = $request->comentarios;

            $oc->fecha        = $request->fecha;

            $oc->save();

            return back()->with('success', 'Encabezado actualizado.');
        });
    }

    /**
     * Autorizar OC
     */
    // public function autorizar($id)
    // {
    //     $oc = OrdenCompra::findOrFail($id);

    //     if ($oc->estado_normalizado === 'cancelada') {
    //         return back()->with('error', 'No puedes autorizar una orden cancelada.');
    //     }

    //     if ($oc->estado_normalizado === 'autorizada') {
    //         return back()->with('success', 'La orden ya estaba autorizada.');
    //     }

    //     // Validación mínima: debe tener al menos 1 detalle
    //     if ($oc->detalles()->count() === 0) {
    //         return back()->with('error', 'No puedes autorizar una orden sin detalles.');
    //     }

    //     $oc->estado = 'AUTORIZADA';
    //     $oc->fecha_autorizacion = now()->toDateString();
    //     $oc->usuario_autoriza = $this->usuarioActualNombre();
    //     $oc->save();

    //     return back()->with('success', 'Orden autorizada.');
    // }
   public function autorizar($id)
{
    $user = auth()->user();
    if (!auth()->user()->can('ordenes_compra.autorizar')) {
        abort(403, 'No tienes permiso para autorizar órdenes de compra.');
    }
    // if (!in_array($user->rol ?? null, ['admin', 'compras'])) {
    //     abort(403, 'No tienes permiso para autorizar órdenes de compra.');
    // }

    $oc = OrdenCompra::findOrFail($id);

    if ($oc->estado_normalizado === 'cancelada') {
        return back()->with('error', 'No puedes autorizar una orden cancelada.');
    }

    if ($oc->estado_normalizado === 'autorizada') {
        return back()->with('success', 'La orden ya estaba autorizada.');
    }

    if ($oc->detalles()->count() === 0) {
        return back()->with('error', 'No puedes autorizar una orden sin detalles.');
    }

    $oc->estado = 'AUTORIZADA';
    $oc->fecha_autorizacion = now()->toDateString();
    $oc->usuario_autoriza = $this->usuarioActualNombre();
    $oc->save();

    return back()->with('success', 'Orden autorizada.');
}


/**
 * Imprimir OC en PDF
 */

public function print(OrdenCompra $orden_compra)
{
    if (!auth()->user()->can('ordenes_compra.imprimir')) {
        abort(403, 'No tienes permiso para imprimir órdenes de compra.');
    }

    $oc = $orden_compra->load(['proveedor', 'areaCatalogo', 'detalles']);

    $pdf = new \FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 12);

    $utf8 = fn($t) => utf8_decode((string) $t);

    // ===== Encabezado =====
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, $utf8('ORDEN DE COMPRA'), 0, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, $utf8('Folio: ') . $utf8($oc->folio), 0, 1, 'L');

    $estado = ucfirst((string) $oc->estado_normalizado);
    $pdf->Cell(0, 6, $utf8('Estado: ') . $utf8($estado), 0, 1, 'L');

    $fecha = (string) ($oc->fecha ?? '');
    if ($fecha) $fecha = substr($fecha, 0, 10);

    $pdf->Cell(0, 6, $utf8('Fecha: ') . $utf8($fecha), 0, 1, 'L');

    $pdf->Ln(2);

    // ===== Datos proveedor / area =====
    $proveedor = $oc->proveedor->nombre ?? '-';
    $area = $oc->areaCatalogo->nombre ?? ($oc->area ?? '-');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 6, $utf8('Proveedor:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, $utf8($proveedor), 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 6, $utf8('Área:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, $utf8($area), 0, 1, 'L');

    $pdf->Ln(4);

    // ===== Tabla detalles =====
    $pdf->SetFont('Arial', 'B', 9);

    $wCant = 15;
    $wUni  = 20;
    $wDesc = 95;
    $wPU   = 22;
    $wIVA  = 22;
    $wImp  = 22;

    $pdf->Cell($wCant, 7, $utf8('Cant'), 1, 0, 'C');
    $pdf->Cell($wUni,  7, $utf8('Unidad'), 1, 0, 'C');
    $pdf->Cell($wDesc, 7, $utf8('Descripción'), 1, 0, 'C');
    $pdf->Cell($wPU,   7, $utf8('P. Unit'), 1, 0, 'C');
    $pdf->Cell($wIVA,  7, $utf8('IVA'), 1, 0, 'C');

    $pdf->Cell($wImp,  7, $utf8('Importe'), 1, 1, 'C');

    $pdf->SetFont('Arial', '', 9);

    // Subtotal desde detalles (por si hay inconsistencias)
    $subCalc = 0.0;
    $ivaCalc = 0.0;
    
    foreach ($oc->detalles as $d) {
        $iva = $d->precio_unitario * $d->iva / 100;
        
         $wImp  = 22;
        $cant = (float) ($d->cantidad ?? 0);
        $uni  = (string) ($d->unidad ?? '');
        $desc = (string) ($d->descripcion ?? '');
        $pu   = (float) ($d->precio_unitario ?? 0);
        $imp = (float) ($d->importe ?? ($cant * $pu));

        $ivaPctLinea = is_numeric($d->iva ?? null) ? (float) $d->iva : (float) ($oc->iva ?? 0);
        $ivaLinea = $imp * ($ivaPctLinea / 100);


        $subCalc += $imp;
        $ivaCalc += $ivaLinea;
        // MultiCell para descripción
        $x = $pdf->GetX();
        $y = $pdf->GetY();

         $pdf->Cell($wCant, 7, number_format($cant, 3), 1, 0, 'R');
            $pdf->Cell($wUni,  7, $utf8($uni ?: '-'), 1, 0, 'C');

            $pdf->SetXY($x + $wCant + $wUni, $y);
            $pdf->MultiCell($wDesc, 7, $utf8($desc), 1, 'L');

            $newY = $pdf->GetY();
            $rowH = $newY - $y;

            // P. Unit
            $pdf->SetXY($x + $wCant + $wUni + $wDesc, $y);
            $pdf->Cell($wPU,  $rowH, '$' . number_format($pu, 2), 1, 0, 'R');

            // IVA (monto)
            $pdf->Cell($wIVA, $rowH, '$' . number_format($ivaLinea, 2), 1, 0, 'R');

            // Importe (base)
            $pdf->Cell($wImp, $rowH, '$' . number_format($imp, 2), 1, 1, 'R');
    }

    // ===== Totales =====
    $pdf->Ln(3);

    // Usa totales guardados si existen, si no usa calculado
    $subtotal = $subCalc;
    $ivaMonto = $ivaCalc;
    $total    = $subtotal + $ivaMonto;

    $ivaPctMostrado = (float) ($oc->iva ?? 0);

    // $ivaMonto = max(0, $total - $subtotal);

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(140, 6, '', 0, 0);
    $pdf->Cell(25, 6, $utf8('Subtotal:'), 0, 0, 'R');
    $pdf->Cell(25, 6, '$' . number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(140, 6, '', 0, 0);
    $pdf->Cell(25, 6, $utf8('IVA ') . $utf8('(' . number_format($ivaPctMostrado, 2) . '%):'), 0, 0, 'R');
    $pdf->Cell(25, 6, '$' . number_format($ivaMonto, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(140, 7, '', 0, 0);
    $pdf->Cell(25, 7, $utf8('Total:'), 0, 0, 'R');
    $pdf->Cell(25, 7, '$' . number_format($total, 2), 0, 1, 'R');

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="OC_'.$oc->folio.'.pdf"');
}

    /**
     * Cancelar OC
     */
    public function cancelar(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);

        if ($oc->estado_normalizado === 'autorizada') {
            return back()->with('error', 'No puedes cancelar una orden ya autorizada (definamos si aplica flujo de cancelación avanzada).');
        }

        if ($oc->estado_normalizado === 'cancelada') {
            return back()->with('success', 'La orden ya estaba cancelada.');
        }

        $motivo = $request->input('motivo');
        if ($motivo) {
            $oc->comentarios = trim(($oc->comentarios ?? '') . "\n[CANCELACIÓN] " . $motivo);
        }

        $oc->estado = 'CANCELADA';
        $oc->save();

        return back()->with('success', 'Orden cancelada.');
    }

    // ============================
    // Helpers
    // ============================

    private function estadoToLegacy(string $estado): string
    {
        return match ($estado) {
            'programada' => 'BORRADOR',
            'autorizada' => 'AUTORIZADA',
            'cancelada'  => 'CANCELADA',
            default      => strtoupper($estado),
        };
    }

    private function usuarioActualNombre(): ?string
    {
        try {
            $u = auth()->user();
            if (!$u) return null;

            // ajusta si tu user tiene name distinto
            return $u->name ?? $u->email ?? (string)$u->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Folio por área (sin tabla de folios por ahora):
     * Genera consecutivo consultando último folio del área.
     * IMPORTANTE: si habrá alta concurrencia, luego migramos al esquema con lock/tabla folios.
     */
    private function generarFolioPorArea(Area $area): string
    {
        $pref = 'OC-' . strtoupper($area->codigo) . '-';

        $ultimo = OrdenCompra::where('folio', 'like', $pref . '%')
            ->orderByDesc('id')
            ->value('folio');

        $num = 0;
        if ($ultimo) {
            $part = str_replace($pref, '', $ultimo);
            $num = (int) ltrim($part, '0');
        }

        $num++;

        return $pref . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }
}
