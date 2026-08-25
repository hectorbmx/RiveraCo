<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;
use App\Models\Area;
use App\Models\Proveedor;
use App\Models\Obra;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialRequest;
use App\Models\OrdenCompra;
use App\Models\CentroCosto;
use App\Models\TipoIva;
use App\Models\TipoRetencion;
use App\Models\DocumentoFirmante;
use App\Services\CivilConceptBalanceService;
use App\Services\ObraCivilInsumoBalanceService;
use App\Services\ObraCivil\ObraCivilMaterialRequestOrderService;
use App\Services\OrdenCompraNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
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
    $this->authorizeAny([
        'ordenes_compra.view.access',
        'ordenes de compra.access',
    ]);

    $search = $request->query('search');
    $estado = $request->query('estado');
    $resumenSemanaGl = null;

    /*
     * Navegación semanal.
     *
     * El parámetro "semana" puede contener cualquier fecha,
     * pero se normaliza siempre al lunes de esa semana.
     */
    try {
        $fechaSemana = $request->filled('semana')
            ? Carbon::createFromFormat(
                'Y-m-d',
                (string) $request->query('semana')
            )->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);
    } catch (\Throwable $e) {
        $fechaSemana = now()->startOfWeek(Carbon::MONDAY);
    }

    $inicioSemanaActual = now()
        ->startOfWeek(Carbon::MONDAY)
        ->startOfDay();

    /*
     * No permitir navegar a semanas futuras.
     */
    if ($fechaSemana->greaterThan($inicioSemanaActual)) {
        $fechaSemana = $inicioSemanaActual->copy();
    }

    $inicioSemana = $fechaSemana
        ->copy()
        ->startOfDay();

    $finSemana = $fechaSemana
        ->copy()
        ->endOfWeek(Carbon::SUNDAY)
        ->endOfDay();

    $semanaAnterior = $inicioSemana
        ->copy()
        ->subWeek()
        ->format('Y-m-d');

    $semanaSiguienteCarbon = $inicioSemana
        ->copy()
        ->addWeek();

    $esSemanaActual = $inicioSemana->isSameDay(
        $inicioSemanaActual
    );

    /*
     * La semana siguiente solo se habilita cuando
     * estamos consultando una semana anterior.
     */
    $semanaSiguiente = $esSemanaActual
        ? null
        : $semanaSiguienteCarbon->format('Y-m-d');

    /*
     * Código de área normalizado.
     */
    $areaCodigo = strtoupper(
        trim((string) $request->query('area_codigo'))
    );

    $q = OrdenCompra::query()
        ->with([
            'proveedor',
            'obra',
            'centroCosto',
            'areaCatalogo',
            'detalles',
            'pagoProveedorActivo',
        ])
        ->when($search, function ($query, $search) {
            $query->whereHas(
                'proveedor',
                function ($q) use ($search) {
                    $q->where(
                        'nombre',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'razon_social',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'rfc',
                        'like',
                        "%{$search}%"
                    );
                }
            );
        })
        ->when($estado, function ($query, $estado) {
            $legacyStatus = $this->estadoToLegacy($estado);

            $query->where(
                'estado',
                $legacyStatus
            );
        })
        ->orderByDesc('fecha')
        ->orderByDesc('id');

    $proveedores = Proveedor::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $areas = Area::orderBy('nombre')
        ->get();

    $obras = Obra::orderBy('nombre')
        ->get();

    $centrosCosto = CentroCosto::where('activo', true)
        ->orderBy('nombre')
        ->get();

    /*
     * Filtro por proveedor.
     */
    if ($request->filled('proveedor_id')) {
        $q->where(
            'proveedor_id',
            $request->proveedor_id
        );
    }

    /*
     * Filtro directo por area_id.
     */
    if ($request->filled('area_id')) {
        $q->where(
            'area_id',
            $request->area_id
        );
    }

    /*
     * Filtro por código de área.
     */
    if ($areaCodigo !== '') {
        $areaFiltro = Area::query()
            ->where('codigo', $areaCodigo)
            ->first();

        if ($areaFiltro) {
            $q->where(
                'area_id',
                $areaFiltro->id
            );
        }
    }

    /*
     * Filtro por obra.
     */
    if ($request->filled('obra_id')) {
        $q->where(
            'obra_id',
            $request->obra_id
        );
    }

    /*
     * El filtro semanal solo aplica al listado especial
     * del área Giralda.
     *
     * Se utiliza la fecha capturada en la orden.
     */
    if ($areaCodigo === 'GL') {
        $q->whereBetween('fecha', [
            $inicioSemana->toDateString(),
            $finSemana->toDateString(),
        ]);
    }

    if ($areaCodigo === 'GL') {
        $ordenesResumen = OrdenCompra::query()
            ->with('detalles')
            ->whereHas('areaCatalogo', function ($query) {
                $query->where('codigo', 'GL');
            })
            ->whereBetween('fecha', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString(),
            ])
            ->whereIn('estado', ['AUTORIZADA', 'VERIFICADA'])
            ->get();

        $totalAutorizado = 0.0;
        $totalVerificado = 0.0;
        $pendientesVerificar = 0;
        $verificadas = 0;

        foreach ($ordenesResumen as $ordenResumen) {
            $totalOrden = 0.0;

            foreach ($ordenResumen->detalles as $detalle) {
                $lineaSubtotal = (float) ($detalle->importe ?? ((float) $detalle->precio_unitario * (float) $detalle->cantidad));
                $lineaIva = ($lineaSubtotal * (float) ($detalle->iva ?? 0)) / 100;
                $totalOrden += $lineaSubtotal + $lineaIva + (float) ($detalle->otros_impuestos ?? 0) - (float) ($detalle->retenciones ?? 0);
            }

            if ($ordenResumen->estado === 'VERIFICADA') {
                $totalVerificado += $totalOrden;
                $verificadas++;
            } else {
                $totalAutorizado += $totalOrden;
                $pendientesVerificar++;
            }
        }

        $resumenSemanaGl = [
            'total_acumulado' => $totalAutorizado + $totalVerificado,
            'total_pendiente_verificar' => $totalAutorizado,
            'total_verificado' => $totalVerificado,
            'pendientes_verificar' => $pendientesVerificar,
            'verificadas' => $verificadas,
            'reposicion_sugerida' => $finSemana->copy()->next(Carbon::TUESDAY),
        ];
    }

    $ordenes = $q
        ->paginate(20)
        ->withQueryString();

    /*
     * Recalcular totales mostrados en el listado
     * a partir de los detalles.
     */
    foreach ($ordenes as $oc) {
        $subtotal = 0.0;
        $iva = 0.0;

        $otros = 0.0;
        $retenciones = 0.0;

        foreach ($oc->detalles as $detalle) {
            $lineaSubtotal = (float) ($detalle->importe ?? ((float) $detalle->precio_unitario * (float) $detalle->cantidad));
            $lineaIva = ($lineaSubtotal * (float) ($detalle->iva ?? 0)) / 100;

            $subtotal += $lineaSubtotal;
            $iva += $lineaIva;
            $otros += (float) ($detalle->otros_impuestos ?? 0);
            $retenciones += (float) ($detalle->retenciones ?? 0);
        }

        $oc->subtotal = $subtotal;
        $oc->iva = $iva;
        $oc->otros_impuestos = $otros;
        $oc->total = $subtotal + $iva + $otros - $retenciones;
    }

    return view(
        'ordencompra.index',
        compact(
            'ordenes',
            'areas',
            'obras',
            'proveedores',
            'centrosCosto',
            'search',
            'estado',
            'inicioSemana',
            'finSemana',
            'semanaAnterior',
            'semanaSiguiente',
            'esSemanaActual',
            'resumenSemanaGl'
        )
    );
}
   public function create()
{
    $this->authorizeAny(['ordenes_compra.create.access', 'ordenes de compra.access']);
    $proveedores = Proveedor::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $areas = Area::orderBy('nombre')->get();

    $obras = Obra::orderBy('nombre')->get();
    $centrosCosto = CentroCosto::where('activo', true)->orderBy('nombre')->get();
    $tiposIva = TipoIva::where('activo', true)->orderBy('porcentaje')->get();
    $selectedAreaId = null;

    if (request()->filled('area_codigo')) {
        $selectedAreaId = Area::where('codigo', request('area_codigo'))->value('id');
    }

    return view('ordencompra.create', compact('proveedores','areas','obras','centrosCosto','tiposIva','selectedAreaId'));
}

    /**
     * Guardar OC (estado inicial: programada -> legacy BORRADOR)
     */
    public function store(StoreOrdenCompraRequest $request, OrdenCompraNotificationService $notifications, ObraCivilMaterialRequestOrderService $materialRequestOrderService)
    {
        $this->authorizeAny(['ordenes_compra.create.access', 'ordenes de compra.access']);
        return DB::transaction(function () use ($request, $notifications, $materialRequestOrderService) {

            $area = Area::findOrFail($request->area_id);
            $esCajaChica = $request->boolean('es_caja_chica');
            $gastosSinFactura = $request->boolean('gastos_sin_factura');

            $oc = new OrdenCompra();

            $oc->folio        = $this->generarFolioPorArea($area); // folio por área
            $oc->proveedor_id = $request->filled('proveedor_id') ? (int) $request->proveedor_id : null;
            $oc->obra_id      = $request->obra_id ? (int)$request->obra_id : null;
            $oc->centro_costo_id = $request->centro_costo_id ? (int)$request->centro_costo_id : null;
            $oc->planeacion_gasto_id = $oc->obra_id && $request->planeacion_gasto_id ? (int)$request->planeacion_gasto_id : null;

            // nuevo
            $oc->area_id      = (int) $request->area_id;
            $oc->es_caja_chica = $esCajaChica;
            $oc->gastos_sin_factura = $gastosSinFactura;
            $oc->moneda       = $request->moneda;
            $oc->tipo_cambio  = $request->moneda === 'MXN' ? ($request->tipo_cambio ?: 1) : $request->tipo_cambio;

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
            $oc->registrado_por = auth()->id();

            // Totales iniciales (0). Se recalcularán al guardar detalles.
            $oc->subtotal = 0;
            // $oc->iva = 0;
            $oc->iva = (float) $request->iva; // IVA base (%)

            $oc->otros_impuestos = 0;
            $oc->total = 0;


            $oc->save();

            if ($request->filled('obra_civil_material_request_items')) {
                $materialRequestOrderService->attachApprovedItemsToOrder(
                    $request->input('obra_civil_material_request_items', []),
                    $oc,
                    $request->user()
                );
            } elseif ($request->filled('obra_civil_material_request_id')) {
                // Compatibilidad temporal con el selector anterior.
                $materialRequest = ObraCivilMaterialRequest::findOrFail((int) $request->obra_civil_material_request_id);
                $materialRequestOrderService->attachApprovedRequestToOrder($materialRequest, $oc, $request->user());
            }

            $notifications->creada($oc);

            return redirect()
                ->route('ordenes_compra.edit', $oc->id)
                ->with('success', 'Orden de compra creada (programada).');
        });
    }

