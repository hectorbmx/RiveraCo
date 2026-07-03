<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancoEmpresa;
use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use App\Services\OrdenCompraNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class PagoProveedorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('pagos_proveedores.view.access');
        $fechaInicio = $request->fecha_inicio
            ? now()->parse($request->fecha_inicio)->startOfDay()
            : now()->startOfWeek()->startOfDay();

        $fechaFin = $request->fecha_fin
            ? now()->parse($request->fecha_fin)->endOfDay()
            : now()->endOfWeek()->endOfDay();

        $pagos = PagoProveedor::query()
            ->with(['ordenCompra.obra', 'ordenCompra.centroCosto', 'proveedor', 'cuentaBancoEmpresa', 'programadoPor'])
            ->whereBetween('fecha_programada', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->when($request->estatus, fn ($q, $estatus) => $q->where('estatus', $estatus))
            ->orderBy('fecha_programada')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('pagos_proveedores.index', [
            'pagos' => $pagos,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'semanaAnteriorInicio' => $fechaInicio->copy()->subWeek()->toDateString(),
            'semanaAnteriorFin' => $fechaFin->copy()->subWeek()->toDateString(),
            'semanaSiguienteInicio' => $fechaInicio->copy()->addWeek()->toDateString(),
            'semanaSiguienteFin' => $fechaFin->copy()->addWeek()->toDateString(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('pagos_proveedores.schedule.access');
        $ordenes = $this->ordenesProgramables()->get();
        $ordenSeleccionada = $request->orden_compra_id
            ? $ordenes->firstWhere('id', (int) $request->orden_compra_id)
            : null;

        $cuentasBanco = CuentaBancoEmpresa::where('activa', true)
            ->orderByDesc('principal')
            ->orderBy('banco')
            ->get();

        return view('pagos_proveedores.create', compact('ordenes', 'ordenSeleccionada', 'cuentasBanco'));
    }

    public function store(Request $request, OrdenCompraNotificationService $notifications)
    {
        $this->authorizePermission('pagos_proveedores.schedule.access');
        $data = $request->validate([
            'orden_compra_id' => ['required', 'integer', 'exists:ordenes_compra,id'],
            'fecha_programada' => ['required', 'date'],
            'cuenta_banco_empresa_id' => ['nullable', 'integer', 'exists:cuentas_banco_empresa,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['nullable', 'string', 'max:50'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $oc = OrdenCompra::with('proveedor')
            ->where('estado', 'AUTORIZADA')
            ->findOrFail($data['orden_compra_id']);

        $existe = PagoProveedor::where('orden_compra_id', $oc->id)
            ->whereIn('estatus', ['programado', 'autorizado', 'pagado'])
            ->exists();

        if ($existe) {
            return back()->withInput()->with('error', 'Esta orden ya tiene un pago activo o pagado.');
        }

        $pago = PagoProveedor::create([
            'orden_compra_id' => $oc->id,
            'proveedor_id' => $oc->proveedor_id,
            'cuenta_banco_empresa_id' => $data['cuenta_banco_empresa_id'] ?? null,
            'fecha_programada' => $data['fecha_programada'],
            'monto' => $data['monto'],
            'moneda' => $oc->moneda ?? 'MXN',
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'referencia' => $data['referencia'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estatus' => 'programado',
            'programado_by' => auth()->id(),
        ]);

        $notifications->pagoProgramado($pago);

        return redirect()->route('pagos-proveedores.index')->with('success', 'Pago programado correctamente.');
    }

    public function autorizar(PagoProveedor $pago)
    {
        $this->authorizePermission('pagos_proveedores.authorize.access');
        if ($pago->estatus !== 'programado') {
            return back()->with('error', 'Solo se pueden autorizar pagos programados.');
        }

        $pago->update([
            'estatus' => 'autorizado',
            'autorizado_by' => auth()->id(),
            'autorizado_at' => now(),
        ]);

        return back()->with('success', 'Pago autorizado.');
    }

    public function pagar(Request $request, PagoProveedor $pago)
    {
        $this->authorizePermission('pagos_proveedores.pay.access');
        if ($pago->estatus !== 'autorizado') {
            return back()->with('error', 'Solo se pueden ejecutar pagos autorizados.');
        }

        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'referencia' => ['nullable', 'string', 'max:255'],
        ]);

        $pago->update([
            'estatus' => 'pagado',
            'fecha_pago' => $data['fecha_pago'],
            'referencia' => $data['referencia'] ?? $pago->referencia,
            'pagado_by' => auth()->id(),
            'pagado_at' => now(),
        ]);

        return back()->with('success', 'Pago marcado como ejecutado.');
    }

    public function cancelar(PagoProveedor $pago)
    {
        $this->authorizePermission('pagos_proveedores.cancel.access');
        if ($pago->estatus === 'pagado') {
            return back()->with('error', 'No se puede cancelar un pago ya ejecutado.');
        }

        $pago->update(['estatus' => 'cancelado']);

        return back()->with('success', 'Programacion cancelada.');
    }


    private function authorizePermission(string $permission, string $message = 'No tienes permiso para realizar esta accion.'): void
    {
        $user = auth()->user();

        if (!$user || !$user->can($permission)) {
            throw new AuthorizationException($message);
        }
    }

    private function ordenesProgramables()
    {
        return OrdenCompra::query()
            ->with(['proveedor', 'obra', 'centroCosto'])
            ->where('estado', 'AUTORIZADA')
            ->whereDoesntHave('pagosProveedor', function ($q) {
                $q->whereIn('estatus', ['programado', 'autorizado', 'pagado']);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }
}
