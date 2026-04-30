<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatCfdi;
use App\Models\SatCfdiPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SatCfdiPagoController extends Controller
{
    public function store(Request $request, SatCfdi $cfdi)
    {
        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'tipo_cambio' => ['nullable', 'numeric', 'min:0'],

            'metodo_pago' => ['nullable', 'string', 'max:50'],
            'referencia' => ['nullable', 'string', 'max:255'],

            'folio_transferencia' => ['nullable', 'string', 'max:255'],
            'numero_cheque' => ['nullable', 'string', 'max:255'],

            'banco_origen' => ['nullable', 'string', 'max:255'],
            'banco_destino' => ['nullable', 'string', 'max:255'],
            'cuenta_origen' => ['nullable', 'string', 'max:255'],
            'cuenta_destino' => ['nullable', 'string', 'max:255'],

            'observaciones' => ['nullable', 'string'],
            'comprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            DB::transaction(function () use ($request, $cfdi, $data) {

                // Bloqueamos la factura para evitar doble pago por doble click
                $cfdi = SatCfdi::where('id', $cfdi->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Solo facturas tipo I por ahora
                if ($cfdi->tipo_comprobante !== 'I') {
                    throw new \Exception('Solo se pueden registrar pagos a CFDIs tipo Ingreso.');
                }

                $totalFactura = round((float) $cfdi->total, 2);

                $totalPagado = round((float) $cfdi->pagos()
                    ->where('estatus', 'activo')
                    ->sum('monto'), 2);

                $saldo = round($totalFactura - $totalPagado, 2);
                $montoNuevo = round((float) $data['monto'], 2);

                if ($saldo <= 0) {
                    throw new \Exception('La factura ya está pagada.');
                }

                // PUE: solo acepta un pago
                if ($cfdi->metodo_pago === 'PUE' && $totalPagado > 0) {
                    throw new \Exception('Esta factura es PUE y ya tiene un pago registrado.');
                }

                // PPD: puede aceptar varios, pero sin exceder saldo
                if ($montoNuevo > $saldo) {
                    throw new \Exception('El monto del pago excede el saldo pendiente.');
                }

                $comprobantePath = null;

                if ($request->hasFile('comprobante')) {
                    $comprobantePath = $request->file('comprobante')
                        ->store('sat/cfdi-pagos/comprobantes', 'public');
                }

                SatCfdiPago::create([
                    'sat_cfdi_id' => $cfdi->id,
                    'cfdi_uuid' => $cfdi->uuid ?? null,

                    'fecha_pago' => $data['fecha_pago'],
                    'monto' => $montoNuevo,
                    'moneda' => $data['moneda'] ?? ($cfdi->moneda ?? 'MXN'),
                    'tipo_cambio' => $data['tipo_cambio'] ?? null,

                    'metodo_pago' => $data['metodo_pago'] ?? null,
                    'referencia' => $data['referencia'] ?? null,

                    'folio_transferencia' => $data['folio_transferencia'] ?? null,
                    'numero_cheque' => $data['numero_cheque'] ?? null,

                    'banco_origen' => $data['banco_origen'] ?? null,
                    'banco_destino' => $data['banco_destino'] ?? null,
                    'cuenta_origen' => $data['cuenta_origen'] ?? null,
                    'cuenta_destino' => $data['cuenta_destino'] ?? null,

                    'observaciones' => $data['observaciones'] ?? null,
                    'comprobante_path' => $comprobantePath,

                    'estatus' => 'activo',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            });

            return back()->with('success', 'Pago registrado correctamente.');

        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}