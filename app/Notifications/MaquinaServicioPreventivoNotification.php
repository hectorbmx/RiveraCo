<?php

namespace App\Notifications;

use App\Models\Maquina;
use Illuminate\Notifications\Notification;

class MaquinaServicioPreventivoNotification extends Notification
{
    public function __construct(private Maquina $maquina, private array $preventivo) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $equipo = trim(($this->maquina->codigo ?? '') . ' ' . ($this->maquina->nombre ?? '')) ?: 'Máquina #' . $this->maquina->id;
        $servicio = $this->preventivo['servicio_nombre'] ?? 'Servicio preventivo';
        return [
            'tipo' => 'maquina_servicio_preventivo',
            'titulo' => 'Preventivo de maquinaria',
            'mensaje' => $equipo . ' · ' . $servicio . ': ' . ($this->preventivo['label'] ?? ''),
            'maquina_id' => $this->maquina->id,
            'servicio_id' => $this->preventivo['servicio_id'] ?? null,
            'estado' => $this->preventivo['estado'] ?? null,
            'proximo_horometro' => $this->preventivo['proximo_horometro'] ?? null,
        ];
    }
}
