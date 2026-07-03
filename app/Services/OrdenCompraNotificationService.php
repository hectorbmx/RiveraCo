<?php

namespace App\Services;

use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use App\Models\User;
use App\Notifications\OrdenCompraFlujoNotification;
use Illuminate\Support\Facades\Notification;

class OrdenCompraNotificationService
{
    public function creada(OrdenCompra $orden): void
    {
        $orden->loadMissing(['proveedor', 'obra']);

        $destinatarios = User::permission('ordenes_compra.authorize.access')->get();

        if ($destinatarios->isEmpty()) {
            $destinatarios = User::permission('ordenes_compra.autorizar')->get();
        }

        $this->send($destinatarios, new OrdenCompraFlujoNotification($orden, 'creada'));
    }

    public function autorizada(OrdenCompra $orden): void
    {
        $orden->loadMissing(['proveedor', 'obra', 'registradoPor']);

        $creador = $this->creatorFor($orden);
        if ($creador) {
            $creador->notify(new OrdenCompraFlujoNotification($orden, 'autorizada'));
        }

        $programadores = User::permission('pagos_proveedores.schedule.access')->get();
        $this->send($programadores, new OrdenCompraFlujoNotification($orden, 'lista_pago'));
    }

    public function pagoProgramado(PagoProveedor $pago): void
    {
        $pago->loadMissing([
            'ordenCompra.proveedor',
            'ordenCompra.obra',
            'ordenCompra.registradoPor',
            'ordenCompra.autorizadoPor',
            'programadoPor',
        ]);

        $orden = $pago->ordenCompra;
        if (!$orden) {
            return;
        }

        $destinatarios = collect([
            $this->creatorFor($orden),
            $orden->autorizadoPor,
            $pago->programadoPor,
        ])->filter();

        $this->send($destinatarios, new OrdenCompraFlujoNotification($orden, 'pago_programado', $pago));
    }

    private function creatorFor(OrdenCompra $orden): ?User
    {
        if ($orden->registradoPor) {
            return $orden->registradoPor;
        }

        $usuarioRegistro = trim((string) $orden->usuario_registro);
        if ($usuarioRegistro === '') {
            return null;
        }

        return User::query()
            ->where('name', $usuarioRegistro)
            ->orWhere('email', $usuarioRegistro)
            ->first();
    }

    private function send(iterable $users, OrdenCompraFlujoNotification $notification): void
    {
        $destinatarios = collect($users)
            ->filter()
            ->unique('id')
            ->values();

        if ($destinatarios->isEmpty()) {
            return;
        }

        Notification::send($destinatarios, $notification);
    }
}