public function edit($id)
{
    $this->authorizeAny([
        'ordenes_compra.edit.access',
        'ordenes de compra.access',
    ]);

    $oc = OrdenCompra::with([
        'detalles.producto',
        'detalles.tipoRetencion',
        'proveedor',
        'obra',
        'centroCosto',
        'areaCatalogo',
    ])->findOrFail($id);

    $areas = Area::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $proveedores = Proveedor::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $obras = Obra::orderBy('nombre')->get();

    $centrosCosto = CentroCosto::where('activo', true)
        ->orderBy('nombre')
        ->get();

    $tiposIva = TipoIva::where('activo', true)
        ->orderBy('porcentaje')
        ->get();

    $tiposRetencion = TipoRetencion::where('activo', true)
        ->orderBy('nombre')
        ->get();

    $subtotalGeneral = 0;
    $ivaMontoGeneral = 0;
    $otrosGeneral = 0;
    $retencionesGeneral = 0;

    foreach ($oc->detalles as $detalle) {
        $detalle->subtotal_bruto = round(
            (float) $detalle->precio_unitario * (float) $detalle->cantidad,
            2
        );

        $detalle->descuento_calculado = round(
            (float) ($detalle->descuento_importe ?? 0),
            2
        );

        $detalle->subtotal = round(
            (float) ($detalle->importe ?? ($detalle->subtotal_bruto - $detalle->descuento_calculado)),
            2
        );

        $detalle->iva_calculado = round(
            ($detalle->subtotal * (float) $detalle->iva) / 100,
            2
        );

        $detalle->total = round(
            $detalle->subtotal
            + $detalle->iva_calculado
            + (float) $detalle->otros_impuestos
            - (float) $detalle->retenciones,
            2
        );

        $subtotalGeneral += $detalle->subtotal;
        $ivaMontoGeneral += $detalle->iva_calculado;
        $otrosGeneral += (float) $detalle->otros_impuestos;
        $retencionesGeneral += (float) $detalle->retenciones;
    }

    $oc->subtotal_calc = round($subtotalGeneral, 2);
    $oc->iva_monto_calc = round($ivaMontoGeneral, 2);
    $oc->otros_monto_calc = round($otrosGeneral, 2);
    $oc->retenciones_monto_calc = round($retencionesGeneral, 2);

    $oc->total_calc = round(
        $subtotalGeneral
        + $ivaMontoGeneral
        + $otrosGeneral
        - $retencionesGeneral,
        2
    );

    return view('ordencompra.edit', compact(
        'oc',
        'areas',
        'proveedores',
        'obras',
        'centrosCosto',
        'tiposIva',
        'tiposRetencion'
    ));
}



    /**
     * Actualizar encabezado (solo si no está autorizada/cancelada)
     */
    public function update(UpdateOrdenCompraRequest $request, $id)
    {
        $this->authorizeAny(['ordenes_compra.edit.access', 'ordenes de compra.access']);
        $oc = OrdenCompra::findOrFail($id);

        // Regla: si ya está autorizada o cancelada, no se edita encabezado
        $estadoNorm = $oc->estado_normalizado;
        if (in_array($estadoNorm, ['autorizada','verificada','cancelada'], true)) {
            return back()->with('error', 'No puedes editar una orden autorizada o cancelada.');
        }

        return DB::transaction(function () use ($request, $oc) {

            $area = Area::findOrFail($request->area_id);
            $esCajaChica = $request->boolean('es_caja_chica');
            $gastosSinFactura = $request->boolean('gastos_sin_factura');

            $oc->proveedor_id = $request->filled('proveedor_id') ? (int) $request->proveedor_id : null;
            $oc->obra_id      = $request->obra_id ? (int)$request->obra_id : null;
            $oc->centro_costo_id = $request->centro_costo_id ? (int)$request->centro_costo_id : null;
            $oc->planeacion_gasto_id = $oc->obra_id && $request->planeacion_gasto_id ? (int)$request->planeacion_gasto_id : null;

            $oc->area_id      = (int) $request->area_id;
            $oc->es_caja_chica = $esCajaChica;
            $oc->gastos_sin_factura = $gastosSinFactura;
            $oc->moneda       = $request->moneda;
            $oc->tipo_cambio  = $request->moneda === 'MXN' ? ($request->tipo_cambio ?: 1) : $request->tipo_cambio;

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
//    public function autorizar($id)
// {
//     $user = auth()->user();
//     if (!auth()->user()->can('ordenes_compra.autorizar')) {
//         abort(403, 'No tienes permiso para autorizar órdenes de compra.');
//     }
//     // if (!in_array($user->rol ?? null, ['admin', 'compras'])) {
//     //     abort(403, 'No tienes permiso para autorizar órdenes de compra.');
//     // }

//     $oc = OrdenCompra::findOrFail($id);

//     if ($oc->estado_normalizado === 'cancelada') {
//         return back()->with('error', 'No puedes autorizar una orden cancelada.');
//     }

//     if ($oc->estado_normalizado === 'autorizada') {
//         return back()->with('success', 'La orden ya estaba autorizada.');
//     }

//     if ($oc->detalles()->count() === 0) {
//         return back()->with('error', 'No puedes autorizar una orden sin detalles.');
//     }

//     $oc->estado = 'AUTORIZADA';
//     $oc->fecha_autorizacion = now()->toDateString();
//     $oc->usuario_autoriza = $this->usuarioActualNombre();
//     $oc->save();

//     return back()->with('success', 'Orden autorizada.');
// }
//nueva funcion autorizar con la parte de las partidas
public function autorizar(Request $request, $id, OrdenCompraNotificationService $notifications)
{
    $this->authorizeAny(['ordenes_compra.authorize.access', 'ordenes_compra.autorizar'], 'No tienes permiso para autorizar ordenes de compra.');

    $oc = OrdenCompra::findOrFail($id);

    if ($oc->estado_normalizado === 'cancelada') {
        return back()->with('error', 'No puedes autorizar una orden cancelada.');
    }

    if (in_array($oc->estado_normalizado, ['autorizada', 'verificada'], true)) {
        return back()->with('success', 'La orden ya estaba autorizada.');
    }

    if ($oc->detalles()->count() === 0) {
        return back()->with('error', 'No puedes autorizar una orden sin detalles.');
    }

    // ── NUEVO: validación de saldo disponible ────────────────────────────────
    if ($oc->planeacion_gasto_id) {
        $gasto = \App\Models\ObraPlaneacionGasto::find($oc->planeacion_gasto_id);

        if ($gasto) {
            $tope = (float) $gasto->precio_unitario * (float) $gasto->cantidad;

            // Suma de OCs ya autorizadas para esta partida (excluyendo la actual)
            $gastadoPrevio = OrdenCompra::where('planeacion_gasto_id', $gasto->id)
                ->where('estado', 'AUTORIZADA')
                ->where('id', '!=', $oc->id)
                ->sum('total');

            $totalConEsta = (float) $gastadoPrevio + (float) $oc->total;

            if ($totalConEsta > $tope) {
                $exceso = number_format($totalConEsta - $tope, 2);
                $topeF  = number_format($tope, 2);
                return back()->with(
                    'error',
                    "No se puede autorizar: se excede el presupuesto de la partida \"{$gasto->concepto}\" "
                    . "por \${$exceso} (tope: \${$topeF})."
                );
            }
        }
    }
    // ── FIN validación ───────────────────────────────────────────────────────

    $civilExcesses = $this->civilAuthorizationExcesses($oc);

    if (!empty($civilExcesses) && !$request->boolean('confirmar_sobregiro_civil')) {
        return back()
            ->with('error', $this->civilAuthorizationExcessMessage($civilExcesses))
            ->with('civil_sobregiro_confirm_oc_id', $oc->id);
    }
    $oc->estado             = 'AUTORIZADA';
    $oc->fecha_autorizacion = now()->toDateString();
    $oc->usuario_autoriza   = $this->usuarioActualNombre();
    $oc->autorizado_por     = auth()->id();
    $oc->save();

    $notifications->autorizada($oc);

    return back()->with('success', 'Orden autorizada.');
}

private function civilAuthorizationExcessMessage(array $excesses): string
{
    $first = $excesses[0] ?? [];
    $total = count($excesses);
    $code = $first['code'] ?? ('ID ' . ($first['civil_concept_id'] ?? '-'));
    $description = $first['description'] ?? 'Concepto civil';
    $unit = $first['unit'] ?? '';
    $requestedQuantity = number_format((float) ($first['requested_quantity'] ?? 0), 4);
    $availableQuantity = number_format((float) ($first['available_quantity'] ?? 0), 4);
    $requestedAmount = number_format((float) ($first['requested_amount'] ?? 0), 2);
    $availableAmount = number_format((float) ($first['available_amount'] ?? 0), 2);
    $extra = $total > 1 ? ' Hay ' . ($total - 1) . ' concepto(s) adicional(es) excedidos.' : '';

    return 'No se puede autorizar: la OC excede el disponible del concepto civil '
        . $code . ' - ' . $description . '. Solicitado: '
        . $requestedQuantity . ($unit ? ' ' . $unit : '') . ' / $' . $requestedAmount
        . '. Disponible: ' . $availableQuantity . ($unit ? ' ' . $unit : '') . ' / $' . $availableAmount
        . '. Para continuar se requiere confirmacion de sobregiro.' . $extra;
}
private function civilAuthorizationExcesses(OrdenCompra $oc): array
{
    if (!$oc->obra || !$this->esObraCivil($oc->obra)) {
        return [];
    }

    $detalles = $oc->detalles()
        ->with('civilConcept')
        ->whereNotNull('civil_concept_id')
        ->get();

    if ($detalles->isEmpty()) {
        return [];
    }

    $conceptIds = $detalles->pluck('civil_concept_id')->filter()->unique()->values();
    $balances = app(CivilConceptBalanceService::class)->summaries($conceptIds, $oc->id);

    return $detalles
        ->groupBy('civil_concept_id')
        ->map(function ($items, $conceptId) use ($balances) {
            $first = $items->first();
            $concept = $first?->civilConcept;
            $balance = $balances->get((int) $conceptId, []);

            $requestedQuantity = (float) $items->sum('cantidad');
            $requestedAmount = (float) $items->sum('importe');
            $availableQuantity = (float) ($balance['available_quantity'] ?? 0);
            $availableAmount = (float) ($balance['available_amount'] ?? 0);
            $exceedsQuantity = $requestedQuantity > $availableQuantity;
            $exceedsAmount = $requestedAmount > $availableAmount;

            if (!$exceedsQuantity && !$exceedsAmount) {
                return null;
            }

            return [
                'civil_concept_id' => (int) $conceptId,
                'code' => $concept?->excel_code,
                'description' => $concept?->description ?? $first?->descripcion,
                'unit' => $concept?->unit ?? $first?->unidad,
                'requested_quantity' => $requestedQuantity,
                'available_quantity' => $availableQuantity,
                'excess_quantity' => $requestedQuantity - $availableQuantity,
                'requested_amount' => $requestedAmount,
                'available_amount' => $availableAmount,
                'excess_amount' => $requestedAmount - $availableAmount,
                'exceeds_quantity' => $exceedsQuantity,
                'exceeds_amount' => $exceedsAmount,
            ];
        })
        ->filter()
        ->values()
        ->all();
}
/**
 * Imprimir OC en PDF
 */
/**
 * Imprimir OC en PDF
 */
public function print(OrdenCompra $orden_compra)
{
    $this->authorizeAny(
        [
            'ordenes_compra.print.access',
            'ordenes_compra.imprimir',
        ],
        'No tienes permiso para imprimir ordenes de compra.'
    );

    $oc = $orden_compra->load([
        'proveedor',
        'obra',
        'centroCosto',
        'areaCatalogo',
        'registradoPor',
        'autorizadoPor',
        'detalles.producto',
        'detalles.tipoRetencion',
    ]);

    /*
     * La columna de retención solo se muestra cuando al menos una
     * partida tiene un tipo de retención o un importe retenido.
     */
    $mostrarRetenciones = $oc->detalles->contains(function ($detalle) {
        return ! empty($detalle->tipo_retencion_id)
            || (float) $detalle->retenciones > 0;
    });

    $mostrarDescuentos = $oc->detalles->contains(function ($detalle) {
        return (float) ($detalle->descuento_importe ?? 0) > 0
            || (float) ($detalle->descuento_porcentaje ?? 0) > 0;
    });

    $pdf = new \FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 12);

    $utf8 = fn ($texto) => utf8_decode((string) $texto);

    // ====== Configuración del layout ======
    $M = 10;
    $W = 216 - ($M * 2);
    $X0 = $M;
    $Y = $M;

    $BLUE = [0, 74, 173];
    $GRAY = [240, 240, 240];

    $setBlue = function () use ($pdf, $BLUE) {
        $pdf->SetDrawColor($BLUE[0], $BLUE[1], $BLUE[2]);
    };

    $setFillBlue = function () use ($pdf, $BLUE) {
        $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
    };

    $setFillGray = function () use ($pdf, $GRAY) {
        $pdf->SetFillColor($GRAY[0], $GRAY[1], $GRAY[2]);
    };

    $money = fn ($numero) => '$' . number_format((float) $numero, 2);

    $fecha = (string) ($oc->fecha ?? '');

    if ($fecha) {
        $fecha = substr($fecha, 0, 10);
    }

    $proveedorNombre = $oc->proveedor->nombre ?? '-';
    $area = $oc->areaCatalogo->nombre ?? ($oc->area ?? '-');

    $centroCostoNombre = $oc->centroCosto
        ? trim(
            ($oc->centroCosto->codigo
                ? $oc->centroCosto->codigo . ' - '
                : '')
            . $oc->centroCosto->nombre
        )
        : null;

    $obraNombre = $oc->obra
        ? trim(
            ($oc->obra->clave_obra
                ? $oc->obra->clave_obra . ' - '
                : '')
            . ($oc->obra->nombre ?? '')
        )
        : (
            $centroCostoNombre
                ? 'Compra general / ' . $centroCostoNombre
                : 'Compra general'
        );

    $obraFolio = $oc->obra->clave_obra
        ?? ($centroCostoNombre ?: 'Compra general');

    $datoBancario = function ($valor): string {
        $valor = trim((string) $valor);

        return $valor === '0' ? '' : $valor;
    };

    $proveedorBanco = $datoBancario(
        $oc->proveedor->banco ?? ''
    );

    $proveedorCuenta = $datoBancario(
        $oc->proveedor->cuenta ?? ''
    );

    $proveedorClabe = $datoBancario(
        $oc->proveedor->clabe ?? ''
    );

    $proveedorCuentaLabel = $proveedorCuenta
        ? 'CUENTA: ' . $proveedorCuenta
        : (
            $proveedorClabe
                ? 'CLABE: ' . $proveedorClabe
                : 'CUENTA: -'
        );

    // ====== HEADER ======
    $pdf->SetXY($X0, $Y);

    $logoPath = public_path('images/logoAzul.png');

    if (is_file($logoPath)) {
        $pdf->Image($logoPath, $X0, $Y, 35);
    }

    $pdf->SetXY($X0 + 40, $Y + 2);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->Cell(
        90,
        8,
        $utf8('ORDEN DE COMPRA'),
        0,
        0,
        'L'
    );

    if (!empty($oc->gastos_sin_factura)) {
        $pdf->SetXY($X0 + 40, $Y + 11);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(
            90,
            5,
            $utf8('GASTO SIN FACTURA'),
            0,
            0,
            'L'
        );
    }

    // Datos de empresa
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($X0 + 135, $Y + 2);

    $pdf->MultiCell(
        0,
        4,
        $utf8(
            "JUSTO SIERRA NO. 2469 COL. LADRON DE GUEVARA\n"
            . "GUADALAJARA, JALISCO, MEXICO C.P. 44600\n"
            . "TEL: 33) 3615-0741 3630-1056"
        ),
        0,
        'R'
    );

    // ====== DATOS DEL PROVEEDOR ======
    $Y += 34;

    $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($X0, $Y);
    $pdf->Cell(35);
    $pdf->Cell(
        85,
        5,
        $utf8('DATOS PROVEEDOR'),
        0,
        0,
        'L'
    );

    // Número de orden y obra
    $pdf->SetDrawColor(255, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);

    $pdf->SetXY($X0 + 125, $Y - 4);
    $pdf->Cell(
        71,
        6,
        $utf8('NO. DE ORDEN: ') . $utf8($oc->folio),
        1,
        1,
        'L'
    );

    $pdf->SetXY($X0 + 125, $Y + 2);
    $pdf->Cell(
        71,
        6,
        $utf8('NO. DE OBRA: ') . $utf8($obraFolio),
        1,
        1,
        'L'
    );

    $setBlue();
    $pdf->SetDrawColor(
        $BLUE[0],
        $BLUE[1],
        $BLUE[2]
    );

    // Caja del proveedor
    $Y += 8;
    $boxH = 32;

    $pdf->Rect($X0, $Y, $W, $boxH);

    $midX = $X0 + 125;

    $pdf->Line(
        $midX,
        $Y,
        $midX,
        $Y + $boxH
    );

    $row1 = $Y + 8;
    $row2 = $Y + 16;
    $row3 = $Y + 24;

    $pdf->Line($X0, $row1, $X0 + $W, $row1);
    $pdf->Line($X0, $row2, $X0 + $W, $row2);
    $pdf->Line($X0, $row3, $X0 + $W, $row3);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 9);

    $pdf->SetXY($X0 + 2, $Y + 2);
    $pdf->Cell(
        0,
        6,
        $utf8('NOMBRE: ')
        . $utf8($proveedorNombre),
        0,
        0,
        'L'
    );

    $pdf->SetXY($X0 + 2, $Y + 10);
    $pdf->Cell(
        0,
        6,
        $utf8('ATENCION: ')
        . $utf8(
            $oc->atencion
            ?? ($oc->proveedor->contacto ?? '')
        ),
        0,
        0,
        'L'
    );

    $pdf->SetXY($X0 + 2, $Y + 18);
    $pdf->Cell(
        0,
        6,
        $utf8('DOMICILIO: ')
        . $utf8($oc->proveedor->domicilio ?? ''),
        0,
        0,
        'L'
    );

    $pdf->SetXY($X0 + 2, $Y + 26);
    $pdf->SetFont('Arial', '', 8);

    $pdf->Cell(
        $midX - $X0 - 4,
        5,
        $utf8('RFC: ')
        . $utf8($oc->proveedor->rfc ?? ''),
        0,
        0,
        'L'
    );

    // Columna derecha
    $pdf->SetFont('Arial', 'B', 9);

    $pdf->SetXY($midX + 2, $Y + 2);
    $pdf->Cell(
        0,
        6,
        $utf8('FECHA: ') . $utf8($fecha),
        0,
        0,
        'L'
    );

    $pdf->SetXY($midX + 2, $Y + 10);
    $pdf->Cell(
        0,
        6,
        $utf8('AREA: ') . $utf8($area),
        0,
        0,
        'L'
    );

    $pdf->SetXY($midX + 2, $Y + 18);
    $pdf->Cell(
        0,
        6,
        $utf8('OBRA: ') . $utf8($obraNombre),
        0,
        0,
        'L'
    );

    $pdf->SetXY($midX + 2, $Y + 26);
    $pdf->SetFont('Arial', '', 7.5);

    $pdf->MultiCell(
        $X0 + $W - $midX - 4,
        3.5,
        $utf8('BANCO: ')
        . $utf8($proveedorBanco ?: '-')
        . "\n"
        . $utf8($proveedorCuentaLabel),
        0,
        'L'
    );

    // ====== TABLA DE DETALLES ======
$Y += $boxH + 6;
$pdf->SetXY($X0, $Y);

$wCant = 13;
$wUni  = 16;
$wPU   = 23;
$wDescuento = $mostrarDescuentos ? 18 : 0;
$wIVA  = 18;
$wRet  = $mostrarRetenciones ? 19 : 0;
$wImp  = 23;

// Todo el espacio restante se entrega a la descripción.
$wDesc = $W - (
    $wCant
    + $wUni
    + $wPU
    + $wDescuento
    + $wIVA
    + $wRet
    + $wImp
);

// Encabezado
$setFillBlue();
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 7.5);

$pdf->Cell($wCant, 7, $utf8('CANT'), 1, 0, 'C', true);
$pdf->Cell($wUni,  7, $utf8('UNIDAD'), 1, 0, 'C', true);
$pdf->Cell($wDesc, 7, $utf8('DESCRIPCION'), 1, 0, 'C', true);
$pdf->Cell($wPU,   7, $utf8('P. UNIT.'), 1, 0, 'C', true);

if ($mostrarDescuentos) {
    $pdf->Cell($wDescuento, 7, $utf8('DESC.'), 1, 0, 'C', true);
}

$pdf->Cell($wIVA,  7, $utf8('IVA'), 1, 0, 'C', true);

if ($mostrarRetenciones) {
    $pdf->Cell($wRet, 7, $utf8('RET.'), 1, 0, 'C', true);
}

$pdf->Cell($wImp, 7, $utf8('IMPORTE'), 1, 1, 'C', true);

// Cuerpo
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 8);

