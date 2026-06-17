<?php

namespace App\Notifications;

use App\Models\Seguro;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeguroVehiculoVencimiento extends Notification
{
    use Queueable;

    protected $seguro;

    /**
     * Create a new notification instance.
     */
    public function __construct(Seguro $seguro)
    {
        $this->seguro = $seguro;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $vehiculo = $this->seguro->asegurable;
        $nombreVehiculo = "N/A";
        
        if ($vehiculo instanceof \App\Models\Vehiculo) {
            $nombreVehiculo = "Vehículo: {$vehiculo->marca} {$vehiculo->modelo} ({$vehiculo->placas})";
        } elseif ($vehiculo instanceof \App\Models\Maquina) {
            $nombreVehiculo = "Máquina: {$vehiculo->economico} - {$vehiculo->nombre}";
        }

        return [
            'tipo' => 'vencimiento_seguro',
            'id' => $this->seguro->id,
            'asegurable_nombre' => $nombreVehiculo,
            'poliza' => $this->seguro->poliza_numero,
            'vence' => $this->seguro->vigencia_hasta->format('d/m/Y'),
            'dias_restantes' => now()->diffInDays($this->seguro->vigencia_hasta, false),
            'mensaje' => "Seguro por vencer: {$nombreVehiculo} - Póliza: {$this->seguro->poliza_numero}",
        ];
    }
}
