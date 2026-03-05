<?php

namespace App\Listeners;

use App\Events\MaquinaEstadoCambiado;
use App\Mail\MaquinaEstadoMail; // IMPORTANTE
use Illuminate\Contracts\Queue\ShouldQueue; // IMPORTANTE
use Illuminate\Support\Facades\Mail;

use Illuminate\Queue\InteractsWithQueue;


class SendMaquinaEstadoNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
   public function handle(MaquinaEstadoCambiado $event): void
    {
        // Enviamos el mailable que creamos en el paso anterior
        Mail::to('control@tuempresa.com')
            ->send(new MaquinaEstadoMail(
                $event->maquina,
                $event->anterior,
                $event->nuevo,
                $event->motivo,
                $event->notas,
            ));
    }
}
