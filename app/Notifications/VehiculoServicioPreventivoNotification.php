<?php

namespace App\Notifications;

use App\Models\Vehiculo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehiculoServicioPreventivoNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $preventivo
     */
    public function __construct(
        protected Vehiculo $vehiculo,
        protected array $preventivo
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->preventivo['label'] ?? 'Servicio preventivo';

        return (new MailMessage)
            ->subject("Alerta de servicio preventivo: {$this->nombreVehiculo()}")
            ->greeting('Alerta de servicio preventivo')
            ->line("Vehiculo: {$this->nombreVehiculo()}")
            ->line("Estado: {$label}")
            ->line('KM actual: ' . $this->formatKm($this->preventivo['km_actual'] ?? null))
            ->line('Proximo servicio: ' . $this->formatKm($this->preventivo['km_proximo_servicio'] ?? null))
            ->line('KM restantes: ' . $this->formatKm($this->preventivo['km_restantes'] ?? null))
            ->action('Ver vehiculo', route('mantenimiento.vehiculos.edit', [
                'vehiculo' => $this->vehiculo->id,
                'tab' => 'mantenimientos',
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->preventivo['label'] ?? 'Servicio preventivo';

        return [
            'tipo' => 'vehiculo_servicio_preventivo',
            'title' => 'Servicio preventivo de vehiculo',
            'titulo' => 'Servicio preventivo de vehiculo',
            'message' => "{$this->nombreVehiculo()}: {$label}",
            'mensaje' => "{$this->nombreVehiculo()}: {$label}",
            'url' => route('mantenimiento.vehiculos.edit', [
                'vehiculo' => $this->vehiculo->id,
                'tab' => 'mantenimientos',
            ]),
            'icon' => 'truck',
            'priority' => ($this->preventivo['estado'] ?? null) === 'vencido' ? 'high' : 'normal',
            'vehiculo_id' => $this->vehiculo->id,
            'vehiculo' => $this->nombreVehiculo(),
            'estado' => $this->preventivo['estado'] ?? null,
            'label' => $label,
            'km_actual' => $this->preventivo['km_actual'] ?? null,
            'km_proximo_servicio' => $this->preventivo['km_proximo_servicio'] ?? null,
            'km_restantes' => $this->preventivo['km_restantes'] ?? null,
        ];
    }

    protected function nombreVehiculo(): string
    {
        return trim("{$this->vehiculo->marca} {$this->vehiculo->modelo} {$this->vehiculo->placas}") ?: "Vehiculo #{$this->vehiculo->id}";
    }

    protected function formatKm(mixed $km): string
    {
        return $km === null ? '-' : number_format((float) $km) . ' km';
    }
}