$subCalc   = 0.0;
$ivaCalc   = 0.0;
$retCalc   = 0.0;
$otrosCalc = 0.0;

/*
 * Calcula aproximadamente la cantidad de líneas que ocupará
 * un texto dentro de un ancho determinado.
 */
$calcularLineas = function (
    \FPDF $pdf,
    string $texto,
    float $ancho
): int {
    $texto = trim($texto);

    if ($texto === '') {
        return 1;
    }

    $anchoUtil = $ancho - 4;
    $palabras = preg_split('/\s+/', $texto);
    $lineas = 1;
    $lineaActual = '';

    foreach ($palabras as $palabra) {
        $prueba = $lineaActual === ''
            ? $palabra
            : $lineaActual . ' ' . $palabra;

        if ($pdf->GetStringWidth($prueba) <= $anchoUtil) {
            $lineaActual = $prueba;
        } else {
            $lineas++;
            $lineaActual = $palabra;
        }
    }

    return max(1, $lineas);
};

foreach ($oc->detalles as $detalle) {
    $cant = (float) ($detalle->cantidad ?? 0);
    $uni  = (string) ($detalle->unidad ?? '');
    $desc = (string) ($detalle->descripcion ?? '');
    $pu   = (float) ($detalle->precio_unitario ?? 0);
    $brutoLinea = round($cant * $pu, 2);
    $descuentoPctLinea = (float) ($detalle->descuento_porcentaje ?? 0);
    $descuentoLinea = round((float) ($detalle->descuento_importe ?? 0), 2);

    if ($descuentoLinea <= 0 && $descuentoPctLinea > 0) {
        $descuentoLinea = round($brutoLinea * ($descuentoPctLinea / 100), 2);
    }

    $subtotalLinea = (float) (
        $detalle->importe
        ?? max(0, $brutoLinea - $descuentoLinea)
    );

    $ivaPctLinea = is_numeric($detalle->iva ?? null)
        ? (float) $detalle->iva
        : (float) ($oc->iva ?? 0);

    $ivaLinea = round(
        $subtotalLinea * ($ivaPctLinea / 100),
        2
    );

    $retencionLinea = round(
        (float) ($detalle->retenciones ?? 0),
        2
    );

    $otrosLinea = round(
        (float) ($detalle->otros_impuestos ?? 0),
        2
    );

    $importeLinea = round(
        $subtotalLinea
        + $ivaLinea
        + $otrosLinea
        - $retencionLinea,
        2
    );

    $subCalc   += $subtotalLinea;
    $ivaCalc   += $ivaLinea;
    $retCalc   += $retencionLinea;
    $otrosCalc += $otrosLinea;

    /*
     * Primero calculamos la altura requerida por la descripción.
     * Todas las celdas usarán exactamente esta misma altura.
     */
    $lineHeight = 6;
    $lineasDescripcion = $calcularLineas(
        $pdf,
        $utf8($desc),
        $wDesc
    );

    $rowH = max(
        7,
        $lineasDescripcion * $lineHeight
    );

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    /*
     * Dibujamos primero todos los bordes con la misma altura.
     */
    $cursorX = $x;

    $pdf->Rect($cursorX, $y, $wCant, $rowH);
    $cursorX += $wCant;

    $pdf->Rect($cursorX, $y, $wUni, $rowH);
    $cursorX += $wUni;

    $pdf->Rect($cursorX, $y, $wDesc, $rowH);
    $cursorX += $wDesc;

    $pdf->Rect($cursorX, $y, $wPU, $rowH);
    $cursorX += $wPU;

    if ($mostrarDescuentos) {
        $pdf->Rect($cursorX, $y, $wDescuento, $rowH);
        $cursorX += $wDescuento;
    }

    $pdf->Rect($cursorX, $y, $wIVA, $rowH);
    $cursorX += $wIVA;

    if ($mostrarRetenciones) {
        $pdf->Rect($cursorX, $y, $wRet, $rowH);
        $cursorX += $wRet;
    }

    $pdf->Rect($cursorX, $y, $wImp, $rowH);

    /*
     * Cantidad
     */
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $wCant,
        $rowH,
        number_format($cant, 1),
        0,
        0,
        'C'
    );

    /*
     * Unidad
     */
    $pdf->SetXY($x + $wCant, $y);
    $pdf->Cell(
        $wUni,
        $rowH,
        $utf8($uni ?: '-'),
        0,
        0,
        'C'
    );

    /*
     * Descripción. El borde ya fue dibujado con Rect(),
     * por eso MultiCell no lleva borde.
     */
    $pdf->SetXY(
        $x + $wCant + $wUni + 1,
        $y + 1
    );

    $pdf->MultiCell(
        $wDesc - 2,
        $lineHeight,
        $utf8($desc),
        0,
        'L'
    );

    /*
     * Precio unitario
     */
    $precioX = $x
        + $wCant
        + $wUni
        + $wDesc;

    $pdf->SetXY($precioX, $y);
    $pdf->Cell(
        $wPU,
        $rowH,
        $money($pu),
        0,
        0,
        'R'
    );

    $descuentoX = $precioX + $wPU;

    if ($mostrarDescuentos) {
        $pdf->SetXY($descuentoX, $y);
        $pdf->SetFont('Arial', '', 7.5);

        $textoDescuento = $descuentoLinea > 0
            ? '-$' . number_format($descuentoLinea, 2)
            : '-';

        $pdf->Cell(
            $wDescuento,
            $rowH,
            $textoDescuento,
            0,
            0,
            $descuentoLinea > 0 ? 'R' : 'C'
        );

        $pdf->SetFont('Arial', '', 8);
    }

    /*
     * IVA: solo importe.
     */
    $ivaX = $descuentoX + $wDescuento;

    $pdf->SetXY($ivaX, $y);
    $pdf->Cell(
        $wIVA,
        $rowH,
        $money($ivaLinea),
        0,
        0,
        'R'
    );

    /*
     * Retención: únicamente el importe para ahorrar espacio.
     */
    $siguienteX = $ivaX + $wIVA;

    if ($mostrarRetenciones) {
        $pdf->SetXY($siguienteX, $y);

        $textoRetencion = $retencionLinea > 0
            ? '-$' . number_format($retencionLinea, 2)
            : '-';

        $pdf->SetFont('Arial', '', 7.5);

        $pdf->Cell(
            $wRet,
            $rowH,
            $textoRetencion,
            0,
            0,
            $retencionLinea > 0 ? 'R' : 'C'
        );

        $pdf->SetFont('Arial', '', 8);

        $siguienteX += $wRet;
    }

    /*
     * Importe final.
     */
    $pdf->SetXY($siguienteX, $y);
    $pdf->SetFont('Arial', 'B', 8);

    $pdf->Cell(
        $wImp,
        $rowH,
        $money($importeLinea),
        0,
        0,
        'R'
    );

    $pdf->SetFont('Arial', '', 8);

    /*
     * Avanzamos manualmente exactamente la altura de la fila.
     */
    $pdf->SetXY(
        $X0,
        $y + $rowH
    );
}

    // ====== NOTAS Y TOTALES ======
    $Y = $pdf->GetY() + 6;

    $subtotal = round($subCalc, 2);
    $ivaMonto = round($ivaCalc, 2);
    $retencionesMonto = round($retCalc, 2);
    $otrosMonto = round($otrosCalc, 2);

    $total = round(
        $subtotal
        + $ivaMonto
        + $otrosMonto
        - $retencionesMonto,
        2
    );

    $ivaPctMostrado = (float) ($oc->iva ?? 0);

    /*
     * Notas.
     * Se imprimen en una columna acotada para que nunca invadan el cuadro de
     * totales. Si el comentario es muy largo, se corta con elipsis; el texto
     * completo queda persistido en ordenes_compra.comentarios.
     */
    $totW = 62;
    $totX = $X0 + $W - $totW;
    $notasLabelW = 15;
    $notasGap = 4;
    $notasX = $X0 + $notasLabelW + 1;
    $notasW = $totX - $notasX - $notasGap;
    $notasLineH = 5.5;
    $notasMaxLineas = 4;

    $partirTexto = function (string $texto, float $anchoMax) use ($pdf): array {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        if ($texto === '') {
            return [];
        }

        $lineas = [];
        $linea = '';

        foreach (explode(' ', $texto) as $palabra) {
            $candidata = $linea === '' ? $palabra : $linea . ' ' . $palabra;

            if ($pdf->GetStringWidth($candidata) <= $anchoMax) {
                $linea = $candidata;
                continue;
            }

            if ($linea !== '') {
                $lineas[] = $linea;
            }

            $linea = $palabra;
        }

        if ($linea !== '') {
            $lineas[] = $linea;
        }

        return $lineas;
    };

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($X0, $Y);
    $pdf->Cell(
        $notasLabelW,
        6,
        $utf8('NOTAS:'),
        0,
        0,
        'L'
    );

    $pdf->SetFont('Arial', '', 9);

    $notasLineas = $partirTexto((string) ($oc->comentarios ?? ''), $notasW);
    $notasTruncadas = count($notasLineas) > $notasMaxLineas;
    $notasLineas = array_slice($notasLineas, 0, $notasMaxLineas);

    if ($notasTruncadas && ! empty($notasLineas)) {
        $ultima = rtrim($notasLineas[count($notasLineas) - 1]);

        while ($ultima !== '' && $pdf->GetStringWidth($ultima . '...') > $notasW) {
            $ultima = rtrim(substr($ultima, 0, -1));
        }

        $notasLineas[count($notasLineas) - 1] = $ultima . '...';
    }

    $notaY = $Y;

    foreach ($notasLineas as $notaLinea) {
        $pdf->SetXY($notasX, $notaY);
        $pdf->Cell(
            $notasW,
            $notasLineH,
            $utf8($notaLinea),
            0,
            0,
            'L'
        );

        $notaY += $notasLineH;
    }

    /*
     * Construcción dinámica de las filas del resumen.
     * Retenciones y otros impuestos solamente aparecen cuando existen.
     */
    $filasTotales = [
        [
            'label' => 'Subtotal:',
            'monto' => $subtotal,
            'total' => false,
        ],
        [
            'label' => 'IVA ('
                . number_format($ivaPctMostrado, 0)
                . '%):',
            'monto' => $ivaMonto,
            'total' => false,
        ],
    ];

    if ($retencionesMonto > 0) {
        $filasTotales[] = [
            'label' => 'Retenciones:',
            'monto' => -$retencionesMonto,
            'total' => false,
        ];
    }

    if ($otrosMonto != 0) {
        $filasTotales[] = [
            'label' => 'Otros impuestos:',
            'monto' => $otrosMonto,
            'total' => false,
        ];
    }

    $monedaStr = strtoupper(trim((string) ($oc->moneda ?? 'MXN')));

    if (in_array($monedaStr, ['USD', 'DOLARES', 'DÓLARES'], true)) {
        $labelTotal = 'Total USD:';
    } elseif (in_array($monedaStr, ['EUR', 'EUROS'], true)) {
        $labelTotal = 'Total EUR:';
    } elseif ($monedaStr !== '' && ! in_array($monedaStr, ['MXN', 'MXP', 'PESOS', 'MN', 'M.N.'], true)) {
        $labelTotal = 'Total ' . $monedaStr . ':';
    } else {
        $labelTotal = 'Total:';
    }

    $filasTotales[] = [
        'label' => $labelTotal,
        'monto' => $total,
        'total' => true,
    ];

    // Caja de totales compacta
    $totY = $Y;
    $altoFilaTotal = 6.5;
    $totH = count($filasTotales) * $altoFilaTotal;

    $pdf->SetDrawColor(
        $BLUE[0],
        $BLUE[1],
        $BLUE[2]
    );

    $pdf->Rect(
        $totX,
        $totY,
        $totW,
        $totH
    );

    $pdf->SetXY($totX, $totY);

    foreach ($filasTotales as $fila) {
        $esTotal = $fila['total'];

        $pdf->SetX($totX);
        $pdf->SetFont(
            'Arial',
            'B',
            $esTotal ? 10 : 9
        );

        $pdf->Cell(
            36,
            $altoFilaTotal,
            $utf8($fila['label']),
            0,
            0,
            'R'
        );

        /*
         * Las retenciones se imprimen con signo negativo.
         */
        $montoTexto = $fila['monto'] < 0
            ? '-'
                . $money(abs($fila['monto']))
            : $money($fila['monto']);

        $pdf->SetFont(
            'Arial',
            $esTotal ? 'B' : '',
            $esTotal ? 10 : 9
        );

        $pdf->Cell(
            26,
            $altoFilaTotal,
            $montoTexto,
            0,
            1,
            'R'
        );
    }

    // ====== DATOS DE FACTURACIÓN ======
    $Y = max(
        $pdf->GetY() + 8,
        $notaY + 8,
        $totY + $totH + 4
    );

    $pdf->SetXY($X0, $Y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(
        $BLUE[0],
        $BLUE[1],
        $BLUE[2]
    );

    $pdf->Cell(
        0,
        6,
        $utf8('DATOS DE FACTURACION:'),
        0,
        1,
        'L'
    );

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetDrawColor(
        $BLUE[0],
        $BLUE[1],
        $BLUE[2]
    );

    $pdf->Rect(
        $X0,
        $Y + 6,
        $W,
        22
    );

    $tiposPagoMap = [
        'PUE' => 'PUE - Pago en una sola exhibicion',
        'PPD' => 'PPD - Pago en parcialidades o diferido',
    ];

    $formasPagoMap = [
        '01' => '01 - Efectivo',
        '02' => '02 - Cheque nominativo',
        '03' => '03 - Transferencia electronica de fondos',
        '04' => '04 - Tarjeta de credito',
        '28' => '28 - Tarjeta de debito',
        '99' => '99 - Por definir',
    ];

    $tipoPagoTexto  = $tiposPagoMap[$oc->tipo_pago] ?? ($oc->tipo_pago ?: '-');
    $formaPagoTexto = $formasPagoMap[$oc->forma_pago] ?? ($oc->forma_pago ?: '-');

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($X0 + 2, $Y + 8);

    $pdf->MultiCell(
        100,
        4,
        $utf8(
            "Razon Social: Rivera Construcciones\n"
            . "RFC: RCO820921T86\n"
            . "Domicilio: Justo Sierra #2469, Col. Ladron de Guevara\n"
            . "Uso del CFDI: G03 Gastos en general"
        ),
        0,
        'L'
    );

    $pdf->SetXY($X0 + 105, $Y + 8);

    $pdf->MultiCell(
        0,
        4,
        $utf8(
            "Regimen del Capital: S.A. de C.V.\n"
            . "Regimen fiscal: General de ley\n"
            . "Metodo de pago: " . $tipoPagoTexto . "\n"
            . "Forma de pago: " . $formaPagoTexto
        ),
        0,
        'L'
    );

    // ====== Firmas ======
    $Y += 34;

    $pdf->SetDrawColor(120, 120, 120);

    $pdf->Line(
        $X0 + 5,
        $Y + 12,
        $X0 + 55,
        $Y + 12
    );

    $pdf->Line(
        $X0 + 60,
        $Y + 12,
        $X0 + 110,
        $Y + 12
    );

    $pdf->Line(
        $X0 + 115,
        $Y + 12,
        $X0 + 165,
        $Y + 12
    );

    $pdf->Line(
        $X0 + 170,
        $Y + 12,
        $X0 + 205,
        $Y + 12
    );

    $solicitaNombre = $oc->registradoPor->name
        ?? $oc->usuario_registro
        ?? '';

    $autorizaNombre = $oc->autorizadoPor->name
        ?? $oc->usuario_autoriza
        ?? '';

    $firmasImpresas = DocumentoFirmante::query()
        ->with('user:id,name')
        ->where('documento', DocumentoFirmante::DOCUMENTO_ORDEN_COMPRA)
        ->where('activo', true)
        ->whereIn('campo', [
            DocumentoFirmante::CAMPO_VOBO_1,
            DocumentoFirmante::CAMPO_VOBO_2,
            DocumentoFirmante::CAMPO_ENTERADO,
        ])
        ->get()
        ->keyBy('campo');

    $vobo1Nombre = $firmasImpresas->get(DocumentoFirmante::CAMPO_VOBO_1)?->user?->name ?? '';
    $vobo2Nombre = $firmasImpresas->get(DocumentoFirmante::CAMPO_VOBO_2)?->user?->name ?? '';

    // Combinar ambos slots; si solo hay uno, no aparece el separador
    $voboNombre = trim(implode(' / ', array_filter([$vobo1Nombre, $vobo2Nombre])));

    $enteradoNombre = $firmasImpresas
        ->get(DocumentoFirmante::CAMPO_ENTERADO)
        ?->user
        ?->name
        ?? '';

    $pdf->SetFont('Arial', 'B', 8);

    $pdf->SetXY($X0 + 5, $Y + 13);
    $pdf->Cell(
        50,
        5,
        $utf8($solicitaNombre),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 60, $Y + 13);
    $pdf->Cell(
        50,
        5,
        $utf8($autorizaNombre),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 115, $Y + 13);
    $pdf->Cell(
        50,
        5,
        $utf8($voboNombre),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 170, $Y + 13);
    $pdf->Cell(
        35,
        5,
        $utf8($enteradoNombre),
        0,
        0,
        'C'
    );

    $pdf->SetTextColor(200, 0, 0);
    $pdf->SetFont('Arial', 'B', 8);

    $pdf->SetXY($X0 + 5, $Y + 18);
    $pdf->Cell(
        50,
        5,
        $utf8('SOLICITA'),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 60, $Y + 18);
    $pdf->Cell(
        50,
        5,
        $utf8('AUTORIZA'),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 115, $Y + 18);
    $pdf->Cell(
        50,
        5,
        $utf8('VoBo'),
        0,
        0,
        'C'
    );

    $pdf->SetXY($X0 + 170, $Y + 18);
    $pdf->Cell(
        35,
        5,
        $utf8('ENTERADO'),
        0,
        0,
        'C'
    );

    // Pie de página
    $pdf->SetTextColor(120, 120, 120);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetXY($X0, 270);

    $pdf->Cell(
        0,
        5,
        $utf8('Page 1/1'),
        0,
        0,
        'C'
    );

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header(
            'Content-Disposition',
            'inline; filename="OC_'
            . $oc->folio
            . '.pdf"'
        );
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

        if (in_array($oc->estado_normalizado, ['autorizada', 'verificada'], true)) {
            return back()->with('error', 'No puedes cancelar una orden autorizada o verificada.');
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
    /**
     * Verificar OC de almacen para corte semanal.
     */
    public function verificar($id)
    {
        $this->authorizeAny(
            ['ordenes_compra.verify.access'],
            'No tienes permiso para verificar ordenes de compra.'
        );

        $oc = OrdenCompra::with('areaCatalogo')->findOrFail($id);

        if ($oc->estado_normalizado === 'cancelada') {
            return back()->with('error', 'No puedes verificar una orden cancelada.');
        }

        if ($oc->estado_normalizado === 'verificada') {
            return back()->with('success', 'La orden ya estaba verificada.');
        }

        if ($oc->estado_normalizado !== 'autorizada') {
            return back()->with('error', 'Solo puedes verificar ordenes autorizadas.');
        }

        $areaCodigo = strtoupper(trim((string) ($oc->areaCatalogo->codigo ?? '')));
        if ($areaCodigo !== 'GL') {
            return back()->with('error', 'La verificacion semanal aplica solo para ordenes del almacen GL.');
        }

        if (! $oc->es_caja_chica) {
            return back()->with('error', 'Solo puedes verificar ordenes marcadas como caja chica.');
        }

        $oc->estado = 'VERIFICADA';
        $oc->fecha_verificacion = now();
        $oc->usuario_verifica = $this->usuarioActualNombre();
        $oc->verificado_por = auth()->id();
        $oc->save();

        return back()->with('success', 'Orden verificada para el corte semanal.');
    }
    // ============================
    // Helpers
    // ============================

    private function estadoToLegacy(string $estado): string
    {
        return match ($estado) {
            'programada' => 'BORRADOR',
            'autorizada' => 'AUTORIZADA',
            'verificada' => 'VERIFICADA',
            'cancelada'  => 'CANCELADA',
            default      => strtoupper($estado),
        };
    }


    private function authorizeAny(array $permissions, string $message = 'No tienes permiso para realizar esta accion.'): void
    {
        $user = auth()->user();

        if (!$user || !$user->canAny($permissions)) {
            throw new AuthorizationException($message);
        }
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

    public function partidasPorObra($obra_id)
{
    $obra = \App\Models\Obra::findOrFail($obra_id);

    if ($this->esObraCivil($obra)) {
        return response()->json($this->partidasCivilPorObra($obra));
    }

    return response()->json($this->partidasPlaneacionPorObra($obra));
}

private function partidasPlaneacionPorObra(Obra $obra)
{
    // IDs de presupuestos vinculados a esta obra
    $presupuestoIds = $obra->presupuestos_vinculados()->pluck('presupuestos.id');

    // Filas base (numero_semana = 0) de la planeacion de esta obra
    $gastos = \App\Models\ObraPlaneacionGasto::query()
        ->where(function ($q) use ($obra, $presupuestoIds) {
            $q->where('obra_id', $obra->id)
              ->orWhereIn('presupuesto_id', $presupuestoIds);
        })
        ->where('numero_semana', 0)
        ->get();

    // Para cada partida calculamos el gastado autorizado sumando OCs autorizadas
    $gastadoPorPartida = \App\Models\OrdenCompra::query()
        ->whereIn('planeacion_gasto_id', $gastos->pluck('id'))
        ->where('estado', 'AUTORIZADA')
        ->selectRaw('planeacion_gasto_id, SUM(total) as total_gastado')
        ->groupBy('planeacion_gasto_id')
        ->pluck('total_gastado', 'planeacion_gasto_id');

    return $gastos->map(function ($g) use ($gastadoPorPartida) {
        $tope     = (float) $g->precio_unitario * (float) $g->cantidad;
        $gastado  = (float) ($gastadoPorPartida[$g->id] ?? 0);
        $disponible = max(0, $tope - $gastado);

        return [
            'id'         => $g->id,
            'source'     => 'planeacion',
            'partida'    => $g->partida,
            'concepto'   => $g->concepto,
            'tope'       => $tope,
            'gastado'    => $gastado,
            'disponible' => $disponible,
        ];
    })->values();
}

private function partidasCivilPorObra(Obra $obra)
{
    $import = \App\Models\CivilCatalogImport::query()
        ->where('obra_id', $obra->id)
        ->whereIn('status', ['imported', 'validated'])
        ->latest()
        ->first();

    if (!$import) {
        return collect();
    }

    $partidas = \App\Models\CivilPartida::query()
        ->with('building')
        ->whereHas('building', function ($query) use ($import) {
            $query->where('civil_catalog_import_id', $import->id);
        })
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    $gastadoPorPartida = DB::table('orden_compra_detalles as ocd')
        ->join('civil_concepts as cc', 'cc.id', '=', 'ocd.civil_concept_id')
        ->join('ordenes_compra as oc', 'oc.id', '=', 'ocd.orden_compra_id')
        ->whereIn('cc.civil_partida_id', $partidas->pluck('id'))
        ->where('oc.estado', 'AUTORIZADA')
        ->selectRaw('cc.civil_partida_id, SUM(ocd.importe) as total_gastado')
        ->groupBy('cc.civil_partida_id')
        ->pluck('total_gastado', 'cc.civil_partida_id');

    return $partidas->map(function ($partida) use ($gastadoPorPartida) {
        $tope = (float) $partida->budget_amount;
        $gastado = (float) ($gastadoPorPartida[$partida->id] ?? 0);

        return [
            'id'         => $partida->id,
            'source'     => 'civil',
            'partida'    => $partida->building?->name ?: 'Obra civil',
            'concepto'   => trim(($partida->code ? $partida->code . ' ' : '') . $partida->name),
            'tope'       => $tope,
            'gastado'    => $gastado,
            'disponible' => max(0, $tope - $gastado),
        ];
    })->values();
}

private function esObraCivil(Obra $obra): bool
{
    return in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true);
}

public function buscarConceptosCivil(Request $request, OrdenCompra $orden_compra)
{
    $term = trim((string) $request->get('q', ''));

    if (mb_strlen($term) < 2 || !$orden_compra->obra_id || !$orden_compra->obra || !$this->esObraCivil($orden_compra->obra)) {
        return response()->json([]);
    }

    $import = \App\Models\CivilCatalogImport::query()
        ->where('obra_id', $orden_compra->obra_id)
        ->whereIn('status', ['imported', 'validated'])
        ->latest()
        ->first();

    if (!$import) {
        return response()->json([]);
    }

    $conceptos = \App\Models\CivilConcept::query()
        ->with('partida.building')
        ->where('is_active', true)
        ->whereHas('partida.building', function ($query) use ($import) {
            $query->where('civil_catalog_import_id', $import->id);
        })
        ->where(function ($query) use ($term) {
            $query->where('description', 'like', "%{$term}%")
                ->orWhere('excel_code', 'like', "%{$term}%");
        })
        ->orderBy('sort_order')
        ->orderBy('id')
        ->limit(20)
        ->get();

    $balances = app(CivilConceptBalanceService::class)->summaries($conceptos->pluck('id'));

    return response()->json($conceptos->map(function ($concepto) use ($balances) {
        $partida = $concepto->partida;
        $building = $partida?->building;
        $balance = $balances->get($concepto->id, []);

        return [
            'id' => $concepto->id,
            'civil_concept_id' => $concepto->id,
            'legacy_prod_id' => null,
            'nombre' => $concepto->description,
            'descripcion' => trim(($building?->name ? $building->name . ' / ' : '') . (($partida?->code || $partida?->name) ? trim(($partida?->code ? $partida->code . ' ' : '') . ($partida?->name ?? '')) : '')),
            'unidad' => $concepto->unit,
            'sku' => $concepto->excel_code,
            'ultimo_precio' => (float) $concepto->unit_price,
            'moneda_precio' => 'MXN',
            'cantidad_presupuesto' => (float) $concepto->budget_quantity,
            'importe_presupuesto' => (float) $concepto->budget_amount,
            'cantidad_usada' => (float) ($balance['used_quantity'] ?? 0),
            'importe_usado' => (float) ($balance['used_amount'] ?? 0),
            'cantidad_disponible' => (float) ($balance['available_quantity'] ?? $concepto->budget_quantity),
            'importe_disponible' => (float) ($balance['available_amount'] ?? $concepto->budget_amount),
            'ordenes_count' => (int) ($balance['orders_count'] ?? 0),
        ];
    }));
}

public function solicitudesMaterialAprobadasPorObra(Obra $obra, ObraCivilMaterialRequestOrderService $materialRequestOrderService)
{
    $this->authorizeAny(['ordenes_compra.create.access', 'ordenes de compra.access']);

    return response()->json([
        'ok' => true,
        'data' => $materialRequestOrderService->approvedPendingItemOptions($obra),
    ]);
}
public function buscarInsumosObra(Request $request, OrdenCompra $orden_compra)
{
    $term = trim((string) $request->get('q', ''));

    if (mb_strlen($term) < 2 || !$orden_compra->obra_id || !$orden_compra->obra || !$this->esObraCivil($orden_compra->obra)) {
        return response()->json([]);
    }

    $insumos = ObraCivilInsumo::query()
        ->where('obra_id', $orden_compra->obra_id)
        ->where('is_active', true)
        ->where('tipo', 'material')
        ->where(function ($query) use ($term) {
            $query->where('concepto', 'like', "%{$term}%")
                ->orWhere('codigo', 'like', "%{$term}%");
        })
        ->orderBy('sort_order')
        ->orderBy('id')
        ->limit(20)
        ->get();

    $balances = app(ObraCivilInsumoBalanceService::class)->summaries($insumos->pluck('id'));

    return response()->json($insumos->map(function (ObraCivilInsumo $insumo) use ($balances) {
        $balance = $balances->get($insumo->id, []);
        $budgetAmount = (float) ($insumo->importe_importado ?? $insumo->importe_calculado ?? 0);

        return [
            'id' => $insumo->id,
            'obra_civil_insumo_id' => $insumo->id,
            'civil_concept_id' => null,
            'legacy_prod_id' => null,
            'nombre' => $insumo->concepto,
            'descripcion' => $insumo->concepto,
            'unidad' => $insumo->unidad,
            'sku' => $insumo->codigo,
            'tipo' => $insumo->tipo,
            'ultimo_precio' => (float) $insumo->precio_unitario,
            'moneda_precio' => 'MXN',
            'cantidad_presupuesto' => (float) $insumo->cantidad_presupuestada,
            'importe_presupuesto' => $budgetAmount,
            'cantidad_usada' => (float) ($balance['used_quantity'] ?? 0),
            'importe_usado' => (float) ($balance['used_amount'] ?? 0),
            'cantidad_disponible' => (float) ($balance['available_quantity'] ?? $insumo->cantidad_presupuestada),
            'importe_disponible' => (float) ($balance['available_amount'] ?? $budgetAmount),
            'ordenes_count' => (int) ($balance['ordenes_count'] ?? 0),
        ];
    }));
}
public function exportarListaPagos(
    Request $request,
    string $formaPago
) {
    $this->authorizeAny([
        'ordenes_compra.view.access',
        'ordenes_compra.print.access',
        'ordenes_compra.imprimir',
        'ordenes de compra.access',
    ]);

    /*
     * Formas de pago permitidas.
     */
    $formasPago = [
        '01' => 'PAGOS EN EFECTIVO',
        '04' => 'PAGOS CON TARJETA DE CREDITO',
    ];

    if (!array_key_exists($formaPago, $formasPago)) {
        abort(404, 'Forma de pago no válida.');
    }

    /*
     * Área permitida.
     */
    $areaCodigo = strtoupper(
        trim((string) $request->query('area_codigo'))
    );

    if ($areaCodigo !== 'GL') {
        abort(
            403,
            'Este reporte solamente está disponible para el área Giralda.'
        );
    }

    $areaCatalogo = Area::query()
        ->where('codigo', $areaCodigo)
        ->firstOrFail();

    /*
     * Semana seleccionada.
     *
     * Si no viene el parámetro, se utiliza la semana actual.
     * Cualquier fecha válida se normaliza al lunes de su semana.
     */
    try {
        $fechaSemana = $request->filled('semana')
            ? Carbon::createFromFormat(
                'Y-m-d',
                (string) $request->query('semana')
            )->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);
    } catch (\Throwable $e) {
        abort(422, 'La semana seleccionada no tiene un formato válido.');
    }

    $inicioSemanaActual = now()
        ->startOfWeek(Carbon::MONDAY)
        ->startOfDay();

    /*
     * No permitir exportar semanas futuras.
     */
    if ($fechaSemana->greaterThan($inicioSemanaActual)) {
        abort(422, 'No se pueden exportar semanas futuras.');
    }

    $inicioSemana = $fechaSemana
        ->copy()
        ->startOfDay();

    $finSemana = $fechaSemana
        ->copy()
        ->endOfWeek(Carbon::SUNDAY)
        ->endOfDay();

    $titulo = $formasPago[$formaPago];

    /*
     * Solo se exportan órdenes:
     *
     * - Autorizadas o verificadas.
     * - De la forma de pago solicitada.
     * - Del área Giralda.
     * - Marcadas como caja chica.
     * - Con folio OC-GL-%.
     * - Con fecha dentro de la semana seleccionada.
     */
    $ordenes = OrdenCompra::query()
        ->with([
            'proveedor',
            'obra',
            'centroCosto',
            'areaCatalogo',
        ])
        ->whereIn('estado', ['AUTORIZADA', 'VERIFICADA'])
        ->where('es_caja_chica', true)
        ->where('forma_pago', $formaPago)
        ->where('area_id', $areaCatalogo->id)
        ->where('folio', 'like', 'OC-GL-%')
        ->whereBetween('fecha', [
            $inicioSemana->toDateString(),
            $finSemana->toDateString(),
        ])
        ->orderBy('fecha')
        ->orderBy('folio')
        ->get();

    /*
     * PDF horizontal tamaño carta.
     */
    $pdf = new \FPDF('L', 'mm', 'Letter');

    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $utf8 = fn ($texto) => utf8_decode((string) $texto);

    $money = fn ($cantidad) => '$' . number_format(
        (float) $cantidad,
        2
    );

    $BLUE = [0, 74, 173];
    $GRAY = [240, 240, 240];

    /*
     * Encabezado reutilizable.
     */
    $imprimirEncabezado = function () use (
        $pdf,
        $utf8,
        $titulo,
        $formaPago,
        $areaCatalogo,
        $inicioSemana,
        $finSemana,
        $BLUE
    ) {
        $pdf->SetTextColor(
            $BLUE[0],
            $BLUE[1],
            $BLUE[2]
        );

        $pdf->SetFont('Arial', 'B', 16);

        $pdf->Cell(
            0,
            8,
            $utf8($titulo),
            0,
            1,
            'C'
        );

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(70, 70, 70);

        $pdf->Cell(
            0,
            6,
            $utf8(
                'Área: '
                . $areaCatalogo->nombre
                . ' | Forma de pago: '
                . $formaPago
                . ' | Periodo: '
                . $inicioSemana->format('d/m/Y')
                . ' al '
                . $finSemana->format('d/m/Y')
                . ' | Solo caja chica autorizada/verificada'
                . ' | Generado: '
                . now()->format('d/m/Y H:i')
            ),
            0,
            1,
            'C'
        );

        $pdf->Ln(4);

        /*
         * Cabecera de la tabla.
         */
        $pdf->SetFillColor(
            $BLUE[0],
            $BLUE[1],
            $BLUE[2]
        );

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);

        $pdf->Cell(30, 8, $utf8('FOLIO'), 1, 0, 'C', true);
        $pdf->Cell(68, 8, $utf8('PROVEEDOR'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, $utf8('AREA'), 1, 0, 'C', true);
        $pdf->Cell(65, 8, $utf8('DESTINO'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, $utf8('FECHA'), 1, 0, 'C', true);
        $pdf->Cell(38, 8, $utf8('TOTAL'), 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
    };

    $imprimirEncabezado();

    $totalGeneral = 0.0;

    /*
     * Mensaje cuando no existen órdenes.
     */
    if ($ordenes->isEmpty()) {
        $pdf->SetFont('Arial', '', 10);

        $pdf->Cell(
            0,
            12,
            $utf8(
                'No existen órdenes de caja chica autorizadas o verificadas con esta forma de pago '
                . 'para el área '
                . $areaCatalogo->nombre
                . ' en el periodo '
                . $inicioSemana->format('d/m/Y')
                . ' al '
                . $finSemana->format('d/m/Y')
                . '.'
            ),
            1,
            1,
            'C'
        );
    }

    /*
     * Cuerpo del reporte.
     */
    foreach ($ordenes as $indice => $oc) {
        if ($pdf->GetY() > 185) {
            $pdf->AddPage();
            $imprimirEncabezado();
        }

        $proveedorNombre = $oc->proveedor?->nombre
            ?: $oc->proveedor?->razon_social
            ?: 'SIN PROVEEDOR';

        $areaNombre = $oc->areaCatalogo?->nombre
            ?: $oc->area
            ?: '-';

        if ($oc->obra) {
            $destino = trim(
                (
                    $oc->obra->clave_obra
                        ? $oc->obra->clave_obra . ' - '
                        : ''
                )
                . ($oc->obra->nombre ?? '')
            );
        } elseif ($oc->centroCosto) {
            $destino = trim(
                (
                    $oc->centroCosto->codigo
                        ? $oc->centroCosto->codigo . ' - '
                        : ''
                )
                . ($oc->centroCosto->nombre ?? '')
            );
        } else {
            $destino = 'Compra general';
        }

        $fecha = $oc->fecha
            ? $oc->fecha->format('d/m/Y')
            : '-';

        $total = (float) $oc->total;

        $totalGeneral += $total;

        $relleno = ($indice % 2) !== 0;

        if ($relleno) {
            $pdf->SetFillColor(
                $GRAY[0],
                $GRAY[1],
                $GRAY[2]
            );
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->SetFont('Arial', '', 8);

        $pdf->Cell(
            30,
            8,
            $utf8($oc->folio),
            1,
            0,
            'L',
            true
        );

        $pdf->Cell(
            68,
            8,
            $utf8(
                mb_strimwidth(
                    $proveedorNombre,
                    0,
                    42,
                    '...'
                )
            ),
            1,
            0,
            'L',
            true
        );

        $pdf->Cell(
            30,
            8,
            $utf8(
                mb_strimwidth(
                    $areaNombre,
                    0,
                    18,
                    '...'
                )
            ),
            1,
            0,
            'L',
            true
        );

        $pdf->Cell(
            65,
            8,
            $utf8(
                mb_strimwidth(
                    $destino,
                    0,
                    39,
                    '...'
                )
            ),
            1,
            0,
            'L',
            true
        );

        $pdf->Cell(
            25,
            8,
            $utf8($fecha),
            1,
            0,
            'C',
            true
        );

        $pdf->SetFont('Arial', 'B', 8);

        $pdf->Cell(
            38,
            8,
            $money($total),
            1,
            1,
            'R',
            true
        );
    }

    /*
     * Resumen final.
     */
    if ($ordenes->isNotEmpty()) {
        if ($pdf->GetY() > 188) {
            $pdf->AddPage();
            $imprimirEncabezado();
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 11);

        $pdf->Cell(
            218,
            9,
            $utf8('TOTAL GENERAL:'),
            1,
            0,
            'R'
        );

        $pdf->Cell(
            38,
            9,
            $money($totalGeneral),
            1,
            1,
            'R'
        );

        $pdf->SetFont('Arial', '', 9);

        $pdf->Cell(
            218,
            7,
            $utf8('Número de órdenes:'),
            0,
            0,
            'R'
        );

        $pdf->Cell(
            38,
            7,
            (string) $ordenes->count(),
            0,
            1,
            'R'
        );
    }

    /*
     * Nombre del archivo con periodo.
     */
    $tipoArchivo = $formaPago === '01'
        ? 'efectivo'
        : 'tarjeta_credito';

    $nombreArchivo =
        'OC_Giralda_'
        . $tipoArchivo
        . '_'
        . $inicioSemana->format('Y-m-d')
        . '_al_'
        . $finSemana->format('Y-m-d')
        . '.pdf';

    return response($pdf->Output('S'))
        ->header('Content-Type', 'application/pdf')
        ->header(
            'Content-Disposition',
            'inline; filename="' . $nombreArchivo . '"'
        );
}

    public function exportarGastosSinFactura(Request $request)
    {
        $this->authorizeAny([
            'ordenes_compra.view.access',
            'ordenes_compra.print.access',
            'ordenes_compra.imprimir',
            'ordenes de compra.access',
        ]);

        $areaCodigo = strtoupper(trim((string) $request->query('area_codigo')));

        if ($areaCodigo !== 'GL') {
            abort(403, 'Este reporte solamente está disponible para el área Giralda.');
        }

        $areaCatalogo = Area::query()
            ->where('codigo', $areaCodigo)
            ->firstOrFail();

        try {
            $fechaSemana = $request->filled('semana')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->query('semana'))->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $e) {
            abort(422, 'La semana seleccionada no tiene un formato válido.');
        }

        $inicioSemanaActual = now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        if ($fechaSemana->greaterThan($inicioSemanaActual)) {
            abort(422, 'No se pueden exportar semanas futuras.');
        }

        $inicioSemana = $fechaSemana->copy()->startOfDay();
        $finSemana = $fechaSemana->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $titulo = 'GASTOS SIN FACTURA';

        $ordenes = OrdenCompra::query()
            ->with([
                'proveedor',
                'obra',
                'centroCosto',
                'areaCatalogo',
            ])
            ->whereIn('estado', ['AUTORIZADA', 'VERIFICADA'])
            ->where('gastos_sin_factura', true)
            ->where('area_id', $areaCatalogo->id)
            ->where('folio', 'like', 'OC-GL-%')
            ->whereBetween('fecha', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString(),
            ])
            ->orderBy('fecha')
            ->orderBy('folio')
            ->get();

        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $utf8 = fn ($texto) => utf8_decode((string) $texto);
        $money = fn ($cantidad) => '$' . number_format((float) $cantidad, 2);

        $BLUE = [0, 74, 173];
        $GRAY = [240, 240, 240];

        $imprimirEncabezado = function () use (
            $pdf,
            $utf8,
            $titulo,
            $areaCatalogo,
            $inicioSemana,
            $finSemana,
            $BLUE
        ) {
            $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 8, $utf8($titulo), 0, 1, 'C');

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(70, 70, 70);

            $pdf->Cell(
                0,
                6,
                $utf8(
                    'Área: '
                    . $areaCatalogo->nombre
                    . ' | Periodo: '
                    . $inicioSemana->format('d/m/Y')
                    . ' al '
                    . $finSemana->format('d/m/Y')
                    . ' | Solo gastos sin factura autorizados/verificados'
                    . ' | Generado: '
                    . now()->format('d/m/Y H:i')
                ),
                0,
                1,
                'C'
            );

            $pdf->Ln(4);

            $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 8);

            $pdf->Cell(30, 8, $utf8('FOLIO'), 1, 0, 'C', true);
            $pdf->Cell(68, 8, $utf8('PROVEEDOR'), 1, 0, 'C', true);
            $pdf->Cell(30, 8, $utf8('AREA'), 1, 0, 'C', true);
            $pdf->Cell(65, 8, $utf8('DESTINO'), 1, 0, 'C', true);
            $pdf->Cell(25, 8, $utf8('FECHA'), 1, 0, 'C', true);
            $pdf->Cell(38, 8, $utf8('TOTAL'), 1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
        };

        $imprimirEncabezado();
        $totalGeneral = 0.0;

        if ($ordenes->isEmpty()) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(
                0,
                12,
                $utf8(
                    'No existen órdenes de gastos sin factura autorizadas o verificadas para el área '
                    . $areaCatalogo->nombre
                    . ' en el periodo '
                    . $inicioSemana->format('d/m/Y')
                    . ' al '
                    . $finSemana->format('d/m/Y')
                    . '.'
                ),
                1,
                1,
                'C'
            );
        }

        foreach ($ordenes as $indice => $oc) {
            if ($pdf->GetY() > 185) {
                $pdf->AddPage();
                $imprimirEncabezado();
            }

            $proveedorNombre = $oc->proveedor?->nombre
                ?: $oc->proveedor?->razon_social
                ?: 'SIN PROVEEDOR';

            $areaNombre = $oc->areaCatalogo?->nombre
                ?: $oc->area
                ?: '-';

            if ($oc->obra) {
                $destino = trim(
                    ($oc->obra->clave_obra ? $oc->obra->clave_obra . ' - ' : '')
                    . ($oc->obra->nombre ?? '')
                );
            } elseif ($oc->centroCosto) {
                $destino = trim(
                    ($oc->centroCosto->codigo ? $oc->centroCosto->codigo . ' - ' : '')
                    . ($oc->centroCosto->nombre ?? '')
                );
            } else {
                $destino = 'Compra general';
            }

            $fecha = $oc->fecha ? $oc->fecha->format('d/m/Y') : '-';
            $total = (float) $oc->total;
            $totalGeneral += $total;

            $relleno = ($indice % 2) !== 0;
            if ($relleno) {
                $pdf->SetFillColor($GRAY[0], $GRAY[1], $GRAY[2]);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(30, 8, $utf8($oc->folio), 1, 0, 'L', true);
            $pdf->Cell(68, 8, $utf8(mb_strimwidth($proveedorNombre, 0, 42, '...')), 1, 0, 'L', true);
            $pdf->Cell(30, 8, $utf8(mb_strimwidth($areaNombre, 0, 18, '...')), 1, 0, 'L', true);
            $pdf->Cell(65, 8, $utf8(mb_strimwidth($destino, 0, 39, '...')), 1, 0, 'L', true);
            $pdf->Cell(25, 8, $utf8($fecha), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(38, 8, $money($total), 1, 1, 'R', true);
        }

        if ($ordenes->isNotEmpty()) {
            if ($pdf->GetY() > 188) {
                $pdf->AddPage();
                $imprimirEncabezado();
            }

            $pdf->Ln(3);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(218, 9, $utf8('TOTAL GENERAL:'), 1, 0, 'R');
            $pdf->Cell(38, 9, $money($totalGeneral), 1, 1, 'R');

            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(218, 7, $utf8('Número de órdenes:'), 0, 0, 'R');
            $pdf->Cell(38, 7, (string) $ordenes->count(), 0, 1, 'R');
        }

        $nombreArchivo = 'OC_Giralda_gastos_sin_factura_'
            . $inicioSemana->format('Y-m-d')
            . '_al_'
            . $finSemana->format('Y-m-d')
            . '.pdf';

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
    }

    public function exportarCaratulaGiralda(Request $request)
    {
        $this->authorizeAny([
            'ordenes_compra.view.access',
            'ordenes_compra.print.access',
            'ordenes_compra.imprimir',
            'ordenes de compra.access',
        ]);

        $areaCodigo = strtoupper(trim((string) $request->query('area_codigo')));

        if ($areaCodigo !== 'GL') {
            abort(403, 'Esta caratula solamente esta disponible para el area Giralda.');
        }

        $areaCatalogo = Area::query()
            ->where('codigo', $areaCodigo)
            ->firstOrFail();

        try {
            $fechaSemana = $request->filled('semana')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->query('semana'))->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $e) {
            abort(422, 'La semana seleccionada no tiene un formato valido.');
        }

        $inicioSemanaActual = now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        if ($fechaSemana->greaterThan($inicioSemanaActual)) {
            abort(422, 'No se pueden imprimir semanas futuras.');
        }

        $inicioSemana = $fechaSemana->copy()->startOfDay();
        $finSemana = $fechaSemana->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $ordenes = OrdenCompra::query()
            ->with(['proveedor', 'obra', 'centroCosto', 'areaCatalogo'])
            ->whereIn('estado', ['AUTORIZADA', 'VERIFICADA'])
            ->where('area_id', $areaCatalogo->id)
            ->where('folio', 'like', 'OC-GL-%')
            ->whereBetween('fecha', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString(),
            ])
            ->orderBy('fecha')
            ->orderBy('folio')
            ->get();

        $grupos = collect([
            'caja_efectivo' => [
                'titulo' => 'Caja chica - efectivo',
                'ordenes' => $ordenes->filter(fn ($oc) => $oc->es_caja_chica && (string) $oc->forma_pago === '01')->values(),
            ],
            'caja_tarjeta' => [
                'titulo' => 'Caja chica - tarjeta de credito',
                'ordenes' => $ordenes->filter(fn ($oc) => $oc->es_caja_chica && (string) $oc->forma_pago === '04')->values(),
            ],
            'gastos_sin_factura' => [
                'titulo' => 'Gastos sin factura',
                'ordenes' => $ordenes->filter(fn ($oc) => (bool) $oc->gastos_sin_factura)->values(),
            ],
            'otras' => [
                'titulo' => 'Otras ordenes GL',
                'ordenes' => $ordenes->filter(fn ($oc) => ! $oc->es_caja_chica && ! $oc->gastos_sin_factura)->values(),
            ],
        ]);

        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $utf8 = fn ($texto) => utf8_decode((string) $texto);
        $money = fn ($cantidad) => '$' . number_format((float) $cantidad, 2);
        $BLUE = [0, 74, 173];
        $GRAY = [242, 244, 247];

        $pdf->SetTextColor($BLUE[0], $BLUE[1], $BLUE[2]);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 9, $utf8('CARATULA DE ORDENES DE COMPRA'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(70, 70, 70);
        $pdf->Cell(0, 6, $utf8('Area: ' . $areaCatalogo->nombre . ' | Periodo: ' . $inicioSemana->format('d/m/Y') . ' al ' . $finSemana->format('d/m/Y')), 0, 1, 'C');
        $pdf->Cell(0, 6, $utf8('Generado: ' . now()->format('d/m/Y H:i')), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(88, 8, $utf8('TIPO'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, $utf8('ORDENES'), 1, 0, 'C', true);
        $pdf->Cell(38, 8, $utf8('TOTAL'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, $utf8('FOLIOS'), 1, 1, 'C', true);

        $totalGeneral = 0.0;
        $totalOrdenes = 0;

        foreach ($grupos as $grupo) {
            $items = $grupo['ordenes'];
            $totalGrupo = (float) $items->sum(fn ($oc) => (float) $oc->total);
            $folios = $items->pluck('folio')->implode(', ');
            $totalGeneral += $totalGrupo;
            $totalOrdenes += $items->count();

            $pdf->SetFillColor($GRAY[0], $GRAY[1], $GRAY[2]);
            $pdf->SetTextColor(20, 20, 20);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(88, 8, $utf8($grupo['titulo']), 1, 0, 'L', true);
            $pdf->Cell(30, 8, (string) $items->count(), 1, 0, 'C', true);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(38, 8, $money($totalGrupo), 1, 0, 'R', true);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(40, 8, $utf8(mb_strimwidth($folios ?: '-', 0, 28, '...')), 1, 1, 'L', true);
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(88, 9, $utf8('TOTAL GENERAL'), 1, 0, 'R');
        $pdf->Cell(30, 9, (string) $totalOrdenes, 1, 0, 'C');
        $pdf->Cell(38, 9, $money($totalGeneral), 1, 0, 'R');
        $pdf->Cell(40, 9, '', 1, 1, 'L');
        $pdf->Ln(7);

        foreach ($grupos as $grupo) {
            $items = $grupo['ordenes'];

            if ($pdf->GetY() > 235) {
                $pdf->AddPage();
            }

            $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(0, 7, $utf8($grupo['titulo'] . ' (' . $items->count() . ')'), 1, 1, 'L', true);

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(28, 7, $utf8('FOLIO'), 1, 0, 'C', true);
            $pdf->Cell(61, 7, $utf8('PROVEEDOR'), 1, 0, 'C', true);
            $pdf->Cell(53, 7, $utf8('DESTINO'), 1, 0, 'C', true);
            $pdf->Cell(24, 7, $utf8('FECHA'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, $utf8('TOTAL'), 1, 1, 'C', true);

            if ($items->isEmpty()) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(90, 90, 90);
                $pdf->Cell(196, 7, $utf8('Sin ordenes para este tipo.'), 1, 1, 'C');
                $pdf->Ln(3);
                continue;
            }

            foreach ($items as $oc) {
                if ($pdf->GetY() > 250) {
                    $pdf->AddPage();
                }

                $proveedorNombre = $oc->proveedor?->nombre ?: $oc->proveedor?->razon_social ?: 'SIN PROVEEDOR';

                if ($oc->obra) {
                    $destino = trim(($oc->obra->clave_obra ? $oc->obra->clave_obra . ' - ' : '') . ($oc->obra->nombre ?? ''));
                } elseif ($oc->centroCosto) {
                    $destino = trim(($oc->centroCosto->codigo ? $oc->centroCosto->codigo . ' - ' : '') . ($oc->centroCosto->nombre ?? ''));
                } else {
                    $destino = 'Compra general';
                }

                $pdf->SetFont('Arial', '', 7);
                $pdf->SetTextColor(20, 20, 20);
                $pdf->Cell(28, 7, $utf8($oc->folio), 1, 0, 'L');
                $pdf->Cell(61, 7, $utf8(mb_strimwidth($proveedorNombre, 0, 39, '...')), 1, 0, 'L');
                $pdf->Cell(53, 7, $utf8(mb_strimwidth($destino, 0, 34, '...')), 1, 0, 'L');
                $pdf->Cell(24, 7, $utf8($oc->fecha ? $oc->fecha->format('d/m/Y') : '-'), 1, 0, 'C');
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell(30, 7, $money($oc->total), 1, 1, 'R');
            }

            $pdf->Ln(4);
        }

        $nombreArchivo = 'OC_Giralda_caratula_'
            . $inicioSemana->format('Y-m-d')
            . '_al_'
            . $finSemana->format('Y-m-d')
            . '.pdf';

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
    }
}







