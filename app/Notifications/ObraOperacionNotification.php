<?php

namespace App\Notifications;

use App\Models\Comision;
use App\Models\ComisionEtapa;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ObraOperacionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Obra $obra,
        protected Comision $comision,
        protected ComisionEtapa $etapa,
        protected ?User $registradoPor = null
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $etapaLabel = $this->etapaLabel($this->etapa->etapa);
        $obraNombre = $this->obra->nombre
            ?? $this->obra->nombre_obra
            ?? $this->obra->clave_obra
            ?? 'Obra';
        $pila = $this->comision->pila?->tipo
            ?? $this->comision->pila?->numero_pila
            ?? $this->etapa->pila?->tipo
            ?? $this->etapa->pila?->numero_pila
            ?? 'N/D';
        $usuario = $this->registradoPor?->name ?? 'Usuario app';

        $titulo = 'Movimiento de obra registrado';
        $mensaje = "{$obraNombre}: {$etapaLabel} registrado en pila {$pila} por {$usuario}.";

        return [
            'tipo' => 'obra_operacion',
            'evento' => 'comision_etapa_registrada',
            'title' => $titulo,
            'titulo' => $titulo,
            'message' => $mensaje,
            'mensaje' => $mensaje,
            'url' => route('obras.comisiones.show', [$this->obra, $this->comision]),
            'icon' => 'clipboard-check',
            'priority' => 'normal',
            'obra_id' => $this->obra->id,
            'obra_nombre' => $obraNombre,
            'comision_id' => $this->comision->id,
            'etapa_id' => $this->etapa->id,
            'etapa' => $this->etapa->etapa,
            'etapa_label' => $etapaLabel,
            'pila_id' => $this->comision->pila_id,
            'pila' => $pila,
            'registrado_por_user_id' => $this->registradoPor?->id,
            'registrado_por' => $usuario,
        ];
    }

    private function etapaLabel(?string $etapa): string
    {
        return match ($etapa) {
            ComisionEtapa::ETAPA_PERFORACION => 'Perforacion',
            ComisionEtapa::ETAPA_BENTONITA => 'Bentonita',
            ComisionEtapa::ETAPA_ADEME => 'Ademe',
            ComisionEtapa::ETAPA_ACERO => 'Colocacion de acero',
            ComisionEtapa::ETAPA_COLADO => 'Colado',
            default => ucfirst((string) $etapa),
        };
    }
}