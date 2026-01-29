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
            // $oc->iva = 0;
            $oc->iva = (float) $request->iva; // IVA base (%)

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
    $ivaMontoGeneral = 0;

    foreach ($oc->detalles as $detalle) {
        $detalle->subtotal = $detalle->precio_unitario * $detalle->cantidad;
        $detalle->iva_calculado = ($detalle->subtotal * $detalle->iva) / 100; // detalle->iva = %
        $detalle->total = $detalle->subtotal + $detalle->iva_calculado;

        $subtotalGeneral += $detalle->subtotal;
        $ivaMontoGeneral += $detalle->iva_calculado;
    }

    // ✅ NO PISES $oc->iva (ese es el % base)
    $oc->subtotal_calc = $subtotalGeneral;
    $oc->iva_monto_calc = $ivaMontoGeneral;
    $oc->total_calc = $subtotalGeneral + $ivaMontoGeneral + ((float)($oc->otros_impuestos ?? 0));

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

    // ====== Config layout ======
    $M = 10;                 // margen
    $W = 216 - ($M * 2);     // ancho útil carta (216mm aprox)
    $X0 = $M;
    $Y = $M;

    // Colores (aprox legacy)
    $BLUE = [0, 74, 173];     // azul
    $GRAY = [240, 240, 240];

    $setBlue = function() use ($pdf, $BLUE) { $pdf->SetDrawColor($BLUE[0], $BLUE[1], $BLUE[2]); };
    $setFillBlue = function() use ($pdf, $BLUE) { $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]); };
    $setFillGray = function() use ($pdf, $GRAY) { $pdf->SetFillColor($GRAY[0], $GRAY[1], $GRAY[2]); };

    // Helpers
    $money = fn($n) => '$' . number_format((float)$n, 2);
    $fecha = (string) ($oc->fecha ?? '');
    if ($fecha) $fecha = substr($fecha, 0, 10);

    $proveedorNombre = $oc->proveedor->nombre ?? '-';
    $area = $oc->areaCatalogo->nombre ?? ($oc->area ?? '-');

    // ====== HEADER (logo + titulo + lineas azules + datos empresa) ======
    $pdf->SetXY($X0, $Y);

    // Logo (ajusta ruta)
    $logoPath = public_path('images/logoAzul.png'); // <-- pon tu logo real
    if (is_file($logoPath)) {
        $pdf->Image($logoPath, $X0, $Y, 35); // ancho 35mm aprox
    }

    // Título a la derecha del logo
    $pdf->SetXY($X0 + 40, $Y + 2);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->Cell(90, 8, $utf8('ORDEN DE COMPRA'), 0, 0, 'L');
    // $pdf->SetXY($X0 + 40, $Y + 12);
    // $pdf->Cell(90, 8, $utf8('DE COMPRA'), 0, 0, 'L');

    // Datos empresa (arriba derecha)
    $pdf->SetTextColor(60,60,60);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($X0 + 135, $Y + 2);
    $pdf->MultiCell(0, 4, $utf8("JUSTO SIERRA NO. 2469 COL. LADRON DE GUEVARA\nGUADALAJARA, JALISCO, MEXICO C.P. 44600\nTEL: 33) 3615-0741 3630-1056"), 0, 'R');

    // Líneas azules tipo “doble”
    // $setBlue();
    // $pdf->Line($X0, $Y + 28, $X0 + $W, $Y + 28);
    // $pdf->Line($X0, $Y + 30, $X0 + $W, $Y + 30);

    // ====== BLOQUE “DATOS PROVEEDOR” + CAJAS (como legacy) ======
    $Y = $Y + 34;
    $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($X0, $Y);
    $pdf->Cell(35);
    $pdf->Cell(85, 5, $utf8('DATOS PROVEEDOR'), 0, 0, 'L');

    // Caja No. Orden / No. Obra (arriba derecha con borde rojo)
    $pdf->SetDrawColor(255, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($X0 + 125, $Y - 4);
    $pdf->Cell(71, 6, $utf8('NO. DE ORDEN: ') . $utf8($oc->folio), 1, 1, 'L');
    $pdf->SetXY($X0 + 125, $Y + 2);
    $pdf->Cell(71, 6, $utf8('NO. DE OBRA: ') . $utf8($oc->obra_folio ?? ''), 1, 1, 'L'); // ajusta si existe

    // Reset azul para cuadros
    $setBlue();
    $pdf->SetDrawColor($BLUE[0], $BLUE[1], $BLUE[2]);

    // Caja grande proveedor (tabla de 2 columnas como legacy)
    $Y += 8;
    $boxH = 32;
    $pdf->Rect($X0, $Y, $W, $boxH);

    // Divisiones internas
    $midX = $X0 + 125;
    $pdf->Line($midX, $Y, $midX, $Y + $boxH);

    // Filas
    $row1 = $Y + 8;
    $row2 = $Y + 16;
    $row3 = $Y + 24;
    $pdf->Line($X0, $row1, $X0 + $W, $row1);
    $pdf->Line($X0, $row2, $X0 + $W, $row2);
    $pdf->Line($X0, $row3, $X0 + $W, $row3);

    // Texto dentro
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', 'B', 9);

    $pdf->SetXY($X0 + 2, $Y + 2);
    $pdf->Cell(0, 6, $utf8('NOMBRE: ') . $utf8($proveedorNombre), 0, 0, 'L');

    $pdf->SetXY($X0 + 2, $Y + 10);
    $pdf->Cell(0, 6, $utf8('ATENCION: ') . $utf8($oc->atencion ?? ($oc->proveedor->contacto ?? '')), 0, 0, 'L');

    $pdf->SetXY($X0 + 2, $Y + 18);
    $pdf->Cell(0, 6, $utf8('DOMICILIO: ') . $utf8($oc->proveedor->domicilio ?? ''), 0, 0, 'L');

    $pdf->SetXY($X0 + 2, $Y + 26);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, $utf8('RFC: ') . $utf8($oc->proveedor->rfc ?? '') . $utf8('   CTA: ') . $utf8($oc->proveedor->cta ?? ''), 0, 0, 'L');

    // Columna derecha
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($midX + 2, $Y + 2);
    $pdf->Cell(0, 6, $utf8('FECHA: ') . $utf8($fecha), 0, 0, 'L');

    $pdf->SetXY($midX + 2, $Y + 10);
    $pdf->Cell(0, 6, $utf8('AREA: ') . $utf8($area), 0, 0, 'L');

    $pdf->SetXY($midX + 2, $Y + 18);
    $pdf->Cell(0, 6, $utf8('OBRA: ') . $utf8($oc->obra_nombre ?? ''), 0, 0, 'L'); // ajusta si existe

    $pdf->SetXY($midX + 2, $Y + 26);
    $pdf->Cell(0, 6, $utf8(auth()->user()->name ?? ''), 0, 0, 'L');

    // ====== TABLA DETALLES (header azul) ======
    $Y += $boxH + 6;
    $pdf->SetXY($X0, $Y);

    $wCant = 15;
    $wUni  = 18;
    $wDesc = 95;
    $wPU   = 28;
    $wIVA  = 22;
    $wImp  = $W - ($wCant + $wUni + $wDesc + $wPU + $wIVA);

    // Header
    $setFillBlue();
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('Arial', 'B', 8);

    $pdf->Cell($wCant, 7, $utf8('CANT'), 1, 0, 'C', true);
    $pdf->Cell($wUni,  7, $utf8('UNIDAD'), 1, 0, 'C', true);
    $pdf->Cell($wDesc, 7, $utf8('DESCRIPCION'), 1, 0, 'C', true);
    $pdf->Cell($wPU,   7, $utf8('P. UNITARIO'), 1, 0, 'C', true);
    $pdf->Cell($wIVA,  7, $utf8('IVA'), 1, 0, 'C', true);
    $pdf->Cell($wImp,  7, $utf8('TOTAL S/IVA'), 1, 1, 'C', true);

    // Body
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 9);

    $subCalc = 0.0;
    $ivaCalc = 0.0;

    foreach ($oc->detalles as $d) {
        $cant = (float) ($d->cantidad ?? 0);
        $uni  = (string) ($d->unidad ?? '');
        $desc = (string) ($d->descripcion ?? '');
        $pu   = (float) ($d->precio_unitario ?? 0);
        $imp  = (float) ($d->importe ?? ($cant * $pu));

        $ivaPctLinea = is_numeric($d->iva ?? null) ? (float) $d->iva : (float) ($oc->iva ?? 0);
        $ivaLinea = $imp * ($ivaPctLinea / 100);

        $subCalc += $imp;
        $ivaCalc += $ivaLinea;

        // MultiCell para descripción manteniendo altura de fila
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell($wCant, 7, number_format($cant, 1), 1, 0, 'C');
        $pdf->Cell($wUni,  7, $utf8($uni ?: '-'), 1, 0, 'C');

        $pdf->SetXY($x + $wCant + $wUni, $y);
        $pdf->MultiCell($wDesc, 7, $utf8($desc), 1, 'L');

        $newY = $pdf->GetY();
        $rowH = $newY - $y;

        $pdf->SetXY($x + $wCant + $wUni + $wDesc, $y);
        $pdf->Cell($wPU,  $rowH, $money($pu), 1, 0, 'R');
        $pdf->Cell($wIVA, $rowH, $money($ivaLinea), 1, 0, 'R');
        $pdf->Cell($wImp, $rowH, $money($imp), 1, 1, 'R');
    }

    // ====== NOTAS + TOTALES (caja derecha) ======
    $Y = $pdf->GetY() + 6;

    $subtotal = $subCalc;
    $ivaMonto = $ivaCalc;
    $total    = $subtotal + $ivaMonto;
    $ivaPctMostrado = (float) ($oc->iva ?? 0);

    // Notas
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($X0, $Y);
    $pdf->Cell(15, 6, $utf8('NOTAS:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(130, 6, $utf8($oc->notas ?? ''), 0, 'L');

    // Totales caja derecha
    $totX = $X0 + 120;
    $totY = $Y;
    $pdf->SetDrawColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->Rect($totX, $totY, 76, 24);

    $pdf->SetXY($totX, $totY);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(50, 8, $utf8('Subtotal:'), 0, 0, 'R');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(26, 8, $money($subtotal), 0, 1, 'R');

    $pdf->SetX($totX);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(50, 8, $utf8('IVA ('.number_format($ivaPctMostrado,0).'%) :'), 0, 0, 'R');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(26, 8, $money($ivaMonto), 0, 1, 'R');

    $pdf->SetX($totX);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 8, $utf8('Total M.N.:'), 0, 0, 'R');
    $pdf->Cell(26, 8, $money($total), 0, 1, 'R');

    // ====== DATOS DE FACTURACION (bloque inferior) ======
    $Y = max($pdf->GetY() + 8, $totY + 28);
    $pdf->SetXY($X0, $Y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->Cell(0, 6, $utf8('DATOS DE FACTURACION:'), 0, 1, 'L');

    $pdf->SetTextColor(0,0,0);
    $pdf->SetDrawColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->Rect($X0, $Y + 6, $W, 22);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($X0 + 2, $Y + 8);
    $pdf->MultiCell(100, 4, $utf8("Razon Social: Rivera Construcciones\nRFC: RCO820921T86\nDomicilio: Justo Sierra #2469\nUso del CFDI: G03 Gastos en general"), 0, 'L');

    $pdf->SetXY($X0 + 105, $Y + 8);
    $pdf->MultiCell(0, 4, $utf8("Regimen del Capital: S.A. de C.V.\nRegimen fiscal: General de ley\nColonia: Ladron de Guevara, Gdl\nMetodo de pago: Pago en una sola exhibicion"), 0, 'L');

    // ====== Firmas ======
    $Y = $Y + 34;
    $pdf->SetDrawColor(120,120,120);
    $pdf->Line($X0 + 5,  $Y + 12, $X0 + 55, $Y + 12);
    $pdf->Line($X0 + 60, $Y + 12, $X0 + 110, $Y + 12);
    $pdf->Line($X0 + 115,$Y + 12, $X0 + 165, $Y + 12);
    $pdf->Line($X0 + 170,$Y + 12, $X0 + 205, $Y + 12);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY($X0 + 5, $Y + 13);
    $pdf->Cell(50, 5, $utf8(auth()->user()->name ?? ''), 0, 0, 'C');
    $pdf->SetXY($X0 + 60, $Y + 13);
    $pdf->Cell(50, 5, $utf8($oc->autoriza_nombre ?? ''), 0, 0, 'C');
    $pdf->SetXY($X0 + 115, $Y + 13);
    $pdf->Cell(50, 5, $utf8(''), 0, 0, 'C');
    $pdf->SetXY($X0 + 170, $Y + 13);
    $pdf->Cell(35, 5, $utf8(''), 0, 0, 'C');

    $pdf->SetTextColor(200,0,0);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY($X0 + 5, $Y + 18);
    $pdf->Cell(50, 5, $utf8('SOLICITA'), 0, 0, 'C');
    $pdf->SetXY($X0 + 60, $Y + 18);
    $pdf->Cell(50, 5, $utf8('AUTORIZA'), 0, 0, 'C');
    $pdf->SetXY($X0 + 115, $Y + 18);
    $pdf->Cell(50, 5, $utf8('VoBo'), 0, 0, 'C');
    $pdf->SetXY($X0 + 170, $Y + 18);
    $pdf->Cell(35, 5, $utf8('ENTERADO'), 0, 0, 'C');

    // Page footer
    $pdf->SetTextColor(120,120,120);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetXY($X0, 270);
    $pdf->Cell(0, 5, $utf8('Page 1/1'), 0, 0, 'C');

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="OC_'.$oc->folio.'.pdf"');
}

// public function print(OrdenCompra $orden_compra)
// {
//     if (!auth()->user()->can('ordenes_compra.imprimir')) {
//         abort(403, 'No tienes permiso para imprimir órdenes de compra.');
//     }

//     $oc = $orden_compra->load(['proveedor', 'areaCatalogo', 'detalles']);

//     $pdf = new \FPDF('P', 'mm', 'Letter');
//     $pdf->AddPage();
//     $pdf->SetAutoPageBreak(true, 12);

//     $utf8 = fn($t) => utf8_decode((string) $t);

//     // ===== Encabezado =====
//     $pdf->SetFont('Arial', 'B', 14);
//     $pdf->Cell(0, 8, $utf8('ORDEN DE COMPRA'), 0, 1, 'L');

//     $pdf->SetFont('Arial', '', 10);
//     $pdf->Cell(0, 6, $utf8('Folio: ') . $utf8($oc->folio), 0, 1, 'L');

//     $estado = ucfirst((string) $oc->estado_normalizado);
//     $pdf->Cell(0, 6, $utf8('Estado: ') . $utf8($estado), 0, 1, 'L');

//     $fecha = (string) ($oc->fecha ?? '');
//     if ($fecha) $fecha = substr($fecha, 0, 10);

//     $pdf->Cell(0, 6, $utf8('Fecha: ') . $utf8($fecha), 0, 1, 'L');

//     $pdf->Ln(2);

//     // ===== Datos proveedor / area =====
//     $proveedor = $oc->proveedor->nombre ?? '-';
//     $area = $oc->areaCatalogo->nombre ?? ($oc->area ?? '-');

//     $pdf->SetFont('Arial', 'B', 10);
//     $pdf->Cell(30, 6, $utf8('Proveedor:'), 0, 0, 'L');
//     $pdf->SetFont('Arial', '', 10);
//     $pdf->Cell(0, 6, $utf8($proveedor), 0, 1, 'L');

//     $pdf->SetFont('Arial', 'B', 10);
//     $pdf->Cell(30, 6, $utf8('Área:'), 0, 0, 'L');
//     $pdf->SetFont('Arial', '', 10);
//     $pdf->Cell(0, 6, $utf8($area), 0, 1, 'L');

//     $pdf->Ln(4);

//     // ===== Tabla detalles =====
//     $pdf->SetFont('Arial', 'B', 9);

//     $wCant = 15;
//     $wUni  = 20;
//     $wDesc = 95;
//     $wPU   = 22;
//     $wIVA  = 22;
//     $wImp  = 22;

//     $pdf->Cell($wCant, 7, $utf8('Cant'), 1, 0, 'C');
//     $pdf->Cell($wUni,  7, $utf8('Unidad'), 1, 0, 'C');
//     $pdf->Cell($wDesc, 7, $utf8('Descripción'), 1, 0, 'C');
//     $pdf->Cell($wPU,   7, $utf8('P. Unit'), 1, 0, 'C');
//     $pdf->Cell($wIVA,  7, $utf8('IVA'), 1, 0, 'C');

//     $pdf->Cell($wImp,  7, $utf8('Importe'), 1, 1, 'C');

//     $pdf->SetFont('Arial', '', 9);

//     // Subtotal desde detalles (por si hay inconsistencias)
//     $subCalc = 0.0;
//     $ivaCalc = 0.0;
    
//     foreach ($oc->detalles as $d) {
//         $iva = $d->precio_unitario * $d->iva / 100;
        
//          $wImp  = 22;
//         $cant = (float) ($d->cantidad ?? 0);
//         $uni  = (string) ($d->unidad ?? '');
//         $desc = (string) ($d->descripcion ?? '');
//         $pu   = (float) ($d->precio_unitario ?? 0);
//         $imp = (float) ($d->importe ?? ($cant * $pu));

//         $ivaPctLinea = is_numeric($d->iva ?? null) ? (float) $d->iva : (float) ($oc->iva ?? 0);
//         $ivaLinea = $imp * ($ivaPctLinea / 100);


//         $subCalc += $imp;
//         $ivaCalc += $ivaLinea;
//         // MultiCell para descripción
//         $x = $pdf->GetX();
//         $y = $pdf->GetY();

//          $pdf->Cell($wCant, 7, number_format($cant, 3), 1, 0, 'R');
//             $pdf->Cell($wUni,  7, $utf8($uni ?: '-'), 1, 0, 'C');

//             $pdf->SetXY($x + $wCant + $wUni, $y);
//             $pdf->MultiCell($wDesc, 7, $utf8($desc), 1, 'L');

//             $newY = $pdf->GetY();
//             $rowH = $newY - $y;

//             // P. Unit
//             $pdf->SetXY($x + $wCant + $wUni + $wDesc, $y);
//             $pdf->Cell($wPU,  $rowH, '$' . number_format($pu, 2), 1, 0, 'R');

//             // IVA (monto)
//             $pdf->Cell($wIVA, $rowH, '$' . number_format($ivaLinea, 2), 1, 0, 'R');

//             // Importe (base)
//             $pdf->Cell($wImp, $rowH, '$' . number_format($imp, 2), 1, 1, 'R');
//     }

//     // ===== Totales =====
//     $pdf->Ln(3);

//     // Usa totales guardados si existen, si no usa calculado
//     $subtotal = $subCalc;
//     $ivaMonto = $ivaCalc;
//     $total    = $subtotal + $ivaMonto;

//     $ivaPctMostrado = (float) ($oc->iva ?? 0);

//     // $ivaMonto = max(0, $total - $subtotal);

//     $pdf->SetFont('Arial', '', 10);
//     $pdf->Cell(140, 6, '', 0, 0);
//     $pdf->Cell(25, 6, $utf8('Subtotal:'), 0, 0, 'R');
//     $pdf->Cell(25, 6, '$' . number_format($subtotal, 2), 0, 1, 'R');

//     $pdf->Cell(140, 6, '', 0, 0);
//     $pdf->Cell(25, 6, $utf8('IVA ') . $utf8('(' . number_format($ivaPctMostrado, 2) . '%):'), 0, 0, 'R');
//     $pdf->Cell(25, 6, '$' . number_format($ivaMonto, 2), 0, 1, 'R');

//     $pdf->SetFont('Arial', 'B', 11);
//     $pdf->Cell(140, 7, '', 0, 0);
//     $pdf->Cell(25, 7, $utf8('Total:'), 0, 0, 'R');
//     $pdf->Cell(25, 7, '$' . number_format($total, 2), 0, 1, 'R');

//     return response($pdf->Output('S'))
//         ->header('Content-Type', 'application/pdf')
//         ->header('Content-Disposition', 'inline; filename="OC_'.$oc->folio.'.pdf"');
// }

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
